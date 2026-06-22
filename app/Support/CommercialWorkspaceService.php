<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Company;
use App\Models\CompanyBranch;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesFollowUp;
use App\Models\SalesOpportunity;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Models\VendorBillItem;
use App\Support\Concerns\ScopesCompaniesByActor;
use App\Support\Contracts\ScopesCompanies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommercialWorkspaceService implements ScopesCompanies
{
    use ScopesCompaniesByActor;

    public function __construct(private readonly AccountingWorkspaceService $accounting) {}

    public function canAccessCompany(User $actor, Company|int $company): bool
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        return $actor->isSuperadmin
            || (int) $actor->company_id === (int) $companyId;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createProduct(User $actor, array $data): Product
    {
        $this->assertCompanyAccess($actor, (int) $data['company_id']);

        return Product::query()->create($this->prepareProductAttributes($data));
    }

    public function updateProduct(User $actor, Product $product, array $data): Product
    {
        $this->assertCompanyAccess($actor, (int) $data['company_id']);

        $product->update($this->prepareProductAttributes($data));

        return $product;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareProductAttributes(array $data): array
    {
        return [
            ...$data,
            'sku' => filled($data['sku'] ?? null) ? Str::upper((string) $data['sku']) : null,
            'status' => $data['status'] ?? Product::STATUS_ACTIVE,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordStockMovement(User $actor, Product $product, array $data): StockMovement
    {
        $this->assertCompanyAccess($actor, $product->company_id);
        $this->assertBelongsToCompany(CompanyBranch::class, $data['branch_id'] ?? null, $product->company_id);

        return DB::transaction(function () use ($actor, $product, $data): StockMovement {
            $movement = StockMovement::query()->create([
                'company_id' => $product->company_id,
                'branch_id' => $data['branch_id'] ?? null,
                'product_id' => $product->id,
                'user_id' => $actor->id,
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? $product->cost_price,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $this->accounting->postStockMovement($actor, $movement->fresh(['product']));

            return $movement->fresh(['product']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createVendor(User $actor, array $data): Vendor
    {
        $this->assertCompanyAccess($actor, (int) $data['company_id']);

        return Vendor::query()->create([
            'company_id' => (int) $data['company_id'],
            'name' => trim((string) $data['name']),
            'status' => $data['status'] ?? Vendor::STATUS_ACTIVE,
            'contact_name' => $data['contact_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'tax_number' => $data['tax_number'] ?? null,
            'address' => $data['address'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createVendorBill(User $actor, array $data, array $items): VendorBill
    {
        $companyId = (int) $data['company_id'];
        $this->assertCompanyAccess($actor, $companyId);
        $this->assertBelongsToCompany(Vendor::class, $data['vendor_id'] ?? null, $companyId);
        $this->assertBelongsToCompany(CompanyBranch::class, $data['branch_id'] ?? null, $companyId);

        return DB::transaction(function () use ($actor, $companyId, $data, $items): VendorBill {
            $normalizedItems = $this->normalizeVendorBillItems($items, $companyId);
            $totals = $this->calculateVendorBillTotals($normalizedItems);
            $bill = VendorBill::query()->create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'vendor_id' => (int) $data['vendor_id'],
                'number' => $data['number'] ?? $this->nextNumber('BILL', VendorBill::query(), $companyId),
                'status' => VendorBill::STATUS_POSTED,
                'issued_at' => $data['issued_at'] ?? now()->toDateString(),
                'due_at' => $data['due_at'] ?? now()->addDays(14)->toDateString(),
                'subtotal' => $totals['subtotal'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            foreach ($normalizedItems as $item) {
                VendorBillItem::query()->create([
                    ...$item,
                    'vendor_bill_id' => $bill->id,
                ]);

                if ($item['product_id']) {
                    StockMovement::query()->create([
                        'company_id' => $companyId,
                        'branch_id' => $data['branch_id'] ?? null,
                        'product_id' => $item['product_id'],
                        'user_id' => $actor->id,
                        'type' => StockMovement::TYPE_IN,
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                        'reference_type' => VendorBill::class,
                        'reference_number' => $bill->number,
                        'occurred_at' => $data['issued_at'] ?? now(),
                        'notes' => __('Vendor bill stock-in: :number', ['number' => $bill->number]),
                        'metadata' => [
                            'source' => 'vendor_bill',
                            'vendor_bill_id' => $bill->id,
                            'line_total' => $item['line_total'],
                        ],
                    ]);
                }
            }

            $this->accounting->postVendorBill($actor, $bill->fresh(['vendor', 'items.product']));

            return $bill->fresh(['vendor', 'items.product', 'accountingJournalEntry']);
        });
    }

    public function markVendorBillPaid(User $actor, VendorBill $bill): VendorBill
    {
        $this->assertCompanyAccess($actor, $bill->company_id);

        return DB::transaction(function () use ($actor, $bill): VendorBill {
            $this->accounting->postVendorBillPayment($actor, $bill->fresh(['vendor']));

            return $bill->fresh(['vendor', 'items.product', 'accountingJournalEntry', 'paymentJournalEntry']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createQuotation(User $actor, array $data, array $items): Quotation
    {
        $companyId = (int) $data['company_id'];
        $this->assertCompanyAccess($actor, $companyId);
        $this->assertBelongsToCompany(Client::class, $data['client_id'] ?? null, $companyId);
        $this->assertBelongsToCompany(Project::class, $data['project_id'] ?? null, $companyId);
        $this->assertBelongsToCompany(SalesOpportunity::class, $data['sales_opportunity_id'] ?? null, $companyId);
        $this->assertBelongsToCompany(CompanyBranch::class, $data['branch_id'] ?? null, $companyId);

        return DB::transaction(function () use ($data, $items, $companyId): Quotation {
            $totals = $this->calculateTotals($items);
            $quotation = Quotation::query()->create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'client_id' => $data['client_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'sales_opportunity_id' => $data['sales_opportunity_id'] ?? null,
                'number' => $data['number'] ?? $this->nextNumber('QTN', Quotation::query(), $companyId),
                'status' => $data['status'] ?? Quotation::STATUS_DRAFT,
                'issued_at' => $data['issued_at'] ?? now()->toDateString(),
                'valid_until' => $data['valid_until'] ?? null,
                'subtotal' => $totals['subtotal'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            foreach ($this->normalizeItems($items, $companyId) as $item) {
                QuotationItem::query()->create([
                    ...$item,
                    'quotation_id' => $quotation->id,
                ]);
            }

            return $quotation->fresh(['items']);
        });
    }

    public function createQuotationFromOpportunity(User $actor, SalesOpportunity $opportunity): Quotation
    {
        $opportunity->loadMissing(['client', 'project', 'quotations.items']);
        $this->assertCompanyAccess($actor, $opportunity->company_id);

        return DB::transaction(function () use ($actor, $opportunity): Quotation {
            $existingQuotation = Quotation::query()
                ->where('company_id', $opportunity->company_id)
                ->where('sales_opportunity_id', $opportunity->id)
                ->first();

            if ($existingQuotation) {
                return $existingQuotation->fresh(['items']);
            }

            $quotation = $this->createQuotation($actor, [
                'company_id' => $opportunity->company_id,
                'client_id' => $opportunity->client_id,
                'project_id' => $opportunity->project_id,
                'sales_opportunity_id' => $opportunity->id,
                'status' => Quotation::STATUS_DRAFT,
                'issued_at' => now()->toDateString(),
                'valid_until' => now()->addDays(14)->toDateString(),
                'notes' => $opportunity->notes,
                'metadata' => [
                    'source' => 'sales_opportunity',
                    'sales_opportunity_id' => $opportunity->id,
                    'sales_opportunity_title' => $opportunity->title,
                ],
            ], [[
                'product_id' => null,
                'description' => $opportunity->title,
                'quantity' => 1,
                'unit_price' => $opportunity->expected_value,
                'tax_rate' => 0,
            ]]);

            if (in_array($opportunity->stage, [SalesOpportunity::STAGE_LEAD, SalesOpportunity::STAGE_QUALIFIED], true)) {
                $opportunity->forceFill([
                    'stage' => SalesOpportunity::STAGE_PROPOSAL,
                    'probability' => $this->defaultProbability(SalesOpportunity::STAGE_PROPOSAL),
                    'metadata' => [
                        ...($opportunity->metadata ?? []),
                        'quotation_id' => $quotation->id,
                        'quoted_at' => now()->toIso8601String(),
                    ],
                ])->save();
            }

            return $quotation;
        });
    }

    public function convertQuotationToInvoice(User $actor, Quotation $quotation): Invoice
    {
        $quotation->loadMissing(['client', 'project', 'items']);
        $this->assertCompanyAccess($actor, $quotation->company_id);

        return DB::transaction(function () use ($actor, $quotation): Invoice {
            $existingInvoice = Invoice::query()
                ->where('company_id', $quotation->company_id)
                ->where('quotation_id', $quotation->id)
                ->first();

            if ($existingInvoice) {
                return $existingInvoice->fresh(['items']);
            }

            $invoice = $this->createInvoice($actor, [
                'company_id' => $quotation->company_id,
                'branch_id' => $quotation->branch_id,
                'client_id' => $quotation->client_id,
                'quotation_id' => $quotation->id,
                'project_id' => $quotation->project_id,
                'issued_at' => now()->toDateString(),
                'due_at' => now()->addDays(14)->toDateString(),
                'notes' => $quotation->notes,
                'metadata' => [
                    'source' => 'quotation_conversion',
                    'quotation_id' => $quotation->id,
                    'quotation_number' => $quotation->number,
                    'branch_id' => $quotation->branch_id,
                ],
            ], $quotation->items->map(fn (QuotationItem $item): array => [
                'product_id' => $item->product_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->tax_rate,
            ])->all());

            $quotation->forceFill([
                'status' => Quotation::STATUS_ACCEPTED,
                'metadata' => [
                    ...($quotation->metadata ?? []),
                    'converted_invoice_id' => $invoice->id,
                    'converted_at' => now()->toIso8601String(),
                ],
            ])->save();

            return $invoice;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createInvoice(User $actor, array $data, array $items): Invoice
    {
        $companyId = (int) $data['company_id'];
        $this->assertCompanyAccess($actor, $companyId);
        $this->assertBelongsToCompany(Client::class, $data['client_id'] ?? null, $companyId);
        $this->assertBelongsToCompany(Quotation::class, $data['quotation_id'] ?? null, $companyId);
        $this->assertBelongsToCompany(Project::class, $data['project_id'] ?? null, $companyId);
        $this->assertBelongsToCompany(CompanyBranch::class, $data['branch_id'] ?? null, $companyId);

        return DB::transaction(function () use ($data, $items, $companyId): Invoice {
            $totals = $this->calculateTotals($items);
            $invoice = Invoice::query()->create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'client_id' => $data['client_id'] ?? null,
                'quotation_id' => $data['quotation_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'number' => $data['number'] ?? $this->nextNumber('INV', Invoice::query(), $companyId),
                'status' => $data['status'] ?? Invoice::STATUS_DRAFT,
                'issued_at' => $data['issued_at'] ?? now()->toDateString(),
                'due_at' => $data['due_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $totals['subtotal'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'metadata' => $data['metadata'] ?? null,
            ]);

            foreach ($this->normalizeItems($items, $companyId) as $item) {
                InvoiceItem::query()->create([
                    ...$item,
                    'invoice_id' => $invoice->id,
                ]);
            }

            return $invoice->fresh(['items']);
        });
    }

    public function markInvoicePaid(User $actor, Invoice $invoice): Invoice
    {
        $this->assertCompanyAccess($actor, $invoice->company_id);

        return DB::transaction(function () use ($actor, $invoice): Invoice {
            $invoice->forceFill([
                'status' => Invoice::STATUS_PAID,
                'paid_at' => $invoice->paid_at ?? now(),
            ])->save();

            $this->accounting->postInvoicePayment($actor, $invoice->fresh(['client']));

            return $invoice->fresh(['items']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createOpportunity(User $actor, array $data): SalesOpportunity
    {
        $companyId = (int) $data['company_id'];
        $this->assertCompanyAccess($actor, $companyId);
        $this->assertBelongsToCompany(Client::class, $data['client_id'] ?? null, $companyId);
        $this->assertBelongsToCompany(Project::class, $data['project_id'] ?? null, $companyId);

        return DB::transaction(function () use ($actor, $companyId, $data): SalesOpportunity {
            $opportunity = SalesOpportunity::query()->create([
                'company_id' => $companyId,
                'client_id' => $data['client_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'owner_id' => $actor->id,
                'title' => $data['title'],
                'stage' => $data['stage'] ?? SalesOpportunity::STAGE_LEAD,
                'expected_value' => $data['expected_value'] ?? 0,
                'probability' => $data['probability'] ?? $this->defaultProbability($data['stage'] ?? SalesOpportunity::STAGE_LEAD),
                'expected_close_at' => $data['expected_close_at'] ?? null,
                'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
                'source' => $data['source'] ?? null,
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            if (filled($data['next_follow_up_at'] ?? null) || filled($data['follow_up_notes'] ?? null)) {
                SalesFollowUp::query()->create([
                    'sales_opportunity_id' => $opportunity->id,
                    'assigned_to' => $actor->id,
                    'due_at' => $data['next_follow_up_at'] ?? null,
                    'status' => SalesFollowUp::STATUS_PENDING,
                    'notes' => $data['follow_up_notes'] ?? $data['notes'] ?? null,
                ]);
            }

            return $opportunity->fresh(['client', 'owner', 'followUps']);
        });
    }

    public function updateOpportunityStage(User $actor, SalesOpportunity $opportunity, string $stage): SalesOpportunity
    {
        $this->assertCompanyAccess($actor, $opportunity->company_id);

        abort_unless(in_array($stage, $this->opportunityStages(), true), 422, 'Invalid sales stage.');

        $opportunity->forceFill([
            'stage' => $stage,
            'probability' => $this->defaultProbability($stage),
        ])->save();

        return $opportunity->fresh(['client', 'owner', 'followUps']);
    }

    public function completeFollowUp(User $actor, SalesFollowUp $followUp): SalesFollowUp
    {
        $followUp->loadMissing('opportunity');
        $opportunity = $followUp->opportunity;

        abort_unless($opportunity instanceof SalesOpportunity, 404);
        $this->assertCompanyAccess($actor, $opportunity->company_id);

        $followUp->forceFill([
            'status' => SalesFollowUp::STATUS_DONE,
            'completed_at' => now(),
        ])->save();

        if ($opportunity->next_follow_up_at?->equalTo($followUp->due_at)) {
            $opportunity->forceFill(['next_follow_up_at' => null])->save();
        }

        return $followUp->fresh(['opportunity']);
    }

    /**
     * @return array{open_value: float, weighted_value: float, won_value: float, lost_value: float, overdue_follow_ups: int, win_rate: float}
     */
    public function salesSummaryForCompanies(array $companyIds): array
    {
        $opportunities = SalesOpportunity::query()
            ->whereIn('company_id', $companyIds)
            ->get(['id', 'stage', 'expected_value', 'probability', 'next_follow_up_at']);

        $openStages = [
            SalesOpportunity::STAGE_LEAD,
            SalesOpportunity::STAGE_QUALIFIED,
            SalesOpportunity::STAGE_PROPOSAL,
        ];

        $closedCount = $opportunities
            ->whereIn('stage', [SalesOpportunity::STAGE_WON, SalesOpportunity::STAGE_LOST])
            ->count();
        $wonCount = $opportunities->where('stage', SalesOpportunity::STAGE_WON)->count();

        return [
            'open_value' => round((float) $opportunities->whereIn('stage', $openStages)->sum('expected_value'), 2),
            'weighted_value' => round((float) $opportunities->whereIn('stage', $openStages)->sum(fn (SalesOpportunity $opportunity): float => (float) $opportunity->expected_value * ($opportunity->probability / 100)), 2),
            'won_value' => round((float) $opportunities->where('stage', SalesOpportunity::STAGE_WON)->sum('expected_value'), 2),
            'lost_value' => round((float) $opportunities->where('stage', SalesOpportunity::STAGE_LOST)->sum('expected_value'), 2),
            'overdue_follow_ups' => $opportunities
                ->filter(fn (SalesOpportunity $opportunity): bool => $opportunity->next_follow_up_at !== null && $opportunity->next_follow_up_at->isPast() && in_array($opportunity->stage, $openStages, true))
                ->count(),
            'win_rate' => $closedCount === 0 ? 0.0 : round(($wonCount / $closedCount) * 100, 2),
        ];
    }

    /**
     * @return array{open_total: float, overdue_total: float, overdue_count: int, due_soon_total: float, due_soon_count: int, paid_total: float}
     */
    public function collectionSummaryForCompanies(array $companyIds): array
    {
        $today = now()->startOfDay();
        $dueSoon = now()->addDays(7)->endOfDay();
        $invoices = Invoice::query()
            ->whereIn('company_id', $companyIds)
            ->get(['id', 'status', 'due_at', 'grand_total']);

        $openInvoices = $invoices->where('status', Invoice::STATUS_SENT);
        $overdueInvoices = $openInvoices->filter(fn (Invoice $invoice): bool => $invoice->due_at !== null && $invoice->due_at->lt($today));
        $dueSoonInvoices = $openInvoices->filter(fn (Invoice $invoice): bool => $invoice->due_at !== null && $invoice->due_at->betweenIncluded($today, $dueSoon));

        return [
            'open_total' => round((float) $openInvoices->sum('grand_total'), 2),
            'overdue_total' => round((float) $overdueInvoices->sum('grand_total'), 2),
            'overdue_count' => $overdueInvoices->count(),
            'due_soon_total' => round((float) $dueSoonInvoices->sum('grand_total'), 2),
            'due_soon_count' => $dueSoonInvoices->count(),
            'paid_total' => round((float) $invoices->where('status', Invoice::STATUS_PAID)->sum('grand_total'), 2),
        ];
    }

    /**
     * @return list<string>
     */
    public function opportunityStages(): array
    {
        return [
            SalesOpportunity::STAGE_LEAD,
            SalesOpportunity::STAGE_QUALIFIED,
            SalesOpportunity::STAGE_PROPOSAL,
            SalesOpportunity::STAGE_WON,
            SalesOpportunity::STAGE_LOST,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{subtotal: float, tax_total: float, grand_total: float}
     */
    private function calculateTotals(array $items): array
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($items as $item) {
            $quantity = max(0, (float) ($item['quantity'] ?? 0));
            $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
            $taxRate = max(0, (float) ($item['tax_rate'] ?? 0));
            $lineSubtotal = $quantity * $unitPrice;

            $subtotal += $lineSubtotal;
            $taxTotal += $lineSubtotal * ($taxRate / 100);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($taxTotal, 2),
            'grand_total' => round($subtotal + $taxTotal, 2),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $items, int $companyId): array
    {
        return collect($items)
            ->map(function (array $item) use ($companyId): array {
                $productId = $item['product_id'] ?? null;
                $this->assertBelongsToCompany(Product::class, $productId, $companyId);

                $quantity = max(0, (float) ($item['quantity'] ?? 0));
                $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
                $taxRate = max(0, (float) ($item['tax_rate'] ?? 0));

                return [
                    'product_id' => $productId ?: null,
                    'description' => (string) ($item['description'] ?? 'Item'),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_rate' => $taxRate,
                    'line_total' => round($quantity * $unitPrice * (1 + ($taxRate / 100)), 2),
                ];
            })
            ->filter(fn (array $item): bool => $item['quantity'] > 0 && $item['unit_price'] >= 0)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeVendorBillItems(array $items, int $companyId): array
    {
        return collect($items)
            ->map(function (array $item) use ($companyId): array {
                $productId = $item['product_id'] ?? null;
                $this->assertBelongsToCompany(Product::class, $productId, $companyId);

                $quantity = max(0, (float) ($item['quantity'] ?? 0));
                $unitCost = max(0, (float) ($item['unit_cost'] ?? 0));
                $taxRate = max(0, (float) ($item['tax_rate'] ?? 0));
                $lineSubtotal = round($quantity * $unitCost, 2);

                return [
                    'product_id' => $productId ?: null,
                    'description' => (string) ($item['description'] ?? 'Purchase item'),
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'tax_rate' => $taxRate,
                    'line_subtotal' => $lineSubtotal,
                    'line_total' => round($lineSubtotal * (1 + ($taxRate / 100)), 2),
                ];
            })
            ->filter(fn (array $item): bool => $item['quantity'] > 0 && $item['unit_cost'] >= 0)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{subtotal: float, tax_total: float, grand_total: float}
     */
    private function calculateVendorBillTotals(array $items): array
    {
        $subtotal = round((float) collect($items)->sum('line_subtotal'), 2);
        $grandTotal = round((float) collect($items)->sum('line_total'), 2);

        return [
            'subtotal' => $subtotal,
            'tax_total' => round($grandTotal - $subtotal, 2),
            'grand_total' => $grandTotal,
        ];
    }

    private function assertCompanyAccess(User $actor, int $companyId): void
    {
        abort_unless($this->canAccessCompany($actor, $companyId), 403);
    }

    private function assertBelongsToCompany(string $modelClass, mixed $id, int $companyId): void
    {
        if ($id === null || $id === '') {
            return;
        }

        if (! $modelClass::query()->whereKey($id)->where('company_id', $companyId)->exists()) {
            throw ValidationException::withMessages([
                'selected_record' => __('Selected record does not belong to the selected company.'),
            ]);
        }
    }

    private function nextNumber(string $prefix, Builder $query, int $companyId): string
    {
        $count = (clone $query)->where('company_id', $companyId)->count() + 1;

        return $prefix.'-'.now()->format('Ymd').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    private function defaultProbability(string $stage): int
    {
        return match ($stage) {
            SalesOpportunity::STAGE_QUALIFIED => 45,
            SalesOpportunity::STAGE_PROPOSAL => 70,
            SalesOpportunity::STAGE_WON => 100,
            SalesOpportunity::STAGE_LOST => 0,
            default => 25,
        };
    }
}

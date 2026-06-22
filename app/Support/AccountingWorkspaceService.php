<?php

namespace App\Support;

use App\Models\AccountingAccount;
use App\Models\AccountingPeriodClosing;
use App\Models\AccountingTaxFiling;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Reimbursement;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\VendorBill;
use App\Support\Concerns\ScopesCompaniesByActor;
use App\Support\Contracts\ScopesCompanies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AccountingWorkspaceService implements ScopesCompanies
{
    use ScopesCompaniesByActor;

    private const DEFAULT_CASH_ACCOUNT = [
        'code' => '1100',
        'name' => 'Cash / Bank',
        'type' => AccountingAccount::TYPE_ASSET,
    ];

    private const DEFAULT_SALES_ACCOUNT = [
        'code' => '4100',
        'name' => 'Sales Revenue',
        'type' => AccountingAccount::TYPE_REVENUE,
    ];

    private const DEFAULT_TAX_PAYABLE_ACCOUNT = [
        'code' => '2100',
        'name' => 'Output Tax Payable',
        'type' => AccountingAccount::TYPE_LIABILITY,
    ];

    private const DEFAULT_INVENTORY_ACCOUNT = [
        'code' => '1200',
        'name' => 'Inventory Asset',
        'type' => AccountingAccount::TYPE_ASSET,
    ];

    private const DEFAULT_INVENTORY_CLEARING_ACCOUNT = [
        'code' => '2200',
        'name' => 'Inventory Clearing',
        'type' => AccountingAccount::TYPE_LIABILITY,
    ];

    private const DEFAULT_ACCOUNTS_PAYABLE_ACCOUNT = [
        'code' => '2300',
        'name' => 'Accounts Payable',
        'type' => AccountingAccount::TYPE_LIABILITY,
    ];

    private const DEFAULT_COGS_ACCOUNT = [
        'code' => '5100',
        'name' => 'Cost of Goods Sold',
        'type' => AccountingAccount::TYPE_EXPENSE,
    ];

    private const DEFAULT_REIMBURSEMENT_EXPENSE_ACCOUNT = [
        'code' => '5200',
        'name' => 'Employee Reimbursements',
        'type' => AccountingAccount::TYPE_EXPENSE,
    ];

    private const DEFAULT_PURCHASE_EXPENSE_ACCOUNT = [
        'code' => '5300',
        'name' => 'Purchase Expenses',
        'type' => AccountingAccount::TYPE_EXPENSE,
    ];

    private const TOKO_INVOICE_SOURCES = [
        'toko_pos_counter_sale',
        'quotation_conversion',
        'legacy_toko_sale',
        'legacy_toko_retail_sale',
    ];

    private const TOKO_VENDOR_BILL_SOURCES = [
        'toko_pos_purchase',
        'legacy_toko_purchase',
    ];

    private const TOKO_JOURNAL_SOURCE_TYPES = [
        'toko_pos_invoice_payment',
        'toko_pos_invoice_refund',
        'toko_pos_operational_expense',
        'toko_pos_purchase_payment',
        'toko_pos_purchase_refund',
    ];

    public function canAccessCompany(User $actor, Company|int $company): bool
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        return $actor->isSuperadmin
            || (int) $actor->company_id === (int) $companyId;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAccount(User $actor, array $data): AccountingAccount
    {
        $this->assertCompanyAccess($actor, (int) $data['company_id']);

        return AccountingAccount::query()->create([
            'company_id' => (int) $data['company_id'],
            'code' => Str::upper(trim((string) $data['code'])),
            'name' => $data['name'],
            'type' => $data['type'],
            'normal_balance' => $data['normal_balance'] ?? $this->defaultNormalBalance((string) $data['type']),
            'is_active' => true,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createSimpleJournal(User $actor, array $data): JournalEntry
    {
        $companyId = (int) $data['company_id'];
        $this->assertCompanyAccess($actor, $companyId);

        $debitAccount = AccountingAccount::query()
            ->whereKey($data['debit_account_id'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->firstOrFail();

        $creditAccount = AccountingAccount::query()
            ->whereKey($data['credit_account_id'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->firstOrFail();

        abort_if($debitAccount->is($creditAccount), 422, 'Debit and credit account must be different.');

        $amount = round((float) $data['amount'], 2);
        abort_if($amount <= 0, 422, 'Journal amount must be greater than zero.');

        return $this->createBalancedJournal($actor, $companyId, [
            'number' => $data['number'] ?? null,
            'entry_date' => $data['entry_date'],
            'reference_number' => $data['reference_number'] ?? null,
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ], [
            [
                'account' => $debitAccount,
                'debit' => $amount,
                'credit' => 0,
                'memo' => $data['memo'] ?? null,
            ],
            [
                'account' => $creditAccount,
                'debit' => 0,
                'credit' => $amount,
                'memo' => $data['memo'] ?? null,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function closePeriod(User $actor, array $data): AccountingPeriodClosing
    {
        $companyId = (int) $data['company_id'];
        $this->assertCompanyAccess($actor, $companyId);

        $periodStart = Carbon::parse($data['period_start'])->toDateString();
        $periodEnd = Carbon::parse($data['period_end'])->toDateString();

        abort_if($periodEnd < $periodStart, 422, 'Closing period end must be after period start.');

        return AccountingPeriodClosing::query()->updateOrCreate([
            'company_id' => $companyId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ], [
            'status' => AccountingPeriodClosing::STATUS_CLOSED,
            'closed_by' => $actor->id,
            'closed_at' => now(),
            'reopened_by' => null,
            'reopened_at' => null,
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function reopenPeriod(User $actor, AccountingPeriodClosing|int $closing): AccountingPeriodClosing
    {
        $closing = $closing instanceof AccountingPeriodClosing
            ? $closing
            : AccountingPeriodClosing::query()->findOrFail($closing);

        $this->assertCompanyAccess($actor, $closing->company_id);

        $closing->forceFill([
            'status' => AccountingPeriodClosing::STATUS_REOPENED,
            'reopened_by' => $actor->id,
            'reopened_at' => now(),
        ])->save();

        return $closing->fresh(['company:id,name', 'closedBy:id,name', 'reopenedBy:id,name']);
    }

    public function isPeriodClosed(int $companyId, string $entryDate): bool
    {
        $date = Carbon::parse($entryDate)->toDateString();

        return AccountingPeriodClosing::query()
            ->where('company_id', $companyId)
            ->where('status', AccountingPeriodClosing::STATUS_CLOSED)
            ->whereDate('period_start', '<=', $date)
            ->whereDate('period_end', '>=', $date)
            ->exists();
    }

    /**
     * @return Collection<int, AccountingPeriodClosing>
     */
    public function periodClosingsForCompanies(array $companyIds): Collection
    {
        return AccountingPeriodClosing::query()
            ->with(['company:id,name', 'closedBy:id,name', 'reopenedBy:id,name'])
            ->whereIn('company_id', $companyIds)
            ->latest('period_end')
            ->latest()
            ->limit(20)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function prepareTaxFiling(User $actor, array $data): AccountingTaxFiling
    {
        $companyId = (int) $data['company_id'];
        $this->assertCompanyAccess($actor, $companyId);

        $periodStart = Carbon::parse($data['period_start'])->toDateString();
        $periodEnd = Carbon::parse($data['period_end'])->toDateString();

        abort_if($periodEnd < $periodStart, 422, 'Tax filing period end must be after period start.');

        $taxSummary = $this->taxSummaryForCompanies([$companyId], $periodStart, $periodEnd);
        $inputTax = round((float) ($data['input_tax'] ?? 0), 2);
        abort_if($inputTax < 0, 422, 'Input tax cannot be negative.');

        $outputTax = round((float) $taxSummary['issued_tax'], 2);

        return AccountingTaxFiling::query()->updateOrCreate([
            'company_id' => $companyId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'tax_type' => $data['tax_type'] ?? AccountingTaxFiling::TYPE_PPN_OUTPUT,
        ], [
            'taxable_turnover' => $taxSummary['issued_subtotal'],
            'output_tax' => $outputTax,
            'input_tax' => $inputTax,
            'net_tax_payable' => round(max(0, $outputTax - $inputTax), 2),
            'status' => AccountingTaxFiling::STATUS_DRAFT,
            'prepared_by' => $actor->id,
            'prepared_at' => now(),
            'filing_reference' => null,
            'payment_reference' => null,
            'notes' => $data['notes'] ?? null,
            'metadata' => [
                'invoice_count' => $taxSummary['invoice_count'],
                'taxable_invoice_count' => $taxSummary['taxable_invoice_count'],
                'posted_tax_payable' => $taxSummary['posted_tax_payable'],
                'unposted_tax' => $taxSummary['unposted_tax'],
                'tax_rates' => $taxSummary['tax_rates']->all(),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function markTaxFilingFiled(User $actor, AccountingTaxFiling|int $filing, array $data = []): AccountingTaxFiling
    {
        $filing = $filing instanceof AccountingTaxFiling
            ? $filing
            : AccountingTaxFiling::query()->findOrFail($filing);

        $this->assertCompanyAccess($actor, $filing->company_id);

        abort_if($filing->status === AccountingTaxFiling::STATUS_PAID, 422, 'Paid tax filings cannot be re-filed.');

        $filing->forceFill([
            'status' => AccountingTaxFiling::STATUS_FILED,
            'filed_by' => $actor->id,
            'filed_at' => now(),
            'filing_reference' => $data['filing_reference'] ?? $filing->filing_reference,
            'notes' => $data['notes'] ?? $filing->notes,
        ])->save();

        return $filing->fresh(['company:id,name', 'preparedBy:id,name', 'filedBy:id,name', 'paidBy:id,name']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function markTaxFilingPaid(User $actor, AccountingTaxFiling|int $filing, array $data = []): AccountingTaxFiling
    {
        $filing = $filing instanceof AccountingTaxFiling
            ? $filing
            : AccountingTaxFiling::query()->findOrFail($filing);

        $this->assertCompanyAccess($actor, $filing->company_id);

        abort_unless(in_array($filing->status, [AccountingTaxFiling::STATUS_DRAFT, AccountingTaxFiling::STATUS_FILED], true), 422, 'Only draft or filed tax filings can be marked paid.');

        $filing->forceFill([
            'status' => AccountingTaxFiling::STATUS_PAID,
            'filed_by' => $filing->filed_by ?: $actor->id,
            'filed_at' => $filing->filed_at ?: now(),
            'paid_by' => $actor->id,
            'paid_at' => now(),
            'filing_reference' => $data['filing_reference'] ?? $filing->filing_reference,
            'payment_reference' => $data['payment_reference'] ?? $filing->payment_reference,
            'notes' => $data['notes'] ?? $filing->notes,
        ])->save();

        return $filing->fresh(['company:id,name', 'preparedBy:id,name', 'filedBy:id,name', 'paidBy:id,name']);
    }

    /**
     * @return Collection<int, AccountingTaxFiling>
     */
    public function taxFilingsForCompanies(array $companyIds): Collection
    {
        return AccountingTaxFiling::query()
            ->with(['company:id,name', 'preparedBy:id,name', 'filedBy:id,name', 'paidBy:id,name'])
            ->whereIn('company_id', $companyIds)
            ->latest('period_end')
            ->latest()
            ->limit(20)
            ->get();
    }

    public function postInvoicePayment(User $actor, Invoice $invoice): JournalEntry
    {
        $this->assertCompanyAccess($actor, $invoice->company_id);

        $existing = JournalEntry::query()
            ->where('source_type', Invoice::class)
            ->where('source_id', $invoice->id)
            ->first();

        if ($existing) {
            return $existing->loadMissing('lines.account');
        }

        $amount = round((float) $invoice->grand_total, 2);
        abort_if($amount <= 0, 422, 'Invoice total must be greater than zero.');

        return DB::transaction(function () use ($actor, $invoice, $amount): JournalEntry {
            $cashAccount = $this->findOrCreateDefaultAccount($invoice->company_id, self::DEFAULT_CASH_ACCOUNT);
            $salesAccount = $this->findOrCreateDefaultAccount($invoice->company_id, self::DEFAULT_SALES_ACCOUNT);
            $taxPayableAccount = $this->findOrCreateDefaultAccount($invoice->company_id, self::DEFAULT_TAX_PAYABLE_ACCOUNT);

            $revenueAmount = round((float) $invoice->subtotal, 2);
            $taxAmount = round((float) $invoice->tax_total, 2);

            $lines = [
                [
                    'account' => $cashAccount,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => __('Invoice payment: :number', ['number' => $invoice->number]),
                ],
                [
                    'account' => $salesAccount,
                    'debit' => 0,
                    'credit' => $taxAmount > 0 ? $revenueAmount : $amount,
                    'memo' => __('Invoice revenue: :number', ['number' => $invoice->number]),
                ],
            ];

            if ($taxAmount > 0) {
                $lines[] = [
                    'account' => $taxPayableAccount,
                    'debit' => 0,
                    'credit' => $taxAmount,
                    'memo' => __('Invoice tax: :number', ['number' => $invoice->number]),
                ];
            }

            $entry = $this->createBalancedJournal($actor, $invoice->company_id, [
                'entry_date' => ($invoice->paid_at ?? now())->toDateString(),
                'source_type' => Invoice::class,
                'source_id' => $invoice->id,
                'reference_number' => $invoice->number,
                'description' => __('Invoice payment posted for :number', ['number' => $invoice->number]),
                'metadata' => [
                    'source' => 'commercial_invoice_payment',
                    'invoice_number' => $invoice->number,
                    'client_id' => $invoice->client_id,
                    'subtotal' => $invoice->subtotal,
                    'tax_total' => $invoice->tax_total,
                    'grand_total' => $invoice->grand_total,
                ],
            ], $lines);

            $invoice->forceFill([
                'status' => Invoice::STATUS_PAID,
                'paid_at' => $invoice->paid_at ?? now(),
                'metadata' => [
                    ...($invoice->metadata ?? []),
                    'accounting_journal_entry_id' => $entry->id,
                    'accounting_posted_at' => now()->toIso8601String(),
                ],
            ])->save();

            return $entry;
        });
    }

    public function postStockMovement(User $actor, StockMovement $movement): ?JournalEntry
    {
        $movement->loadMissing('product');
        $this->assertCompanyAccess($actor, $movement->company_id);

        $existing = JournalEntry::query()
            ->where('source_type', StockMovement::class)
            ->where('source_id', $movement->id)
            ->first();

        if ($existing) {
            return $existing->loadMissing('lines.account');
        }

        $unitCost = (float) ($movement->unit_cost ?? $movement->product?->cost_price ?? 0);
        $amount = round((float) $movement->quantity * $unitCost, 2);

        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($actor, $movement, $amount): JournalEntry {
            $inventoryAccount = $this->findOrCreateDefaultAccount($movement->company_id, self::DEFAULT_INVENTORY_ACCOUNT);
            $clearingAccount = $this->findOrCreateDefaultAccount($movement->company_id, self::DEFAULT_INVENTORY_CLEARING_ACCOUNT);
            $cogsAccount = $this->findOrCreateDefaultAccount($movement->company_id, self::DEFAULT_COGS_ACCOUNT);
            $isStockOut = $movement->type === StockMovement::TYPE_OUT;

            $entry = $this->createBalancedJournal($actor, $movement->company_id, [
                'entry_date' => ($movement->occurred_at ?? now())->toDateString(),
                'source_type' => StockMovement::class,
                'source_id' => $movement->id,
                'reference_number' => $movement->reference_number,
                'description' => __('Stock movement posted: :product', ['product' => $movement->product?->name ?? $movement->id]),
                'metadata' => [
                    'source' => 'commercial_stock_movement',
                    'stock_movement_id' => $movement->id,
                    'product_id' => $movement->product_id,
                    'movement_type' => $movement->type,
                    'quantity' => $movement->quantity,
                    'unit_cost' => $movement->unit_cost,
                    'amount' => $amount,
                ],
            ], $isStockOut ? [
                [
                    'account' => $cogsAccount,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => __('COGS for stock out: :product', ['product' => $movement->product?->name ?? $movement->id]),
                ],
                [
                    'account' => $inventoryAccount,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => __('Inventory reduction: :product', ['product' => $movement->product?->name ?? $movement->id]),
                ],
            ] : [
                [
                    'account' => $inventoryAccount,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => __('Inventory increase: :product', ['product' => $movement->product?->name ?? $movement->id]),
                ],
                [
                    'account' => $clearingAccount,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => __('Inventory clearing: :product', ['product' => $movement->product?->name ?? $movement->id]),
                ],
            ]);

            $movement->forceFill([
                'metadata' => [
                    ...($movement->metadata ?? []),
                    'accounting_journal_entry_id' => $entry->id,
                    'accounting_posted_at' => now()->toIso8601String(),
                ],
            ])->save();

            return $entry;
        });
    }

    public function postReimbursement(User $actor, Reimbursement $reimbursement): ?JournalEntry
    {
        $reimbursement->loadMissing('user');

        if ($reimbursement->status !== 'approved' || $reimbursement->accounting_journal_entry_id !== null) {
            return $reimbursement->accountingJournalEntry?->loadMissing('lines.account');
        }

        $companyId = $reimbursement->user?->company_id;

        if ($companyId === null) {
            return null;
        }

        $this->assertCompanyAccess($actor, (int) $companyId);

        $existing = JournalEntry::query()
            ->where('source_type', Reimbursement::class)
            ->where('source_id', $reimbursement->id)
            ->first();

        if ($existing) {
            $reimbursement->forceFill([
                'accounting_journal_entry_id' => $existing->id,
                'accounting_posted_at' => $reimbursement->accounting_posted_at ?? now(),
            ])->save();

            return $existing->loadMissing('lines.account');
        }

        $amount = round((float) $reimbursement->amount, 2);

        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($actor, $reimbursement, $companyId, $amount): JournalEntry {
            $expenseAccount = $this->findOrCreateDefaultAccount((int) $companyId, self::DEFAULT_REIMBURSEMENT_EXPENSE_ACCOUNT);
            $cashAccount = $this->findOrCreateDefaultAccount((int) $companyId, self::DEFAULT_CASH_ACCOUNT);

            $entry = $this->createBalancedJournal($actor, (int) $companyId, [
                'entry_date' => ($reimbursement->finance_approved_at ?? $reimbursement->updated_at ?? now())->toDateString(),
                'source_type' => Reimbursement::class,
                'source_id' => $reimbursement->id,
                'reference_number' => 'RMB-'.$reimbursement->id,
                'description' => __('Reimbursement expense posted for :employee', ['employee' => $reimbursement->user?->name ?? $reimbursement->user_id]),
                'metadata' => [
                    'source' => 'reimbursement_approval',
                    'reimbursement_id' => $reimbursement->id,
                    'employee_id' => $reimbursement->user_id,
                    'type' => $reimbursement->type,
                    'amount' => $reimbursement->amount,
                ],
            ], [
                [
                    'account' => $expenseAccount,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => __('Reimbursement expense: :type', ['type' => $reimbursement->type]),
                ],
                [
                    'account' => $cashAccount,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => __('Reimbursement payment: :type', ['type' => $reimbursement->type]),
                ],
            ]);

            $reimbursement->forceFill([
                'accounting_journal_entry_id' => $entry->id,
                'accounting_posted_at' => now(),
            ])->save();

            return $entry;
        });
    }

    public function postVendorBill(User $actor, VendorBill $bill): JournalEntry
    {
        $bill->loadMissing(['items.product', 'vendor']);
        $this->assertCompanyAccess($actor, $bill->company_id);

        if ($bill->accounting_journal_entry_id !== null) {
            return $bill->accountingJournalEntry?->loadMissing('lines.account')
                ?? JournalEntry::query()->findOrFail($bill->accounting_journal_entry_id)->loadMissing('lines.account');
        }

        $existing = JournalEntry::query()
            ->where('source_type', VendorBill::class)
            ->where('source_id', $bill->id)
            ->first();

        if ($existing) {
            $bill->forceFill(['accounting_journal_entry_id' => $existing->id])->save();

            return $existing->loadMissing('lines.account');
        }

        $amount = round((float) $bill->grand_total, 2);
        abort_if($amount <= 0, 422, 'Vendor bill total must be greater than zero.');

        return DB::transaction(function () use ($actor, $bill, $amount): JournalEntry {
            $inventoryAccount = $this->findOrCreateDefaultAccount($bill->company_id, self::DEFAULT_INVENTORY_ACCOUNT);
            $purchaseExpenseAccount = $this->findOrCreateDefaultAccount($bill->company_id, self::DEFAULT_PURCHASE_EXPENSE_ACCOUNT);
            $apAccount = $this->findOrCreateDefaultAccount($bill->company_id, self::DEFAULT_ACCOUNTS_PAYABLE_ACCOUNT);
            $inventoryAmount = 0.0;
            $expenseAmount = 0.0;

            foreach ($bill->items as $item) {
                $lineAmount = round((float) $item->line_total, 2);

                if ($item->product_id) {
                    $inventoryAmount += $lineAmount;
                } else {
                    $expenseAmount += $lineAmount;
                }
            }

            $lines = [];

            if ($inventoryAmount > 0) {
                $lines[] = [
                    'account' => $inventoryAccount,
                    'debit' => round($inventoryAmount, 2),
                    'credit' => 0,
                    'memo' => __('Vendor bill inventory: :number', ['number' => $bill->number]),
                ];
            }

            if ($expenseAmount > 0) {
                $lines[] = [
                    'account' => $purchaseExpenseAccount,
                    'debit' => round($expenseAmount, 2),
                    'credit' => 0,
                    'memo' => __('Vendor bill expense: :number', ['number' => $bill->number]),
                ];
            }

            $lines[] = [
                'account' => $apAccount,
                'debit' => 0,
                'credit' => $amount,
                'memo' => __('Vendor payable: :number', ['number' => $bill->number]),
            ];

            $entry = $this->createBalancedJournal($actor, $bill->company_id, [
                'entry_date' => ($bill->issued_at ?? now())->toDateString(),
                'source_type' => VendorBill::class,
                'source_id' => $bill->id,
                'reference_number' => $bill->number,
                'description' => __('Vendor bill posted for :vendor', ['vendor' => $bill->vendor?->name ?? $bill->vendor_id]),
                'metadata' => [
                    'source' => 'vendor_bill',
                    'vendor_bill_id' => $bill->id,
                    'vendor_id' => $bill->vendor_id,
                    'subtotal' => $bill->subtotal,
                    'tax_total' => $bill->tax_total,
                    'grand_total' => $bill->grand_total,
                ],
            ], $lines);

            $bill->forceFill(['accounting_journal_entry_id' => $entry->id])->save();

            return $entry;
        });
    }

    public function postVendorBillPayment(User $actor, VendorBill $bill): JournalEntry
    {
        $bill->loadMissing('vendor');
        $this->assertCompanyAccess($actor, $bill->company_id);

        if ($bill->payment_journal_entry_id !== null) {
            return $bill->paymentJournalEntry?->loadMissing('lines.account')
                ?? JournalEntry::query()->findOrFail($bill->payment_journal_entry_id)->loadMissing('lines.account');
        }

        $amount = round((float) $bill->grand_total, 2);
        abort_if($amount <= 0, 422, 'Vendor bill total must be greater than zero.');

        return DB::transaction(function () use ($actor, $bill, $amount): JournalEntry {
            $apAccount = $this->findOrCreateDefaultAccount($bill->company_id, self::DEFAULT_ACCOUNTS_PAYABLE_ACCOUNT);
            $cashAccount = $this->findOrCreateDefaultAccount($bill->company_id, self::DEFAULT_CASH_ACCOUNT);

            $entry = $this->createBalancedJournal($actor, $bill->company_id, [
                'entry_date' => now()->toDateString(),
                'source_type' => VendorBill::class,
                'source_id' => $bill->id,
                'reference_number' => $bill->number,
                'description' => __('Vendor bill payment posted for :vendor', ['vendor' => $bill->vendor?->name ?? $bill->vendor_id]),
                'metadata' => [
                    'source' => 'vendor_bill_payment',
                    'vendor_bill_id' => $bill->id,
                    'vendor_id' => $bill->vendor_id,
                    'grand_total' => $bill->grand_total,
                ],
            ], [
                [
                    'account' => $apAccount,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => __('Vendor payable paid: :number', ['number' => $bill->number]),
                ],
                [
                    'account' => $cashAccount,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => __('Vendor payment: :number', ['number' => $bill->number]),
                ],
            ]);

            $bill->forceFill([
                'status' => VendorBill::STATUS_PAID,
                'paid_at' => $bill->paid_at ?? now(),
                'payment_journal_entry_id' => $entry->id,
            ])->save();

            return $entry;
        });
    }

    /**
     * @return array{debit: float, credit: float}
     */
    public function totalsForCompanies(array $companyIds, ?string $startDate = null, ?string $endDate = null): array
    {
        $totals = JournalEntryLine::query()
            ->whereHas('journalEntry', function (Builder $query) use ($companyIds, $startDate, $endDate): void {
                $query->whereIn('company_id', $companyIds);
                $this->applyEntryDateRange($query, $startDate, $endDate);
            })
            ->selectRaw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total')
            ->first();

        return [
            'debit' => round((float) ($totals?->debit_total ?? 0), 2),
            'credit' => round((float) ($totals?->credit_total ?? 0), 2),
        ];
    }

    /**
     * @return array{assets: float, liabilities: float, equity: float, revenue: float, expenses: float, net_income: float}
     */
    public function financialSummaryForCompanies(array $companyIds, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounting_accounts', 'accounting_accounts.id', '=', 'journal_entry_lines.accounting_account_id')
            ->whereIn('journal_entries.company_id', $companyIds);

        $this->applyJoinedEntryDateRange($query, $startDate, $endDate);

        $rows = $query
            ->selectRaw('accounting_accounts.type, COALESCE(SUM(journal_entry_lines.debit), 0) as debit_total, COALESCE(SUM(journal_entry_lines.credit), 0) as credit_total')
            ->groupBy('accounting_accounts.type')
            ->get()
            ->keyBy('type');

        $balance = function (string $type) use ($rows): float {
            $row = $rows->get($type);
            $debit = (float) ($row?->debit_total ?? 0);
            $credit = (float) ($row?->credit_total ?? 0);

            return in_array($type, [AccountingAccount::TYPE_ASSET, AccountingAccount::TYPE_EXPENSE], true)
                ? round($debit - $credit, 2)
                : round($credit - $debit, 2);
        };

        $revenue = $balance(AccountingAccount::TYPE_REVENUE);
        $expenses = $balance(AccountingAccount::TYPE_EXPENSE);

        return [
            'assets' => $balance(AccountingAccount::TYPE_ASSET),
            'liabilities' => $balance(AccountingAccount::TYPE_LIABILITY),
            'equity' => $balance(AccountingAccount::TYPE_EQUITY),
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_income' => round($revenue - $expenses, 2),
        ];
    }

    /**
     * @return array{
     *     assets: float,
     *     liabilities: float,
     *     equity: float,
     *     retained_earnings: float,
     *     equity_with_income: float,
     *     balance_check: float,
     *     working_capital: float
     * }
     */
    public function balanceSheetForCompanies(array $companyIds, ?string $startDate = null, ?string $endDate = null): array
    {
        $summary = $this->financialSummaryForCompanies($companyIds, $startDate, $endDate);
        $retainedEarnings = $summary['net_income'];
        $equityWithIncome = round($summary['equity'] + $retainedEarnings, 2);

        return [
            'assets' => $summary['assets'],
            'liabilities' => $summary['liabilities'],
            'equity' => $summary['equity'],
            'retained_earnings' => $retainedEarnings,
            'equity_with_income' => $equityWithIncome,
            'balance_check' => round($summary['assets'] - $summary['liabilities'] - $equityWithIncome, 2),
            'working_capital' => round($summary['assets'] - $summary['liabilities'], 2),
        ];
    }

    /**
     * @return array{
     *     invoice_count: int,
     *     taxable_invoice_count: int,
     *     issued_subtotal: float,
     *     issued_tax: float,
     *     issued_total: float,
     *     paid_tax: float,
     *     posted_tax_payable: float,
     *     unposted_tax: float,
     *     tax_rates: Collection<int, array{rate: float, taxable_amount: float, tax_amount: float}>
     * }
     */
    public function taxSummaryForCompanies(array $companyIds, ?string $startDate = null, ?string $endDate = null): array
    {
        $invoiceQuery = Invoice::query()->whereIn('company_id', $companyIds);

        if (filled($startDate)) {
            $invoiceQuery->whereDate('issued_at', '>=', Carbon::parse($startDate)->toDateString());
        }

        if (filled($endDate)) {
            $invoiceQuery->whereDate('issued_at', '<=', Carbon::parse($endDate)->toDateString());
        }

        $invoiceTotals = (clone $invoiceQuery)
            ->selectRaw('
                COUNT(*) as invoice_count,
                COALESCE(SUM(CASE WHEN tax_total > 0 THEN 1 ELSE 0 END), 0) as taxable_invoice_count,
                COALESCE(SUM(subtotal), 0) as issued_subtotal,
                COALESCE(SUM(tax_total), 0) as issued_tax,
                COALESCE(SUM(grand_total), 0) as issued_total,
                COALESCE(SUM(CASE WHEN status = ? THEN tax_total ELSE 0 END), 0) as paid_tax
            ', [Invoice::STATUS_PAID])
            ->first();

        $postedTaxPayableQuery = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounting_accounts', 'accounting_accounts.id', '=', 'journal_entry_lines.accounting_account_id')
            ->whereIn('journal_entries.company_id', $companyIds)
            ->where('accounting_accounts.code', self::DEFAULT_TAX_PAYABLE_ACCOUNT['code']);

        $this->applyJoinedEntryDateRange($postedTaxPayableQuery, $startDate, $endDate);

        $postedTaxPayable = $postedTaxPayableQuery
            ->selectRaw('COALESCE(SUM(journal_entry_lines.credit - journal_entry_lines.debit), 0) as tax_payable')
            ->value('tax_payable');

        $taxRates = InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->whereIn('invoices.company_id', $companyIds)
            ->when(filled($startDate), fn (Builder $query) => $query->whereDate('invoices.issued_at', '>=', Carbon::parse($startDate)->toDateString()))
            ->when(filled($endDate), fn (Builder $query) => $query->whereDate('invoices.issued_at', '<=', Carbon::parse($endDate)->toDateString()))
            ->where('invoice_items.tax_rate', '>', 0)
            ->selectRaw('
                invoice_items.tax_rate,
                COALESCE(SUM(invoice_items.line_total), 0) as taxable_amount,
                COALESCE(SUM(invoice_items.line_total * invoice_items.tax_rate / 100), 0) as tax_amount
            ')
            ->groupBy('invoice_items.tax_rate')
            ->orderBy('invoice_items.tax_rate')
            ->get()
            ->map(fn ($row): array => [
                'rate' => round((float) $row->tax_rate, 2),
                'taxable_amount' => round((float) $row->taxable_amount, 2),
                'tax_amount' => round((float) $row->tax_amount, 2),
            ])
            ->values();

        $issuedTax = round((float) ($invoiceTotals?->issued_tax ?? 0), 2);
        $postedTax = round((float) ($postedTaxPayable ?? 0), 2);

        return [
            'invoice_count' => (int) ($invoiceTotals?->invoice_count ?? 0),
            'taxable_invoice_count' => (int) ($invoiceTotals?->taxable_invoice_count ?? 0),
            'issued_subtotal' => round((float) ($invoiceTotals?->issued_subtotal ?? 0), 2),
            'issued_tax' => $issuedTax,
            'issued_total' => round((float) ($invoiceTotals?->issued_total ?? 0), 2),
            'paid_tax' => round((float) ($invoiceTotals?->paid_tax ?? 0), 2),
            'posted_tax_payable' => $postedTax,
            'unposted_tax' => round(max(0, $issuedTax - $postedTax), 2),
            'tax_rates' => $taxRates,
        ];
    }

    /**
     * @return Collection<int, array{account_id:int,code:string,name:string,type:string,debit:float,credit:float,balance:float}>
     */
    public function accountBalancesForCompanies(array $companyIds, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounting_accounts', 'accounting_accounts.id', '=', 'journal_entry_lines.accounting_account_id')
            ->whereIn('journal_entries.company_id', $companyIds);

        $this->applyJoinedEntryDateRange($query, $startDate, $endDate);

        return $query
            ->selectRaw('
                accounting_accounts.id as account_id,
                accounting_accounts.code,
                accounting_accounts.name,
                accounting_accounts.type,
                COALESCE(SUM(journal_entry_lines.debit), 0) as debit_total,
                COALESCE(SUM(journal_entry_lines.credit), 0) as credit_total
            ')
            ->groupBy('accounting_accounts.id', 'accounting_accounts.code', 'accounting_accounts.name', 'accounting_accounts.type')
            ->orderBy('accounting_accounts.code')
            ->get()
            ->map(function ($row): array {
                $debit = round((float) $row->debit_total, 2);
                $credit = round((float) $row->credit_total, 2);
                $balance = in_array($row->type, [AccountingAccount::TYPE_ASSET, AccountingAccount::TYPE_EXPENSE], true)
                    ? round($debit - $credit, 2)
                    : round($credit - $debit, 2);

                return [
                    'account_id' => (int) $row->account_id,
                    'code' => (string) $row->code,
                    'name' => (string) $row->name,
                    'type' => (string) $row->type,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $balance,
                ];
            })
            ->values();
    }

    /**
     * @return array{current: float, days_1_30: float, days_31_60: float, days_61_90: float, days_90_plus: float, total: float, count: int}
     */
    public function receivablesAgingForCompanies(array $companyIds, ?string $asOfDate = null): array
    {
        $asOf = filled($asOfDate) ? Carbon::parse($asOfDate)->startOfDay() : now()->startOfDay();
        $buckets = $this->emptyAgingBuckets();

        Invoice::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', Invoice::STATUS_SENT)
            ->where('grand_total', '>', 0)
            ->get(['id', 'due_at', 'issued_at', 'grand_total'])
            ->each(function (Invoice $invoice) use (&$buckets, $asOf): void {
                $basisDate = $invoice->due_at ?? $invoice->issued_at ?? $asOf;
                $this->addToAgingBucket($buckets, Carbon::parse($basisDate)->startOfDay(), $asOf, (float) $invoice->grand_total);
            });

        return $buckets;
    }

    /**
     * @return array{current: float, days_1_30: float, days_31_60: float, days_61_90: float, days_90_plus: float, total: float, count: int}
     */
    public function payablesAgingForCompanies(array $companyIds, ?string $asOfDate = null): array
    {
        $asOf = filled($asOfDate) ? Carbon::parse($asOfDate)->startOfDay() : now()->startOfDay();
        $buckets = $this->emptyAgingBuckets();

        VendorBill::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', VendorBill::STATUS_POSTED)
            ->where('grand_total', '>', 0)
            ->get(['id', 'due_at', 'issued_at', 'grand_total'])
            ->each(function (VendorBill $bill) use (&$buckets, $asOf): void {
                $basisDate = $bill->due_at ?? $bill->issued_at ?? $asOf;
                $this->addToAgingBucket($buckets, Carbon::parse($basisDate)->startOfDay(), $asOf, (float) $bill->grand_total);
            });

        return $buckets;
    }

    /**
     * @return array{inflows: float, outflows: float, net_cash: float}
     */
    public function cashflowSummaryForCompanies(array $companyIds, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounting_accounts', 'accounting_accounts.id', '=', 'journal_entry_lines.accounting_account_id')
            ->whereIn('journal_entries.company_id', $companyIds)
            ->where('accounting_accounts.code', self::DEFAULT_CASH_ACCOUNT['code']);

        $this->applyJoinedEntryDateRange($query, $startDate, $endDate);

        $totals = $query
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as inflows, COALESCE(SUM(journal_entry_lines.credit), 0) as outflows')
            ->first();

        $inflows = round((float) ($totals?->inflows ?? 0), 2);
        $outflows = round((float) ($totals?->outflows ?? 0), 2);

        return [
            'inflows' => $inflows,
            'outflows' => $outflows,
            'net_cash' => round($inflows - $outflows, 2),
        ];
    }

    /**
     * @return array{
     *     sales_total: float,
     *     open_ar: float,
     *     purchase_total: float,
     *     open_ap: float,
     *     operational_expenses: float,
     *     posted_journals: int
     * }
     */
    public function tokoContributionForCompanies(array $companyIds, ?string $startDate = null, ?string $endDate = null): array
    {
        $invoiceQuery = Invoice::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('metadata->source', self::TOKO_INVOICE_SOURCES)
            ->where('status', '!=', Invoice::STATUS_CANCELLED);

        if (filled($startDate)) {
            $invoiceQuery->whereDate('issued_at', '>=', Carbon::parse($startDate)->toDateString());
        }

        if (filled($endDate)) {
            $invoiceQuery->whereDate('issued_at', '<=', Carbon::parse($endDate)->toDateString());
        }

        $vendorBillQuery = VendorBill::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('metadata->source', self::TOKO_VENDOR_BILL_SOURCES)
            ->where('status', '!=', VendorBill::STATUS_CANCELLED);

        if (filled($startDate)) {
            $vendorBillQuery->whereDate('issued_at', '>=', Carbon::parse($startDate)->toDateString());
        }

        if (filled($endDate)) {
            $vendorBillQuery->whereDate('issued_at', '<=', Carbon::parse($endDate)->toDateString());
        }

        $expenseQuery = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereIn('journal_entries.company_id', $companyIds)
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
            ->where('journal_entries.source_type', 'toko_pos_operational_expense');

        $this->applyJoinedEntryDateRange($expenseQuery, $startDate, $endDate);

        $tokoInvoiceIds = (clone $invoiceQuery)->pluck('id')->all();
        $tokoVendorBillIds = (clone $vendorBillQuery)->pluck('id')->all();

        $journalQuery = JournalEntry::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->where(function (Builder $query) use ($tokoInvoiceIds, $tokoVendorBillIds): void {
                $query->whereIn('source_type', self::TOKO_JOURNAL_SOURCE_TYPES)
                    ->orWhere(function (Builder $nested) use ($tokoInvoiceIds): void {
                        $nested
                            ->where('source_type', Invoice::class)
                            ->whereIn('source_id', $tokoInvoiceIds === [] ? [0] : $tokoInvoiceIds);
                    })
                    ->orWhere(function (Builder $nested) use ($tokoVendorBillIds): void {
                        $nested
                            ->where('source_type', VendorBill::class)
                            ->whereIn('source_id', $tokoVendorBillIds === [] ? [0] : $tokoVendorBillIds);
                    });
            });

        $this->applyEntryDateRange($journalQuery, $startDate, $endDate);

        return [
            'sales_total' => round((float) (clone $invoiceQuery)->sum('grand_total'), 2),
            'open_ar' => round((float) (clone $invoiceQuery)->where('status', Invoice::STATUS_SENT)->sum('grand_total'), 2),
            'purchase_total' => round((float) (clone $vendorBillQuery)->sum('grand_total'), 2),
            'open_ap' => round((float) (clone $vendorBillQuery)->where('status', VendorBill::STATUS_POSTED)->sum('grand_total'), 2),
            'operational_expenses' => round((float) $expenseQuery->sum('journal_entry_lines.debit'), 2),
            'posted_journals' => (int) $journalQuery->count(),
        ];
    }

    /**
     * @return Collection<int, array{date: string, journal_number: string, reference_number: ?string, company: string, account_code: string, account_name: string, account_type: string, description: ?string, memo: ?string, debit: float, credit: float}>
     */
    public function ledgerLinesForCompanies(array $companyIds, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounting_accounts', 'accounting_accounts.id', '=', 'journal_entry_lines.accounting_account_id')
            ->join('companies', 'companies.id', '=', 'journal_entries.company_id')
            ->whereIn('journal_entries.company_id', $companyIds);

        $this->applyJoinedEntryDateRange($query, $startDate, $endDate);

        return $query
            ->selectRaw('
                journal_entries.entry_date,
                journal_entries.number as journal_number,
                journal_entries.reference_number,
                journal_entries.description,
                companies.name as company_name,
                accounting_accounts.code as account_code,
                accounting_accounts.name as account_name,
                accounting_accounts.type as account_type,
                journal_entry_lines.memo,
                journal_entry_lines.debit,
                journal_entry_lines.credit
            ')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_entry_lines.id')
            ->get()
            ->map(fn ($row): array => [
                'date' => Carbon::parse($row->entry_date)->toDateString(),
                'journal_number' => (string) $row->journal_number,
                'reference_number' => $row->reference_number ? (string) $row->reference_number : null,
                'company' => (string) $row->company_name,
                'account_code' => (string) $row->account_code,
                'account_name' => (string) $row->account_name,
                'account_type' => (string) $row->account_type,
                'description' => $row->description ? (string) $row->description : null,
                'memo' => $row->memo ? (string) $row->memo : null,
                'debit' => round((float) $row->debit, 2),
                'credit' => round((float) $row->credit, 2),
            ])
            ->values();
    }

    private function assertCompanyAccess(User $actor, int $companyId): void
    {
        abort_unless($this->canAccessCompany($actor, $companyId), 403);
    }

    private function defaultNormalBalance(string $type): string
    {
        return in_array($type, [AccountingAccount::TYPE_ASSET, AccountingAccount::TYPE_EXPENSE], true)
            ? AccountingAccount::BALANCE_DEBIT
            : AccountingAccount::BALANCE_CREDIT;
    }

    /**
     * @param  array{code: string, name: string, type: string}  $account
     */
    private function findOrCreateDefaultAccount(int $companyId, array $account): AccountingAccount
    {
        return AccountingAccount::query()->firstOrCreate([
            'company_id' => $companyId,
            'code' => $account['code'],
        ], [
            'name' => $account['name'],
            'type' => $account['type'],
            'normal_balance' => $this->defaultNormalBalance($account['type']),
            'is_active' => true,
            'metadata' => ['system_default' => true],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{account: AccountingAccount, debit: float|int, credit: float|int, memo?: string|null}>  $lines
     */
    private function createBalancedJournal(User $actor, int $companyId, array $data, array $lines): JournalEntry
    {
        $this->assertPeriodOpen($companyId, (string) $data['entry_date']);

        $debitTotal = round(array_sum(array_map(fn (array $line): float => (float) $line['debit'], $lines)), 2);
        $creditTotal = round(array_sum(array_map(fn (array $line): float => (float) $line['credit'], $lines)), 2);

        abort_if($debitTotal <= 0 || $creditTotal <= 0 || $debitTotal !== $creditTotal, 422, 'Journal entry must be balanced.');

        return DB::transaction(function () use ($actor, $companyId, $data, $lines): JournalEntry {
            $entry = JournalEntry::query()->create([
                'company_id' => $companyId,
                'created_by' => $actor->id,
                'number' => $data['number'] ?? $this->nextJournalNumber($companyId),
                'entry_date' => $data['entry_date'],
                'status' => JournalEntry::STATUS_POSTED,
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'description' => $data['description'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            foreach ($lines as $line) {
                /** @var AccountingAccount $account */
                $account = $line['account'];

                $this->assertAccountBelongsToCompany($account, $companyId);

                JournalEntryLine::query()->create([
                    'journal_entry_id' => $entry->id,
                    'accounting_account_id' => $account->id,
                    'debit' => round((float) $line['debit'], 2),
                    'credit' => round((float) $line['credit'], 2),
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            return $entry->fresh(['lines.account']);
        });
    }

    private function assertAccountBelongsToCompany(AccountingAccount $account, int $companyId): void
    {
        abort_unless((int) $account->company_id === $companyId && $account->is_active, 422, 'Selected account is not available for this company.');
    }

    private function assertPeriodOpen(int $companyId, string $entryDate): void
    {
        if ($this->isPeriodClosed($companyId, $entryDate)) {
            throw new HttpException(423, 'Accounting period is closed.');
        }
    }

    private function applyEntryDateRange(Builder $query, ?string $startDate, ?string $endDate): void
    {
        if (filled($startDate)) {
            $query->whereDate('entry_date', '>=', Carbon::parse($startDate)->toDateString());
        }

        if (filled($endDate)) {
            $query->whereDate('entry_date', '<=', Carbon::parse($endDate)->toDateString());
        }
    }

    private function applyJoinedEntryDateRange(Builder $query, ?string $startDate, ?string $endDate): void
    {
        if (filled($startDate)) {
            $query->whereDate('journal_entries.entry_date', '>=', Carbon::parse($startDate)->toDateString());
        }

        if (filled($endDate)) {
            $query->whereDate('journal_entries.entry_date', '<=', Carbon::parse($endDate)->toDateString());
        }
    }

    /**
     * @return array{current: float, days_1_30: float, days_31_60: float, days_61_90: float, days_90_plus: float, total: float, count: int}
     */
    private function emptyAgingBuckets(): array
    {
        return [
            'current' => 0.0,
            'days_1_30' => 0.0,
            'days_31_60' => 0.0,
            'days_61_90' => 0.0,
            'days_90_plus' => 0.0,
            'total' => 0.0,
            'count' => 0,
        ];
    }

    /**
     * @param  array{current: float, days_1_30: float, days_31_60: float, days_61_90: float, days_90_plus: float, total: float, count: int}  $buckets
     */
    private function addToAgingBucket(array &$buckets, Carbon $dueDate, Carbon $asOf, float $amount): void
    {
        $daysOverdue = (int) $dueDate->diffInDays($asOf, false);
        $bucket = match (true) {
            $daysOverdue <= 0 => 'current',
            $daysOverdue <= 30 => 'days_1_30',
            $daysOverdue <= 60 => 'days_31_60',
            $daysOverdue <= 90 => 'days_61_90',
            default => 'days_90_plus',
        };

        $buckets[$bucket] = round($buckets[$bucket] + $amount, 2);
        $buckets['total'] = round($buckets['total'] + $amount, 2);
        $buckets['count']++;
    }

    private function nextJournalNumber(int $companyId): string
    {
        $count = JournalEntry::query()->where('company_id', $companyId)->count() + 1;

        return 'JRN-'.now()->format('Ymd').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Support;

use App\Models\AccountingAccount;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class TokoPosSalesService
{
    public function createCounterSale(User $actor, array $data): Invoice
    {
        $companyId = (int) ($data['company_id'] ?? 0);
        $this->assertAccess($actor, $companyId);
        $items = array_values($data['items'] ?? []);
        abort_if($items === [], 422, 'Counter sale requires at least one item.');

        return DB::transaction(function () use ($actor, $companyId, $data, $items): Invoice {
            $lines = [];
            $subtotal = 0.0;
            foreach ($items as $item) {
                $product = Product::query()->where('company_id', $companyId)->findOrFail((int) ($item['product_id'] ?? 0));
                $quantity = round((float) ($item['quantity'] ?? 0), 3);
                abort_if($quantity <= 0, 422, 'Item quantity must be greater than zero.');
                $price = round((float) ($item['unit_price'] ?? $product->selling_price), 2);
                $total = round($quantity * $price, 2);
                $lines[] = compact('product', 'quantity', 'price', 'total');
                $subtotal += $total;
            }
            $discount = round((float) ($data['discount_amount'] ?? 0), 2);
            $additional = round((float) ($data['additional_charge'] ?? 0), 2);
            abort_if($discount < 0 || $additional < 0, 422, 'Invalid sale adjustment.');
            $grandTotal = round(max(0, $subtotal - $discount + $additional), 2);
            $payments = $this->payments($data, $grandTotal);
            $paidTotal = round(array_sum(array_column($payments, 'amount')), 2);
            $paid = ($data['payment_status'] ?? 'paid') === 'paid' && $paidTotal >= $grandTotal;
            $invoice = Invoice::query()->create([
                'company_id' => $companyId, 'branch_id' => $data['branch_id'] ?? null, 'client_id' => $data['client_id'] ?? null,
                'number' => $this->nextNumber($companyId), 'status' => $paid ? Invoice::STATUS_PAID : Invoice::STATUS_SENT,
                'issued_at' => now()->toDateString(), 'due_at' => now()->addDays(max(0, (int) ($data['due_days'] ?? 0)))->toDateString(),
                'paid_at' => $paid ? now() : null, 'subtotal' => $subtotal, 'tax_total' => 0, 'grand_total' => $grandTotal,
                'metadata' => ['source' => 'toko_pos_counter_sale', 'payment_status' => $paid ? 'paid' : 'unpaid',
                    'payment_method' => $data['payment_method'] ?? ($payments[0]['method'] ?? null), 'bank_code' => $data['bank_code'] ?? null,
                    'discount_amount' => $discount, 'additional_charge' => $additional, 'payments' => $payments,
                    'paid_total' => min($paidTotal, $grandTotal), 'remaining_total' => max(0, $grandTotal - $paidTotal)],
            ]);
            foreach ($lines as $line) {
                $product = $line['product'];
                InvoiceItem::query()->create(['invoice_id' => $invoice->id, 'product_id' => $product->id, 'description' => $product->name, 'quantity' => $line['quantity'], 'unit_price' => $line['price'], 'tax_rate' => 0, 'line_total' => $line['total']]);
                if ($product->stock_tracking) {
                    $this->stock($actor, $invoice, $product, StockMovement::TYPE_OUT, $line['quantity'], 'toko_pos_counter_sale');
                }
            }
            if ($paid && $grandTotal > 0) {
                $this->journal($actor, $invoice, $grandTotal, 'toko_pos_invoice_payment', false);
            }

            return $invoice->fresh(['items.product']);
        });
    }

    public function recordInvoicePayment(User $actor, Invoice $invoice, array $data): Invoice
    {
        $this->assertAccess($actor, (int) $invoice->company_id);
        abort_if($invoice->status === Invoice::STATUS_CANCELLED, 422, 'Cancelled invoices cannot receive payments.');
        $amount = round((float) ($data['amount'] ?? 0), 2);
        abort_if($amount <= 0, 422, 'Payment amount must be greater than zero.');

        return DB::transaction(function () use ($actor, $invoice, $data, $amount): Invoice {
            $metadata = $invoice->metadata ?? [];
            $payments = $metadata['payments'] ?? [];
            $payments[] = ['amount' => $amount, 'method' => $data['method'] ?? null, 'bank_code' => $data['bank_code'] ?? null, 'reference' => $data['reference'] ?? null, 'recorded_at' => now()->toIso8601String()];
            $paid = round(array_sum(array_column($payments, 'amount')), 2);
            $remaining = max(0, round((float) $invoice->grand_total - $paid, 2));
            $complete = $remaining <= 0;
            $invoice->forceFill(['status' => $complete ? Invoice::STATUS_PAID : Invoice::STATUS_SENT, 'paid_at' => $complete ? now() : null,
                'metadata' => [...$metadata, 'payments' => $payments, 'paid_total' => min($paid, (float) $invoice->grand_total), 'remaining_total' => $remaining, 'payment_status' => $complete ? 'paid' : 'partial']])->save();
            $this->journal($actor, $invoice, $amount, 'toko_pos_invoice_payment', false);

            return $invoice->fresh(['items.product']);
        });
    }

    public function cancelCounterSale(User $actor, Invoice $invoice, string $reason): Invoice
    {
        $this->assertAccess($actor, (int) $invoice->company_id);
        abort_if($invoice->status === Invoice::STATUS_CANCELLED, 422, 'Counter sale has already been cancelled.');

        return DB::transaction(function () use ($actor, $invoice, $reason): Invoice {
            $invoice->loadMissing('items.product');
            foreach ($invoice->items as $item) {
                if ($item->product?->stock_tracking) {
                    $this->stock($actor, $invoice, $item->product, StockMovement::TYPE_IN, (float) $item->quantity, 'toko_pos_counter_sale_cancel');
                }
            }
            $metadata = $invoice->metadata ?? [];
            $metadata['cancel_reason'] = $reason;
            if ($invoice->status === Invoice::STATUS_PAID) {
                $entry = $this->journal($actor, $invoice, (float) $invoice->grand_total, 'toko_pos_invoice_refund', true);
                $metadata['refund_journal_entry_id'] = $entry->id;
                $metadata['payment_status'] = 'refunded';
            }
            $invoice->forceFill(['status' => Invoice::STATUS_CANCELLED, 'metadata' => $metadata])->save();

            return $invoice->fresh(['items.product']);
        });
    }

    private function payments(array $data, float $total): array
    {
        $payments = $data['payments'] ?? [];
        if ($payments === [] && ($data['payment_status'] ?? 'paid') === 'paid') {
            $payments = [['amount' => $total, 'method' => $data['payment_method'] ?? 'Cash', 'bank_code' => $data['bank_code'] ?? null, 'reference' => null]];
        }

        return array_values(array_map(fn (array $payment) => ['amount' => round((float) ($payment['amount'] ?? 0), 2), 'method' => $payment['method'] ?? null, 'bank_code' => $payment['bank_code'] ?? null, 'reference' => $payment['reference'] ?? null], $payments));
    }

    private function stock(User $actor, Invoice $invoice, Product $product, string $type, float $quantity, string $source): void
    {
        StockMovement::query()->create(['company_id' => $invoice->company_id, 'branch_id' => $invoice->branch_id, 'product_id' => $product->id, 'user_id' => $actor->id, 'type' => $type, 'quantity' => $quantity, 'unit_cost' => $product->cost_price, 'reference_type' => Invoice::class, 'reference_number' => $invoice->number, 'occurred_at' => now(), 'metadata' => ['source' => $source, 'invoice_id' => $invoice->id]]);
    }

    private function journal(User $actor, Invoice $invoice, float $amount, string $source, bool $refund): JournalEntry
    {
        $cash = AccountingAccount::query()->firstOrCreate(['company_id' => $invoice->company_id, 'code' => '1010'], ['name' => 'Cash on Hand', 'type' => AccountingAccount::TYPE_ASSET, 'normal_balance' => AccountingAccount::BALANCE_DEBIT, 'is_active' => true]);
        $ar = AccountingAccount::query()->firstOrCreate(['company_id' => $invoice->company_id, 'code' => '1100'], ['name' => 'Accounts Receivable', 'type' => AccountingAccount::TYPE_ASSET, 'normal_balance' => AccountingAccount::BALANCE_DEBIT, 'is_active' => true]);
        $entry = JournalEntry::query()->create(['company_id' => $invoice->company_id, 'created_by' => $actor->id, 'number' => 'POS-JRN-'.str_pad((string) (JournalEntry::query()->where('company_id', $invoice->company_id)->count() + 1), 6, '0', STR_PAD_LEFT), 'entry_date' => now()->toDateString(), 'status' => JournalEntry::STATUS_POSTED, 'source_type' => $source, 'source_id' => $invoice->id, 'reference_number' => $invoice->number, 'metadata' => ['invoice_id' => $invoice->id]]);
        foreach ($refund ? [[$ar, $amount, 0], [$cash, 0, $amount]] : [[$cash, $amount, 0], [$ar, 0, $amount]] as [$account, $debit, $credit]) {
            JournalEntryLine::query()->create(['journal_entry_id' => $entry->id, 'accounting_account_id' => $account->id, 'debit' => $debit, 'credit' => $credit]);
        }

        return $entry->load('lines');
    }

    private function nextNumber(int $companyId): string
    {
        return 'POS-'.now()->format('Ymd').'-'.str_pad((string) (Invoice::query()->where('company_id', $companyId)->where('number', 'like', 'POS-%')->count() + 1), 5, '0', STR_PAD_LEFT);
    }

    private function assertAccess(User $actor, int $companyId): void
    {
        abort_unless($companyId > 0 && ($actor->isSuperadmin || (int) $actor->company_id === $companyId), 403);
    }
}

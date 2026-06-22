<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ScopesCompanySelection;
use App\Livewire\Concerns\ValidatesCompanyId;
use App\Models\AccountingAccount;
use App\Models\JournalEntry;
use App\Support\AccountingWorkspaceService;
use App\Support\Contracts\ScopesCompanies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class AccountingWorkspace extends Component
{
    use InteractsWithBanner;
    use ScopesCompanySelection;
    use ValidatesCompanyId;

    private const TABS = ['journals', 'accounts', 'reports', 'tax'];

    private const DEFAULT_TAB = 'journals';

    protected AccountingWorkspaceService $accounting;

    #[Url(history: true)]
    public string $activeTab = 'journals';

    public string $search = '';

    public string $accountCompanyId = '';

    public string $accountCode = '';

    public string $accountName = '';

    public string $accountType = AccountingAccount::TYPE_ASSET;

    public string $journalCompanyId = '';

    public string $journalDate = '';

    public string $journalDebitAccountId = '';

    public string $journalCreditAccountId = '';

    public string $journalAmount = '0';

    public string $journalReference = '';

    public string $journalDescription = '';

    public string $reportStartDate = '';

    public string $reportEndDate = '';

    public string $closingCompanyId = '';

    public string $closingStartDate = '';

    public string $closingEndDate = '';

    public string $closingNotes = '';

    public string $taxCompanyId = '';

    public string $taxStartDate = '';

    public string $taxEndDate = '';

    public string $taxInputTax = '0';

    public string $taxFilingReference = '';

    public string $taxPaymentReference = '';

    public string $taxNotes = '';

    public function boot(AccountingWorkspaceService $accounting): void
    {
        Gate::authorize('viewAccountingWorkspace');

        $this->accounting = $accounting;
    }

    public function mount(): void
    {
        $this->normalizeActiveTab();

        $this->journalDate = now()->toDateString();
        $this->reportStartDate = now()->startOfMonth()->toDateString();
        $this->reportEndDate = now()->endOfMonth()->toDateString();
        $this->closingStartDate = now()->startOfMonth()->toDateString();
        $this->closingEndDate = now()->endOfMonth()->toDateString();
        $this->taxStartDate = now()->startOfMonth()->toDateString();
        $this->taxEndDate = now()->endOfMonth()->toDateString();

        $companyId = $this->defaultCompanyId();

        if ($companyId === null) {
            return;
        }

        $this->accountCompanyId = $companyId;
        $this->journalCompanyId = $companyId;
        $this->closingCompanyId = $companyId;
        $this->taxCompanyId = $companyId;
    }

    public function updatedJournalCompanyId(): void
    {
        $this->reset(['journalDebitAccountId', 'journalCreditAccountId']);
    }

    public function updatedActiveTab(): void
    {
        $this->normalizeActiveTab();
    }

    public function createAccount(): void
    {
        Gate::authorize('manageAccountingWorkspace');

        $validated = $this->validate([
            'accountCompanyId' => $this->companyIdRules(),
            'accountCode' => ['required', 'string', 'max:32'],
            'accountName' => ['required', 'string', 'max:180'],
            'accountType' => ['required', Rule::in($this->accountTypes())],
        ]);

        $this->accounting->createAccount(auth()->user(), [
            'company_id' => (int) $validated['accountCompanyId'],
            'code' => $validated['accountCode'],
            'name' => $validated['accountName'],
            'type' => $validated['accountType'],
        ]);

        $this->reset(['accountCode', 'accountName']);
        $this->banner(__('Account created.'));
    }

    public function createJournal(): void
    {
        Gate::authorize('manageAccountingWorkspace');

        $validated = $this->validate([
            'journalCompanyId' => $this->companyIdRules(),
            'journalDate' => ['required', 'date'],
            'journalDebitAccountId' => [
                'required',
                'integer',
                Rule::exists('accounting_accounts', 'id')->where('company_id', (int) $this->journalCompanyId)->where('is_active', true),
            ],
            'journalCreditAccountId' => [
                'required',
                'integer',
                'different:journalDebitAccountId',
                Rule::exists('accounting_accounts', 'id')->where('company_id', (int) $this->journalCompanyId)->where('is_active', true),
            ],
            'journalAmount' => ['required', 'numeric', 'min:0.01', 'max:999999999999'],
            'journalReference' => ['nullable', 'string', 'max:120'],
            'journalDescription' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->accounting->createSimpleJournal(auth()->user(), [
            'company_id' => (int) $validated['journalCompanyId'],
            'entry_date' => $validated['journalDate'],
            'debit_account_id' => (int) $validated['journalDebitAccountId'],
            'credit_account_id' => (int) $validated['journalCreditAccountId'],
            'amount' => $validated['journalAmount'],
            'reference_number' => $validated['journalReference'] ?: null,
            'description' => $validated['journalDescription'] ?: null,
        ]);

        $this->reset(['journalDebitAccountId', 'journalCreditAccountId', 'journalAmount', 'journalReference', 'journalDescription']);
        $this->journalAmount = '0';
        $this->banner(__('Journal posted.'));
    }

    public function closeAccountingPeriod(): void
    {
        Gate::authorize('manageAccountingWorkspace');

        $validated = $this->validate([
            'closingCompanyId' => $this->companyIdRules(),
            'closingStartDate' => ['required', 'date'],
            'closingEndDate' => ['required', 'date', 'after_or_equal:closingStartDate'],
            'closingNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->accounting->closePeriod(auth()->user(), [
            'company_id' => (int) $validated['closingCompanyId'],
            'period_start' => $validated['closingStartDate'],
            'period_end' => $validated['closingEndDate'],
            'notes' => $validated['closingNotes'] ?: null,
        ]);

        $this->reset('closingNotes');
        $this->banner(__('Accounting period closed.'));
    }

    public function reopenAccountingPeriod(int $closingId): void
    {
        Gate::authorize('manageAccountingWorkspace');

        $this->accounting->reopenPeriod(auth()->user(), $closingId);
        $this->banner(__('Accounting period reopened.'));
    }

    public function prepareTaxFiling(): void
    {
        Gate::authorize('manageAccountingWorkspace');

        $validated = $this->validate([
            'taxCompanyId' => $this->companyIdRules(),
            'taxStartDate' => ['required', 'date'],
            'taxEndDate' => ['required', 'date', 'after_or_equal:taxStartDate'],
            'taxInputTax' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'taxNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->accounting->prepareTaxFiling(auth()->user(), [
            'company_id' => (int) $validated['taxCompanyId'],
            'period_start' => $validated['taxStartDate'],
            'period_end' => $validated['taxEndDate'],
            'input_tax' => $validated['taxInputTax'],
            'notes' => $validated['taxNotes'] ?: null,
        ]);

        $this->reset(['taxNotes']);
        $this->taxInputTax = '0';
        $this->banner(__('Tax filing draft prepared.'));
    }

    public function markTaxFilingFiled(int $filingId): void
    {
        Gate::authorize('manageAccountingWorkspace');

        $validated = $this->validate([
            'taxFilingReference' => ['nullable', 'string', 'max:120'],
            'taxNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->accounting->markTaxFilingFiled(auth()->user(), $filingId, [
            'filing_reference' => $validated['taxFilingReference'] ?: null,
            'notes' => $validated['taxNotes'] ?: null,
        ]);

        $this->reset(['taxFilingReference', 'taxNotes']);
        $this->banner(__('Tax filing marked as filed.'));
    }

    public function markTaxFilingPaid(int $filingId): void
    {
        Gate::authorize('manageAccountingWorkspace');

        $validated = $this->validate([
            'taxFilingReference' => ['nullable', 'string', 'max:120'],
            'taxPaymentReference' => ['nullable', 'string', 'max:120'],
            'taxNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->accounting->markTaxFilingPaid(auth()->user(), $filingId, [
            'filing_reference' => $validated['taxFilingReference'] ?: null,
            'payment_reference' => $validated['taxPaymentReference'] ?: null,
            'notes' => $validated['taxNotes'] ?: null,
        ]);

        $this->reset(['taxFilingReference', 'taxPaymentReference', 'taxNotes']);
        $this->banner(__('Tax filing marked as paid.'));
    }

    public function resetReportPeriod(): void
    {
        $this->reportStartDate = now()->startOfMonth()->toDateString();
        $this->reportEndDate = now()->endOfMonth()->toDateString();
    }

    public function render()
    {
        $user = auth()->user();
        $companyIds = $this->scopedCompanyIds($user);
        $companies = $this->companyOptions($companyIds);

        $accounts = AccountingAccount::query()
            ->with('company:id,name')
            ->whereIn('company_id', $companyIds)
            ->when($this->search !== '', fn (Builder $query) => $query->where(function (Builder $nested): void {
                $nested
                    ->where('code', 'like', '%'.$this->search.'%')
                    ->orWhere('name', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('code')
            ->get();

        $journals = JournalEntry::query()
            ->with(['company:id,name', 'creator:id,name', 'lines.account'])
            ->whereIn('company_id', $companyIds)
            ->when($this->reportStartDate !== '', fn (Builder $query) => $query->whereDate('entry_date', '>=', $this->reportStartDate))
            ->when($this->reportEndDate !== '', fn (Builder $query) => $query->whereDate('entry_date', '<=', $this->reportEndDate))
            ->latest('entry_date')
            ->latest()
            ->get();
        $selectedJournalCompanyId = $this->scopedCompanyId($companyIds, $this->journalCompanyId);

        return view('livewire.admin.accounting-workspace', [
            'companies' => $companies,
            'accounts' => $accounts,
            'journalAccountOptions' => $selectedJournalCompanyId === null
                ? $accounts->where('is_active', true)->values()
                : $accounts->where('company_id', $selectedJournalCompanyId)->where('is_active', true)->values(),
            'journals' => $journals,
            'totals' => $this->accounting->totalsForCompanies($companyIds, $this->reportStartDate, $this->reportEndDate),
            'financialSummary' => $this->accounting->financialSummaryForCompanies($companyIds, $this->reportStartDate, $this->reportEndDate),
            'balanceSheet' => $this->accounting->balanceSheetForCompanies($companyIds, $this->reportStartDate, $this->reportEndDate),
            'taxSummary' => $this->accounting->taxSummaryForCompanies($companyIds, $this->reportStartDate, $this->reportEndDate),
            'accountBalances' => $this->accounting->accountBalancesForCompanies($companyIds, $this->reportStartDate, $this->reportEndDate),
            'receivablesAging' => $this->accounting->receivablesAgingForCompanies($companyIds, $this->reportEndDate),
            'payablesAging' => $this->accounting->payablesAgingForCompanies($companyIds, $this->reportEndDate),
            'cashflowSummary' => $this->accounting->cashflowSummaryForCompanies($companyIds, $this->reportStartDate, $this->reportEndDate),
            'tokoContribution' => $this->accounting->tokoContributionForCompanies($companyIds, $this->reportStartDate, $this->reportEndDate),
            'ledgerLines' => $this->accounting->ledgerLinesForCompanies($companyIds, $this->reportStartDate, $this->reportEndDate),
            'periodClosings' => $this->accounting->periodClosingsForCompanies($companyIds),
            'taxFilings' => $this->accounting->taxFilingsForCompanies($companyIds),
            'accountTypes' => $this->accountTypes(),
            'canManage' => $user->can('manageAccountingWorkspace'),
        ]);
    }

    /**
     * @return list<string>
     */
    private function accountTypes(): array
    {
        return [
            AccountingAccount::TYPE_ASSET,
            AccountingAccount::TYPE_LIABILITY,
            AccountingAccount::TYPE_EQUITY,
            AccountingAccount::TYPE_REVENUE,
            AccountingAccount::TYPE_EXPENSE,
        ];
    }

    protected function companyScopeService(): ScopesCompanies
    {
        return $this->accounting;
    }
}

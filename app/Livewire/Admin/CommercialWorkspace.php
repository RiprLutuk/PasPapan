<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ScopesCompanySelection;
use App\Livewire\Forms\Commercial\DocumentForm;
use App\Livewire\Forms\Commercial\OpportunityForm;
use App\Livewire\Forms\Commercial\ProductForm;
use App\Livewire\Forms\Commercial\StockMovementForm;
use App\Livewire\Forms\Commercial\VendorBillForm;
use App\Livewire\Forms\Commercial\VendorForm;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\SalesFollowUp;
use App\Models\SalesOpportunity;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Support\CommercialWorkspaceService;
use App\Support\Contracts\ScopesCompanies;
use App\Support\MoneyInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CommercialWorkspace extends Component
{
    use InteractsWithBanner;
    use ScopesCompanySelection;
    use WithPagination;

    private const TABS = ['pipeline', 'products', 'stock', 'purchases', 'quotations', 'invoices'];

    private const DEFAULT_TAB = 'products';

    protected CommercialWorkspaceService $commerce;

    #[Url(history: true)]
    public string $activeTab = 'products';

    public string $search = '';

    public ProductForm $productForm;

    public StockMovementForm $stockMovementForm;

    public VendorForm $vendorForm;

    public VendorBillForm $vendorBillForm;

    public DocumentForm $documentForm;

    public OpportunityForm $opportunityForm;

    public bool $showProductModal = false;

    public bool $showStockMovementModal = false;

    public bool $showVendorModal = false;

    public bool $showVendorBillModal = false;

    public bool $showDocumentModal = false;

    public bool $showOpportunityModal = false;

    public function boot(CommercialWorkspaceService $commerce): void
    {
        Gate::authorize('viewCommercialWorkspace');

        $this->commerce = $commerce;
    }

    public function mount(): void
    {
        $this->normalizeActiveTab();

        $companyId = $this->defaultCompanyId();

        if ($companyId === null) {
            return;
        }

        $this->productForm->companyId = $companyId;
        $this->vendorForm->companyId = $companyId;
        $this->documentForm->companyId = $companyId;
        $this->opportunityForm->companyId = $companyId;
    }

    public function updatedDocumentFormCompanyId(): void
    {
        $this->documentForm->reset(['clientId', 'projectId', 'productId']);
    }

    public function updatedOpportunityFormCompanyId(): void
    {
        $this->opportunityForm->reset(['clientId', 'projectId']);
    }

    public function updatedVendorBillFormVendorId(): void
    {
        $this->vendorBillForm->reset('productId');
    }

    public function updatedActiveTab(): void
    {
        $this->normalizeActiveTab();
    }

    public function updatingSearch(): void
    {
        $this->resetPage('productsPage');
    }

    public function openCreateProductModal(): void
    {
        $this->productForm->resetForm();
        $this->showProductModal = true;
    }

    public function saveProduct(): void
    {
        Gate::authorize('manageCommercialWorkspace');

        $this->productForm->sellingPrice = $this->normalizeCurrency($this->productForm->sellingPrice);
        $this->productForm->costPrice = $this->normalizeCurrency($this->productForm->costPrice);
        $this->productForm->reorderPoint = $this->normalizeCurrency($this->productForm->reorderPoint);
        $validated = $this->productForm->validate();

        $data = [
            'company_id' => (int) $validated['companyId'],
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?: null,
            'status' => $validated['status'],
            'unit' => $validated['unit'],
            'selling_price' => $validated['sellingPrice'],
            'cost_price' => $validated['costPrice'],
            'stock_tracking' => true,
            'reorder_point' => $validated['reorderPoint'],
        ];

        if ($this->productForm->productId) {
            $product = Product::query()->findOrFail($this->productForm->productId);
            $this->commerce->updateProduct(auth()->user(), $product, $data);
            $message = __('Product updated successfully.');
        } else {
            $this->commerce->createProduct(auth()->user(), $data);
            $message = __('Product created successfully.');
        }

        $this->productForm->resetForm();
        $this->showProductModal = false;
        $this->banner($message);
    }

    public function editProduct(int $productId): void
    {
        Gate::authorize('manageCommercialWorkspace');

        $product = Product::query()->findOrFail($productId);

        $this->productForm->productId = $product->id;
        $this->productForm->companyId = (string) $product->company_id;
        $this->productForm->name = $product->name;
        $this->productForm->sku = (string) $product->sku;
        $this->productForm->status = $product->status;
        $this->productForm->unit = $product->unit;
        // Money is stored as a decimal string; cast to string so the x-mask Alpine input can format it.
        $this->productForm->sellingPrice = (string) (float) $product->selling_price;
        $this->productForm->costPrice = (string) (float) $product->cost_price;
        $this->productForm->reorderPoint = (string) (float) $product->reorder_point;

        $this->showProductModal = true;
    }

    public function recordStockMovement(): void
    {
        Gate::authorize('manageCommercialWorkspace');

        $this->stockMovementForm->quantity = $this->normalizeCurrency($this->stockMovementForm->quantity);
        $this->stockMovementForm->unitCost = $this->normalizeCurrency($this->stockMovementForm->unitCost);
        $validated = $this->stockMovementForm->validate();
        $product = Product::query()->findOrFail((int) $validated['productId']);

        $this->commerce->recordStockMovement(auth()->user(), $product, [
            'type' => $validated['type'],
            'quantity' => $validated['quantity'],
            'unit_cost' => $validated['unitCost'] !== '' ? $validated['unitCost'] : null,
            'notes' => $validated['notes'] ?: null,
        ]);

        $this->stockMovementForm->reset(['type', 'quantity', 'notes']);
        $this->showStockMovementModal = false;
        $this->banner(__('Stock movement recorded.'));
    }

    public function createVendor(): void
    {
        Gate::authorize('manageCommercialWorkspace');

        $validated = $this->vendorForm->validate();

        $this->commerce->createVendor(auth()->user(), [
            'company_id' => (int) $validated['companyId'],
            'name' => $validated['name'],
            'contact_name' => $validated['contactName'] ?: null,
            'email' => $validated['email'] ?: null,
            'phone' => $validated['phone'] ?: null,
        ]);

        $this->vendorForm->reset();
        $this->showVendorModal = false;
        $this->banner(__('Vendor created successfully.'));
    }

    public function createVendorBill(): void
    {
        Gate::authorize('manageCommercialWorkspace');

        $this->vendorBillForm->quantity = $this->normalizeCurrency($this->vendorBillForm->quantity);
        $this->vendorBillForm->unitCost = $this->normalizeCurrency($this->vendorBillForm->unitCost);
        $validated = $this->vendorBillForm->validate();
        $vendor = Vendor::query()->findOrFail((int) $validated['vendorId']);

        $this->commerce->createVendorBill(auth()->user(), [
            'company_id' => $vendor->company_id,
            'vendor_id' => $vendor->id,
            'due_at' => $validated['dueAt'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ], [[
            'product_id' => $validated['productId'] ?: null,
            'description' => $validated['description'],
            'quantity' => $validated['quantity'],
            'unit_cost' => $validated['unitCost'],
            'tax_rate' => $validated['taxRate'],
        ]]);

        $this->vendorBillForm->reset(['vendorId', 'productId', 'quantity']);
        $this->showVendorBillModal = false;
        $this->banner(__('Vendor bill logged.'));
    }

    public function markVendorBillPaid(int $billId): void
    {
        Gate::authorize('manageCommercialWorkspace');

        $bill = VendorBill::query()->findOrFail($billId);
        $this->commerce->markVendorBillPaid(auth()->user(), $bill);
        $this->banner(__('Vendor bill paid and posted to accounting.'));
    }

    public function createQuotation(): void
    {
        Gate::authorize('manageCommercialWorkspace');

        $this->documentForm->quantity = $this->normalizeCurrency($this->documentForm->quantity);
        $this->documentForm->unitPrice = $this->normalizeCurrency($this->documentForm->unitPrice);
        $validated = $this->documentForm->validate();

        $this->commerce->createQuotation(auth()->user(), [
            'company_id' => (int) $validated['companyId'],
            'client_id' => $validated['clientId'] ?: null,
            'project_id' => $validated['projectId'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ], [[
            'product_id' => $validated['productId'] ?: null,
            'description' => $validated['description'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unitPrice'],
            'tax_rate' => $validated['taxRate'],
        ]]);

        $this->documentForm->resetForm();
        $this->showDocumentModal = false;
        $this->banner(__('Quotation created.'));
    }

    public function createInvoice(): void
    {
        Gate::authorize('manageCommercialWorkspace');

        $this->documentForm->quantity = $this->normalizeCurrency($this->documentForm->quantity);
        $this->documentForm->unitPrice = $this->normalizeCurrency($this->documentForm->unitPrice);
        $validated = $this->documentForm->validate();

        $this->commerce->createInvoice(auth()->user(), [
            'company_id' => (int) $validated['companyId'],
            'client_id' => $validated['clientId'] ?: null,
            'project_id' => $validated['projectId'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ], [[
            'product_id' => $validated['productId'] ?: null,
            'description' => $validated['description'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unitPrice'],
            'tax_rate' => $validated['taxRate'],
        ]]);

        $this->documentForm->resetForm();
        $this->showDocumentModal = false;
        $this->banner(__('Invoice created.'));
    }

    public function markInvoicePaid(int $invoiceId): void
    {
        Gate::authorize('manageCommercialWorkspace');

        $invoice = Invoice::query()->findOrFail($invoiceId);
        $this->commerce->markInvoicePaid(auth()->user(), $invoice);
        $this->banner(__('Invoice marked as paid and posted to accounting.'));
    }

    public function convertQuotationToInvoice(int $quotationId): void
    {
        Gate::authorize('manageCommercialWorkspace');

        $quotation = Quotation::query()->findOrFail($quotationId);
        $this->commerce->convertQuotationToInvoice(auth()->user(), $quotation);

        $this->activeTab = 'invoices';
        $this->banner(__('Quotation converted to invoice.'));
    }

    private function normalizeCurrency(string $value): string
    {
        return MoneyInput::normalizeDecimal($value);
    }

    public function createOpportunity(): void
    {
        Gate::authorize('manageCommercialWorkspace');

        $validated = $this->opportunityForm->validate($this->opportunityForm->rules($this->commerce->opportunityStages()));

        $this->commerce->createOpportunity(auth()->user(), [
            'company_id' => (int) $validated['companyId'],
            'client_id' => $validated['clientId'] ?: null,
            'project_id' => $validated['projectId'] ?: null,
            'title' => $validated['title'],
            'stage' => $validated['stage'],
            'expected_value' => $validated['expectedValue'],
            'expected_close_at' => $validated['expectedCloseAt'] ?: null,
            'next_follow_up_at' => $validated['nextFollowUpAt'] ?: null,
            'source' => $validated['source'] ?: null,
            'notes' => $validated['notes'] ?: null,
            'follow_up_notes' => $validated['notes'] ?: null,
        ]);

        $this->opportunityForm->reset(['clientId', 'projectId', 'title', 'expectedValue', 'expectedCloseAt', 'source']);
        $this->showOpportunityModal = false;
        $this->banner(__('Opportunity created.'));
    }

    public function moveOpportunityStage(int $opportunityId, string $stage): void
    {
        Gate::authorize('manageCommercialWorkspace');

        $opportunity = SalesOpportunity::query()->findOrFail($opportunityId);
        $this->commerce->updateOpportunityStage(auth()->user(), $opportunity, $stage);
        $this->banner(__('Sales stage updated.'));
    }

    public function createQuotationFromOpportunity(int $opportunityId): void
    {
        Gate::authorize('manageCommercialWorkspace');

        $opportunity = SalesOpportunity::query()->findOrFail($opportunityId);
        $this->commerce->createQuotationFromOpportunity(auth()->user(), $opportunity);

        $this->activeTab = 'quotations';
        $this->banner(__('Quotation created from opportunity.'));
    }

    public function completeFollowUp(int $followUpId): void
    {
        Gate::authorize('manageCommercialWorkspace');

        $followUp = SalesFollowUp::query()->findOrFail($followUpId);
        $this->commerce->completeFollowUp(auth()->user(), $followUp);
        $this->banner(__('Follow-up marked as done.'));
    }

    public function render()
    {
        $user = auth()->user();
        $companyIds = $this->scopedCompanyIds($user);
        $companies = $this->companyOptions($companyIds);

        $paginatedProducts = Product::query()
            ->with(['company:id,name', 'stockMovements'])
            ->whereIn('company_id', $companyIds)
            ->when($this->search !== '', fn (Builder $query) => $query->where(function (Builder $nested): void {
                $nested
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('sku', 'like', '%'.$this->search.'%');
            }))
            ->latest('updated_at')
            ->paginate(12, ['*'], 'productsPage');

        $allProducts = Product::query()
            ->with(['company:id,name'])
            ->whereIn('company_id', $companyIds)
            ->orderBy('name')
            ->get();

        $clients = Client::query()
            ->whereIn('company_id', $companyIds)
            ->orderBy('name')
            ->get(['id', 'company_id', 'name']);

        $projects = Project::query()
            ->whereIn('company_id', $companyIds)
            ->orderBy('name')
            ->get(['id', 'company_id', 'client_id', 'name']);

        $quotations = Quotation::query()
            ->with(['company:id,name', 'client:id,name', 'project:id,name', 'items'])
            ->whereIn('company_id', $companyIds)
            ->latest('updated_at')
            ->get();

        $invoices = Invoice::query()
            ->with(['company:id,name', 'client:id,name', 'project:id,name', 'items'])
            ->whereIn('company_id', $companyIds)
            ->latest('updated_at')
            ->get();

        $vendors = Vendor::query()
            ->whereIn('company_id', $companyIds)
            ->when($this->search !== '', fn (Builder $query) => $query->where(function (Builder $nested): void {
                $nested
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('contact_name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('name')
            ->get();

        $vendorBills = VendorBill::query()
            ->with(['company:id,name', 'vendor:id,name', 'items.product', 'accountingJournalEntry', 'paymentJournalEntry'])
            ->whereIn('company_id', $companyIds)
            ->latest('updated_at')
            ->get();

        $opportunities = SalesOpportunity::query()
            ->with(['company:id,name', 'client:id,name', 'project:id,name', 'owner:id,name', 'followUps', 'quotations:id,sales_opportunity_id,number,status'])
            ->whereIn('company_id', $companyIds)
            ->when($this->search !== '', fn (Builder $query) => $query->where(function (Builder $nested): void {
                $nested
                    ->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('source', 'like', '%'.$this->search.'%')
                    ->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->where('name', 'like', '%'.$this->search.'%'));
            }))
            ->latest('updated_at')
            ->get();

        $documentCompanyId = $this->scopedCompanyId($companyIds, $this->documentForm->companyId);
        $opportunityCompanyId = $this->scopedCompanyId($companyIds, $this->opportunityForm->companyId);
        $billVendorCompanyId = $this->selectedVendorCompanyId();

        return view('livewire.admin.commercial-workspace', [
            'companies' => $companies,
            'paginatedProducts' => $paginatedProducts,
            'products' => $allProducts,
            'clients' => $clients,
            'projects' => $projects,
            'documentClientOptions' => $documentCompanyId === null ? $clients : $clients->where('company_id', $documentCompanyId)->values(),
            'documentProjectOptions' => $documentCompanyId === null ? $projects : $projects->where('company_id', $documentCompanyId)->values(),
            'documentProductOptions' => $documentCompanyId === null ? $allProducts : $allProducts->where('company_id', $documentCompanyId)->values(),
            'opportunityClientOptions' => $opportunityCompanyId === null ? $clients : $clients->where('company_id', $opportunityCompanyId)->values(),
            'opportunityProjectOptions' => $opportunityCompanyId === null ? $projects : $projects->where('company_id', $opportunityCompanyId)->values(),
            'billProductOptions' => $billVendorCompanyId === null ? $allProducts : $allProducts->where('company_id', $billVendorCompanyId)->values(),
            'quotations' => $quotations,
            'invoices' => $invoices,
            'vendors' => $vendors,
            'vendorBills' => $vendorBills,
            'opportunities' => $opportunities,
            'opportunityStages' => $this->commerce->opportunityStages(),
            'salesSummary' => $this->commerce->salesSummaryForCompanies($companyIds),
            'collectionSummary' => $this->commerce->collectionSummaryForCompanies($companyIds),
            'canManage' => $user->can('manageCommercialWorkspace'),
        ]);
    }

    protected function companyScopeService(): ScopesCompanies
    {
        return $this->commerce;
    }

    private function selectedVendorCompanyId(): ?int
    {
        if ($this->vendorBillForm->vendorId === '') {
            return null;
        }

        return Vendor::query()->whereKey($this->vendorBillForm->vendorId)->value('company_id');
    }
}

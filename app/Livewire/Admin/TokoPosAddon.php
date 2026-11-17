<?php

namespace App\Livewire\Admin;

use App\Models\AccountingAccount;
use App\Models\Client;
use App\Models\Company;
use App\Models\CompanyBranch;
use App\Models\DeliveryLetter;
use App\Models\ImportExportRun;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Support\CommercialWorkspaceService;
use App\Support\Helpers;
use App\Support\ImportExportRunViewService;
use App\Support\TokoLegacyImportPreviewService;
use App\Support\TokoPosCutoverArchiveService;
use App\Support\TokoPosCutoverReadinessService;
use App\Support\TokoPosDeliveryLetterService;
use App\Support\TokoPosInventoryAdjustmentService;
use App\Support\TokoPosPurchaseService;
use App\Support\TokoPosQuotationService;
use App\Support\TokoPosReportService;
use App\Support\TokoPosSalesService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TokoPosAddon extends Component
{
    use InteractsWithBanner;

    private const PAGES = [
        'dashboard' => ['label' => 'Toko Dashboard', 'route' => 'admin.toko'],
        'pos' => ['label' => 'POS', 'route' => 'admin.toko.pos'],
        'products' => ['label' => 'Products', 'route' => 'admin.toko.products'],
        'customers' => ['label' => 'Customers', 'route' => 'admin.toko.customers'],
        'vendors' => ['label' => 'Vendors', 'route' => 'admin.toko.vendors'],
        'purchases' => ['label' => 'Purchases', 'route' => 'admin.toko.purchases'],
        'inventory' => ['label' => 'Inventory', 'route' => 'admin.toko.inventory'],
        'returns' => ['label' => 'Returns', 'route' => 'admin.toko.returns'],
        'quotations' => ['label' => 'Quotations', 'route' => 'admin.toko.quotations'],
        'delivery-letters' => ['label' => 'Delivery Letters', 'route' => 'admin.toko.delivery-letters'],
        'cash' => ['label' => 'Cash', 'route' => 'admin.toko.cash'],
        'reports' => ['label' => 'Reports', 'route' => 'admin.toko.reports'],
        'migration' => ['label' => 'Migration', 'route' => 'admin.toko.migration'],
    ];

    private const SALES_SOURCES = [
        'toko_pos_counter_sale',
        'quotation_conversion',
        'legacy_toko_sale',
        'legacy_toko_retail_sale',
    ];

    private const RETAIL_SALES_SOURCES = [
        'toko_pos_counter_sale',
        'legacy_toko_retail_sale',
    ];

    private const NON_RETAIL_SALES_SOURCES = [
        'quotation_conversion',
        'legacy_toko_sale',
    ];

    private const PURCHASE_SOURCES = [
        'toko_pos_purchase',
        'legacy_toko_purchase',
    ];

    public string $page = 'dashboard';

    public string $selectedCompanyId = '';

    public string $selectedBranchId = '';

    public string $selectedDumpKey = 'toko';

    public string $selectedProductId = '';

    public string $productSearch = '';

    public string $productCatalogFilter = 'all';

    public int $productPage = 1;

    public int $productMovementPage = 1;

    public int $operationalExpenseReportPage = 1;

    public string $quickCustomerName = '';

    public string $quickCustomerPhone = '';

    public bool $showingQuickCustomerModal = false;

    public ?int $targetCompanyId = null;

    public string $productWorkspace = 'catalog';

    public string $barcodeProductId = '';

    public string $barcodePrintQuantity = '1';

    public string $selectedProductStockCardId = '';

    public string $productCategoryName = '';

    public string $productBrandName = '';

    public string $selectedClientId = '';

    public string $saleBarcode = '';

    public string $saleQuantity = '1';

    public string $salePaymentStatus = 'paid';

    public string $saleDiscountAmount = '0';

    public string $saleAdditionalCharge = '0';

    public string $saleTenderedAmount = '0';

    public string $saleTenderMethod = 'Cash';

    public string $saleTenderAmount = '0';

    public string $saleTenderBankCode = '';

    public string $saleTenderReference = '';

    public string $saleDueDays = '7';

    public string $salePaymentMethod = 'Cash';

    public string $saleBankCode = '';

    public ?array $lastCreatedInvoice = null;

    public bool $showPosBackOffice = false;

    public string $selectedPaymentInvoiceId = '';

    public string $invoicePaymentAmount = '0';

    public string $invoicePaymentMethod = '';

    public string $invoicePaymentBankCode = '';

    public string $invoicePaymentReference = '';

    public string $selectedCancelInvoiceId = '';

    public string $cancelInvoiceReason = '';

    public string $selectedSalesInvoiceDetailId = '';

    public string $salesSearch = '';

    public int $salesPage = 1;

    public string $deliveryLetterSearch = '';

    public int $deliveryLetterPage = 1;

    /**
     * @var list<array{product_id:int, name:string, quantity:float, unit_price:float, line_total:float}>
     */
    public array $saleCart = [];

    /**
     * @var list<array{method:string, amount:float, bank_code:string, reference:string}>
     */
    public array $saleTenderLines = [];

    public string $selectedPurchaseVendorId = '';

    public string $selectedPurchaseProductId = '';

    public string $purchaseQuantity = '1';

    public string $purchaseUnitCost = '0';

    public string $purchaseDueAt = '';

    public string $purchasePoNumber = '';

    public string $purchaseExtraCost = '0';

    public string $purchaseReceiverName = '';

    public string $purchaseNotes = '';

    public string $selectedVendorBillPaymentId = '';

    public string $vendorBillPaymentAmount = '';

    public string $selectedCancelVendorBillId = '';

    public string $cancelPurchaseReason = '';

    public string $selectedPurchaseBillDetailId = '';

    public string $purchaseSearch = '';

    public int $purchasePage = 1;

    /**
     * @var list<array{product_id:int, name:string, quantity:float, unit_cost:float, line_total:float}>
     */
    public array $purchaseCart = [];

    public string $selectedQuotationClientId = '';

    public string $selectedQuotationProductId = '';

    public string $quotationQuantity = '1';

    public string $quotationUnitPrice = '0';

    public string $quotationSearch = '';

    public int $quotationPage = 1;

    /**
     * @var list<array{product_id:int, name:string, quantity:float, unit_price:float, line_total:float}>
     */
    public array $quotationCart = [];

    public string $selectedAdjustmentProductId = '';

    public string $countedStockQuantity = '0';

    public string $selectedManualStockProductId = '';

    public string $manualStockType = 'in';

    public string $manualStockQuantity = '1';

    public string $manualStockReferenceNumber = '';

    public string $manualStockNotes = '';

    public string $inventoryMovementSearch = '';

    public int $inventoryMovementPage = 1;

    public string $selectedCancelStockMovementId = '';

    public string $cancelStockMovementReason = '';

    public string $selectedReturnProductId = '';

    public string $returnQuantity = '1';

    public string $returnType = 'sales';

    public string $returnReferenceNumber = '';

    public ?int $editingProductId = null;

    public string $productName = '';

    public string $productSku = '';

    public string $productBarcode = '';

    public string $productBrand = '';

    public string $productCategory = '';

    public string $productUnit = 'pcs';

    public string $productColor = '';

    public string $productSize = '';

    public string $productLocation = '';

    public string $productExpiredAt = '';

    public string $productCostPrice = '0';

    public string $productSellingPrice = '0';

    public string $productReorderPoint = '0';

    public string $productStatus = Product::STATUS_ACTIVE;

    public ?int $editingCustomerId = null;

    public string $customerCode = '';

    public string $customerName = '';

    public string $customerPhone = '';

    public string $customerEmail = '';

    public string $customerAddress = '';

    public string $customerStatus = Client::STATUS_ACTIVE;

    public string $customerSearch = '';

    public int $customerPage = 1;

    public ?int $editingVendorId = null;

    public string $vendorCode = '';

    public string $vendorName = '';

    public string $vendorPhone = '';

    public string $vendorEmail = '';

    public string $vendorAddress = '';

    public string $vendorStatus = Vendor::STATUS_ACTIVE;

    public string $vendorSearch = '';

    public int $vendorPage = 1;

    public string $selectedVendorDetailId = '';

    public string $paymentMethodName = '';

    public string $bankCode = '';

    public string $bankName = '';

    public string $bankAccountNumber = '';

    public string $bankAccountName = '';

    public string $expenseTypeName = '';

    public string $operationalExpenseType = '';

    public string $operationalExpenseAmount = '0';

    public string $operationalExpensePaymentMethod = '';

    public string $operationalExpenseBankCode = '';

    public string $operationalExpenseDescription = '';

    public string $operationalExpenseSearch = '';

    public int $operationalExpensePage = 1;

    public string $operationalExpenseFromDate = '';

    public string $operationalExpenseToDate = '';

    public ?int $editingOperationalExpenseId = null;

    public string $paymentHistorySearch = '';

    public int $paymentHistoryPage = 1;

    public string $reportFromDate = '';

    public string $reportToDate = '';

    protected TokoLegacyImportPreviewService $legacyPreview;

    protected CommercialWorkspaceService $commerce;

    protected ImportExportRunViewService $importExportRuns;

    protected TokoPosSalesService $posSales;

    protected TokoPosPurchaseService $posPurchase;

    protected TokoPosQuotationService $posQuotation;

    protected TokoPosInventoryAdjustmentService $posInventory;

    protected TokoPosReportService $posReport;

    protected TokoPosDeliveryLetterService $posDeliveryLetter;

    protected TokoPosCutoverReadinessService $cutoverReadiness;

    protected TokoPosCutoverArchiveService $cutoverArchive;

    public function boot(
        TokoLegacyImportPreviewService $legacyPreview,
        CommercialWorkspaceService $commerce,
        ImportExportRunViewService $importExportRuns,
        TokoPosCutoverArchiveService $cutoverArchive,
        TokoPosCutoverReadinessService $cutoverReadiness,
        TokoPosDeliveryLetterService $posDeliveryLetter,
        TokoPosInventoryAdjustmentService $posInventory,
        TokoPosPurchaseService $posPurchase,
        TokoPosQuotationService $posQuotation,
        TokoPosReportService $posReport,
        TokoPosSalesService $posSales,
    ): void {
        $this->legacyPreview = $legacyPreview;
        $this->commerce = $commerce;
        $this->importExportRuns = $importExportRuns;
        $this->cutoverArchive = $cutoverArchive;
        $this->cutoverReadiness = $cutoverReadiness;
        $this->posDeliveryLetter = $posDeliveryLetter;
        $this->posInventory = $posInventory;
        $this->posPurchase = $posPurchase;
        $this->posQuotation = $posQuotation;
        $this->posReport = $posReport;
        $this->posSales = $posSales;
        Gate::authorize('viewTokoPosAddon');
    }

    public function mount(string $page = 'dashboard'): void
    {
        $this->page = array_key_exists($page, self::PAGES) ? $page : 'dashboard';
        $from = request()->query('from');
        $to = request()->query('to');

        $this->reportFromDate = is_string($from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : '';
        $this->reportToDate = is_string($to) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ? $to : '';
        $this->defaultCompanyId();

        abort_if($this->page === 'migration' && ! $this->migrationEnabled(), 404);
        abort_if($this->page === 'migration' && ! (auth()->user()?->can('importTokoPosAddon') ?? false), 403);
    }

    /**
     * @return list<array{key:string, label:string, route:string, href:string, active:bool}>
     */
    public function getTokoNavigationProperty(): array
    {
        return collect(self::PAGES)
            ->reject(fn (array $item, string $key): bool => $key === 'migration' && ! $this->migrationAccessible())
            ->map(fn (array $item, string $key): array => [
                'key' => $key,
                'label' => __($item['label']),
                'route' => $item['route'],
                'href' => route($item['route']),
                'active' => $this->page === $key,
            ])
            ->values()
            ->all();
    }

    public function getPageTitleProperty(): string
    {
        return __(self::PAGES[$this->page]['label'] ?? self::PAGES['dashboard']['label']);
    }

    public function setProductWorkspace(string $workspace): void
    {
        $this->productWorkspace = in_array($workspace, ['catalog', 'create', 'barcode', 'categories', 'brands'], true)
            ? $workspace
            : 'catalog';

        if ($this->productWorkspace === 'create') {
            $this->resetCatalogProductForm();
        }
    }

    public function saveCatalogProduct(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();

        if ($targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before saving product.'));

            return;
        }

        $validated = $this->validate([
            'productName' => ['required', 'string', 'max:255'],
            'productSku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->where('company_id', $targetCompanyId)
                    ->ignore($this->editingProductId),
            ],
            'productBarcode' => ['nullable', 'string', 'max:100'],
            'productBrand' => ['nullable', 'string', 'max:100'],
            'productCategory' => ['nullable', 'string', 'max:100'],
            'productUnit' => ['required', 'string', 'max:32'],
            'productColor' => ['nullable', 'string', 'max:100'],
            'productSize' => ['nullable', 'string', 'max:100'],
            'productLocation' => ['nullable', 'string', 'max:100'],
            'productExpiredAt' => ['nullable', 'date'],
            'productCostPrice' => ['required', 'numeric', 'min:0'],
            'productSellingPrice' => ['required', 'numeric', 'min:0'],
            'productReorderPoint' => ['required', 'numeric', 'min:0'],
            'productStatus' => ['required', Rule::in([Product::STATUS_ACTIVE, Product::STATUS_INACTIVE])],
        ]);

        $product = $this->editingProductId === null
            ? new Product(['company_id' => $targetCompanyId])
            : Product::query()
                ->where('company_id', $targetCompanyId)
                ->findOrFail($this->editingProductId);

        $metadata = is_array($product->metadata) ? $product->metadata : [];
        $metadata['barcode'] = trim($validated['productBarcode'] ?? '');
        $metadata['brand'] = trim($validated['productBrand'] ?? '');
        $metadata['category'] = trim($validated['productCategory'] ?? '');
        $metadata['color'] = trim($validated['productColor'] ?? '');
        $metadata['size'] = trim($validated['productSize'] ?? '');
        $metadata['location'] = trim($validated['productLocation'] ?? '');
        $metadata['expired_at'] = filled($validated['productExpiredAt'] ?? null) ? $validated['productExpiredAt'] : null;
        $metadata['source'] = $metadata['source'] ?? 'toko_pos_catalog';

        $product->fill([
            'name' => trim($validated['productName']),
            'sku' => filled($validated['productSku'] ?? null) ? trim($validated['productSku']) : null,
            'status' => $validated['productStatus'],
            'unit' => trim($validated['productUnit']),
            'cost_price' => (float) $validated['productCostPrice'],
            'selling_price' => (float) $validated['productSellingPrice'],
            'reorder_point' => (float) $validated['productReorderPoint'],
            'stock_tracking' => true,
            'metadata' => $metadata,
        ]);
        $product->save();

        $this->resetCatalogProductForm();
        $this->banner(__('Product saved.'));
    }

    public function saveProductCategory(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $validated = $this->validate([
            'productCategoryName' => ['required', 'string', 'max:100'],
        ]);

        $name = trim($validated['productCategoryName']);
        $categories = collect($this->readTokoSetting('toko_pos.product_categories'))
            ->reject(fn (array $category): bool => strcasecmp((string) ($category['name'] ?? ''), $name) === 0)
            ->values();

        $categories->push([
            'code' => str_pad((string) ($categories->count() + 1), 4, '0', STR_PAD_LEFT),
            'name' => $name,
            'active' => true,
        ]);

        $this->storeTokoSetting('toko_pos.product_categories', $categories->values()->all(), 'Toko product categories.');
        $this->productCategoryName = '';
        $this->banner(__('Category saved.'));
    }

    public function saveProductBrand(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $validated = $this->validate([
            'productBrandName' => ['required', 'string', 'max:100'],
        ]);

        $name = trim($validated['productBrandName']);
        $brands = collect($this->readTokoSetting('toko_pos.product_brands'))
            ->reject(fn (array $brand): bool => strcasecmp((string) ($brand['name'] ?? ''), $name) === 0)
            ->values();

        $brands->push([
            'code' => str_pad((string) ($brands->count() + 1), 4, '0', STR_PAD_LEFT),
            'name' => $name,
            'active' => true,
        ]);

        $this->storeTokoSetting('toko_pos.product_brands', $brands->values()->all(), 'Toko product brands.');
        $this->productBrandName = '';
        $this->banner(__('Brand saved.'));
    }

    public function deleteProductCategory(string $name): void
    {
        Gate::authorize('manageTokoPosAddon');

        $target = trim($name);
        $categories = collect($this->readTokoSetting('toko_pos.product_categories'))
            ->reject(fn (array $category): bool => strcasecmp((string) ($category['name'] ?? ''), $target) === 0)
            ->values()
            ->all();

        $this->storeTokoSetting('toko_pos.product_categories', $categories, 'Toko product categories.');
        $this->banner(__('Category deleted.'));
    }

    public function deleteProductBrand(string $name): void
    {
        Gate::authorize('manageTokoPosAddon');

        $target = trim($name);
        $brands = collect($this->readTokoSetting('toko_pos.product_brands'))
            ->reject(fn (array $brand): bool => strcasecmp((string) ($brand['name'] ?? ''), $target) === 0)
            ->values()
            ->all();

        $this->storeTokoSetting('toko_pos.product_brands', $brands, 'Toko product brands.');
        $this->banner(__('Brand deleted.'));
    }

    public function editCatalogProduct(int $productId): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();

        if ($targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before editing product.'));

            return;
        }

        $product = Product::query()
            ->where('company_id', $targetCompanyId)
            ->findOrFail($productId);
        $metadata = is_array($product->metadata) ? $product->metadata : [];

        $this->editingProductId = $product->id;
        $this->productName = $product->name;
        $this->productSku = (string) $product->sku;
        $this->productBarcode = (string) ($metadata['barcode'] ?? '');
        $this->productBrand = (string) ($metadata['brand'] ?? '');
        $this->productCategory = (string) ($metadata['category'] ?? '');
        $this->productUnit = $product->unit ?: 'pcs';
        $this->productColor = (string) ($metadata['color'] ?? '');
        $this->productSize = (string) ($metadata['size'] ?? '');
        $this->productLocation = (string) ($metadata['location'] ?? '');
        $this->productExpiredAt = (string) ($metadata['expired_at'] ?? '');
        $this->productCostPrice = (string) (float) $product->cost_price;
        $this->productSellingPrice = (string) (float) $product->selling_price;
        $this->productReorderPoint = (string) (float) $product->reorder_point;
        $this->productStatus = $product->status;
        $this->productWorkspace = 'create';
    }

    public function resetCatalogProductForm(): void
    {
        $this->editingProductId = null;
        $this->productName = '';
        $this->productSku = '';
        $this->productBarcode = '';
        $this->productBrand = '';
        $this->productCategory = '';
        $this->productUnit = 'pcs';
        $this->productColor = '';
        $this->productSize = '';
        $this->productLocation = '';
        $this->productExpiredAt = '';
        $this->productCostPrice = '0';
        $this->productSellingPrice = '0';
        $this->productReorderPoint = '0';
        $this->productStatus = Product::STATUS_ACTIVE;
    }

    public function setProductCatalogFilter(string $filter): void
    {
        $this->productCatalogFilter = in_array($filter, ['all', 'low_stock', 'expired'], true) ? $filter : 'all';
        $this->productPage = 1;
    }

    public function updatedProductSearch(): void
    {
        $this->productPage = 1;
    }

    public function nextProductPage(): void
    {
        $this->gotoProductPage($this->productPage + 1);
    }

    public function previousProductPage(): void
    {
        $this->gotoProductPage($this->productPage - 1);
    }

    public function gotoProductPage(int $page): void
    {
        $targetCompanyId = $this->defaultCompanyId();
        $meta = $this->productTableMeta($targetCompanyId);

        $this->productPage = min(max(1, $page), $meta['pages']);
    }

    public function viewProductStockCard(int $productId): void
    {
        $targetCompanyId = $this->defaultCompanyId();

        if ($targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before opening product stock card.'));

            return;
        }

        $exists = Product::query()
            ->where('company_id', $targetCompanyId)
            ->whereKey($productId)
            ->exists();

        if (! $exists) {
            $this->dangerBanner(__('Product is not available for this company.'));

            return;
        }

        $this->selectedProductStockCardId = (string) $productId;
    }

    public function clearProductStockCard(): void
    {
        $this->selectedProductStockCardId = '';
    }

    public function updatedPurchaseSearch(): void
    {
        $this->purchasePage = 1;
    }

    public function nextPurchasePage(): void
    {
        $this->gotoPurchasePage($this->purchasePage + 1);
    }

    public function previousPurchasePage(): void
    {
        $this->gotoPurchasePage($this->purchasePage - 1);
    }

    public function gotoPurchasePage(int $page): void
    {
        $targetCompanyId = $this->defaultCompanyId();
        $meta = $this->purchaseTableMeta($targetCompanyId);

        $this->purchasePage = min(max(1, $page), $meta['pages']);
    }

    public function viewPurchaseBillDetail(int $billId): void
    {
        $targetCompanyId = $this->defaultCompanyId();

        if ($targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before opening purchase detail.'));

            return;
        }

        $exists = VendorBill::query()
            ->where('company_id', $targetCompanyId)
            ->where('metadata->source', 'toko_pos_purchase')
            ->whereKey($billId)
            ->exists();

        if (! $exists) {
            $this->dangerBanner(__('Purchase bill is not available for this company.'));

            return;
        }

        $this->selectedPurchaseBillDetailId = (string) $billId;
    }

    public function clearPurchaseBillDetail(): void
    {
        $this->selectedPurchaseBillDetailId = '';
    }

    public function deactivateCatalogProduct(int $productId): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();

        if ($targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before deactivating product.'));

            return;
        }

        Product::query()
            ->where('company_id', $targetCompanyId)
            ->whereKey($productId)
            ->update(['status' => Product::STATUS_INACTIVE]);

        $this->banner('Product deactivated.');
    }

    public function saveTokoCustomer(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();

        if ($targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before saving customer.'));

            return;
        }

        $validated = $this->validate([
            'customerCode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('clients', 'code')->where('company_id', $targetCompanyId)->ignore($this->editingCustomerId),
            ],
            'customerName' => ['required', 'string', 'max:255'],
            'customerPhone' => ['nullable', 'string', 'max:100'],
            'customerEmail' => ['nullable', 'email', 'max:255'],
            'customerAddress' => ['nullable', 'string', 'max:1000'],
            'customerStatus' => ['required', Rule::in([Client::STATUS_ACTIVE, Client::STATUS_INACTIVE])],
        ]);

        $client = $this->editingCustomerId === null
            ? new Client(['company_id' => $targetCompanyId])
            : Client::query()->where('company_id', $targetCompanyId)->findOrFail($this->editingCustomerId);
        $metadata = is_array($client->metadata) ? $client->metadata : [];
        $metadata['source'] = $metadata['source'] ?? 'toko_pos_customer';

        $client->fill([
            'name' => trim($validated['customerName']),
            'code' => filled($validated['customerCode'] ?? null) ? trim($validated['customerCode']) : null,
            'status' => $validated['customerStatus'],
            'contact_name' => trim($validated['customerName']),
            'contact_phone' => trim($validated['customerPhone'] ?? ''),
            'contact_email' => trim($validated['customerEmail'] ?? ''),
            'address' => trim($validated['customerAddress'] ?? ''),
            'metadata' => $metadata,
        ]);
        $client->save();

        $this->resetTokoCustomerForm();
        $this->banner(__('Customer saved.'));
    }

    public function editTokoCustomer(int $clientId): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();

        if ($targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before editing customer.'));

            return;
        }

        $client = Client::query()->where('company_id', $targetCompanyId)->findOrFail($clientId);
        $this->editingCustomerId = $client->id;
        $this->customerCode = (string) $client->code;
        $this->customerName = $client->name;
        $this->customerPhone = (string) $client->contact_phone;
        $this->customerEmail = (string) $client->contact_email;
        $this->customerAddress = (string) $client->address;
        $this->customerStatus = $client->status;
    }

    public function resetTokoCustomerForm(): void
    {
        $this->editingCustomerId = null;
        $this->customerCode = '';
        $this->customerName = '';
        $this->customerPhone = '';
        $this->customerEmail = '';
        $this->customerAddress = '';
        $this->customerStatus = Client::STATUS_ACTIVE;
    }

    public function deactivateTokoCustomer(int $clientId): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();

        if ($targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before deactivating customer.'));

            return;
        }

        Client::query()
            ->where('company_id', $targetCompanyId)
            ->whereKey($clientId)
            ->update(['status' => Client::STATUS_INACTIVE]);

        if ($this->editingCustomerId === $clientId) {
            $this->resetTokoCustomerForm();
        }

        $this->banner(__('Customer deactivated.'));
    }

    public function convertTokoCustomer(int $clientId): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $actor = Auth::user();

        if ($targetCompanyId === null || $actor === null) {
            $this->dangerBanner(__('Choose a target company before converting customer.'));

            return;
        }

        $client = Client::query()
            ->where('company_id', $targetCompanyId)
            ->findOrFail($clientId);
        $metadata = is_array($client->metadata) ? $client->metadata : [];

        $client->forceFill([
            'status' => Client::STATUS_ACTIVE,
            'metadata' => [
                ...$metadata,
                'membership_status' => 'berlangganan',
                'converted_to_member_at' => now()->toIso8601String(),
                'converted_to_member_by' => $actor->id,
            ],
        ])->save();

        $this->banner(__('Customer converted to Berlangganan.'));
    }

    public function updatedCustomerSearch(): void
    {
        $this->customerPage = 1;
    }

    public function nextCustomerPage(): void
    {
        $this->gotoCustomerPage($this->customerPage + 1);
    }

    public function previousCustomerPage(): void
    {
        $this->gotoCustomerPage($this->customerPage - 1);
    }

    public function gotoCustomerPage(int $page): void
    {
        $targetCompanyId = $this->defaultCompanyId();
        $meta = $this->customerTableMeta($targetCompanyId);

        $this->customerPage = min(max(1, $page), $meta['pages']);
    }

    public function saveTokoVendor(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();

        if ($targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before saving vendor.'));

            return;
        }

        $validated = $this->validate([
            'vendorCode' => ['nullable', 'string', 'max:100'],
            'vendorName' => ['required', 'string', 'max:255'],
            'vendorPhone' => ['nullable', 'string', 'max:100'],
            'vendorEmail' => ['nullable', 'email', 'max:255'],
            'vendorAddress' => ['nullable', 'string', 'max:1000'],
            'vendorStatus' => ['required', Rule::in([Vendor::STATUS_ACTIVE, Vendor::STATUS_INACTIVE])],
        ]);

        if (filled($validated['vendorCode'] ?? null)) {
            $duplicateVendor = Vendor::query()
                ->where('company_id', $targetCompanyId)
                ->where('metadata->legacy_code', trim($validated['vendorCode']))
                ->when($this->editingVendorId !== null, fn ($query) => $query->whereKeyNot($this->editingVendorId))
                ->exists();

            if ($duplicateVendor) {
                $this->addError('vendorCode', __('The vendor code has already been taken.'));

                return;
            }
        }

        $vendor = $this->editingVendorId === null
            ? new Vendor(['company_id' => $targetCompanyId])
            : Vendor::query()->where('company_id', $targetCompanyId)->findOrFail($this->editingVendorId);
        $metadata = is_array($vendor->metadata) ? $vendor->metadata : [];
        $metadata['source'] = $metadata['source'] ?? 'toko_pos_vendor';
        $metadata['legacy_code'] = filled($validated['vendorCode'] ?? null) ? trim($validated['vendorCode']) : null;

        $vendor->fill([
            'name' => trim($validated['vendorName']),
            'status' => $validated['vendorStatus'],
            'contact_name' => trim($validated['vendorName']),
            'phone' => trim($validated['vendorPhone'] ?? ''),
            'email' => trim($validated['vendorEmail'] ?? ''),
            'address' => trim($validated['vendorAddress'] ?? ''),
            'metadata' => $metadata,
        ]);
        $vendor->save();

        $this->resetTokoVendorForm();
        $this->banner(__('Vendor saved.'));
    }

    public function editTokoVendor(int $vendorId): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();

        if ($targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before editing vendor.'));

            return;
        }

        $vendor = Vendor::query()->where('company_id', $targetCompanyId)->findOrFail($vendorId);
        $metadata = is_array($vendor->metadata) ? $vendor->metadata : [];
        $this->editingVendorId = $vendor->id;
        $this->vendorCode = (string) ($metadata['legacy_code'] ?? '');
        $this->vendorName = $vendor->name;
        $this->vendorPhone = (string) $vendor->phone;
        $this->vendorEmail = (string) $vendor->email;
        $this->vendorAddress = (string) $vendor->address;
        $this->vendorStatus = $vendor->status;
    }

    public function resetTokoVendorForm(): void
    {
        $this->editingVendorId = null;
        $this->vendorCode = '';
        $this->vendorName = '';
        $this->vendorPhone = '';
        $this->vendorEmail = '';
        $this->vendorAddress = '';
        $this->vendorStatus = Vendor::STATUS_ACTIVE;
    }

    public function resetPaymentMethodForm(): void
    {
        $this->reset(['paymentMethodName']);
        $this->resetValidation();
    }

    public function resetBankAccountForm(): void
    {
        $this->reset(['bankCode', 'bankName', 'bankAccountNumber', 'bankAccountName']);
        $this->resetValidation();
    }

    public function resetExpenseTypeForm(): void
    {
        $this->reset(['expenseTypeName']);
        $this->resetValidation();
    }

    public function resetOperationalExpenseForm(): void
    {
        $this->reset(['operationalExpenseType', 'operationalExpenseAmount', 'operationalExpensePaymentMethod', 'operationalExpenseBankCode', 'operationalExpenseDescription', 'editingOperationalExpenseId']);
        $this->resetValidation();
    }

    public function deactivateTokoVendor(int $vendorId): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();

        if ($targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before deactivating vendor.'));

            return;
        }

        Vendor::query()
            ->where('company_id', $targetCompanyId)
            ->whereKey($vendorId)
            ->update(['status' => Vendor::STATUS_INACTIVE]);

        if ($this->editingVendorId === $vendorId) {
            $this->resetTokoVendorForm();
        }

        $this->banner(__('Vendor deactivated.'));
    }

    public function viewTokoVendorDetail(int $vendorId): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();

        if ($targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before viewing vendor detail.'));

            return;
        }

        Vendor::query()
            ->where('company_id', $targetCompanyId)
            ->findOrFail($vendorId);

        $this->selectedVendorDetailId = (string) $vendorId;
    }

    public function clearTokoVendorDetail(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $this->selectedVendorDetailId = '';
    }

    public function updatedVendorSearch(): void
    {
        $this->vendorPage = 1;
    }

    public function nextVendorPage(): void
    {
        $this->gotoVendorPage($this->vendorPage + 1);
    }

    public function previousVendorPage(): void
    {
        $this->gotoVendorPage($this->vendorPage - 1);
    }

    public function gotoVendorPage(int $page): void
    {
        $targetCompanyId = $this->defaultCompanyId();
        $meta = $this->vendorTableMeta($targetCompanyId);

        $this->vendorPage = min(max(1, $page), $meta['pages']);
    }

    public function savePaymentMethod(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $validated = $this->validate([
            'paymentMethodName' => ['required', 'string', 'max:100'],
        ]);

        $methods = collect($this->paymentMethods)
            ->reject(fn (array $method): bool => strcasecmp($method['name'], trim($validated['paymentMethodName'])) === 0)
            ->push(['name' => trim($validated['paymentMethodName']), 'active' => true])
            ->values()
            ->all();

        $this->storeTokoSetting('toko_pos.payment_methods', $methods, 'Toko payment methods.');
        unset($this->paymentMethods);
        $this->paymentMethodName = '';
        $this->banner(__('Payment method saved.'));
    }

    public function saveBankAccount(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $validated = $this->validate([
            'bankCode' => ['required', 'string', 'max:100'],
            'bankName' => ['required', 'string', 'max:100'],
            'bankAccountNumber' => ['required', 'string', 'max:100'],
            'bankAccountName' => ['required', 'string', 'max:255'],
        ]);

        $accounts = collect($this->bankAccounts)
            ->reject(fn (array $account): bool => strcasecmp($account['code'], trim($validated['bankCode'])) === 0)
            ->push([
                'code' => trim($validated['bankCode']),
                'bank' => trim($validated['bankName']),
                'number' => trim($validated['bankAccountNumber']),
                'name' => trim($validated['bankAccountName']),
                'active' => true,
            ])
            ->values()
            ->all();

        $this->storeTokoSetting('toko_pos.bank_accounts', $accounts, 'Toko bank accounts.');
        unset($this->bankAccounts);
        $this->bankCode = '';
        $this->bankName = '';
        $this->bankAccountNumber = '';
        $this->bankAccountName = '';
        $this->banner(__('Bank account saved.'));
    }

    public function saveExpenseType(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $validated = $this->validate([
            'expenseTypeName' => ['required', 'string', 'max:100'],
        ]);

        $types = collect($this->expenseTypes)
            ->reject(fn (array $type): bool => strcasecmp($type['name'], trim($validated['expenseTypeName'])) === 0)
            ->push(['name' => trim($validated['expenseTypeName']), 'active' => true])
            ->values()
            ->all();

        $this->storeTokoSetting('toko_pos.expense_types', $types, 'Toko operational expense types.');
        unset($this->expenseTypes);
        $this->expenseTypeName = '';
        $this->banner(__('Expense type saved.'));
    }

    public function updatedOperationalExpenseSearch(): void
    {
        $this->operationalExpensePage = 1;
    }

    public function updatedOperationalExpenseFromDate(): void
    {
        $this->operationalExpensePage = 1;
    }

    public function updatedOperationalExpenseToDate(): void
    {
        $this->operationalExpensePage = 1;
    }

    public function nextOperationalExpensePage(): void
    {
        $this->gotoOperationalExpensePage($this->operationalExpensePage + 1);
    }

    public function previousOperationalExpensePage(): void
    {
        $this->gotoOperationalExpensePage($this->operationalExpensePage - 1);
    }

    public function gotoOperationalExpensePage(int $page): void
    {
        $targetCompanyId = $this->defaultCompanyId();
        $meta = $this->operationalExpenseTableMeta($targetCompanyId);

        $this->operationalExpensePage = min(max(1, $page), $meta['pages']);
    }

    public function updatedDeliveryLetterSearch(): void
    {
        $this->deliveryLetterPage = 1;
    }

    public function nextDeliveryLetterPage(): void
    {
        $this->gotoDeliveryLetterPage($this->deliveryLetterPage + 1);
    }

    public function previousDeliveryLetterPage(): void
    {
        $this->gotoDeliveryLetterPage($this->deliveryLetterPage - 1);
    }

    public function gotoDeliveryLetterPage(int $page): void
    {
        $targetCompanyId = $this->defaultCompanyId();
        $meta = $this->deliveryLetterTableMeta($targetCompanyId);

        $this->deliveryLetterPage = min(max(1, $page), $meta['pages']);
    }

    public function updatedSalesSearch(): void
    {
        $this->salesPage = 1;
    }

    public function nextSalesPage(): void
    {
        $this->gotoSalesPage($this->salesPage + 1);
    }

    public function previousSalesPage(): void
    {
        $this->gotoSalesPage($this->salesPage - 1);
    }

    public function gotoSalesPage(int $page): void
    {
        $targetCompanyId = $this->defaultCompanyId();
        $meta = $this->salesTableMeta($targetCompanyId);

        $this->salesPage = min(max(1, $page), $meta['pages']);
    }

    public function viewSalesInvoiceDetail(int $invoiceId): void
    {
        $targetCompanyId = $this->defaultCompanyId();

        if ($targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before opening invoice detail.'));

            return;
        }

        $exists = Invoice::query()
            ->where('company_id', $targetCompanyId)
            ->where(function ($query): void {
                $query->where('metadata->source', 'toko_pos_counter_sale')
                    ->orWhere('metadata->source', 'quotation_conversion');
            })
            ->whereKey($invoiceId)
            ->exists();

        if (! $exists) {
            $this->dangerBanner(__('Invoice is not available for this Toko workspace.'));

            return;
        }

        $this->selectedSalesInvoiceDetailId = (string) $invoiceId;
    }

    public function clearSalesInvoiceDetail(): void
    {
        $this->selectedSalesInvoiceDetailId = '';
    }

    public function updatedInventoryMovementSearch(): void
    {
        $this->inventoryMovementPage = 1;
    }

    public function nextInventoryMovementPage(): void
    {
        $this->gotoInventoryMovementPage($this->inventoryMovementPage + 1);
    }

    public function previousInventoryMovementPage(): void
    {
        $this->gotoInventoryMovementPage($this->inventoryMovementPage - 1);
    }

    public function gotoInventoryMovementPage(int $page): void
    {
        $targetCompanyId = $this->defaultCompanyId();
        $meta = $this->inventoryMovementTableMeta($targetCompanyId);

        $this->inventoryMovementPage = min(max(1, $page), $meta['pages']);
    }

    public function nextProductMovementPage(): void
    {
        $this->gotoProductMovementPage($this->productMovementPage + 1);
    }

    public function previousProductMovementPage(): void
    {
        $this->gotoProductMovementPage($this->productMovementPage - 1);
    }

    public function gotoProductMovementPage(int $page): void
    {
        $targetCompanyId = $this->defaultCompanyId();
        $meta = $this->productMovementTableMeta($targetCompanyId);

        $this->productMovementPage = min(max(1, $page), $meta['pages']);
    }

    public function nextOperationalExpenseReportPage(): void
    {
        $this->gotoOperationalExpenseReportPage($this->operationalExpenseReportPage + 1);
    }

    public function previousOperationalExpenseReportPage(): void
    {
        $this->gotoOperationalExpenseReportPage($this->operationalExpenseReportPage - 1);
    }

    public function gotoOperationalExpenseReportPage(int $page): void
    {
        $targetCompanyId = $this->defaultCompanyId();
        $meta = $this->operationalExpenseReportTableMeta($targetCompanyId);

        $this->operationalExpenseReportPage = min(max(1, $page), $meta['pages']);
    }

    public function updatedPaymentHistorySearch(): void
    {
        $this->paymentHistoryPage = 1;
    }

    public function nextPaymentHistoryPage(): void
    {
        $this->gotoPaymentHistoryPage($this->paymentHistoryPage + 1);
    }

    public function previousPaymentHistoryPage(): void
    {
        $this->gotoPaymentHistoryPage($this->paymentHistoryPage - 1);
    }

    public function gotoPaymentHistoryPage(int $page): void
    {
        $targetCompanyId = $this->defaultCompanyId();
        $meta = $this->paymentHistoryTableMeta($targetCompanyId);

        $this->paymentHistoryPage = min(max(1, $page), $meta['pages']);
    }

    public function recordOperationalExpense(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $actor = Auth::user();

        if ($targetCompanyId === null || $actor === null) {
            $this->dangerBanner(__('Choose a target company before recording expense.'));

            return;
        }

        $validated = $this->validate([
            'operationalExpenseType' => ['required', 'string', 'max:100'],
            'operationalExpenseAmount' => ['required', 'numeric', 'min:0.01'],
            'operationalExpensePaymentMethod' => ['nullable', 'string', 'max:100'],
            'operationalExpenseBankCode' => ['nullable', 'string', 'max:100'],
            'operationalExpenseDescription' => ['required', 'string', 'max:1000'],
        ]);

        $amount = round((float) $validated['operationalExpenseAmount'], 2);

        DB::transaction(function () use ($actor, $targetCompanyId, $validated, $amount): void {
            $expenseAccount = $this->findOrCreateTokoAccount($targetCompanyId, '5400', 'Operating Expenses', AccountingAccount::TYPE_EXPENSE);
            $cashAccount = $this->findOrCreateTokoAccount($targetCompanyId, '1100', 'Cash / Bank', AccountingAccount::TYPE_ASSET);
            $editingId = (int) ($this->editingOperationalExpenseId ?? 0);

            if ($editingId > 0) {
                $entry = JournalEntry::query()
                    ->with('lines')
                    ->where('company_id', $targetCompanyId)
                    ->where('source_type', 'toko_pos_operational_expense')
                    ->findOrFail($editingId);
                $metadata = is_array($entry->metadata) ? $entry->metadata : [];

                $entry->forceFill([
                    'status' => JournalEntry::STATUS_POSTED,
                    'description' => trim($validated['operationalExpenseDescription']),
                    'metadata' => [
                        ...$metadata,
                        'source' => 'toko_pos_operational_expense',
                        'expense_type' => trim($validated['operationalExpenseType']),
                        'payment_method' => trim($validated['operationalExpensePaymentMethod'] ?? ''),
                        'bank_code' => trim($validated['operationalExpenseBankCode'] ?? ''),
                        'edited_at' => now()->toIso8601String(),
                        'edited_by' => $actor->id,
                    ],
                ])->save();

                $debitLine = $entry->lines->first(fn (JournalEntryLine $line): bool => (float) $line->debit > 0)
                    ?? $entry->lines->first();
                $creditLine = $entry->lines->first(fn (JournalEntryLine $line): bool => (float) $line->credit > 0)
                    ?? $entry->lines->skip(1)->first();

                if ($debitLine) {
                    $debitLine->forceFill([
                        'accounting_account_id' => $expenseAccount->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => trim($validated['operationalExpenseType']),
                    ])->save();
                }

                if ($creditLine) {
                    $creditLine->forceFill([
                        'accounting_account_id' => $cashAccount->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => trim($validated['operationalExpensePaymentMethod'] ?? ''),
                    ])->save();
                }

                return;
            }

            $entry = JournalEntry::query()->create([
                'company_id' => $targetCompanyId,
                'created_by' => $actor->id,
                'number' => $this->nextTokoJournalNumber($targetCompanyId),
                'entry_date' => now()->toDateString(),
                'status' => JournalEntry::STATUS_POSTED,
                'source_type' => 'toko_pos_operational_expense',
                'reference_number' => 'OPEX-'.now()->format('YmdHis'),
                'description' => trim($validated['operationalExpenseDescription']),
                'metadata' => [
                    'source' => 'toko_pos_operational_expense',
                    'expense_type' => trim($validated['operationalExpenseType']),
                    'payment_method' => trim($validated['operationalExpensePaymentMethod'] ?? ''),
                    'bank_code' => trim($validated['operationalExpenseBankCode'] ?? ''),
                ],
            ]);

            JournalEntryLine::query()->create([
                'journal_entry_id' => $entry->id,
                'accounting_account_id' => $expenseAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => trim($validated['operationalExpenseType']),
            ]);
            JournalEntryLine::query()->create([
                'journal_entry_id' => $entry->id,
                'accounting_account_id' => $cashAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => trim($validated['operationalExpensePaymentMethod'] ?? ''),
            ]);
        });

        $this->operationalExpenseType = '';
        $this->operationalExpenseAmount = '0';
        $this->operationalExpensePaymentMethod = '';
        $this->operationalExpenseBankCode = '';
        $this->operationalExpenseDescription = '';
        $this->editingOperationalExpenseId = null;
        $this->banner(__('Operational expense recorded.'));
    }

    public function editOperationalExpense(int $entryId): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();

        if ($targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before editing expense.'));

            return;
        }

        $entry = JournalEntry::query()
            ->with('lines')
            ->where('company_id', $targetCompanyId)
            ->where('source_type', 'toko_pos_operational_expense')
            ->findOrFail($entryId);
        $metadata = is_array($entry->metadata) ? $entry->metadata : [];

        $this->editingOperationalExpenseId = $entry->id;
        $this->operationalExpenseType = (string) ($metadata['expense_type'] ?? '');
        $this->operationalExpenseAmount = (string) round((float) $entry->lines->sum('debit'), 2);
        $this->operationalExpensePaymentMethod = (string) ($metadata['payment_method'] ?? '');
        $this->operationalExpenseBankCode = (string) ($metadata['bank_code'] ?? '');
        $this->operationalExpenseDescription = (string) $entry->description;
    }

    public function voidOperationalExpense(int $entryId): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $actor = Auth::user();

        if ($targetCompanyId === null || $actor === null) {
            $this->dangerBanner(__('Choose a target company before voiding expense.'));

            return;
        }

        DB::transaction(function () use ($actor, $targetCompanyId, $entryId): void {
            $entry = JournalEntry::query()
                ->with('lines')
                ->where('company_id', $targetCompanyId)
                ->where('source_type', 'toko_pos_operational_expense')
                ->findOrFail($entryId);
            $metadata = is_array($entry->metadata) ? $entry->metadata : [];

            $entry->forceFill([
                'status' => 'void',
                'metadata' => [
                    ...$metadata,
                    'voided_at' => now()->toIso8601String(),
                    'voided_by' => $actor->id,
                ],
            ])->save();

            foreach ($entry->lines as $line) {
                $line->forceFill([
                    'debit' => 0,
                    'credit' => 0,
                ])->save();
            }
        });

        if ($this->editingOperationalExpenseId === $entryId) {
            $this->editingOperationalExpenseId = null;
        }

        $this->banner(__('Operational expense voided.'));
    }

    public function getSummaryProperty(): array
    {
        $companyId = $this->defaultCompanyId();

        if ($companyId === null) {
            return [];
        }

        $today = now()->toDateString();
        $branchId = $this->currentBranchIdForCompany($companyId);
        $report = $this->posReport->summary($companyId, null, null, $branchId);

        $todaySales = (float) Invoice::query()
            ->where('company_id', $companyId)
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->where('metadata->source', 'toko_pos_counter_sale')
            ->whereDate('issued_at', $today)
            ->sum('grand_total');
        $todayPurchases = (float) VendorBill::query()
            ->where('company_id', $companyId)
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->where('metadata->source', 'toko_pos_purchase')
            ->whereDate('issued_at', $today)
            ->sum('grand_total');

        return [
            [
                'label' => __('Today Sales'),
                'value' => $todaySales,
                'caption' => __('Counter sales posted today.'),
            ],
            [
                'label' => __('Today Purchases'),
                'value' => $todayPurchases,
                'caption' => __('Purchase receiving posted today.'),
            ],
            [
                'label' => __('Gross Profit'),
                'value' => $report['gross_profit']['estimated'],
                'caption' => __('Estimated selling minus product cost.'),
            ],
            [
                'label' => __('AR'),
                'value' => $report['aging']['accounts_receivable'],
                'caption' => __('Outstanding customer invoices.'),
            ],
            [
                'label' => __('AP'),
                'value' => $report['aging']['accounts_payable'],
                'caption' => __('Outstanding vendor bills.'),
            ],
        ];
    }

    /**
     * @return list<array{key:string, label:string, filename:string, path:string, exists:bool, size_bytes:int|null, updated_at:string|null}>
     */
    public function getDumpSourcesProperty(): array
    {
        return $this->legacyPreview->availableDumpSources();
    }

    public function getSelectedDumpPathProperty(): string
    {
        $selected = collect($this->dumpSources)
            ->firstWhere('key', $this->selectedDumpKey);

        return $selected['path'] ?? ($this->dumpSources[0]['path'] ?? '../toko-pandan/database/toko.sql');
    }

    public function importMasterData(): void
    {
        Gate::authorize('importTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $actor = Auth::user();

        if ($targetCompanyId === null || $actor === null) {
            $this->dangerBanner(__('Choose a target company before running the import.'));

            return;
        }

        $run = $this->legacyPreview->importMasterData($actor, $this->selectedDumpPath, $targetCompanyId);

        if ($run->status === 'failed') {
            $this->dangerBanner(__('Toko master import failed: :message', ['message' => $run->error_message]));

            return;
        }

        $this->banner(__('Toko master import completed. Run #:id', ['id' => $run->id]));
    }

    public function importHistoricalDocuments(): void
    {
        Gate::authorize('importTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $actor = Auth::user();

        if ($targetCompanyId === null || $actor === null) {
            $this->dangerBanner(__('Choose a target company before running the history import.'));

            return;
        }

        $run = $this->legacyPreview->importHistoricalDocuments($actor, $this->selectedDumpPath, $targetCompanyId);

        if ($run->status === 'failed') {
            $this->dangerBanner(__('Toko history import failed: :message', ['message' => $run->error_message]));

            return;
        }

        $this->banner(__('Toko history import completed. Run #:id', ['id' => $run->id]));
    }

    public function archiveCutoverReport(): void
    {
        Gate::authorize('exportTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $actor = Auth::user();

        if ($targetCompanyId === null || $actor === null) {
            $this->dangerBanner(__('Choose a target company before archiving cutover report.'));

            return;
        }

        $run = $this->cutoverArchive->archive($actor, $this->selectedDumpPath, $targetCompanyId);

        $this->banner(__('Cutover archive created. Report #:id', ['id' => $run->id]));
    }

    public function addToSaleCart(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $productId = (int) $this->selectedProductId;
        $quantity = max(0, (float) $this->saleQuantity);

        if ($targetCompanyId === null || $productId <= 0 || $quantity <= 0) {
            $this->dangerBanner(__('Select a product and quantity before adding to cart.'));

            return;
        }

        $product = Product::query()
            ->where('company_id', $targetCompanyId)
            ->where('status', Product::STATUS_ACTIVE)
            ->find($productId);

        if (! $product) {
            $this->dangerBanner(__('Selected product is not available for this company.'));

            return;
        }

        $existingIndex = collect($this->saleCart)->search(fn (array $item): bool => (int) $item['product_id'] === $product->id);

        if ($product->stock_tracking) {
            $existingQty = $existingIndex !== false ? (float) $this->saleCart[$existingIndex]['quantity'] : 0.0;
            $stockBalance = $product->stockBalance();

            if ($stockBalance < ($existingQty + $quantity)) {
                $this->dangerBanner(__('Sisa stok tidak mencukupi untuk :product. Stok saat ini: :stock', [
                    'product' => $product->name,
                    'stock' => Helpers::formatNumberId($stockBalance, 0),
                ]));

                return;
            }
        }

        if ($existingIndex !== false) {
            $this->saleCart[$existingIndex]['quantity'] = (float) $this->saleCart[$existingIndex]['quantity'] + $quantity;
            $this->saleCart[$existingIndex]['line_total'] = round($this->saleCart[$existingIndex]['quantity'] * $this->saleCart[$existingIndex]['unit_price'], 2);
        } else {
            $unitPrice = (float) $product->selling_price;
            $this->saleCart[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => round($quantity * $unitPrice, 2),
            ];
        }

        $this->saleQuantity = '1';
        $this->selectedProductId = '';
    }

    public function createQuickCustomer(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $this->validate([
            'quickCustomerName' => 'required|string|max:255',
            'quickCustomerPhone' => 'nullable|string|max:50',
        ]);

        $targetCompanyId = $this->defaultCompanyId();

        $client = Client::query()->create([
            'company_id' => $targetCompanyId,
            'name' => $this->quickCustomerName,
            'contact_phone' => $this->quickCustomerPhone,
            'status' => Client::STATUS_ACTIVE,
            'metadata' => ['source' => 'toko_pos_quick_add'],
        ]);

        $this->selectedClientId = (string) $client->id;
        $this->quickCustomerName = '';
        $this->quickCustomerPhone = '';
        $this->showingQuickCustomerModal = false;
        $this->dispatch('close-modal', 'quick-customer-modal');
        $this->successBanner(__('Pelanggan berhasil ditambahkan.'));
    }

    public function addScannedSaleBarcode(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $barcode = trim($this->saleBarcode);

        if ($targetCompanyId === null || $barcode === '') {
            $this->dangerBanner(__('Scan or enter a barcode before adding to cart.'));

            return;
        }

        $product = Product::query()
            ->where('company_id', $targetCompanyId)
            ->where('status', Product::STATUS_ACTIVE)
            ->where(function ($query) use ($barcode): void {
                $query->where('sku', $barcode)
                    ->orWhere('metadata->barcode', $barcode);
            })
            ->first();

        if (! $product) {
            $this->dangerBanner(__('No active product matches barcode :barcode.', ['barcode' => $barcode]));

            return;
        }

        $this->selectedProductId = (string) $product->id;
        $this->addToSaleCart();
        $this->saleBarcode = '';
    }

    public function removeSaleCartItem(int $index): void
    {
        Gate::authorize('manageTokoPosAddon');

        unset($this->saleCart[$index]);
        $this->saleCart = array_values($this->saleCart);
    }

    public function setSalePaymentMode(string $mode): void
    {
        if ($mode === 'unpaid') {
            $this->salePaymentStatus = 'unpaid';
            $this->salePaymentMethod = 'Tempo';
            $this->saleTenderedAmount = '0';
            $this->saleTenderLines = [];

            return;
        }

        $this->salePaymentStatus = 'paid';
        $this->salePaymentMethod = match ($mode) {
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'debit' => 'Debit/Credit Card',
            'split' => 'Split Tender',
            default => 'Cash',
        };
    }

    public function addSaleTenderLine(): void
    {
        Gate::authorize('manageTokoPosAddon');

        if ($this->salePaymentStatus !== 'paid') {
            $this->dangerBanner(__('Split tender is only available for paid counter sales.'));

            return;
        }

        $this->validate([
            'saleTenderMethod' => ['required', 'string', 'max:100'],
            'saleTenderAmount' => ['required', 'numeric', 'min:0.01'],
            'saleTenderBankCode' => ['nullable', 'string', 'max:100'],
            'saleTenderReference' => ['nullable', 'string', 'max:100'],
        ]);

        $this->saleTenderLines[] = [
            'method' => trim($this->saleTenderMethod),
            'amount' => round((float) $this->saleTenderAmount, 2),
            'bank_code' => trim($this->saleTenderBankCode),
            'reference' => trim($this->saleTenderReference),
        ];

        $this->salePaymentMethod = 'Split Tender';
        $this->saleTenderedAmount = (string) $this->saleTenderTotal();
        $this->saleTenderAmount = '0';
        $this->saleTenderBankCode = '';
        $this->saleTenderReference = '';
    }

    public function removeSaleTenderLine(int $index): void
    {
        Gate::authorize('manageTokoPosAddon');

        unset($this->saleTenderLines[$index]);
        $this->saleTenderLines = array_values($this->saleTenderLines);
        $this->saleTenderedAmount = (string) $this->saleTenderTotal();
    }

    public function createCounterSale(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);
        $actor = Auth::user();

        if ($targetCompanyId === null || $actor === null || $this->saleCart === []) {
            $this->dangerBanner(__('Add at least one item before creating the counter sale.'));

            return;
        }

        $this->validate([
            'saleDiscountAmount' => ['required', 'numeric', 'min:0'],
            'saleAdditionalCharge' => ['required', 'numeric', 'min:0'],
            'saleDueDays' => ['required', 'integer', 'min:0', 'max:365'],
            'salePaymentMethod' => ['nullable', 'string', 'max:100'],
            'saleBankCode' => ['nullable', 'string', 'max:100'],
        ]);

        $payableTotal = $this->salePayableTotal();
        $saleTenderTotal = $this->saleTenderTotal();

        if ($this->salePaymentStatus === 'paid' && $this->saleTenderLines !== [] && $saleTenderTotal < $payableTotal) {
            $this->dangerBanner(__('Tender total is lower than total payment.'));

            return;
        }

        if ($this->salePaymentStatus === 'paid'
            && $this->saleTenderLines === []
            && str($this->salePaymentMethod)->lower()->contains('cash')
            && (float) $this->saleTenderedAmount < $payableTotal) {
            $this->dangerBanner(__('Cash tender is lower than total payment.'));

            return;
        }

        $invoice = $this->posSales->createCounterSale($actor, [
            'company_id' => $targetCompanyId,
            'branch_id' => $targetBranchId,
            'client_id' => $this->selectedClientId !== '' ? (int) $this->selectedClientId : null,
            'payment_status' => $this->salePaymentStatus,
            'discount_amount' => $this->saleDiscountAmount,
            'additional_charge' => $this->saleAdditionalCharge,
            'due_days' => $this->saleDueDays,
            'payment_method' => $this->saleTenderLines !== [] ? 'Split Tender' : $this->salePaymentMethod,
            'bank_code' => $this->saleBankCode,
            'payments' => $this->saleTenderLines,
            'items' => $this->saleCart,
        ]);

        $this->saleCart = [];
        $this->selectedClientId = '';
        $this->salePaymentStatus = 'paid';
        $this->saleDiscountAmount = '0';
        $this->saleAdditionalCharge = '0';
        $this->saleTenderedAmount = '0';
        $this->saleTenderMethod = 'Cash';
        $this->saleTenderAmount = '0';
        $this->saleTenderBankCode = '';
        $this->saleTenderReference = '';
        $this->saleTenderLines = [];
        $this->saleDueDays = '7';
        $this->salePaymentMethod = 'Cash';
        $this->saleBankCode = '';

        $this->lastCreatedInvoice = [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'total' => $invoice->grand_total,
            'print_url' => route('admin.toko.invoices.pdf', $invoice, false),
            'thermal_print_url' => route('admin.toko.invoices.thermal', $invoice, false),
        ];

        $this->banner(__('Counter sale created. Nota #:number', ['number' => $invoice->number]));
    }

    public function recordInvoicePayment(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);
        $actor = Auth::user();
        $invoiceId = (int) $this->selectedPaymentInvoiceId;

        if ($targetCompanyId === null || $actor === null || $invoiceId <= 0) {
            $this->dangerBanner(__('Select an invoice before recording payment.'));

            return;
        }

        $this->validate([
            'invoicePaymentAmount' => ['required', 'numeric', 'min:0.01'],
            'invoicePaymentMethod' => ['nullable', 'string', 'max:100'],
            'invoicePaymentBankCode' => ['nullable', 'string', 'max:100'],
            'invoicePaymentReference' => ['nullable', 'string', 'max:100'],
        ]);

        $invoice = Invoice::query()
            ->where('company_id', $targetCompanyId)
            ->when($targetBranchId !== null, fn ($query) => $query->where('branch_id', $targetBranchId))
            ->where(function ($query): void {
                $query->where('metadata->source', 'toko_pos_counter_sale')
                    ->orWhere('metadata->source', 'quotation_conversion');
            })
            ->findOrFail($invoiceId);

        $invoice = $this->posSales->recordInvoicePayment($actor, $invoice, [
            'amount' => $this->invoicePaymentAmount,
            'method' => $this->invoicePaymentMethod,
            'bank_code' => $this->invoicePaymentBankCode,
            'reference' => $this->invoicePaymentReference,
        ]);

        $this->selectedPaymentInvoiceId = '';
        $this->invoicePaymentAmount = '0';
        $this->invoicePaymentMethod = '';
        $this->invoicePaymentBankCode = '';
        $this->invoicePaymentReference = '';

        $this->banner(__('Invoice payment recorded. Invoice #:number', ['number' => $invoice->number]));
    }

    public function cancelCounterSale(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);
        $actor = Auth::user();
        $invoiceId = (int) $this->selectedCancelInvoiceId;

        if ($targetCompanyId === null || $actor === null || $invoiceId <= 0) {
            $this->dangerBanner(__('Select a counter sale before cancelling it.'));

            return;
        }

        $this->validate([
            'cancelInvoiceReason' => ['required', 'string', 'max:1000'],
        ]);

        $invoice = Invoice::query()
            ->where('company_id', $targetCompanyId)
            ->when($targetBranchId !== null, fn ($query) => $query->where('branch_id', $targetBranchId))
            ->where('metadata->source', 'toko_pos_counter_sale')
            ->whereIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_SENT, Invoice::STATUS_PAID])
            ->findOrFail($invoiceId);

        $invoice = $this->posSales->cancelCounterSale($actor, $invoice, $this->cancelInvoiceReason);

        $this->selectedCancelInvoiceId = '';
        $this->cancelInvoiceReason = '';

        $this->banner(__('Counter sale cancelled. Invoice #:number', ['number' => $invoice->number]));
    }

    public function addToPurchaseCart(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $productId = (int) $this->selectedPurchaseProductId;
        $quantity = max(0, (float) $this->purchaseQuantity);

        if ($targetCompanyId === null || $productId <= 0 || $quantity <= 0) {
            $this->dangerBanner(__('Select a purchase product and quantity before adding to cart.'));

            return;
        }

        $product = Product::query()
            ->where('company_id', $targetCompanyId)
            ->where('status', Product::STATUS_ACTIVE)
            ->find($productId);

        if (! $product) {
            $this->dangerBanner(__('Selected purchase product is not available for this company.'));

            return;
        }

        $unitCost = max(0, (float) $this->purchaseUnitCost);
        if ($unitCost <= 0) {
            $unitCost = (float) $product->cost_price;
        }

        $this->purchaseCart[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'line_total' => round($quantity * $unitCost, 2),
        ];

        $this->selectedPurchaseProductId = '';
        $this->purchaseQuantity = '1';
        $this->purchaseUnitCost = '0';
    }

    public function removePurchaseCartItem(int $index): void
    {
        Gate::authorize('manageTokoPosAddon');

        unset($this->purchaseCart[$index]);
        $this->purchaseCart = array_values($this->purchaseCart);
    }

    public function createPurchase(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);
        $actor = Auth::user();
        $vendorId = (int) $this->selectedPurchaseVendorId;

        if ($targetCompanyId === null || $actor === null || $vendorId <= 0 || $this->purchaseCart === []) {
            $this->dangerBanner(__('Select a vendor and add at least one item before creating the purchase.'));

            return;
        }

        $validated = $this->validate([
            'purchaseDueAt' => ['nullable', 'date'],
            'purchasePoNumber' => ['nullable', 'string', 'max:100'],
            'purchaseExtraCost' => ['nullable', 'numeric', 'min:0'],
            'purchaseReceiverName' => ['nullable', 'string', 'max:120'],
            'purchaseNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $bill = $this->posPurchase->createPurchase($actor, [
            'company_id' => $targetCompanyId,
            'branch_id' => $targetBranchId,
            'vendor_id' => $vendorId,
            'items' => $this->purchaseCart,
            'due_at' => filled($validated['purchaseDueAt'] ?? null) ? $validated['purchaseDueAt'] : null,
            'po_number' => trim((string) ($validated['purchasePoNumber'] ?? '')),
            'extra_cost' => (float) ($validated['purchaseExtraCost'] ?? 0),
            'receiver_name' => trim((string) ($validated['purchaseReceiverName'] ?? '')),
            'notes' => trim((string) ($validated['purchaseNotes'] ?? '')),
        ]);

        $this->purchaseCart = [];
        $this->selectedPurchaseVendorId = '';
        $this->purchaseDueAt = '';
        $this->purchasePoNumber = '';
        $this->purchaseExtraCost = '0';
        $this->purchaseReceiverName = '';
        $this->purchaseNotes = '';

        $this->banner(__('Purchase created. Bill #:number', ['number' => $bill->number]));
    }

    public function payVendorBill(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $validated = $this->validate([
            'vendorBillPaymentAmount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);
        $actor = Auth::user();
        $billId = (int) $this->selectedVendorBillPaymentId;

        if ($targetCompanyId === null || $actor === null || $billId <= 0) {
            $this->dangerBanner(__('Select a vendor bill before recording payment.'));

            return;
        }

        $bill = VendorBill::query()
            ->where('company_id', $targetCompanyId)
            ->when($targetBranchId !== null, fn ($query) => $query->where('branch_id', $targetBranchId))
            ->where('metadata->source', 'toko_pos_purchase')
            ->where('status', VendorBill::STATUS_POSTED)
            ->findOrFail($billId);

        $paymentAmount = (float) ($validated['vendorBillPaymentAmount'] ?? 0);
        $bill = $this->posPurchase->recordVendorBillPayment($actor, $bill, $paymentAmount > 0 ? $paymentAmount : null);
        $this->selectedVendorBillPaymentId = '';
        $this->vendorBillPaymentAmount = '';

        $this->banner(__('Vendor bill payment recorded. Bill #:number', ['number' => $bill->number]));
    }

    public function cancelPurchase(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);
        $actor = Auth::user();
        $billId = (int) $this->selectedCancelVendorBillId;

        if ($targetCompanyId === null || $actor === null || $billId <= 0) {
            $this->dangerBanner(__('Select a purchase before cancelling it.'));

            return;
        }

        $this->validate([
            'cancelPurchaseReason' => ['required', 'string', 'max:1000'],
        ]);

        $bill = VendorBill::query()
            ->where('company_id', $targetCompanyId)
            ->when($targetBranchId !== null, fn ($query) => $query->where('branch_id', $targetBranchId))
            ->where('metadata->source', 'toko_pos_purchase')
            ->whereIn('status', [VendorBill::STATUS_POSTED, VendorBill::STATUS_PAID])
            ->findOrFail($billId);

        $bill = $this->posPurchase->cancelPurchase($actor, $bill, $this->cancelPurchaseReason);

        $this->selectedCancelVendorBillId = '';
        $this->cancelPurchaseReason = '';

        $this->banner(__('Purchase cancelled. Bill #:number', ['number' => $bill->number]));
    }

    public function addToQuotationCart(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $productId = (int) $this->selectedQuotationProductId;
        $quantity = max(0, (float) $this->quotationQuantity);

        if ($targetCompanyId === null || $productId <= 0 || $quantity <= 0) {
            $this->dangerBanner(__('Select a quotation product and quantity before adding to cart.'));

            return;
        }

        $product = Product::query()
            ->where('company_id', $targetCompanyId)
            ->where('status', Product::STATUS_ACTIVE)
            ->find($productId);

        if (! $product) {
            $this->dangerBanner(__('Selected quotation product is not available for this company.'));

            return;
        }

        $unitPrice = max(0, (float) $this->quotationUnitPrice);
        if ($unitPrice <= 0) {
            $unitPrice = (float) $product->selling_price;
        }

        $this->quotationCart[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => round($quantity * $unitPrice, 2),
        ];

        $this->selectedQuotationProductId = '';
        $this->quotationQuantity = '1';
        $this->quotationUnitPrice = '0';
    }

    public function removeQuotationCartItem(int $index): void
    {
        Gate::authorize('manageTokoPosAddon');

        unset($this->quotationCart[$index]);
        $this->quotationCart = array_values($this->quotationCart);
    }

    public function createQuotation(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);
        $actor = Auth::user();

        if ($targetCompanyId === null || $actor === null || $this->quotationCart === []) {
            $this->dangerBanner(__('Add at least one item before creating the quotation.'));

            return;
        }

        $quotation = $this->posQuotation->createQuotation($actor, [
            'company_id' => $targetCompanyId,
            'branch_id' => $targetBranchId,
            'client_id' => $this->selectedQuotationClientId !== '' ? (int) $this->selectedQuotationClientId : null,
            'items' => $this->quotationCart,
        ]);

        $this->quotationCart = [];
        $this->selectedQuotationClientId = '';

        $this->banner(__('Quotation created. Quotation #:number', ['number' => $quotation->number]));
    }

    public function convertQuotationToInvoice(int $quotationId): void
    {
        Gate::authorize('manageTokoPosAddon');

        $actor = Auth::user();
        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);

        if ($actor === null || $targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before converting quotation.'));

            return;
        }

        $quotation = Quotation::query()
            ->where('company_id', $targetCompanyId)
            ->when($targetBranchId !== null, fn ($query) => $query->where('branch_id', $targetBranchId))
            ->where('metadata->source', 'toko_pos_quotation')
            ->find($quotationId);

        if (! $quotation) {
            $this->dangerBanner(__('Quotation is not available for this add-on.'));

            return;
        }

        if ($quotation->status === Quotation::STATUS_REJECTED) {
            $this->dangerBanner(__('Rejected quotation cannot be converted.'));

            return;
        }

        $invoice = $this->posQuotation->convertToInvoice($actor, $quotation);

        $this->banner(__('Quotation converted. Invoice #:number', ['number' => $invoice->number]));
    }

    public function updatedQuotationSearch(): void
    {
        $this->quotationPage = 1;
    }

    public function nextQuotationPage(): void
    {
        $this->gotoQuotationPage($this->quotationPage + 1);
    }

    public function previousQuotationPage(): void
    {
        $this->gotoQuotationPage($this->quotationPage - 1);
    }

    public function gotoQuotationPage(int $page): void
    {
        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);
        $meta = $this->quotationTableMeta($targetCompanyId);

        $this->quotationPage = min(max(1, $page), $meta['pages']);
    }

    public function markQuotationAccepted(int $quotationId): void
    {
        Gate::authorize('manageTokoPosAddon');

        $actor = Auth::user();
        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);

        if ($actor === null || $targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before updating quotation.'));

            return;
        }

        $quotation = Quotation::query()
            ->where('company_id', $targetCompanyId)
            ->when($targetBranchId !== null, fn ($query) => $query->where('branch_id', $targetBranchId))
            ->where('metadata->source', 'toko_pos_quotation')
            ->find($quotationId);

        if (! $quotation) {
            $this->dangerBanner(__('Quotation is not available for this add-on.'));

            return;
        }

        $this->posQuotation->accept($actor, $quotation);

        $this->banner(__('Quotation finalized as accepted.'));
    }

    public function markQuotationRejected(int $quotationId): void
    {
        Gate::authorize('manageTokoPosAddon');

        $actor = Auth::user();
        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);

        if ($actor === null || $targetCompanyId === null) {
            $this->dangerBanner(__('Choose a target company before updating quotation.'));

            return;
        }

        $quotation = Quotation::query()
            ->where('company_id', $targetCompanyId)
            ->when($targetBranchId !== null, fn ($query) => $query->where('branch_id', $targetBranchId))
            ->where('metadata->source', 'toko_pos_quotation')
            ->find($quotationId);

        if (! $quotation) {
            $this->dangerBanner(__('Quotation is not available for this add-on.'));

            return;
        }

        $this->posQuotation->reject($actor, $quotation);

        $this->banner(__('Quotation rejected.'));
    }

    public function recordStockOpname(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);
        $actor = Auth::user();
        $productId = (int) $this->selectedAdjustmentProductId;

        if ($targetCompanyId === null || $actor === null || $productId <= 0) {
            $this->dangerBanner(__('Select a product before recording stock opname.'));

            return;
        }

        $movement = $this->posInventory->recordStockOpname($actor, [
            'company_id' => $targetCompanyId,
            'branch_id' => $targetBranchId,
            'product_id' => $productId,
            'counted_quantity' => $this->countedStockQuantity,
        ]);

        $this->selectedAdjustmentProductId = '';
        $this->countedStockQuantity = '0';

        $this->banner(__('Stock opname recorded. Movement #:id', ['id' => $movement->id]));
    }

    public function recordManualStockMovement(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);
        $actor = Auth::user();
        $productId = (int) $this->selectedManualStockProductId;

        if ($targetCompanyId === null || $actor === null || $productId <= 0) {
            $this->dangerBanner(__('Select a product before recording manual stock movement.'));

            return;
        }

        $this->validate([
            'manualStockType' => ['required', Rule::in(['in', 'out'])],
            'manualStockQuantity' => ['required', 'numeric', 'min:0.001'],
            'manualStockReferenceNumber' => ['nullable', 'string', 'max:100'],
            'manualStockNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'company_id' => $targetCompanyId,
            'branch_id' => $targetBranchId,
            'product_id' => $productId,
            'quantity' => $this->manualStockQuantity,
            'reference_number' => $this->manualStockReferenceNumber,
            'notes' => $this->manualStockNotes,
        ];

        $movement = $this->manualStockType === 'in'
            ? $this->posInventory->recordManualStockIn($actor, $payload)
            : $this->posInventory->recordManualStockOut($actor, $payload);

        $this->selectedManualStockProductId = '';
        $this->manualStockType = 'in';
        $this->manualStockQuantity = '1';
        $this->manualStockReferenceNumber = '';
        $this->manualStockNotes = '';

        $this->banner(__('Manual stock movement recorded. Movement #:id', ['id' => $movement->id]));
    }

    public function cancelStockMovement(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $actor = Auth::user();
        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);
        $movementId = (int) $this->selectedCancelStockMovementId;

        if ($actor === null || $targetCompanyId === null || $movementId <= 0) {
            $this->dangerBanner(__('Select a stock movement before cancellation.'));

            return;
        }

        $this->validate([
            'selectedCancelStockMovementId' => ['required', 'integer'],
            'cancelStockMovementReason' => ['nullable', 'string', 'max:1000'],
        ]);

        $movement = StockMovement::query()
            ->with('product')
            ->where('company_id', $targetCompanyId)
            ->when($targetBranchId !== null, fn ($query) => $query->where('branch_id', $targetBranchId))
            ->find($movementId);

        if (! $movement) {
            $this->dangerBanner(__('Stock movement is not available for this add-on.'));

            return;
        }

        $reversal = $this->posInventory->cancelMovement($actor, $movement, $this->cancelStockMovementReason);

        $this->selectedCancelStockMovementId = '';
        $this->cancelStockMovementReason = '';

        $this->banner(__('Stock movement canceled. Reversal #:id', ['id' => $reversal->id]));
    }

    public function recordInventoryReturn(): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);
        $actor = Auth::user();
        $productId = (int) $this->selectedReturnProductId;

        if ($targetCompanyId === null || $actor === null || $productId <= 0) {
            $this->dangerBanner(__('Select a product before recording return.'));

            return;
        }

        $payload = [
            'company_id' => $targetCompanyId,
            'branch_id' => $targetBranchId,
            'product_id' => $productId,
            'quantity' => $this->returnQuantity,
            'reference_number' => $this->returnReferenceNumber !== '' ? $this->returnReferenceNumber : null,
        ];
        $movement = $this->returnType === 'purchase'
            ? $this->posInventory->recordPurchaseReturn($actor, $payload)
            : $this->posInventory->recordSalesReturn($actor, $payload);

        $this->selectedReturnProductId = '';
        $this->returnQuantity = '1';
        $this->returnReferenceNumber = '';

        $this->banner(__('Return recorded. Movement #:id', ['id' => $movement->id]));
    }

    public function createDeliveryLetterFromInvoice(int $invoiceId): void
    {
        Gate::authorize('manageTokoPosAddon');

        $targetCompanyId = $this->defaultCompanyId();
        $actor = Auth::user();

        if ($targetCompanyId === null || $actor === null) {
            $this->dangerBanner(__('Choose a target company before creating delivery letter.'));

            return;
        }

        $invoice = Invoice::query()
            ->where('company_id', $targetCompanyId)
            ->where(function ($query): void {
                $query->where('metadata->source', 'toko_pos_counter_sale')
                    ->orWhere('metadata->source', 'quotation_conversion');
            })
            ->find($invoiceId);

        if (! $invoice) {
            $this->dangerBanner(__('Invoice is not available for Toko delivery letter.'));

            return;
        }

        $letter = $this->posDeliveryLetter->createFromInvoice($actor, $invoice);

        $this->banner(__('Delivery letter created. Letter #:number', ['number' => $letter->number]));
    }

    public function render(): View
    {
        $targetCompanyId = $this->defaultCompanyId();
        $targetBranchId = $targetCompanyId === null ? null : $this->currentBranchIdForCompany($targetCompanyId);
        $companyOptions = $this->companyOptions();
        $branchOptions = $this->branchOptions($targetCompanyId);
        $tokoReport = $targetCompanyId === null ? null : $this->posReport->summary($targetCompanyId, $this->reportFromDate ?: null, $this->reportToDate ?: null, $targetBranchId);
        $saleCartTotal = round(collect($this->saleCart)->sum('line_total'), 2);
        $salePayableTotal = $this->salePayableTotal();
        $saleTenderTotal = $this->saleTenderLines !== []
            ? $this->saleTenderTotal()
            : round((float) $this->saleTenderedAmount, 2);

        $baseData = [
            'activePage' => $this->page,
            'pageTitle' => $this->pageTitle,
            'companyOptions' => $companyOptions,
            'branchOptions' => $branchOptions,
            'tokoNavigation' => $this->tokoNavigation,
            'targetCompany' => $targetCompanyId === null
                ? null
                : Company::query()->find($targetCompanyId, ['id', 'name']),
            'targetBranch' => $targetBranchId === null
                ? null
                : CompanyBranch::query()->find($targetBranchId, ['id', 'name']),
            'canManage' => auth()->user()?->can('manageTokoPosAddon') ?? false,
            'canImport' => auth()->user()?->can('importTokoPosAddon') ?? false,
            'canExport' => auth()->user()?->can('exportTokoPosAddon') ?? false,
        ];

        if ($this->page === 'pos') {
            $posBackOfficeData = $this->showPosBackOffice
                ? [
                    'paymentInvoiceOptions' => $this->paymentInvoiceOptions($targetCompanyId),
                    'cancelInvoiceOptions' => $this->cancelInvoiceOptions($targetCompanyId),
                    'recentPosInvoices' => $this->recentPosInvoices($targetCompanyId),
                    'salesInvoiceRows' => $this->salesInvoiceRows($targetCompanyId),
                    'salesTableMeta' => $this->salesTableMeta($targetCompanyId),
                    'salesInvoiceDetail' => $this->salesInvoiceDetail($targetCompanyId),
                ]
                : [
                    'paymentInvoiceOptions' => [],
                    'cancelInvoiceOptions' => [],
                    'recentPosInvoices' => [],
                    'salesInvoiceRows' => [],
                    'salesTableMeta' => ['page' => 1, 'pages' => 1, 'total' => 0, 'start' => 0, 'end' => 0],
                    'salesInvoiceDetail' => null,
                ];

            return view('livewire.admin.toko-pos-addon', array_merge($baseData, $posBackOfficeData, [
                'productOptions' => $this->productOptions($targetCompanyId),
                'clientOptions' => $this->clientOptions($targetCompanyId),
                'saleCartTotal' => $saleCartTotal,
                'saleProductPreview' => $this->saleProductPreview($targetCompanyId),
                'salePayableTotal' => $salePayableTotal,
                'saleTenderedAmount' => $this->saleTenderedAmount,
                'saleTenderTotal' => $saleTenderTotal,
                'saleChangeDue' => max(0, round($saleTenderTotal - $salePayableTotal, 2)),
                'nextSaleDraftNumber' => $targetCompanyId === null
                    ? '-'
                    : $this->nextSaleDraftNumber($targetCompanyId),
            ]));
        }

        return view('livewire.admin.toko-pos-addon', [
            'summary' => $this->summary,
            'dumpSources' => $this->dumpSources,
            'legacyPreview' => $this->legacyPreview->preview($this->selectedDumpPath, $targetCompanyId),
            'activePage' => $this->page,
            'pageTitle' => $this->pageTitle,
            'companyOptions' => $companyOptions,
            'branchOptions' => $branchOptions,
            'tokoNavigation' => $this->tokoNavigation,
            'paymentMethods' => $this->paymentMethods,
            'bankAccounts' => $this->bankAccounts,
            'expenseTypes' => $this->expenseTypes,
            'recentOperationalExpenses' => $this->recentOperationalExpenses($targetCompanyId),
            'operationalExpenseRows' => $this->operationalExpenseRows($targetCompanyId),
            'operationalExpenseTableMeta' => $this->operationalExpenseTableMeta($targetCompanyId),
            'operationalExpenseReportRows' => $this->operationalExpenseReportRows($targetCompanyId),
            'operationalExpenseReportTableMeta' => $this->operationalExpenseReportTableMeta($targetCompanyId),
            'operationalExpenseExportQuery' => array_filter([
                'from' => $this->operationalExpenseFromDate,
                'to' => $this->operationalExpenseToDate,
            ], fn (string $value): bool => $value !== ''),
            'paymentHistoryRows' => $this->paymentHistoryRows($targetCompanyId),
            'paymentHistoryTableMeta' => $this->paymentHistoryTableMeta($targetCompanyId),
            'inventoryMovementRows' => $this->inventoryMovementRows($targetCompanyId),
            'inventoryMovementTableMeta' => $this->inventoryMovementTableMeta($targetCompanyId),
            'stockAdjustmentReportRows' => $this->stockAdjustmentReportRows($targetCompanyId),
            'productMovementReportRows' => $this->productMovementReportRows($targetCompanyId),
            'productMovementTableMeta' => $this->productMovementTableMeta($targetCompanyId),
            'targetCompany' => $targetCompanyId === null
                ? null
                : Company::query()->find($targetCompanyId, ['id', 'name']),
            'targetBranch' => $targetBranchId === null
                ? null
                : CompanyBranch::query()->find($targetBranchId, ['id', 'name']),
            'productOptions' => $this->productOptions($targetCompanyId),
            'productCatalogSummary' => $this->productCatalogSummary($targetCompanyId),
            'productRows' => $this->productRows($targetCompanyId),
            'productTableMeta' => $this->productTableMeta($targetCompanyId),
            'productWorkspaceTabs' => $this->productWorkspaceTabs(),
            'barcodeProductPreview' => $this->barcodeProductPreview($targetCompanyId),
            'productStockCardDetail' => $this->productStockCardDetail($targetCompanyId),
            'productCategoryRows' => $this->productTaxonomyRows($targetCompanyId, 'category'),
            'productBrandRows' => $this->productTaxonomyRows($targetCompanyId, 'brand'),
            'clientOptions' => $this->clientOptions($targetCompanyId),
            'customerRows' => $this->customerRows($targetCompanyId),
            'customerTableMeta' => $this->customerTableMeta($targetCompanyId),
            'customerIncomeRows' => $this->customerIncomeRows($targetCompanyId),
            'vendorOptions' => $this->vendorOptions($targetCompanyId),
            'vendorRows' => $this->vendorRows($targetCompanyId),
            'vendorTableMeta' => $this->vendorTableMeta($targetCompanyId),
            'vendorApDetail' => $this->vendorApDetail($targetCompanyId),
            'vendorBillPaymentOptions' => $this->vendorBillPaymentOptions($targetCompanyId),
            'cancelVendorBillOptions' => $this->cancelVendorBillOptions($targetCompanyId),
            'purchaseBillRows' => $this->purchaseBillRows($targetCompanyId),
            'purchaseTableMeta' => $this->purchaseTableMeta($targetCompanyId),
            'purchaseBillDetail' => $this->purchaseBillDetail($targetCompanyId),
            'purchaseApAging' => $this->purchaseApAging($targetCompanyId),
            'vendorPaymentHistoryRows' => $this->vendorPaymentHistoryRows($targetCompanyId),
            'cancelStockMovementOptions' => $this->cancelStockMovementOptions($targetCompanyId),
            'recentPosInvoices' => $this->recentPosInvoices($targetCompanyId),
            'deliveryLetterRows' => $this->deliveryLetterRows($targetCompanyId),
            'deliveryLetterTableMeta' => $this->deliveryLetterTableMeta($targetCompanyId),
            'salesInvoiceRows' => $this->salesInvoiceRows($targetCompanyId),
            'salesTableMeta' => $this->salesTableMeta($targetCompanyId),
            'salesInvoiceDetail' => $this->salesInvoiceDetail($targetCompanyId),
            'paymentInvoiceOptions' => $this->paymentInvoiceOptions($targetCompanyId),
            'cancelInvoiceOptions' => $this->cancelInvoiceOptions($targetCompanyId),
            'recentTokoQuotations' => $this->quotationRows($targetCompanyId),
            'quotationRows' => $this->quotationRows($targetCompanyId),
            'quotationTableMeta' => $this->quotationTableMeta($targetCompanyId),
            'tokoReport' => $tokoReport,
            'reportPeriod' => [
                'from' => $this->reportFromDate,
                'to' => $this->reportToDate,
                'active' => $this->reportFromDate !== '' || $this->reportToDate !== '',
            ],
            'reportExportQuery' => array_filter([
                'from' => $this->reportFromDate,
                'to' => $this->reportToDate,
            ], fn (string $value): bool => $value !== ''),
            'dashboardOverview' => $this->dashboardOverview($targetCompanyId, $tokoReport),
            'cutoverReadiness' => $targetCompanyId === null ? null : $this->cutoverReadiness->snapshot($this->selectedDumpPath, $targetCompanyId),
            'latestHistoricalReconciliation' => $this->latestHistoricalReconciliation($targetCompanyId),
            'latestMonthlyHistoricalReconciliation' => $this->latestMonthlyHistoricalReconciliation($targetCompanyId),
            'latestCashBankHistoricalReconciliation' => $this->latestCashBankHistoricalReconciliation($targetCompanyId),
            'saleCartTotal' => $saleCartTotal,
            'purchaseCartTotal' => round(collect($this->purchaseCart)->sum('line_total'), 2),
            'quotationCartTotal' => round(collect($this->quotationCart)->sum('line_total'), 2),
            'canManage' => auth()->user()?->can('manageTokoPosAddon') ?? false,
            'canImport' => auth()->user()?->can('importTokoPosAddon') ?? false,
            'canExport' => auth()->user()?->can('exportTokoPosAddon') ?? false,
            'recentRuns' => $this->importExportRuns->recentForResources(['toko_pos_master', 'toko_pos_history', 'toko_pos_cutover_archive'], null, 8),
            'saleProductPreview' => $this->saleProductPreview($targetCompanyId),
            'salePayableTotal' => $salePayableTotal,
            'saleTenderedAmount' => $this->saleTenderedAmount,
            'saleTenderTotal' => $saleTenderTotal,
            'saleChangeDue' => max(0, round($saleTenderTotal - $salePayableTotal, 2)),
            'nextSaleDraftNumber' => $targetCompanyId === null
                ? '-'
                : $this->nextSaleDraftNumber($targetCompanyId),
        ]);
    }

    private function defaultCompanyId(): ?int
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $companyQuery = $this->commerce->scopeCompanies(Company::query(), $user);
        $selectedCompanyId = (int) $this->selectedCompanyId;

        if ($selectedCompanyId > 0 && (clone $companyQuery)->whereKey($selectedCompanyId)->exists()) {
            return $selectedCompanyId;
        }

        $companyId = (clone $companyQuery)->orderBy('name')->value('id');

        if ($companyId !== null) {
            $this->selectedCompanyId = (string) $companyId;
        }

        return $companyId === null ? null : (int) $companyId;
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    private function companyOptions(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        return $this->commerce
            ->scopeCompanies(Company::query(), $user)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Company $company): array => [
                'id' => (int) $company->id,
                'name' => $company->name,
            ])
            ->values()
            ->all();
    }

    public function updatedSelectedCompanyId(): void
    {
        $this->selectedBranchId = '';
        $this->resetTablePages();
    }

    public function updatedSelectedBranchId(): void
    {
        $this->resetTablePages();
    }

    private function resetTablePages(): void
    {
        $this->productPage = 1;
        $this->customerPage = 1;
        $this->vendorPage = 1;
        $this->purchasePage = 1;
        $this->inventoryMovementPage = 1;
        $this->operationalExpensePage = 1;
        $this->paymentHistoryPage = 1;
        $this->deliveryLetterPage = 1;
        $this->salesPage = 1;
        $this->quotationPage = 1;
    }

    /**
     * @return list<array{id:int,name:string,code:string|null,label:string}>
     */
    private function branchOptions(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return CompanyBranch::query()
            ->where('company_id', $companyId)
            ->where('status', CompanyBranch::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (CompanyBranch $branch): array => [
                'id' => (int) $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'label' => trim($branch->name.($branch->code ? ' · '.$branch->code : '')),
            ])
            ->values()
            ->all();
    }

    private function currentBranchIdForCompany(int $companyId): ?int
    {
        $branchId = (int) $this->selectedBranchId;

        if ($branchId <= 0) {
            return null;
        }

        $exists = CompanyBranch::query()
            ->where('company_id', $companyId)
            ->where('status', CompanyBranch::STATUS_ACTIVE)
            ->whereKey($branchId)
            ->exists();

        if (! $exists) {
            $this->selectedBranchId = '';

            return null;
        }

        return $branchId;
    }

    private function withSelectedBranch($query, int $companyId, string $column = 'branch_id')
    {
        $branchId = $this->currentBranchIdForCompany($companyId);

        return $branchId === null ? $query : $query->where($column, $branchId);
    }

    /**
     * @return list<array{key:string,label:string,legacy_count:int,target_count:int,count_gap:int,legacy_total:float|null,target_total:float|null,total_gap:float|null,matched:bool}>
     */
    private function latestHistoricalReconciliation(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        $run = ImportExportRun::query()
            ->where('resource', 'toko_pos_history')
            ->where('status', 'completed')
            ->where('meta->company_id', $companyId)
            ->latest('id')
            ->first();
        $reconciliation = $run?->meta['reconciliation'] ?? [];

        if (! is_array($reconciliation) || $reconciliation === []) {
            return [];
        }

        return collect($reconciliation)
            ->map(fn (array $bucket, string $key): array => [
                'key' => $key,
                'label' => str($key)->replace('_', ' ')->headline()->toString(),
                'legacy_count' => (int) ($bucket['legacy_count'] ?? 0),
                'target_count' => (int) ($bucket['target_count'] ?? 0),
                'count_gap' => (int) ($bucket['count_gap'] ?? 0),
                'legacy_total' => array_key_exists('legacy_total', $bucket) && $bucket['legacy_total'] !== null ? (float) $bucket['legacy_total'] : null,
                'target_total' => array_key_exists('target_total', $bucket) && $bucket['target_total'] !== null ? (float) $bucket['target_total'] : null,
                'total_gap' => array_key_exists('total_gap', $bucket) && $bucket['total_gap'] !== null ? (float) $bucket['total_gap'] : null,
                'matched' => (bool) ($bucket['matched'] ?? false),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{month:string,legacy:array{sales:float,purchases:float,operational_expenses:float,net_income:float},target:array{sales:float,purchases:float,operational_expenses:float,net_income:float},gaps:array{sales:float,purchases:float,operational_expenses:float,net_income:float},matched:bool}>
     */
    private function latestMonthlyHistoricalReconciliation(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        $run = ImportExportRun::query()
            ->where('resource', 'toko_pos_history')
            ->where('status', 'completed')
            ->where('meta->company_id', $companyId)
            ->latest('id')
            ->first();
        $monthly = $run?->meta['monthly_reconciliation'] ?? [];

        if (! is_array($monthly) || $monthly === []) {
            return [];
        }

        return collect($monthly)
            ->map(fn (array $bucket, string $month): array => [
                'month' => $month,
                'legacy' => $this->normalizeMonthlyReconciliationValues($bucket['legacy'] ?? []),
                'target' => $this->normalizeMonthlyReconciliationValues($bucket['target'] ?? []),
                'gaps' => $this->normalizeMonthlyReconciliationValues($bucket['gaps'] ?? []),
                'matched' => (bool) ($bucket['matched'] ?? false),
            ])
            ->sortBy('month')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{sales:float,purchases:float,operational_expenses:float,net_income:float}
     */
    private function normalizeMonthlyReconciliationValues(array $values): array
    {
        return [
            'sales' => (float) ($values['sales'] ?? 0),
            'purchases' => (float) ($values['purchases'] ?? 0),
            'operational_expenses' => (float) ($values['operational_expenses'] ?? 0),
            'net_income' => (float) ($values['net_income'] ?? 0),
        ];
    }

    /**
     * @return list<array{group:string,bucket:string,legacy_total:float,target_total:float,gap:float,matched:bool}>
     */
    private function latestCashBankHistoricalReconciliation(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        $run = ImportExportRun::query()
            ->where('resource', 'toko_pos_history')
            ->where('status', 'completed')
            ->where('meta->company_id', $companyId)
            ->latest('id')
            ->first();
        $cashBank = $run?->meta['cash_bank_reconciliation'] ?? [];

        if (! is_array($cashBank) || $cashBank === []) {
            return [];
        }

        $rows = [];
        foreach ($cashBank as $group => $buckets) {
            if (! is_array($buckets)) {
                continue;
            }

            foreach ($buckets as $bucket => $values) {
                if (! is_array($values)) {
                    continue;
                }

                $rows[] = [
                    'group' => str((string) $group)->replace('_', ' ')->headline()->toString(),
                    'bucket' => (string) $bucket,
                    'legacy_total' => (float) ($values['legacy_total'] ?? 0),
                    'target_total' => (float) ($values['target_total'] ?? 0),
                    'gap' => (float) ($values['gap'] ?? 0),
                    'matched' => (bool) ($values['matched'] ?? false),
                ];
            }
        }

        return $rows;
    }

    /**
     * @return list<array{name:string, active:bool}>
     */
    public function getPaymentMethodsProperty(): array
    {
        return collect($this->readTokoSetting('toko_pos.payment_methods'))
            ->filter(fn ($method): bool => is_array($method) && filled($method['name'] ?? null))
            ->map(fn (array $method): array => [
                'name' => (string) $method['name'],
                'active' => (bool) ($method['active'] ?? true),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{code:string, bank:string, number:string, name:string, active:bool}>
     */
    public function getBankAccountsProperty(): array
    {
        return collect($this->readTokoSetting('toko_pos.bank_accounts'))
            ->filter(fn ($account): bool => is_array($account) && filled($account['code'] ?? null))
            ->map(fn (array $account): array => [
                'code' => (string) $account['code'],
                'bank' => (string) ($account['bank'] ?? ''),
                'number' => (string) ($account['number'] ?? ''),
                'name' => (string) ($account['name'] ?? ''),
                'active' => (bool) ($account['active'] ?? true),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{name:string, active:bool}>
     */
    public function getExpenseTypesProperty(): array
    {
        return collect($this->readTokoSetting('toko_pos.expense_types'))
            ->filter(fn ($type): bool => is_array($type) && filled($type['name'] ?? null))
            ->map(fn (array $type): array => [
                'name' => (string) $type['name'],
                'active' => (bool) ($type['active'] ?? true),
            ])
            ->values()
            ->all();
    }

    private function readTokoSetting(string $key): array
    {
        $decoded = json_decode((string) Setting::getValue($key, '[]'), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function storeTokoSetting(string $key, array $value, string $description): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => json_encode($value, JSON_THROW_ON_ERROR),
                'group' => 'toko_pos',
                'type' => 'json',
                'description' => $description,
            ],
        );
        Setting::flushCache($key);
    }

    private function migrationEnabled(): bool
    {
        return strtolower((string) Setting::getValue('toko_pos.migration_enabled', 'true')) !== 'false';
    }

    private function migrationAccessible(): bool
    {
        return $this->migrationEnabled()
            && (auth()->user()?->can('importTokoPosAddon') ?? false);
    }

    private function findOrCreateTokoAccount(int $companyId, string $code, string $name, string $type): AccountingAccount
    {
        return AccountingAccount::query()->firstOrCreate([
            'company_id' => $companyId,
            'code' => $code,
        ], [
            'name' => $name,
            'type' => $type,
            'normal_balance' => in_array($type, [AccountingAccount::TYPE_ASSET, AccountingAccount::TYPE_EXPENSE], true)
                ? AccountingAccount::BALANCE_DEBIT
                : AccountingAccount::BALANCE_CREDIT,
            'is_active' => true,
            'metadata' => ['source' => 'toko_pos_default_account'],
        ]);
    }

    private function nextTokoJournalNumber(int $companyId): string
    {
        return 'TJ-'.now()->format('Ymd').'-'.str_pad((string) (JournalEntry::query()->where('company_id', $companyId)->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    private function nextSaleDraftNumber(int $companyId): string
    {
        $prefix = 'NOTA-'.now()->format('Ymd').'-';

        return $prefix.str_pad((string) (Invoice::query()
            ->where('company_id', $companyId)
            ->where('number', 'like', $prefix.'%')
            ->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return list<array{id:int, name:string, label:string}>
     */
    private function productOptions(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return Product::query()
            ->with('stockMovements')
            ->where('company_id', $companyId)
            ->where('status', Product::STATUS_ACTIVE)
            ->orderBy('name')
            ->limit(60)
            ->get(['id', 'name', 'sku', 'selling_price', 'metadata', 'stock_tracking'])
            ->map(function (Product $product): array {
                $metadata = is_array($product->metadata) ? $product->metadata : [];

                $stockText = '';
                if ($product->stock_tracking) {
                    $stock = $product->stockBalance();
                    if ($stock <= 0) {
                        $stockText = ' · (Stok Habis)';
                    } else {
                        $stockText = ' · (Sisa: '.Helpers::formatNumberId($stock, 0).')';
                    }
                }

                $label = trim($product->name.' · '.$product->sku.' · '.($metadata['barcode'] ?? '').' · Rp'.Helpers::formatNumberId((float) $product->selling_price).$stockText, ' ·');

                return [
                    'id' => $product->id,
                    'name' => $label,
                    'label' => $label,
                ];
            })
            ->all();
    }

    /**
     * @return list<array{key:string,label:string,caption:string}>
     */
    private function productWorkspaceTabs(): array
    {
        return [
            ['key' => 'catalog', 'label' => 'Data Barang', 'caption' => 'SKU, harga, stok, dan atribut toko.'],
            ['key' => 'create', 'label' => 'Tambah Barang', 'caption' => 'Form standard dan advanced.'],
            ['key' => 'barcode', 'label' => 'Barcode', 'caption' => 'Cetak label dari katalog aktif.'],
            ['key' => 'categories', 'label' => 'Kategori', 'caption' => 'Master kategori dan pemakaian.'],
            ['key' => 'brands', 'label' => 'Brand', 'caption' => 'Master merek dan pemakaian.'],
        ];
    }

    /**
     * @return array{id:int,name:string,sku:string,barcode:string,quantity:int,print_url:string}|null
     */
    private function barcodeProductPreview(?int $companyId): ?array
    {
        $productId = (int) $this->barcodeProductId;

        if ($companyId === null || $productId <= 0) {
            return null;
        }

        $product = Product::query()
            ->where('company_id', $companyId)
            ->where('status', Product::STATUS_ACTIVE)
            ->find($productId);

        if (! $product) {
            return null;
        }

        $metadata = is_array($product->metadata) ? $product->metadata : [];
        $quantity = min(15, max(1, (int) $this->barcodePrintQuantity));

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => (string) ($product->sku ?? ''),
            'barcode' => (string) ($metadata['barcode'] ?? $product->sku ?? ''),
            'quantity' => $quantity,
            'print_url' => route('admin.toko.products.barcodes', [
                'products' => array_fill(0, $quantity, $product->id),
            ], false),
        ];
    }

    /**
     * @return list<array{code:string,name:string,products_count:int,source:string}>
     */
    private function productTaxonomyRows(?int $companyId, string $type): array
    {
        if ($companyId === null) {
            return [];
        }

        $metadataKey = $type === 'brand' ? 'brand' : 'category';
        $settingKey = $type === 'brand' ? 'toko_pos.product_brands' : 'toko_pos.product_categories';

        $observed = Product::query()
            ->where('company_id', $companyId)
            ->get(['id', 'metadata'])
            ->map(fn (Product $product): string => trim((string) ((is_array($product->metadata) ? $product->metadata : [])[$metadataKey] ?? '')))
            ->filter()
            ->countBy();

        $rows = collect($this->readTokoSetting($settingKey))
            ->filter(fn ($row): bool => is_array($row) && filled($row['name'] ?? null))
            ->map(fn (array $row, int $index): array => [
                'code' => (string) ($row['code'] ?? str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)),
                'name' => (string) $row['name'],
                'products_count' => (int) ($observed[(string) $row['name']] ?? 0),
                'source' => 'Master',
            ]);

        $observedRows = $observed
            ->reject(fn (int $count, string $name): bool => $rows->contains(fn (array $row): bool => strcasecmp($row['name'], $name) === 0))
            ->map(fn (int $count, string $name): array => [
                'name' => $name,
                'products_count' => $count,
                'source' => 'Catalog',
            ])
            ->values()
            ->map(fn (array $row, int $index): array => [
                'code' => str_pad((string) ($rows->count() + $index + 1), 4, '0', STR_PAD_LEFT),
                ...$row,
            ]);

        return $rows
            ->concat($observedRows)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * @return array{name:string, sku:string, stock:float, unit_price:float, quantity:float, line_total:float}|null
     */
    private function saleProductPreview(?int $companyId): ?array
    {
        $productId = (int) $this->selectedProductId;

        if ($companyId === null || $productId <= 0) {
            return null;
        }

        $product = Product::query()
            ->where('company_id', $companyId)
            ->where('status', Product::STATUS_ACTIVE)
            ->find($productId);

        if (! $product) {
            return null;
        }

        $quantity = max(0, (float) $this->saleQuantity);
        $unitPrice = (float) $product->selling_price;

        return [
            'name' => $product->name,
            'sku' => (string) ($product->sku ?? ''),
            'stock' => $product->stockBalance(),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => round($quantity * $unitPrice, 2),
        ];
    }

    private function salePayableTotal(): float
    {
        $cartTotal = round((float) collect($this->saleCart)->sum('line_total'), 2);
        $discount = max(0, (float) $this->saleDiscountAmount);
        $charge = max(0, (float) $this->saleAdditionalCharge);

        return max(0, round($cartTotal - $discount + $charge, 2));
    }

    private function saleTenderTotal(): float
    {
        return round((float) collect($this->saleTenderLines)->sum(fn (array $line): float => (float) ($line['amount'] ?? 0)), 2);
    }

    /**
     * @param  array<string, mixed>|null  $report
     * @return array{
     *     kpis:list<array{label:string,value:int|float,caption:string}>,
     *     stock_kpis:list<array{label:string,value:int|float,caption:string}>,
     *     top_stock:list<array{name:string,balance:float,percent:float}>,
     *     top_outgoing:list<array{name:string,quantity:float,percent:float}>,
     *     aging:list<array{label:string,ar:float,ap:float}>,
     *     summary:list<array{label:string,current_month:float,last_month:float,current_year:float}>,
     *     revenue_mix:array{labels:list<string>,values:list<float>},
     *     expense_mix:array{labels:list<string>,values:list<float>},
     *     profit_kpis:list<array{label:string,value:float,caption:string,format:string}>,
     *     monthly_net_trend:list<array{month:string,income:float,cost:float,net:float,report_url:string}>
     * }
     */
    private function dashboardOverview(?int $companyId, ?array $report): array
    {
        if ($companyId === null) {
            return [
                'kpis' => [],
                'stock_kpis' => [],
                'top_stock' => [],
                'top_outgoing' => [],
                'aging' => [],
                'summary' => [],
                'revenue_mix' => ['labels' => [], 'values' => []],
                'expense_mix' => ['labels' => [], 'values' => []],
                'profit_kpis' => [],
                'monthly_net_trend' => [],
            ];
        }

        $products = Product::query()
            ->with('stockMovements')
            ->where('company_id', $companyId)
            ->where('status', Product::STATUS_ACTIVE)
            ->where('stock_tracking', true)
            ->get(['id', 'name', 'sku', 'reorder_point', 'stock_tracking', 'cost_price', 'selling_price', 'metadata']);
        $dashboardProducts = $this->legacyDashboardProducts($products);
        $stockAvailable = round((float) $dashboardProducts->sum(fn (Product $product): float => $this->legacyStockBalance($product)), 3);
        $stockInQuantity = round((float) $dashboardProducts->sum(fn (Product $product): float => $this->legacyNumber($product, 'terbeli', 0.0)), 3);
        $stockOutQuantity = round((float) $dashboardProducts->sum(fn (Product $product): float => $this->legacyNumber($product, 'terjual', 0.0)), 3);
        if ($stockInQuantity <= 0.0 && $stockOutQuantity <= 0.0) {
            $stockMovements = StockMovement::query()
                ->where('company_id', $companyId)
                ->get(['type', 'quantity', 'metadata']);
            $stockInQuantity = round((float) $stockMovements
                ->filter(fn (StockMovement $movement): bool => ($movement->metadata['affects_stock'] ?? true) !== false)
                ->where('type', StockMovement::TYPE_IN)
                ->sum('quantity'), 3);
            $stockOutQuantity = round((float) $stockMovements
                ->filter(fn (StockMovement $movement): bool => ($movement->metadata['affects_stock'] ?? true) !== false)
                ->where('type', StockMovement::TYPE_OUT)
                ->sum('quantity'), 3);
        }
        $maxStock = max(1, (float) $dashboardProducts->max(fn (Product $product): float => $this->legacyStockBalance($product)));
        $topStock = $dashboardProducts
            ->map(fn (Product $product): array => [
                'name' => $product->name,
                'balance' => $this->legacyStockBalance($product),
                'percent' => min(100, max(6, ($this->legacyStockBalance($product) / $maxStock) * 100)),
            ])
            ->sortByDesc('balance')
            ->take(5)
            ->values()
            ->all();

        $legacyTopOutgoing = $dashboardProducts
            ->map(fn (Product $product): array => [
                'name' => $product->name,
                'quantity' => $this->legacyNumber($product, 'terjual', 0.0),
            ])
            ->filter(fn (array $row): bool => (float) $row['quantity'] > 0)
            ->sortByDesc('quantity')
            ->take(5)
            ->values();
        $legacyMaxOutgoing = max(1, (float) $legacyTopOutgoing->max('quantity'));
        $topOutgoing = $legacyTopOutgoing->isNotEmpty()
            ? $legacyTopOutgoing
                ->map(fn (array $row): array => [
                    'name' => (string) $row['name'],
                    'quantity' => (float) $row['quantity'],
                    'percent' => min(100, max(6, ((float) $row['quantity'] / $legacyMaxOutgoing) * 100)),
                ])
                ->values()
                ->all()
            : collect($report['sales']['by_product'] ?? [])
                ->take(5)
                ->map(function (array $row) use ($report): array {
                    $maxQuantity = max(1, ...array_map(fn ($item): float => (float) ($item['quantity'] ?? 0), $report['sales']['by_product'] ?? [['quantity' => 0]]));

                    return [
                        'name' => (string) ($row['product'] ?? '-'),
                        'quantity' => (float) ($row['quantity'] ?? 0),
                        'percent' => min(100, max(6, ((float) ($row['quantity'] ?? 0) / $maxQuantity) * 100)),
                    ];
                })
                ->values()
                ->all();

        $now = now();
        $lastMonth = now()->subMonth();
        $retailSalesCurrentMonth = $this->salesTotalForPeriod($companyId, $now->copy()->startOfMonth(), $now->copy()->endOfMonth(), self::RETAIL_SALES_SOURCES);
        $retailSalesLastMonth = $this->salesTotalForPeriod($companyId, $lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth(), self::RETAIL_SALES_SOURCES);
        $retailSalesCurrentYear = $this->salesTotalForPeriod($companyId, $now->copy()->startOfYear(), $now->copy()->endOfYear(), self::RETAIL_SALES_SOURCES);
        $nonRetailSalesCurrentMonth = $this->salesTotalForPeriod($companyId, $now->copy()->startOfMonth(), $now->copy()->endOfMonth(), self::NON_RETAIL_SALES_SOURCES);
        $nonRetailSalesLastMonth = $this->salesTotalForPeriod($companyId, $lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth(), self::NON_RETAIL_SALES_SOURCES);
        $nonRetailSalesCurrentYear = $this->salesTotalForPeriod($companyId, $now->copy()->startOfYear(), $now->copy()->endOfYear(), self::NON_RETAIL_SALES_SOURCES);
        $expensesCurrentMonth = $this->operationalExpenseTotalForPeriod($companyId, $now->copy()->startOfMonth(), $now->copy()->endOfMonth());
        $expensesLastMonth = $this->operationalExpenseTotalForPeriod($companyId, $lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth());
        $expensesCurrentYear = $this->operationalExpenseTotalForPeriod($companyId, $now->copy()->startOfYear(), $now->copy()->endOfYear());
        $incomeCurrentMonth = $retailSalesCurrentMonth + $nonRetailSalesCurrentMonth;
        $incomeLastMonth = $retailSalesLastMonth + $nonRetailSalesLastMonth;
        $incomeCurrentYear = $retailSalesCurrentYear + $nonRetailSalesCurrentYear;
        $netIncomeCurrentYear = $incomeCurrentYear - $expensesCurrentYear;
        $grossProfit = (float) ($report['gross_profit']['estimated'] ?? 0);
        $grossMargin = $incomeCurrentYear > 0 ? ($grossProfit / $incomeCurrentYear) * 100 : 0.0;

        return [
            'kpis' => [
                ['label' => 'Karyawan HRIS', 'value' => $this->legacyEmployeeCount($companyId), 'caption' => 'Data orang dari HRIS; biaya gaji masuk finance Toko.'],
                ['label' => 'Supplier', 'value' => Vendor::query()->where('company_id', $companyId)->count(), 'caption' => 'Vendor aktif untuk pembelian.'],
                ['label' => 'Barang', 'value' => $dashboardProducts->count(), 'caption' => 'SKU katalog toko aktif.'],
                ['label' => 'Barang Stok Minimum', 'value' => count($report['low_stock'] ?? []), 'caption' => 'Butuh perhatian restock.'],
            ],
            'stock_kpis' => [
                ['label' => 'Stok Tersedia', 'value' => $stockAvailable, 'caption' => 'Total saldo stok aktif saat ini.'],
                ['label' => 'Barang Keluar', 'value' => $stockOutQuantity, 'caption' => 'Akumulasi mutasi stok keluar.'],
                ['label' => 'Barang Masuk', 'value' => $stockInQuantity, 'caption' => 'Akumulasi mutasi stok masuk.'],
                ['label' => 'Total Estimasi Modal', 'value' => (float) ($report['stock_valuation']['cost'] ?? 0), 'caption' => 'Nilai modal dari saldo stok.'],
                ['label' => 'Total Estimasi Pemasukan', 'value' => (float) ($report['stock_valuation']['revenue'] ?? 0), 'caption' => 'Nilai jual estimasi dari stok.'],
                ['label' => 'Total Estimasi Laba', 'value' => (float) ($report['stock_valuation']['profit'] ?? 0), 'caption' => 'Estimasi margin stok berjalan.'],
                ['label' => 'Total Omzet', 'value' => (float) ($report['stock_valuation']['sold_revenue'] ?? 0), 'caption' => 'Omzet barang yang sudah terjual.'],
            ],
            'top_stock' => $topStock,
            'top_outgoing' => $topOutgoing,
            'aging' => [
                ['label' => 'Segera Jatuh Tempo', 'ar' => $this->invoiceAgingTotal($companyId, -9999, 0), 'ap' => $this->vendorBillAgingTotal($companyId, -9999, 0)],
                ['label' => 'Jatuh Tempo <30 hari', 'ar' => $this->invoiceAgingTotal($companyId, 1, 30), 'ap' => $this->vendorBillAgingTotal($companyId, 1, 30)],
                ['label' => 'Jatuh Tempo 30-60 hari', 'ar' => $this->invoiceAgingTotal($companyId, 31, 60), 'ap' => $this->vendorBillAgingTotal($companyId, 31, 60)],
                ['label' => 'Jatuh Tempo 60-90 hari', 'ar' => $this->invoiceAgingTotal($companyId, 61, 90), 'ap' => $this->vendorBillAgingTotal($companyId, 61, 90)],
                ['label' => 'Jatuh Tempo >90 hari', 'ar' => $this->invoiceAgingTotal($companyId, 91, 9999), 'ap' => $this->vendorBillAgingTotal($companyId, 91, 9999)],
            ],
            'summary' => [
                ['label' => 'Pendapatan Retail', 'current_month' => $retailSalesCurrentMonth, 'last_month' => $retailSalesLastMonth, 'current_year' => $retailSalesCurrentYear],
                ['label' => 'Pendapatan Non Retail', 'current_month' => $nonRetailSalesCurrentMonth, 'last_month' => $nonRetailSalesLastMonth, 'current_year' => $nonRetailSalesCurrentYear],
                ['label' => 'Biaya Operasional', 'current_month' => $expensesCurrentMonth, 'last_month' => $expensesLastMonth, 'current_year' => $expensesCurrentYear],
                ['label' => 'Net Income', 'current_month' => $incomeCurrentMonth - $expensesCurrentMonth, 'last_month' => $incomeLastMonth - $expensesLastMonth, 'current_year' => $netIncomeCurrentYear],
            ],
            'revenue_mix' => [
                'labels' => ['penjualan retail', 'Invoice', 'Belum dibayar'],
                'values' => [$retailSalesCurrentYear, $nonRetailSalesCurrentYear, (float) ($report['aging']['accounts_receivable'] ?? 0)],
            ],
            'expense_mix' => $this->expenseMix($companyId),
            'profit_kpis' => [
                ['label' => 'Pendapatan Tahun Ini', 'value' => $incomeCurrentYear, 'caption' => 'Retail + invoice/non-retail.', 'format' => 'money'],
                ['label' => 'Biaya Operasional', 'value' => $expensesCurrentYear, 'caption' => 'Total biaya tahun berjalan.', 'format' => 'money'],
                ['label' => 'Laba Bersih', 'value' => $netIncomeCurrentYear, 'caption' => 'Pendapatan dikurangi biaya operasional.', 'format' => 'money'],
                ['label' => 'Margin Kotor', 'value' => $grossMargin, 'caption' => 'Gross profit dibanding pendapatan.', 'format' => 'percent'],
            ],
            'monthly_net_trend' => $this->monthlyNetTrend($companyId),
        ];
    }

    private function legacyDashboardProducts($products)
    {
        $legacyProducts = $products
            ->filter(function (Product $product): bool {
                $metadata = is_array($product->metadata) ? $product->metadata : [];

                return ($metadata['source'] ?? null) === 'legacy_toko_import'
                    && is_array($metadata['legacy_toko'] ?? null);
            })
            ->values();

        return $legacyProducts->isNotEmpty() ? $legacyProducts : $products;
    }

    private function legacyStockBalance(Product $product): float
    {
        return $this->legacyNumber($product, 'sisa', $product->stockBalance());
    }

    private function legacyNumber(Product $product, string $key, float $fallback): float
    {
        $metadata = is_array($product->metadata) ? $product->metadata : [];
        $legacy = is_array($metadata['legacy_toko'] ?? null) ? $metadata['legacy_toko'] : [];

        if (array_key_exists($key, $legacy) && is_numeric($legacy[$key])) {
            return (float) $legacy[$key];
        }

        return $fallback;
    }

    private function legacyEmployeeCount(int $companyId): int
    {
        $mapping = json_decode(Setting::getValue('toko_pos.legacy_system_mapping', '{}'), true);

        if (
            is_array($mapping)
            && (int) ($mapping['company_id'] ?? 0) === $companyId
            && is_numeric($mapping['users']['legacy_count'] ?? null)
        ) {
            return (int) $mapping['users']['legacy_count'];
        }

        return User::query()->where('company_id', $companyId)->count();
    }

    /**
     * @param  list<string>  $sources
     */
    private function whereMetadataSourceIn($query, array $sources, string $column = 'metadata->source'): void
    {
        foreach ($sources as $index => $source) {
            if ($index === 0) {
                $query->where($column, $source);
            } else {
                $query->orWhere($column, $source);
            }
        }
    }

    /**
     * @param  list<string>  $sources
     */
    private function salesTotalForPeriod(int $companyId, $start, $end, array $sources = self::SALES_SOURCES): float
    {
        $branchId = $this->currentBranchIdForCompany($companyId);

        return round((float) Invoice::query()
            ->where('company_id', $companyId)
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->where(fn ($query) => $this->whereMetadataSourceIn($query, $sources))
            ->whereBetween('issued_at', [$start->toDateString(), $end->toDateString()])
            ->sum('grand_total'), 2);
    }

    private function operationalExpenseTotalForPeriod(int $companyId, $start, $end): float
    {
        return round((float) JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.source_type', 'toko_pos_operational_expense')
            ->whereBetween('journal_entries.entry_date', [$start->toDateString(), $end->toDateString()])
            ->sum('journal_entry_lines.debit'), 2);
    }

    /**
     * @return list<array{month:string,income:float,cost:float,net:float,report_url:string}>
     */
    private function monthlyNetTrend(int $companyId): array
    {
        return collect(range(5, 0))
            ->map(function (int $monthsAgo) use ($companyId): array {
                $month = now()->subMonths($monthsAgo);
                $start = $month->copy()->startOfMonth();
                $end = $month->copy()->endOfMonth();
                $income = $this->salesTotalForPeriod($companyId, $start, $end);
                $cost = $this->operationalExpenseTotalForPeriod($companyId, $start, $end);

                return [
                    'month' => $month->format('M Y'),
                    'income' => $income,
                    'cost' => $cost,
                    'net' => round($income - $cost, 2),
                    'report_url' => route('admin.toko.reports', [
                        'from' => $start->toDateString(),
                        'to' => $end->toDateString(),
                    ], false),
                ];
            })
            ->values()
            ->all();
    }

    private function invoiceAgingTotal(int $companyId, int $minDays, int $maxDays): float
    {
        $branchId = $this->currentBranchIdForCompany($companyId);

        return round((float) Invoice::query()
            ->where('company_id', $companyId)
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->where(fn ($query) => $this->whereMetadataSourceIn($query, self::SALES_SOURCES))
            ->where('status', '!=', Invoice::STATUS_PAID)
            ->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->get(['grand_total', 'due_at'])
            ->sum(function (Invoice $invoice) use ($minDays, $maxDays): float {
                $days = $invoice->due_at ? now()->startOfDay()->diffInDays($invoice->due_at->startOfDay(), false) * -1 : 0;

                return $days >= $minDays && $days <= $maxDays ? (float) $invoice->grand_total : 0;
            }), 2);
    }

    private function vendorBillAgingTotal(int $companyId, int $minDays, int $maxDays): float
    {
        $branchId = $this->currentBranchIdForCompany($companyId);

        return round((float) VendorBill::query()
            ->where('company_id', $companyId)
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->where(fn ($query) => $this->whereMetadataSourceIn($query, self::PURCHASE_SOURCES))
            ->where('status', '!=', VendorBill::STATUS_PAID)
            ->where('status', '!=', VendorBill::STATUS_CANCELLED)
            ->get(['grand_total', 'due_at'])
            ->sum(function (VendorBill $bill) use ($minDays, $maxDays): float {
                $days = $bill->due_at ? now()->startOfDay()->diffInDays($bill->due_at->startOfDay(), false) * -1 : 0;

                return $days >= $minDays && $days <= $maxDays ? (float) $bill->grand_total : 0;
            }), 2);
    }

    /**
     * @return array{labels:list<string>,values:list<float>}
     */
    private function expenseMix(int $companyId): array
    {
        $rows = JournalEntry::query()
            ->with('lines')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.source_type', 'toko_pos_operational_expense')
            ->latest('journal_entries.entry_date')
            ->latest('journal_entries.id')
            ->limit(120)
            ->get()
            ->groupBy(fn (JournalEntry $entry): string => (string) ($entry->metadata['expense_type'] ?? 'operasional toko'))
            ->map(fn ($entries, string $type): array => [
                'type' => $type,
                'total' => round((float) $entries->sum(fn (JournalEntry $entry): float => (float) $entry->lines->sum('debit')), 2),
            ])
            ->sortByDesc('total')
            ->take(6)
            ->values();

        return [
            'labels' => $rows->pluck('type')->map(fn ($label): string => (string) $label)->values()->all(),
            'values' => $rows->pluck('total')->map(fn ($total): float => (float) $total)->values()->all(),
        ];
    }

    /**
     * @return array{total:int,active:int,low_stock:int,expired:int,brands:int,categories:int}
     */
    private function productCatalogSummary(?int $companyId): array
    {
        if ($companyId === null) {
            return ['total' => 0, 'active' => 0, 'low_stock' => 0, 'expired' => 0, 'brands' => 0, 'categories' => 0];
        }

        $products = Product::query()
            ->with('stockMovements')
            ->where('company_id', $companyId)
            ->where('status', Product::STATUS_ACTIVE)
            ->where(function ($query): void {
                $query->whereNull('metadata->source')
                    ->orWhere('metadata->source', '!=', 'legacy_toko_placeholder');
            })
            ->get(['id', 'name', 'sku', 'status', 'unit', 'cost_price', 'selling_price', 'reorder_point', 'metadata'])
            ->map(fn (Product $product): array => $this->productCatalogRow($product));

        return [
            'total' => $products->count(),
            'active' => $products->where('status', Product::STATUS_ACTIVE)->count(),
            'low_stock' => $products->where('is_low_stock', true)->count(),
            'expired' => $products->where('is_expired', true)->count(),
            'brands' => $products->pluck('brand')->filter()->unique()->count(),
            'categories' => $products->pluck('category')->filter()->unique()->count(),
        ];
    }

    /**
     * @return list<array{id:int, name:string, sku:string|null, barcode:string, brand:string, category:string, unit:string, color:string, size:string, location:string, expired_at:string|null, cost_price:float, selling_price:float, reorder_point:float, stock_balance:float, margin:float, is_low_stock:bool, is_expired:bool, suggested_restock_quantity:float, expired_action:string, status:string, print_url:string}>
     */
    private function productRows(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        $rows = $this->filteredProductCatalogRows($companyId);
        $pages = max(1, (int) ceil($rows->count() / 10));
        $page = min(max(1, $this->productPage), $pages);

        if ($page !== $this->productPage) {
            $this->productPage = $page;
        }

        return $rows
            ->slice(($page - 1) * 10, 10)
            ->values()
            ->all();
    }

    /**
     * @return array{page:int,pages:int,total:int,start:int,end:int}
     */
    private function productTableMeta(?int $companyId): array
    {
        if ($companyId === null) {
            return ['page' => 1, 'pages' => 1, 'total' => 0, 'start' => 0, 'end' => 0];
        }

        $total = $this->filteredProductCatalogRows($companyId)->count();
        $pages = max(1, (int) ceil($total / 10));
        $page = min(max(1, $this->productPage), $pages);

        if ($page !== $this->productPage) {
            $this->productPage = $page;
        }

        $start = $total === 0 ? 0 : (($page - 1) * 10) + 1;
        $end = $total === 0 ? 0 : min($total, $page * 10);

        return ['page' => $page, 'pages' => $pages, 'total' => $total, 'start' => $start, 'end' => $end];
    }

    private function filteredProductCatalogRows(int $companyId)
    {
        $search = str($this->productSearch)->lower()->trim()->toString();

        return Product::query()
            ->with('stockMovements')
            ->where('company_id', $companyId)
            ->where('status', Product::STATUS_ACTIVE)
            ->where(function ($query): void {
                $query->whereNull('metadata->source')
                    ->orWhere('metadata->source', '!=', 'legacy_toko_placeholder');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'status', 'unit', 'cost_price', 'selling_price', 'reorder_point', 'metadata'])
            ->map(fn (Product $product): array => $this->productCatalogRow($product))
            ->filter(function (array $row) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                return str($row['name'].' '.$row['sku'].' '.$row['barcode'].' '.$row['brand'].' '.$row['category'].' '.$row['location'])
                    ->lower()
                    ->contains($search);
            })
            ->filter(fn (array $row): bool => match ($this->productCatalogFilter) {
                'low_stock' => $row['is_low_stock'],
                'expired' => $row['is_expired'],
                default => true,
            })
            ->values();
    }

    /**
     * @return array{id:int, name:string, sku:string|null, barcode:string, brand:string, category:string, unit:string, color:string, size:string, location:string, expired_at:string|null, cost_price:float, selling_price:float, reorder_point:float, stock_balance:float, margin:float, is_low_stock:bool, is_expired:bool, suggested_restock_quantity:float, expired_action:string, status:string, print_url:string}
     */
    private function productCatalogRow(Product $product): array
    {
        $metadata = is_array($product->metadata) ? $product->metadata : [];
        $stockBalance = $product->stockBalance();
        $expiredAt = $metadata['expired_at'] ?? null;
        $isExpired = filled($expiredAt) && now()->toDateString() >= (string) $expiredAt;
        $reorderPoint = (float) $product->reorder_point;
        $isLowStock = $reorderPoint > 0 && $stockBalance <= $reorderPoint;
        $suggestedRestockQuantity = $isLowStock ? max(0, $reorderPoint - $stockBalance) : 0.0;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => (string) ($metadata['barcode'] ?? ''),
            'brand' => (string) ($metadata['brand'] ?? ''),
            'category' => (string) ($metadata['category'] ?? ''),
            'unit' => $product->unit,
            'color' => (string) ($metadata['color'] ?? ''),
            'size' => (string) ($metadata['size'] ?? ''),
            'location' => (string) ($metadata['location'] ?? ''),
            'expired_at' => $expiredAt,
            'cost_price' => (float) $product->cost_price,
            'selling_price' => (float) $product->selling_price,
            'reorder_point' => $reorderPoint,
            'stock_balance' => $stockBalance,
            'margin' => (float) $product->selling_price - (float) $product->cost_price,
            'is_low_stock' => $isLowStock,
            'is_expired' => $isExpired,
            'suggested_restock_quantity' => $suggestedRestockQuantity,
            'expired_action' => $isExpired ? 'Karantina / tarik dari rak' : '',
            'status' => $product->status,
            'print_url' => route('admin.toko.products.barcodes', ['products' => [$product->id]], false),
        ];
    }

    /**
     * @return array{id:int, name:string, sku:string|null, barcode:string, brand:string, category:string, location:string, unit:string, stock_balance:float, cost_price:float, selling_price:float, margin:float, movements:list<array{date:string, type:string, reference:string, quantity:float, unit_cost:float, source:string, notes:string, balance:float}>}|null
     */
    private function productStockCardDetail(?int $companyId): ?array
    {
        $productId = (int) $this->selectedProductStockCardId;

        if ($companyId === null || $productId <= 0) {
            return null;
        }

        $product = Product::query()
            ->with(['stockMovements' => fn ($query) => $query->orderBy('occurred_at')->orderBy('id')])
            ->where('company_id', $companyId)
            ->find($productId);

        if (! $product instanceof Product) {
            return null;
        }

        $metadata = is_array($product->metadata) ? $product->metadata : [];
        $runningBalance = 0.0;

        $movements = $product->stockMovements
            ->map(function (StockMovement $movement) use (&$runningBalance): array {
                $quantity = (float) $movement->quantity;
                $signedQuantity = $movement->type === StockMovement::TYPE_OUT ? $quantity * -1 : $quantity;
                $runningBalance += $signedQuantity;
                $metadata = is_array($movement->metadata) ? $movement->metadata : [];

                return [
                    'date' => $movement->occurred_at?->format('d M Y H:i') ?? '-',
                    'type' => $movement->type,
                    'reference' => $movement->reference_number ?: '-',
                    'quantity' => $signedQuantity,
                    'unit_cost' => (float) $movement->unit_cost,
                    'source' => (string) ($metadata['source'] ?? '-'),
                    'notes' => $movement->notes ?: '-',
                    'balance' => $runningBalance,
                ];
            })
            ->reverse()
            ->values()
            ->all();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => (string) ($metadata['barcode'] ?? ''),
            'brand' => (string) ($metadata['brand'] ?? ''),
            'category' => (string) ($metadata['category'] ?? ''),
            'location' => (string) ($metadata['location'] ?? ''),
            'unit' => $product->unit,
            'stock_balance' => $runningBalance,
            'cost_price' => (float) $product->cost_price,
            'selling_price' => (float) $product->selling_price,
            'margin' => (float) $product->selling_price - (float) $product->cost_price,
            'movements' => $movements,
        ];
    }

    /**
     * @return list<array{id:int, label:string}>
     */
    private function clientOptions(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return Client::query()
            ->where('company_id', $companyId)
            ->where('status', Client::STATUS_ACTIVE)
            ->orderBy('name')
            ->limit(60)
            ->get(['id', 'name', 'contact_phone'])
            ->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => trim($client->name.($client->contact_phone ? ' · '.$client->contact_phone : '')),
            ])
            ->all();
    }

    /**
     * @return list<array{id:int, code:string|null, name:string, phone:string, email:string, address:string, status:string}>
     */
    private function customerRows(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        $rows = $this->filteredCustomerRows($companyId);
        $pages = max(1, (int) ceil($rows->count() / 10));
        $page = min(max(1, $this->customerPage), $pages);

        if ($page !== $this->customerPage) {
            $this->customerPage = $page;
        }

        return $rows
            ->slice(($page - 1) * 10, 10)
            ->values()
            ->all();
    }

    /**
     * @return array{page:int,pages:int,total:int,start:int,end:int}
     */
    private function customerTableMeta(?int $companyId): array
    {
        if ($companyId === null) {
            return ['page' => 1, 'pages' => 1, 'total' => 0, 'start' => 0, 'end' => 0];
        }

        $total = $this->filteredCustomerRows($companyId)->count();
        $pages = max(1, (int) ceil($total / 10));
        $page = min(max(1, $this->customerPage), $pages);

        if ($page !== $this->customerPage) {
            $this->customerPage = $page;
        }

        $start = $total === 0 ? 0 : (($page - 1) * 10) + 1;
        $end = $total === 0 ? 0 : min($total, $page * 10);

        return ['page' => $page, 'pages' => $pages, 'total' => $total, 'start' => $start, 'end' => $end];
    }

    private function filteredCustomerRows(int $companyId)
    {
        $search = str($this->customerSearch)->lower()->trim()->toString();

        return Client::query()
            ->where('company_id', $companyId)
            ->latest('id')
            ->get(['id', 'code', 'name', 'contact_phone', 'contact_email', 'address', 'status', 'metadata'])
            ->map(fn (Client $client): array => [
                'id' => $client->id,
                'code' => $client->code,
                'name' => $client->name,
                'phone' => (string) $client->contact_phone,
                'email' => (string) $client->contact_email,
                'address' => (string) $client->address,
                'status' => $client->status,
                'membership_status' => $this->customerMembershipLabel($client),
            ])
            ->filter(function (array $row) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                return str($row['code'].' '.$row['name'].' '.$row['phone'].' '.$row['email'].' '.$row['address'].' '.$row['status'])
                    ->lower()
                    ->contains($search);
            })
            ->values();
    }

    private function customerMembershipLabel(Client $client): string
    {
        $metadata = is_array($client->metadata) ? $client->metadata : [];
        $status = str((string) ($metadata['membership_status'] ?? 'berlangganan'))->lower()->trim()->toString();

        return match ($status) {
            'prospect', 'nonaktif', 'non-active', 'non_active' => 'Prospect',
            'walk-in', 'walk_in' => 'Walk-in',
            default => 'Berlangganan',
        };
    }

    /**
     * @return list<array{customer:string, total:float, ar_total:float, invoice_count:int}>
     */
    private function customerIncomeRows(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return Invoice::query()
            ->leftJoin('clients', 'clients.id', '=', 'invoices.client_id')
            ->where('invoices.company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId, 'invoices.branch_id'))
            ->where(function ($query): void {
                $query->where('invoices.metadata->source', 'toko_pos_counter_sale')
                    ->orWhere('invoices.metadata->source', 'quotation_conversion');
            })
            ->selectRaw("COALESCE(clients.name, 'Walk-in') as customer_name")
            ->selectRaw('SUM(invoices.grand_total) as total')
            ->selectRaw('SUM(CASE WHEN invoices.status != ? THEN invoices.grand_total ELSE 0 END) as ar_total', [Invoice::STATUS_PAID])
            ->selectRaw('COUNT(*) as invoice_count')
            ->groupBy('customer_name')
            ->orderByDesc('total')
            ->limit(40)
            ->get()
            ->map(fn ($row): array => [
                'customer' => (string) $row->customer_name,
                'total' => round((float) $row->total, 2),
                'ar_total' => round((float) $row->ar_total, 2),
                'invoice_count' => (int) $row->invoice_count,
            ])
            ->all();
    }

    /**
     * @return list<array{id:int, label:string}>
     */
    private function cancelStockMovementOptions(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return StockMovement::query()
            ->with('product:id,name,sku')
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->where(function ($query): void {
                $query->whereNull('metadata->source')
                    ->orWhere('metadata->source', '!=', 'toko_pos_stock_cancellation');
            })
            ->whereNull('metadata->canceled_at')
            ->latest('id')
            ->limit(50)
            ->get(['id', 'product_id', 'type', 'quantity', 'reference_number', 'metadata'])
            ->map(function (StockMovement $movement): array {
                $product = $movement->product;
                $productLabel = $product
                    ? trim($product->name.' · '.$product->sku)
                    : __('Unknown product');

                return [
                    'id' => $movement->id,
                    'label' => trim('#'.$movement->id.' · '.$productLabel.' · '.$movement->type.' · '.Helpers::formatNumberId((float) $movement->quantity, 3).' · '.($movement->reference_number ?? '-')),
                ];
            })
            ->all();
    }

    /**
     * @return list<array{id:int, date:string, product:string, type:string, reference:string, quantity:float, unit_cost:float, source:string, notes:string}>
     */
    private function inventoryMovementRows(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        $rows = $this->filteredInventoryMovementRows($companyId);
        $pages = max(1, (int) ceil($rows->count() / 10));
        $page = min(max(1, $this->inventoryMovementPage), $pages);

        if ($page !== $this->inventoryMovementPage) {
            $this->inventoryMovementPage = $page;
        }

        return $rows
            ->slice(($page - 1) * 10, 10)
            ->values()
            ->all();
    }

    /**
     * @return array{page:int,pages:int,total:int,start:int,end:int}
     */
    private function inventoryMovementTableMeta(?int $companyId): array
    {
        if ($companyId === null) {
            return ['page' => 1, 'pages' => 1, 'total' => 0, 'start' => 0, 'end' => 0];
        }

        $total = $this->filteredInventoryMovementRows($companyId)->count();
        $pages = max(1, (int) ceil($total / 10));
        $page = min(max(1, $this->inventoryMovementPage), $pages);

        if ($page !== $this->inventoryMovementPage) {
            $this->inventoryMovementPage = $page;
        }

        $start = $total === 0 ? 0 : (($page - 1) * 10) + 1;
        $end = $total === 0 ? 0 : min($total, $page * 10);

        return ['page' => $page, 'pages' => $pages, 'total' => $total, 'start' => $start, 'end' => $end];
    }

    private function filteredInventoryMovementRows(int $companyId)
    {
        $search = str($this->inventoryMovementSearch)->lower()->trim()->toString();

        return StockMovement::query()
            ->with('product:id,name,sku')
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->latest('occurred_at')
            ->latest('id')
            ->get(['id', 'product_id', 'type', 'quantity', 'unit_cost', 'reference_number', 'occurred_at', 'notes', 'metadata'])
            ->map(function (StockMovement $movement): array {
                $metadata = is_array($movement->metadata) ? $movement->metadata : [];

                return [
                    'id' => $movement->id,
                    'date' => $movement->occurred_at?->format('d M Y H:i') ?? '-',
                    'product' => trim(($movement->product?->name ?? '-').' '.($movement->product?->sku ? '· '.$movement->product->sku : '')),
                    'type' => $movement->type,
                    'reference' => $movement->reference_number ?: '-',
                    'quantity' => (float) $movement->quantity,
                    'unit_cost' => (float) $movement->unit_cost,
                    'source' => (string) ($metadata['source'] ?? '-'),
                    'notes' => $movement->notes ?: '-',
                ];
            })
            ->filter(function (array $row) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                return str($row['date'].' '.$row['product'].' '.$row['type'].' '.$row['reference'].' '.$row['quantity'].' '.$row['unit_cost'].' '.$row['source'].' '.$row['notes'])
                    ->lower()
                    ->contains($search);
            })
            ->values();
    }

    /**
     * @return list<array{id:int, label:string}>
     */
    private function vendorOptions(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return Vendor::query()
            ->where('company_id', $companyId)
            ->where('status', Vendor::STATUS_ACTIVE)
            ->orderBy('name')
            ->limit(60)
            ->get(['id', 'name'])
            ->map(fn (Vendor $vendor): array => [
                'id' => $vendor->id,
                'label' => $vendor->name,
            ])
            ->all();
    }

    /**
     * @return list<array{id:int, code:string, name:string, phone:string, email:string, address:string, status:string}>
     */
    private function vendorRows(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        $rows = $this->filteredVendorRows($companyId);
        $pages = max(1, (int) ceil($rows->count() / 10));
        $page = min(max(1, $this->vendorPage), $pages);

        if ($page !== $this->vendorPage) {
            $this->vendorPage = $page;
        }

        return $rows
            ->slice(($page - 1) * 10, 10)
            ->values()
            ->all();
    }

    /**
     * @return array{page:int,pages:int,total:int,start:int,end:int}
     */
    private function vendorTableMeta(?int $companyId): array
    {
        if ($companyId === null) {
            return ['page' => 1, 'pages' => 1, 'total' => 0, 'start' => 0, 'end' => 0];
        }

        $total = $this->filteredVendorRows($companyId)->count();
        $pages = max(1, (int) ceil($total / 10));
        $page = min(max(1, $this->vendorPage), $pages);

        if ($page !== $this->vendorPage) {
            $this->vendorPage = $page;
        }

        $start = $total === 0 ? 0 : (($page - 1) * 10) + 1;
        $end = $total === 0 ? 0 : min($total, $page * 10);

        return ['page' => $page, 'pages' => $pages, 'total' => $total, 'start' => $start, 'end' => $end];
    }

    private function filteredVendorRows(int $companyId)
    {
        $search = str($this->vendorSearch)->lower()->trim()->toString();

        return Vendor::query()
            ->where('company_id', $companyId)
            ->latest('id')
            ->get(['id', 'name', 'phone', 'email', 'address', 'status', 'metadata'])
            ->map(function (Vendor $vendor): array {
                $metadata = is_array($vendor->metadata) ? $vendor->metadata : [];

                return [
                    'id' => $vendor->id,
                    'code' => (string) ($metadata['legacy_code'] ?? ''),
                    'name' => $vendor->name,
                    'phone' => (string) $vendor->phone,
                    'email' => (string) $vendor->email,
                    'address' => (string) $vendor->address,
                    'status' => $vendor->status,
                ];
            })
            ->filter(function (array $row) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                return str($row['code'].' '.$row['name'].' '.$row['phone'].' '.$row['email'].' '.$row['address'].' '.$row['status'])
                    ->lower()
                    ->contains($search);
            })
            ->values();
    }

    /**
     * @return array{id:int, code:string, name:string, total_purchases:float, open_ap:float, paid_total:float, bill_count:int, rows:list<array{number:string,status:string,issued_at:string|null,due_at:string|null,total:float,paid_total:float,balance_due:float}>}|null
     */
    private function vendorApDetail(?int $companyId): ?array
    {
        $vendorId = (int) $this->selectedVendorDetailId;

        if ($companyId === null || $vendorId <= 0) {
            return null;
        }

        $vendor = Vendor::query()
            ->where('company_id', $companyId)
            ->find($vendorId);

        if (! $vendor instanceof Vendor) {
            return null;
        }

        $metadata = is_array($vendor->metadata) ? $vendor->metadata : [];
        $bills = VendorBill::query()
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->where('vendor_id', $vendor->id)
            ->where('metadata->source', 'toko_pos_purchase')
            ->latest('issued_at')
            ->latest('id')
            ->get(['id', 'number', 'status', 'grand_total', 'issued_at', 'due_at', 'metadata']);

        $rows = $bills
            ->map(function (VendorBill $bill): array {
                $metadata = is_array($bill->metadata) ? $bill->metadata : [];
                $paidTotal = (float) ($metadata['paid_total'] ?? ($bill->status === VendorBill::STATUS_PAID ? $bill->grand_total : 0));
                $balanceDue = (float) ($metadata['balance_due'] ?? ($bill->status === VendorBill::STATUS_PAID ? 0 : $bill->grand_total));

                return [
                    'number' => $bill->number,
                    'status' => $bill->status,
                    'issued_at' => $bill->issued_at?->format('d M Y'),
                    'due_at' => $bill->due_at?->format('d M Y'),
                    'total' => (float) $bill->grand_total,
                    'paid_total' => $paidTotal,
                    'balance_due' => $balanceDue,
                ];
            })
            ->values();

        return [
            'id' => $vendor->id,
            'code' => (string) ($metadata['legacy_code'] ?? ''),
            'name' => $vendor->name,
            'total_purchases' => round((float) $rows->sum('total'), 2),
            'open_ap' => round((float) $rows->sum('balance_due'), 2),
            'paid_total' => round((float) $rows->sum('paid_total'), 2),
            'bill_count' => $rows->count(),
            'rows' => $rows->take(10)->all(),
        ];
    }

    /**
     * @return list<array{id:int, label:string}>
     */
    private function vendorBillPaymentOptions(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return VendorBill::query()
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->where('metadata->source', 'toko_pos_purchase')
            ->where('status', VendorBill::STATUS_POSTED)
            ->latest('id')
            ->limit(40)
            ->get(['id', 'number', 'grand_total', 'due_at', 'metadata'])
            ->map(function (VendorBill $bill): array {
                $metadata = is_array($bill->metadata) ? $bill->metadata : [];
                $balanceDue = (float) ($metadata['balance_due'] ?? $bill->grand_total);

                return [
                    'id' => $bill->id,
                    'label' => $bill->number.' · Balance '.Helpers::formatNumberId($balanceDue).' · '.($bill->due_at?->format('d M Y') ?? '-'),
                ];
            })
            ->all();
    }

    /**
     * @return list<array{id:int, label:string}>
     */
    private function cancelVendorBillOptions(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return VendorBill::query()
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->where('metadata->source', 'toko_pos_purchase')
            ->where('status', VendorBill::STATUS_POSTED)
            ->latest('id')
            ->limit(40)
            ->get(['id', 'number', 'grand_total', 'due_at'])
            ->map(fn (VendorBill $bill): array => [
                'id' => $bill->id,
                'label' => $bill->number.' · '.Helpers::formatNumberId((float) $bill->grand_total).' · '.($bill->due_at?->format('d M Y') ?? '-'),
            ])
            ->all();
    }

    /**
     * @return list<array{id:int, number:string, vendor:string, status:string, total:float, issued_at:string|null, paid_at:string|null, cancel_reason:string, print_url:string, items:list<array{description:string, quantity:float, unit_cost:float, line_total:float}>}>
     */
    private function purchaseBillRows(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        $rows = $this->filteredPurchaseBillRows($companyId);
        $pages = max(1, (int) ceil($rows->count() / 10));
        $page = min(max(1, $this->purchasePage), $pages);

        if ($page !== $this->purchasePage) {
            $this->purchasePage = $page;
        }

        return $rows
            ->slice(($page - 1) * 10, 10)
            ->values()
            ->all();
    }

    /**
     * @return array{page:int,pages:int,total:int,start:int,end:int}
     */
    private function purchaseTableMeta(?int $companyId): array
    {
        if ($companyId === null) {
            return ['page' => 1, 'pages' => 1, 'total' => 0, 'start' => 0, 'end' => 0];
        }

        $total = $this->filteredPurchaseBillRows($companyId)->count();
        $pages = max(1, (int) ceil($total / 10));
        $page = min(max(1, $this->purchasePage), $pages);

        if ($page !== $this->purchasePage) {
            $this->purchasePage = $page;
        }

        $start = $total === 0 ? 0 : (($page - 1) * 10) + 1;
        $end = $total === 0 ? 0 : min($total, $page * 10);

        return ['page' => $page, 'pages' => $pages, 'total' => $total, 'start' => $start, 'end' => $end];
    }

    private function filteredPurchaseBillRows(int $companyId)
    {
        $search = str($this->purchaseSearch)->lower()->trim()->toString();

        return VendorBill::query()
            ->with(['vendor:id,name', 'items:id,vendor_bill_id,description,quantity,unit_cost,line_total'])
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->where('metadata->source', 'toko_pos_purchase')
            ->latest('id')
            ->get(['id', 'vendor_id', 'number', 'status', 'grand_total', 'issued_at', 'paid_at', 'metadata'])
            ->map(function (VendorBill $bill): array {
                $metadata = is_array($bill->metadata) ? $bill->metadata : [];

                return [
                    'id' => $bill->id,
                    'number' => $bill->number,
                    'vendor' => $bill->vendor?->name ?? '-',
                    'status' => $bill->status,
                    'total' => (float) $bill->grand_total,
                    'issued_at' => $bill->issued_at?->format('d M Y'),
                    'paid_at' => $bill->paid_at?->format('d M Y'),
                    'cancel_reason' => (string) ($metadata['cancel_reason'] ?? ''),
                    'print_url' => route('admin.toko.purchases.pdf', $bill, false),
                    'items' => $bill->items
                        ->map(fn ($item): array => [
                            'description' => $item->description,
                            'quantity' => (float) $item->quantity,
                            'unit_cost' => (float) $item->unit_cost,
                            'line_total' => (float) $item->line_total,
                        ])
                        ->all(),
                ];
            })
            ->filter(function (array $row) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                $itemText = collect($row['items'])
                    ->map(fn (array $item): string => $item['description'].' '.$item['quantity'].' '.$item['unit_cost'].' '.$item['line_total'])
                    ->implode(' ');

                return str($row['number'].' '.$row['vendor'].' '.$row['status'].' '.$row['total'].' '.$row['issued_at'].' '.$row['cancel_reason'].' '.$itemText)
                    ->lower()
                    ->contains($search);
            })
            ->values();
    }

    /**
     * @return array{id:int, number:string, vendor:string, status:string, total:float, issued_at:string|null, due_at:string|null, paid_at:string|null, po_number:string, receiver_name:string, extra_cost:float, notes:string, cancel_reason:string, print_url:string, items:list<array{description:string, quantity:float, unit_cost:float, line_total:float}>}|null
     */
    private function purchaseBillDetail(?int $companyId): ?array
    {
        $billId = (int) $this->selectedPurchaseBillDetailId;

        if ($companyId === null || $billId <= 0) {
            return null;
        }

        $bill = VendorBill::query()
            ->with(['vendor:id,name', 'items:id,vendor_bill_id,description,quantity,unit_cost,line_total'])
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->where('metadata->source', 'toko_pos_purchase')
            ->find($billId);

        if (! $bill instanceof VendorBill) {
            return null;
        }

        $metadata = is_array($bill->metadata) ? $bill->metadata : [];

        return [
            'id' => $bill->id,
            'number' => $bill->number,
            'vendor' => $bill->vendor?->name ?? '-',
            'status' => $bill->status,
            'total' => (float) $bill->grand_total,
            'issued_at' => $bill->issued_at?->format('d M Y'),
            'due_at' => $bill->due_at?->format('d M Y'),
            'paid_at' => $bill->paid_at?->format('d M Y'),
            'po_number' => (string) ($metadata['po_number'] ?? ''),
            'receiver_name' => (string) ($metadata['receiver_name'] ?? ''),
            'extra_cost' => (float) ($metadata['extra_cost'] ?? 0),
            'notes' => (string) $bill->notes,
            'cancel_reason' => (string) ($metadata['cancel_reason'] ?? ''),
            'print_url' => route('admin.toko.purchases.pdf', $bill, false),
            'items' => $bill->items
                ->map(fn ($item): array => [
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_cost' => (float) $item->unit_cost,
                    'line_total' => (float) $item->line_total,
                ])
                ->all(),
        ];
    }

    /**
     * @return array{overdue:array{label:string,count:int,total:float}, due_soon:array{label:string,count:int,total:float}, not_yet_due:array{label:string,count:int,total:float}, total:array{label:string,count:int,total:float}}
     */
    private function purchaseApAging(?int $companyId): array
    {
        $empty = [
            'overdue' => ['label' => 'Overdue', 'count' => 0, 'total' => 0.0],
            'due_soon' => ['label' => 'Due This Week', 'count' => 0, 'total' => 0.0],
            'not_yet_due' => ['label' => 'Not Yet Due', 'count' => 0, 'total' => 0.0],
            'total' => ['label' => 'Total AP', 'count' => 0, 'total' => 0.0],
        ];

        if ($companyId === null) {
            return $empty;
        }

        $today = today();
        $dueSoonEnd = $today->copy()->addDays(7);

        $bills = VendorBill::query()
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->where('metadata->source', 'toko_pos_purchase')
            ->where('status', VendorBill::STATUS_POSTED)
            ->get(['id', 'due_at', 'grand_total', 'metadata']);

        foreach ($bills as $bill) {
            $metadata = is_array($bill->metadata) ? $bill->metadata : [];
            $amount = (float) ($metadata['balance_due'] ?? $bill->grand_total);
            $bucket = 'not_yet_due';

            if ($bill->due_at !== null && $bill->due_at->lt($today)) {
                $bucket = 'overdue';
            } elseif ($bill->due_at !== null && $bill->due_at->betweenIncluded($today, $dueSoonEnd)) {
                $bucket = 'due_soon';
            }

            $empty[$bucket]['count']++;
            $empty[$bucket]['total'] += $amount;
            $empty['total']['count']++;
            $empty['total']['total'] += $amount;
        }

        return $empty;
    }

    /**
     * @return list<array{bill_number:string, vendor:string, paid_at:string|null, journal_number:string, amount:float}>
     */
    private function vendorPaymentHistoryRows(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return JournalEntry::query()
            ->where('company_id', $companyId)
            ->where('source_type', 'toko_pos_purchase_payment')
            ->latest('entry_date')
            ->latest('id')
            ->limit(10)
            ->get(['id', 'number', 'entry_date', 'reference_number', 'source_id', 'metadata'])
            ->map(function (JournalEntry $entry): array {
                $metadata = is_array($entry->metadata) ? $entry->metadata : [];
                $bill = VendorBill::query()
                    ->with('vendor:id,name')
                    ->find($entry->source_id);

                return [
                    'bill_number' => $entry->reference_number ?: (string) ($metadata['vendor_bill_number'] ?? '-'),
                    'vendor' => $bill?->vendor?->name ?? '-',
                    'paid_at' => $entry->entry_date?->format('d M Y'),
                    'journal_number' => $entry->number,
                    'amount' => (float) ($metadata['amount'] ?? 0),
                ];
            })
            ->all();
    }

    /**
     * @return list<array{date:string, type:string, description:string, amount:float, payment_method:string}>
     */
    private function recentOperationalExpenses(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return JournalEntry::query()
            ->where('company_id', $companyId)
            ->where('source_type', 'toko_pos_operational_expense')
            ->with('lines')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (JournalEntry $entry): array => [
                'date' => $entry->entry_date?->format('d M Y') ?? '-',
                'type' => (string) ($entry->metadata['expense_type'] ?? '-'),
                'description' => (string) $entry->description,
                'amount' => (float) $entry->lines->sum('debit'),
                'payment_method' => (string) ($entry->metadata['payment_method'] ?? '-'),
            ])
            ->all();
    }

    /**
     * @return list<array{date:string, reference:string, type:string, description:string, amount:float, payment_method:string, bank_code:string}>
     */
    private function operationalExpenseRows(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        $rows = $this->filteredOperationalExpenseRows($companyId);
        $pages = max(1, (int) ceil($rows->count() / 10));
        $page = min(max(1, $this->operationalExpensePage), $pages);

        if ($page !== $this->operationalExpensePage) {
            $this->operationalExpensePage = $page;
        }

        return $rows
            ->slice(($page - 1) * 10, 10)
            ->values()
            ->all();
    }

    /**
     * @return array{page:int,pages:int,total:int,start:int,end:int}
     */
    private function operationalExpenseTableMeta(?int $companyId): array
    {
        if ($companyId === null) {
            return ['page' => 1, 'pages' => 1, 'total' => 0, 'start' => 0, 'end' => 0];
        }

        $total = $this->filteredOperationalExpenseRows($companyId)->count();
        $pages = max(1, (int) ceil($total / 10));
        $page = min(max(1, $this->operationalExpensePage), $pages);

        if ($page !== $this->operationalExpensePage) {
            $this->operationalExpensePage = $page;
        }

        $start = $total === 0 ? 0 : (($page - 1) * 10) + 1;
        $end = $total === 0 ? 0 : min($total, $page * 10);

        return ['page' => $page, 'pages' => $pages, 'total' => $total, 'start' => $start, 'end' => $end];
    }

    private function filteredOperationalExpenseRows(int $companyId)
    {
        $search = str($this->operationalExpenseSearch)->lower()->trim()->toString();
        $fromDate = $this->validDateOrNull($this->operationalExpenseFromDate);
        $toDate = $this->validDateOrNull($this->operationalExpenseToDate);

        $query = JournalEntry::query()
            ->where('company_id', $companyId)
            ->where('source_type', 'toko_pos_operational_expense')
            ->with('lines')
            ->latest('id');

        if ($fromDate !== null) {
            $query->whereDate('entry_date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $query->whereDate('entry_date', '<=', $toDate);
        }

        return $query
            ->get(['id', 'entry_date', 'reference_number', 'description', 'status', 'metadata'])
            ->map(fn (JournalEntry $entry): array => [
                'id' => $entry->id,
                'date' => $entry->entry_date?->format('d M Y') ?? '-',
                'reference' => (string) $entry->reference_number,
                'status' => (string) $entry->status,
                'type' => (string) ($entry->metadata['expense_type'] ?? '-'),
                'description' => (string) $entry->description,
                'amount' => (float) $entry->lines->sum('debit'),
                'payment_method' => (string) ($entry->metadata['payment_method'] ?? '-'),
                'bank_code' => (string) ($entry->metadata['bank_code'] ?? '-'),
            ])
            ->filter(function (array $row) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                return str($row['date'].' '.$row['reference'].' '.$row['type'].' '.$row['description'].' '.$row['amount'].' '.$row['payment_method'].' '.$row['bank_code'])
                    ->lower()
                    ->contains($search);
            })
            ->values();
    }

    private function validDateOrNull(string $date): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null;
    }

    private function operationalExpenseReportQuery(int $companyId)
    {
        return JournalEntry::query()
            ->where('company_id', $companyId)
            ->where('source_type', 'toko_pos_operational_expense');
    }

    private function operationalExpenseReportRows(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        $query = $this->operationalExpenseReportQuery($companyId);
        $totalCount = $query->count();
        $pages = max(1, (int) ceil($totalCount / 10));
        $page = min(max(1, $this->operationalExpenseReportPage), $pages);

        if ($page !== $this->operationalExpenseReportPage) {
            $this->operationalExpenseReportPage = $page;
        }

        return $query
            ->with('lines')
            ->latest('id')
            ->offset(($page - 1) * 10)
            ->limit(10)
            ->get()
            ->map(fn (JournalEntry $entry): array => [
                'date' => $entry->entry_date?->format('d M Y') ?? '-',
                'type' => (string) ($entry->metadata['expense_type'] ?? '-'),
                'description' => (string) $entry->description,
                'amount' => (float) $entry->lines->sum('debit'),
                'payment_method' => (string) ($entry->metadata['payment_method'] ?? '-'),
                'bank_code' => (string) ($entry->metadata['bank_code'] ?? '-'),
            ])
            ->all();
    }

    private function operationalExpenseReportTableMeta(?int $companyId): array
    {
        if ($companyId === null) {
            return ['page' => 1, 'pages' => 1, 'total' => 0, 'start' => 0, 'end' => 0];
        }

        $totalCount = $this->operationalExpenseReportQuery($companyId)->count();
        $pages = max(1, (int) ceil($totalCount / 10));
        $page = min(max(1, $this->operationalExpenseReportPage), $pages);

        if ($page !== $this->operationalExpenseReportPage) {
            $this->operationalExpenseReportPage = $page;
        }

        $start = $totalCount === 0 ? 0 : (($page - 1) * 10) + 1;
        $end = $totalCount === 0 ? 0 : min($totalCount, $page * 10);

        return ['page' => $page, 'pages' => $pages, 'total' => $totalCount, 'start' => $start, 'end' => $end];
    }

    /**
     * @return list<array{invoice_number:string, amount:float, method:string, bank_code:string, reference:string, paid_at:string}>
     */
    private function paymentHistoryRows(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        $rows = $this->filteredPaymentHistoryRows($companyId);
        $pages = max(1, (int) ceil($rows->count() / 10));
        $page = min(max(1, $this->paymentHistoryPage), $pages);

        if ($page !== $this->paymentHistoryPage) {
            $this->paymentHistoryPage = $page;
        }

        return $rows
            ->slice(($page - 1) * 10, 10)
            ->values()
            ->all();
    }

    /**
     * @return array{page:int,pages:int,total:int,start:int,end:int}
     */
    private function paymentHistoryTableMeta(?int $companyId): array
    {
        if ($companyId === null) {
            return ['page' => 1, 'pages' => 1, 'total' => 0, 'start' => 0, 'end' => 0];
        }

        $total = $this->filteredPaymentHistoryRows($companyId)->count();
        $pages = max(1, (int) ceil($total / 10));
        $page = min(max(1, $this->paymentHistoryPage), $pages);

        if ($page !== $this->paymentHistoryPage) {
            $this->paymentHistoryPage = $page;
        }

        $start = $total === 0 ? 0 : (($page - 1) * 10) + 1;
        $end = $total === 0 ? 0 : min($total, $page * 10);

        return ['page' => $page, 'pages' => $pages, 'total' => $total, 'start' => $start, 'end' => $end];
    }

    private function filteredPaymentHistoryRows(int $companyId)
    {
        $search = str($this->paymentHistorySearch)->lower()->trim()->toString();

        return Invoice::query()
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->where(function ($query): void {
                $query->where('metadata->source', 'toko_pos_counter_sale')
                    ->orWhere('metadata->source', 'quotation_conversion');
            })
            ->latest('id')
            ->get(['number', 'metadata'])
            ->flatMap(function (Invoice $invoice) {
                $metadata = is_array($invoice->metadata) ? $invoice->metadata : [];

                return collect($metadata['payments'] ?? [])
                    ->filter(fn ($payment): bool => is_array($payment))
                    ->map(fn (array $payment): array => [
                        'invoice_number' => $invoice->number,
                        'amount' => (float) ($payment['amount'] ?? 0),
                        'method' => (string) ($payment['method'] ?? ''),
                        'bank_code' => (string) ($payment['bank_code'] ?? ''),
                        'reference' => (string) ($payment['reference'] ?? ''),
                        'paid_at' => filled($payment['paid_at'] ?? null) ? (string) $payment['paid_at'] : '',
                    ]);
            })
            ->values()
            ->filter(function (array $row) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                return str($row['invoice_number'].' '.$row['amount'].' '.$row['method'].' '.$row['bank_code'].' '.$row['reference'].' '.$row['paid_at'])
                    ->lower()
                    ->contains($search);
            })
            ->values();
    }

    /**
     * @return list<array{date:string, product:string, reference:string, previous_quantity:float, counted_quantity:float, delta:float, notes:string}>
     */
    private function stockAdjustmentReportRows(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return StockMovement::query()
            ->with('product:id,name')
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->where('metadata->source', 'toko_pos_stock_opname')
            ->latest('occurred_at')
            ->latest('id')
            ->limit(40)
            ->get(['product_id', 'reference_number', 'occurred_at', 'notes', 'metadata'])
            ->map(function (StockMovement $movement): array {
                $metadata = is_array($movement->metadata) ? $movement->metadata : [];

                return [
                    'date' => $movement->occurred_at?->format('d M Y H:i') ?? '-',
                    'product' => $movement->product?->name ?? '-',
                    'reference' => $movement->reference_number ?: '-',
                    'previous_quantity' => (float) ($metadata['previous_quantity'] ?? 0),
                    'counted_quantity' => (float) ($metadata['counted_quantity'] ?? 0),
                    'delta' => (float) ($metadata['delta'] ?? 0),
                    'notes' => $movement->notes ?: '-',
                ];
            })
            ->all();
    }

    /**
     * @return list<array{date:string, product:string, sku:string, type:string, reference:string, quantity:float, unit_cost:float, source:string, notes:string}>
     */
    private function productMovementReportQuery(int $companyId)
    {
        $fromDate = $this->validDateOrNull($this->reportFromDate);
        $toDate = $this->validDateOrNull($this->reportToDate);

        $query = StockMovement::query()
            ->with('product:id,name,sku')
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId));

        if ($fromDate !== null) {
            $query->whereDate('occurred_at', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $query->whereDate('occurred_at', '<=', $toDate);
        }

        return $query;
    }

    private function productMovementReportRows(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        $query = $this->productMovementReportQuery($companyId);
        $total = clone $query;
        $totalCount = $total->count();
        $pages = max(1, (int) ceil($totalCount / 10));
        $page = min(max(1, $this->productMovementPage), $pages);

        if ($page !== $this->productMovementPage) {
            $this->productMovementPage = $page;
        }

        return $query
            ->latest('occurred_at')
            ->latest('id')
            ->offset(($page - 1) * 10)
            ->limit(10)
            ->get(['id', 'product_id', 'type', 'quantity', 'unit_cost', 'reference_number', 'occurred_at', 'notes', 'metadata'])
            ->map(function (StockMovement $movement): array {
                $metadata = is_array($movement->metadata) ? $movement->metadata : [];

                return [
                    'date' => $movement->occurred_at?->format('d M Y H:i') ?? '-',
                    'product' => $movement->product?->name ?? '-',
                    'sku' => $movement->product?->sku ?? '-',
                    'type' => $movement->type,
                    'reference' => $movement->reference_number ?: '-',
                    'quantity' => (float) $movement->quantity,
                    'unit_cost' => (float) $movement->unit_cost,
                    'source' => (string) ($metadata['source'] ?? '-'),
                    'notes' => $movement->notes ?: '-',
                ];
            })
            ->all();
    }

    private function productMovementTableMeta(?int $companyId): array
    {
        if ($companyId === null) {
            return ['page' => 1, 'pages' => 1, 'total' => 0, 'start' => 0, 'end' => 0];
        }

        $totalCount = $this->productMovementReportQuery($companyId)->count();
        $pages = max(1, (int) ceil($totalCount / 10));
        $page = min(max(1, $this->productMovementPage), $pages);

        if ($page !== $this->productMovementPage) {
            $this->productMovementPage = $page;
        }

        $start = $totalCount === 0 ? 0 : (($page - 1) * 10) + 1;
        $end = $totalCount === 0 ? 0 : min($totalCount, $page * 10);

        return ['page' => $page, 'pages' => $pages, 'total' => $totalCount, 'start' => $start, 'end' => $end];
    }

    /**
     * @return list<array{id:int, number:string, status:string, total:float, issued_at:string|null, print_url:string, thermal_print_url:string, delivery_letter_url:string|null, has_delivery_letter:bool}>
     */
    private function recentPosInvoices(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return Invoice::query()
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->where('metadata->source', 'toko_pos_counter_sale')
            ->latest('id')
            ->limit(8)
            ->get(['id', 'number', 'status', 'grand_total', 'issued_at'])
            ->map(function (Invoice $invoice): array {
                $deliveryLetter = DeliveryLetter::query()
                    ->where('invoice_id', $invoice->id)
                    ->where('metadata->source', 'toko_pos_delivery_letter')
                    ->latest('id')
                    ->first(['id']);

                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'status' => $invoice->status,
                    'total' => (float) $invoice->grand_total,
                    'issued_at' => $invoice->issued_at?->format('d M Y'),
                    'print_url' => route('admin.toko.invoices.pdf', $invoice, false),
                    'thermal_print_url' => route('admin.toko.invoices.thermal', $invoice, false),
                    'delivery_letter_url' => $deliveryLetter ? route('admin.toko.delivery-letters.pdf', $deliveryLetter, false) : null,
                    'has_delivery_letter' => $deliveryLetter !== null,
                ];
            })
            ->all();
    }

    /**
     * @return list<array{id:int, number:string, status:string, issued_at:string|null, invoice_number:string, customer:string, destination:string, driver_name:string, vehicle_number:string, print_url:string}>
     */
    private function deliveryLetterRows(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        $rows = $this->filteredDeliveryLetterRows($companyId);
        $pages = max(1, (int) ceil($rows->count() / 10));
        $page = min(max(1, $this->deliveryLetterPage), $pages);

        if ($page !== $this->deliveryLetterPage) {
            $this->deliveryLetterPage = $page;
        }

        return $rows
            ->slice(($page - 1) * 10, 10)
            ->values()
            ->all();
    }

    /**
     * @return array{page:int,pages:int,total:int,start:int,end:int}
     */
    private function deliveryLetterTableMeta(?int $companyId): array
    {
        if ($companyId === null) {
            return ['page' => 1, 'pages' => 1, 'total' => 0, 'start' => 0, 'end' => 0];
        }

        $total = $this->filteredDeliveryLetterRows($companyId)->count();
        $pages = max(1, (int) ceil($total / 10));
        $page = min(max(1, $this->deliveryLetterPage), $pages);

        if ($page !== $this->deliveryLetterPage) {
            $this->deliveryLetterPage = $page;
        }

        $start = $total === 0 ? 0 : (($page - 1) * 10) + 1;
        $end = $total === 0 ? 0 : min($total, $page * 10);

        return ['page' => $page, 'pages' => $pages, 'total' => $total, 'start' => $start, 'end' => $end];
    }

    private function filteredDeliveryLetterRows(int $companyId)
    {
        $search = str($this->deliveryLetterSearch)->lower()->trim()->toString();

        return DeliveryLetter::query()
            ->with(['client:id,name', 'invoice:id,number'])
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->latest('id')
            ->get(['id', 'client_id', 'invoice_id', 'number', 'status', 'issued_at', 'destination', 'driver_name', 'vehicle_number'])
            ->map(fn (DeliveryLetter $letter): array => [
                'id' => $letter->id,
                'number' => $letter->number,
                'status' => $letter->status,
                'issued_at' => $letter->issued_at?->format('d M Y'),
                'invoice_number' => $letter->invoice?->number ?? '-',
                'customer' => $letter->client?->name ?? '-',
                'destination' => $letter->destination ?: '-',
                'driver_name' => $letter->driver_name ?: '-',
                'vehicle_number' => $letter->vehicle_number ?: '-',
                'print_url' => route('admin.toko.delivery-letters.pdf', $letter, false),
            ])
            ->filter(function (array $row) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                return str($row['number'].' '.$row['status'].' '.$row['issued_at'].' '.$row['invoice_number'].' '.$row['customer'].' '.$row['destination'].' '.$row['driver_name'].' '.$row['vehicle_number'])
                    ->lower()
                    ->contains($search);
            })
            ->values();
    }

    /**
     * @return list<array{id:int, number:string, status:string, total:float, issued_at:string|null, payment_status:string, payment_summary:string, cancel_reason:string, print_url:string, thermal_print_url:string, items:list<array{description:string, quantity:float, unit_price:float, line_total:float}>}>
     */
    private function salesInvoiceRows(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        $rows = $this->filteredSalesInvoiceRows($companyId);
        $pages = max(1, (int) ceil($rows->count() / 10));
        $page = min(max(1, $this->salesPage), $pages);

        if ($page !== $this->salesPage) {
            $this->salesPage = $page;
        }

        return $rows
            ->slice(($page - 1) * 10, 10)
            ->values()
            ->all();
    }

    /**
     * @return array{page:int,pages:int,total:int,start:int,end:int}
     */
    private function salesTableMeta(?int $companyId): array
    {
        if ($companyId === null) {
            return ['page' => 1, 'pages' => 1, 'total' => 0, 'start' => 0, 'end' => 0];
        }

        $total = $this->filteredSalesInvoiceRows($companyId)->count();
        $pages = max(1, (int) ceil($total / 10));
        $page = min(max(1, $this->salesPage), $pages);

        if ($page !== $this->salesPage) {
            $this->salesPage = $page;
        }

        $start = $total === 0 ? 0 : (($page - 1) * 10) + 1;
        $end = $total === 0 ? 0 : min($total, $page * 10);

        return ['page' => $page, 'pages' => $pages, 'total' => $total, 'start' => $start, 'end' => $end];
    }

    private function filteredSalesInvoiceRows(int $companyId)
    {
        $search = str($this->salesSearch)->lower()->trim()->toString();

        return Invoice::query()
            ->with('items:id,invoice_id,description,quantity,unit_price,line_total')
            ->with('client:id,name')
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->where('metadata->source', 'toko_pos_counter_sale')
            ->latest('id')
            ->get(['id', 'client_id', 'number', 'status', 'grand_total', 'issued_at', 'metadata'])
            ->map(function (Invoice $invoice): array {
                $metadata = is_array($invoice->metadata) ? $invoice->metadata : [];
                $payments = collect($metadata['payments'] ?? [])->filter(fn ($payment): bool => is_array($payment))->values();
                $paymentSummary = $payments->map(function (array $payment): string {
                    return trim(implode(' · ', array_filter([
                        (string) ($payment['method'] ?? ''),
                        (string) ($payment['bank_code'] ?? ''),
                        (string) ($payment['reference'] ?? ''),
                    ], fn (string $value): bool => $value !== '')));
                })->filter()->implode(' | ');

                if ($paymentSummary === '') {
                    $paymentSummary = trim(implode(' · ', array_filter([
                        (string) ($metadata['payment_method'] ?? ''),
                        (string) ($metadata['bank_code'] ?? ''),
                    ], fn (string $value): bool => $value !== ''))) ?: '-';
                }

                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'customer' => $invoice->client?->name ?? 'Walk-in',
                    'status' => $invoice->status,
                    'total' => (float) $invoice->grand_total,
                    'issued_at' => $invoice->issued_at?->format('d M Y'),
                    'payment_status' => (string) ($metadata['payment_status'] ?? $invoice->status),
                    'payment_summary' => $paymentSummary,
                    'cancel_reason' => (string) ($metadata['cancel_reason'] ?? ''),
                    'print_url' => route('admin.toko.invoices.pdf', $invoice, false),
                    'thermal_print_url' => route('admin.toko.invoices.thermal', $invoice, false),
                    'items' => $invoice->items
                        ->map(fn ($item): array => [
                            'description' => $item->description,
                            'quantity' => (float) $item->quantity,
                            'unit_price' => (float) $item->unit_price,
                            'line_total' => (float) $item->line_total,
                        ])
                        ->all(),
                ];
            })
            ->filter(function (array $row) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                $itemText = collect($row['items'])
                    ->map(fn (array $item): string => $item['description'].' '.$item['quantity'].' '.$item['unit_price'].' '.$item['line_total'])
                    ->implode(' ');

                return str($row['number'].' '.$row['customer'].' '.$row['status'].' '.$row['payment_status'].' '.$row['payment_summary'].' '.$row['cancel_reason'].' '.$row['total'].' '.$row['issued_at'].' '.$itemText)
                    ->lower()
                    ->contains($search);
            })
            ->values();
    }

    /**
     * @return array{id:int,number:string,customer:string,status:string,payment_status:string,payment_summary:string,total:float,issued_at:string|null,due_at:string|null,cancel_reason:string,print_url:string,thermal_print_url:string,delivery_letter_url:string|null,has_delivery_letter:bool,items:list<array{description:string,quantity:float,unit_price:float,line_total:float}>}|null
     */
    private function salesInvoiceDetail(?int $companyId): ?array
    {
        $invoiceId = (int) $this->selectedSalesInvoiceDetailId;

        if ($companyId === null || $invoiceId <= 0) {
            return null;
        }

        $invoice = Invoice::query()
            ->with(['client:id,name', 'items:id,invoice_id,description,quantity,unit_price,line_total'])
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->where(function ($query): void {
                $query->where('metadata->source', 'toko_pos_counter_sale')
                    ->orWhere('metadata->source', 'quotation_conversion');
            })
            ->find($invoiceId);

        if (! $invoice) {
            return null;
        }

        $metadata = is_array($invoice->metadata) ? $invoice->metadata : [];
        $payments = collect($metadata['payments'] ?? [])->filter(fn ($payment): bool => is_array($payment))->values();
        $paymentSummary = $payments->map(function (array $payment): string {
            return trim(implode(' · ', array_filter([
                (string) ($payment['method'] ?? ''),
                (string) ($payment['bank_code'] ?? ''),
                (string) ($payment['reference'] ?? ''),
            ], fn (string $value): bool => $value !== '')));
        })->filter()->implode(' | ');

        if ($paymentSummary === '') {
            $paymentSummary = trim(implode(' · ', array_filter([
                (string) ($metadata['payment_method'] ?? ''),
                (string) ($metadata['bank_code'] ?? ''),
            ], fn (string $value): bool => $value !== ''))) ?: '-';
        }

        $deliveryLetter = DeliveryLetter::query()
            ->where('invoice_id', $invoice->id)
            ->where('metadata->source', 'toko_pos_delivery_letter')
            ->latest('id')
            ->first(['id']);

        return [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'customer' => $invoice->client?->name ?? 'Walk-in',
            'status' => $invoice->status,
            'payment_status' => (string) ($metadata['payment_status'] ?? $invoice->status),
            'payment_summary' => $paymentSummary,
            'total' => (float) $invoice->grand_total,
            'issued_at' => $invoice->issued_at?->format('d M Y'),
            'due_at' => $invoice->due_at?->format('d M Y'),
            'cancel_reason' => (string) ($metadata['cancel_reason'] ?? ''),
            'print_url' => route('admin.toko.invoices.pdf', $invoice, false),
            'thermal_print_url' => route('admin.toko.invoices.thermal', $invoice, false),
            'delivery_letter_url' => $deliveryLetter ? route('admin.toko.delivery-letters.pdf', $deliveryLetter, false) : null,
            'has_delivery_letter' => $deliveryLetter !== null,
            'items' => $invoice->items
                ->map(fn ($item): array => [
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ])
                ->all(),
        ];
    }

    /**
     * @return list<array{id:int, label:string, remaining:float}>
     */
    private function paymentInvoiceOptions(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return Invoice::query()
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->whereIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_SENT])
            ->where(function ($query): void {
                $query->where('metadata->source', 'toko_pos_counter_sale')
                    ->orWhere('metadata->source', 'quotation_conversion');
            })
            ->latest('id')
            ->limit(40)
            ->get(['id', 'number', 'grand_total', 'metadata'])
            ->map(function (Invoice $invoice): array {
                $paidTotal = (float) ($invoice->metadata['paid_total'] ?? 0);
                $remaining = max(0, round((float) $invoice->grand_total - $paidTotal, 2));

                return [
                    'id' => $invoice->id,
                    'label' => $invoice->number.' · '.Helpers::formatNumberId($remaining),
                    'remaining' => $remaining,
                ];
            })
            ->filter(fn (array $invoice): bool => $invoice['remaining'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return list<array{id:int, label:string}>
     */
    private function cancelInvoiceOptions(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return Invoice::query()
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->where('metadata->source', 'toko_pos_counter_sale')
            ->whereIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_SENT, Invoice::STATUS_PAID])
            ->latest('id')
            ->limit(40)
            ->get(['id', 'number', 'status', 'grand_total'])
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'label' => $invoice->number.' · '.$invoice->status.' · '.Helpers::formatNumberId((float) $invoice->grand_total),
            ])
            ->all();
    }

    /**
     * @return list<array{id:int, number:string, status:string, customer:string, total:float, issued_at:string|null, valid_until:string|null, print_url:string, converted:bool, rejected:bool}>
     */
    private function quotationRows(?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        $rows = $this->filteredQuotationRows($companyId);
        $pages = max(1, (int) ceil($rows->count() / 10));
        $page = min(max(1, $this->quotationPage), $pages);

        if ($page !== $this->quotationPage) {
            $this->quotationPage = $page;
        }

        return $rows
            ->slice(($page - 1) * 10, 10)
            ->values()
            ->all();
    }

    /**
     * @return array{page:int,pages:int,total:int,start:int,end:int}
     */
    private function quotationTableMeta(?int $companyId): array
    {
        if ($companyId === null) {
            return ['page' => 1, 'pages' => 1, 'total' => 0, 'start' => 0, 'end' => 0];
        }

        $total = $this->filteredQuotationRows($companyId)->count();
        $pages = max(1, (int) ceil($total / 10));
        $page = min(max(1, $this->quotationPage), $pages);

        if ($page !== $this->quotationPage) {
            $this->quotationPage = $page;
        }

        $start = $total === 0 ? 0 : (($page - 1) * 10) + 1;
        $end = $total === 0 ? 0 : min($total, $page * 10);

        return ['page' => $page, 'pages' => $pages, 'total' => $total, 'start' => $start, 'end' => $end];
    }

    private function filteredQuotationRows(int $companyId)
    {
        $search = str($this->quotationSearch)->lower()->trim()->toString();

        return Quotation::query()
            ->with('client:id,name')
            ->where('company_id', $companyId)
            ->tap(fn ($query) => $this->withSelectedBranch($query, $companyId))
            ->where('metadata->source', 'toko_pos_quotation')
            ->latest('id')
            ->get(['id', 'client_id', 'number', 'status', 'grand_total', 'issued_at', 'valid_until', 'metadata'])
            ->map(fn (Quotation $quotation): array => [
                'id' => $quotation->id,
                'number' => $quotation->number,
                'status' => $quotation->status,
                'customer' => $quotation->client?->name ?? 'Walk-in',
                'total' => (float) $quotation->grand_total,
                'issued_at' => $quotation->issued_at?->format('d M Y'),
                'valid_until' => $quotation->valid_until?->format('d M Y'),
                'print_url' => route('admin.toko.quotations.pdf', $quotation, false),
                'converted' => filled($quotation->metadata['converted_invoice_id'] ?? null),
                'rejected' => $quotation->status === Quotation::STATUS_REJECTED,
            ])
            ->filter(function (array $row) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                return str($row['number'].' '.$row['status'].' '.$row['customer'].' '.$row['issued_at'].' '.$row['valid_until'].' '.Helpers::formatNumberId($row['total']))
                    ->lower()
                    ->contains($search);
            })
            ->values();
    }
}

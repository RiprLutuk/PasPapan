import { expect, test } from '@playwright/test';

const adminEmail = process.env.E2E_ADMIN_EMAIL ?? 'superadmin@example.com';
async function login(page) {
  const loginToken = process.env.E2E_LOGIN_TOKEN;

  if (loginToken) {
    await page.goto('/login');
    const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    await page.evaluate(async ({ csrfToken, loginToken, adminEmail }) => {
      await fetch('/__e2e-login', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken ?? '' },
        body: new URLSearchParams({ token: loginToken, email: adminEmail, to: '/admin/toko' }),
      });
    }, { csrfToken, loginToken, adminEmail });
    await page.goto('/admin/toko');
    await expect(page).not.toHaveURL(/\/login$/);

    return;
  }

  throw new Error('E2E_LOGIN_TOKEN is required outside local mode.');
}

async function expectHealthyTokoPage(page, path: string, text: RegExp | string) {
  const pageErrors: string[] = [];
  const consoleErrors: string[] = [];

  page.on('pageerror', (error) => pageErrors.push(error.message));
  page.on('console', (message) => {
    if (message.type() === 'error') {
      consoleErrors.push(message.text());
    }
  });

  await page.goto(path);
  await expect(page.locator('body')).toBeVisible();
  await expect(page.locator('body')).not.toContainText(/Internal Server Error|Illuminate\\|QueryException|Undefined column|Stack trace/i);
  await expect(page.locator('body')).toContainText(text);
  await page.waitForLoadState('networkidle');

  expect(pageErrors, `${path} should not throw browser page errors`).toEqual([]);
  expect(
    consoleErrors.filter((error) => ! /favicon|Failed to load resource.*404/i.test(error)),
    `${path} should not log blocking console errors`,
  ).toEqual([]);
}

test('admin toko pages render without server or browser errors', async ({ page }) => {
  await login(page);

  for (const [path, text] of [
    ['/admin/toko', /Toko Dashboard|Transaction Command Center/i],
    ['/admin/toko/pos', /POS Counter Sale|Data Penjualan/i],
    ['/admin/toko/products', /Products|Data Barang/i],
    ['/admin/toko/customers', /Customers|Data pelanggan/i],
    ['/admin/toko/vendors', /Vendors|Supplier/i],
    ['/admin/toko/purchases', /Purchases|Pembelian/i],
    ['/admin/toko/inventory', /Inventory|Stok/i],
    ['/admin/toko/returns', /Returns|Retur/i],
    ['/admin/toko/quotations', /Quotations|Penawaran/i],
    ['/admin/toko/delivery-letters', /Delivery Letters|Surat Jalan/i],
    ['/admin/toko/cash', /Cash|Operasional/i],
    ['/admin/toko/reports', /Reports|Laporan/i],
  ] as Array<[string, RegExp | string]>) {
    await expectHealthyTokoPage(page, path, text);
  }
});

test('admin toko non destructive table controls are interactive', async ({ page }) => {
  await login(page);

  await page.goto('/admin/toko/products');
  await expect(page.locator('body')).toContainText(/Showing 1 to 10/i);
  await page.getByLabel(/Search/i).fill('sigma');
  await expect(page.locator('body')).toContainText(/Sigma|Showing/i);
  await page.getByLabel(/Search/i).fill('');
  const productNext = page.getByRole('button', { name: /^Next$/ }).last();
  await expect(productNext).toBeEnabled();
  await productNext.click();
  await expect(page.locator('body')).toContainText(/Page 2|Showing 11 to 20/i);

  await page.goto('/admin/toko/customers');
  await page.getByLabel(/Search/i).fill('wardi');
  await expect(page.locator('body')).toContainText(/WARDI|Wardi/i);

  await page.goto('/admin/toko/vendors');
  await page.getByLabel(/Search/i).fill('cempaka');
  await expect(page.locator('body')).toContainText(/Cempaka/i);

  await page.goto('/admin/toko/purchases');
  const purchaseNext = page.getByRole('button', { name: /^Next$/ }).last();
  if (await purchaseNext.isEnabled()) {
    await purchaseNext.click();
    await expect(page.locator('body')).toContainText(/Page 2|Showing 11 to 20/i);
  }

  await page.goto('/admin/toko/inventory');
  const inventoryNext = page.getByRole('button', { name: /^Next$/ }).last();
  if (await inventoryNext.isEnabled()) {
    await inventoryNext.click();
    await expect(page.locator('body')).toContainText(/Page 2|Showing 11 to 20/i);
  }
});

test('admin toko core workspaces respond without posting cutoff data', async ({ page }) => {
  await login(page);

  await page.goto('/admin/toko/pos');
  await page.getByLabel(/Scan Barcode/i).fill('BRG000714');
  await page.keyboard.press('Enter');
  await expect(page.locator('body')).toContainText(/Per Mesin cuci tbg/i);
  await page.getByLabel(/Jumlah Jual/i).fill('1');
  await page.getByRole('button', { name: /^Tambah$/ }).first().click();
  await expect(page.locator('body')).toContainText(/SKU000714/i);
  await expect(page.locator('body')).toContainText(/Total Bayar/i);
  await page.getByRole('button', { name: /Remove/i }).first().click();
  await expect(page.locator('body')).toContainText(/Cart is empty/i);

  await page.goto('/admin/toko/products');
  await page.getByRole('button', { name: /^Tambah Barang$/ }).first().click();
  await expect(page.getByPlaceholder(/Product name/i)).toBeVisible();
  await expect(page.getByPlaceholder(/SKU/i)).toBeVisible();
  await expect(page.getByRole('button', { name: /^Tambah Barang$/ }).last()).toBeVisible();

  await page.getByRole('button', { name: /^Barcode$/ }).first().click();
  await expect(page.locator('body')).toContainText(/Modul Cetak Barcode|Label Preview/i);

  await page.getByRole('button', { name: /^Kategori$/ }).click();
  await expect(page.locator('body')).toContainText(/Data Kategori|Nama Kategori/i);

  await page.getByRole('button', { name: /^Brand$/ }).click();
  await expect(page.locator('body')).toContainText(/Data Brand|Nama Brand/i);

  await page.getByRole('button', { name: /^Data Barang$/ }).click();
  const stockCardButton = page.getByRole('button', { name: /Stock Card/i }).first();
  if (await stockCardButton.isVisible()) {
    await stockCardButton.click();
    await expect(page.locator('body')).toContainText(/Stock Card|Mutasi|Movement/i);
  }
});

test('admin toko pos payment buttons and guards are operational', async ({ page }) => {
  await login(page);

  await page.goto('/admin/toko/pos');
  const unpaidButton = page.getByRole('button', { name: /Piutang \/ Tempo/i });
  const transferButton = page.getByRole('button', { name: /Transfer Bank/i });
  const cashButton = page.getByRole('button', { name: /Bayar Sekarang/i });
  const splitButton = page.getByRole('button', { name: /^Split Tender$/i });

  await unpaidButton.click();
  await expect(unpaidButton).toHaveAttribute('aria-pressed', 'true');
  await expect(page.locator('body')).toContainText(/Piutang \/ Tempo/i);

  await transferButton.click();
  await expect(transferButton).toHaveAttribute('aria-pressed', 'true');
  await expect(page.locator('body')).toContainText(/Transfer Bank/i);

  await cashButton.click();
  await expect(cashButton).toHaveAttribute('aria-pressed', 'true');
  await expect(page.locator('body')).toContainText(/Cash/i);

  await splitButton.click();
  await expect(splitButton).toHaveAttribute('aria-pressed', 'true');
  await expect(page.locator('body')).toContainText(/Tambah Pembayaran/i);

  await page.getByRole('button', { name: /Konfirmasi Pembayaran/i }).click();
  await expect(page.locator('body')).toContainText(/Add at least one item before creating the counter sale/i);

  await page.getByLabel(/Scan Barcode/i).fill('BRG000714');
  await page.keyboard.press('Enter');
  await expect(page.locator('body')).toContainText(/Per Mesin cuci tbg/i);

  await page.getByLabel(/Split tender method/i).selectOption('QRIS');
  await page.getByPlaceholder(/Amount/i).fill('1000');
  await page.getByPlaceholder(/Reference/i).fill('QR-SMOKE');
  await page.getByRole('button', { name: /Tambah Pembayaran/i }).click();
  await expect(page.locator('body')).toContainText(/QRIS/i);
  await expect(page.locator('body')).toContainText(/QR-SMOKE/i);
  await page.getByRole('button', { name: /Remove tender line/i }).click();
  await expect(page.locator('body')).not.toContainText(/QR-SMOKE/i);

  await page.getByRole('button', { name: /Buka tools admin POS/i }).click();
  await expect(page.locator('body')).toContainText(/Invoice Payments/i);
  await expect(page.locator('body')).toContainText(/Cancel Counter Sale/i);
  await expect(page.locator('body')).toContainText(/Invoice/i);

  await page.getByRole('button', { name: /Record/i }).click();
  await expect(page.locator('body')).toContainText(/Select an invoice|The selected payment invoice id field is required|Invoice/i);

  await page.getByRole('button', { name: /Tutup tools admin POS/i }).click();
  await expect(page.locator('body')).not.toContainText(/Invoice Payments/i);
});

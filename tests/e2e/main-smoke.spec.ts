import { expect, test } from '@playwright/test';

const adminEmail = process.env.E2E_ADMIN_EMAIL ?? 'apk.demo.superadmin@paspapan.test';
const adminPassword = process.env.E2E_ADMIN_PASSWORD ?? '12345678';
const userEmail = process.env.E2E_USER_EMAIL ?? 'apk.demo.user@paspapan.test';
const userPassword = process.env.E2E_USER_PASSWORD ?? '12345678';
async function login(page, email: string, password: string) {
  const loginToken = process.env.E2E_LOGIN_TOKEN;

  if (loginToken) {
    await page.goto('/login');
    const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    await page.evaluate(async ({ csrfToken, email, loginToken }) => {
      await fetch('/__e2e-login', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken ?? '' },
        body: new URLSearchParams({ token: loginToken, email, to: '/home' }),
      });
    }, { csrfToken, email, loginToken });
    await page.goto('/home');
    await expect(page).not.toHaveURL(/\/login$/);

    return;
  }

  await page.goto('/login');
  await page.locator('input[name="email"]').fill(email);
  await page.locator('input[name="password"]').fill(password);
  await page.getByRole('button', { name: /log in|login|masuk/i }).click();
  await expect(page).not.toHaveURL(/\/login$/);
}

async function expectHealthyPage(page, path: string) {
  await page.goto(path);
  await expect(page.locator('body')).toBeVisible();
  await expect(page.locator('body')).not.toContainText(/server error|exception|stack trace/i);
}

test('public login page renders', async ({ page }) => {
  await page.goto('/login');
  await expect(page.locator('input[name="email"]')).toBeVisible();
  await expect(page.locator('input[name="password"]')).toBeVisible();
});

test('admin smoke covers RBAC menus approvals attendance QR HR payroll import export backup health', async ({ page }) => {
  await login(page, adminEmail, adminPassword);

  for (const path of [
    '/admin/dashboard',
    '/admin/inbox',
    '/admin/employees',
    '/admin/attendances',
    '/admin/barcodes',
    '/admin/import-export/users',
    '/admin/import-export/attendances',
    '/admin/leaves',
    '/admin/overtime',
    '/admin/hr-checklists',
    '/admin/reimbursements',
    '/admin/manage-kasbon',
    '/admin/payrolls',
    '/admin/reports',
    '/admin/system-maintenance',
    '/admin/operational-health',
  ]) {
    await expectHealthyPage(page, path);
  }
});

test('user smoke covers attendance check in out leave overtime reimbursement payslip documents approvals HR tasks', async ({ page }) => {
  await login(page, userEmail, userPassword);

  for (const path of [
    '/home',
    '/scan',
    '/attendance-history',
    '/apply-leave',
    '/overtime',
    '/reimbursement',
    '/my-kasbon',
    '/payroll',
    '/document-requests',
    '/hr-tasks',
    '/approvals',
  ]) {
    await expectHealthyPage(page, path);
  }
});

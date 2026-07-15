import { test, expect } from '@playwright/test';

/**
 * Admin logs in, searches the customer list, and opens a customer's order
 * history. Read-only flow — no records are created or mutated.
 */

const ADMIN_EMAIL = 'owner@ezjual.test';
// Seeded local dev credential; override via env for other environments.
const ADMIN_PASSWORD = process.env.EZJUAL_ADMIN_PASSWORD ?? 'password';

test('admin searches customers and views a customer\'s order history', async ({ page }) => {
    await page.goto('/login');
    await page.getByRole('textbox', { name: 'Email address' }).fill(ADMIN_EMAIL);
    await page.getByRole('textbox', { name: 'Password' }).fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);

    await page.goto('/sales/customers');
    await page.getByRole('textbox', { name: 'Cari nama atau no. telefon...' }).fill('Ali');

    const customerLink = page.getByRole('link', { name: 'Ali Bin Ahmad' });
    await expect(customerLink).toBeVisible();

    // The search narrows the table down to just the matching customer.
    await expect(page.getByRole('row')).toHaveCount(2); // header row + the one match

    await customerLink.click();
    await expect(page).toHaveURL(/\/sales\/customers\/\d+$/);
    await expect(page.getByText('Ali Bin Ahmad')).toBeVisible();
    await expect(page.getByText('No. Telefon: 012-3456789')).toBeVisible();

    await expect(page.getByText('Sejarah Pesanan')).toBeVisible();
    const orderRows = page.getByRole('row').filter({ hasText: 'ORD-' });
    await expect(orderRows.first()).toBeVisible();
});

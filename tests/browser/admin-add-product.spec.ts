import { test, expect } from '@playwright/test';

/**
 * Admin logs in and creates a new product via the "Produk Baharu" modal on
 * the products page, then confirms the save toast and the new row.
 *
 * This hits the real app database (no RefreshDatabase) — every run inserts
 * a new product row with the same name below, same as clicking through the
 * app by hand. Re-running repeatedly will create duplicate "Ayam Percik
 * Special" rows; delete them via the "Padam" button or the DB if the list
 * gets cluttered.
 */

const ADMIN_EMAIL = 'owner@ezjual.test';
// Seeded local dev password; override via env for other environments.
const ADMIN_PASSWORD = process.env.EZJUAL_ADMIN_PASSWORD ?? 'password';

const PRODUCT_NAME = 'Ayam Percik Special';
const PRODUCT_DESCRIPTION = 'Ayam percik bakar dengan sos kelapa istimewa, disajikan dengan nasi himpit.';
const PRODUCT_PRICE = '12.50';
const PRODUCT_STOCK = '30';

test('admin creates a new product', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(ADMIN_EMAIL);
    await page.getByRole('textbox', { name: 'Password' }).fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);

    await page.goto('/sales/products');
    await page.getByRole('button', { name: 'Produk Baharu' }).click();

    await page.getByRole('textbox', { name: 'Nama Produk' }).fill(PRODUCT_NAME);
    await page.getByRole('textbox', { name: 'Penerangan' }).fill(PRODUCT_DESCRIPTION);
    await page.getByRole('spinbutton', { name: 'Harga (RM)' }).fill(PRODUCT_PRICE);
    await page.getByRole('spinbutton', { name: 'Stok' }).fill(PRODUCT_STOCK);
    // "Aktif" switch is on by default — left untouched.

    await page.getByRole('button', { name: 'Simpan' }).click();

    await expect(page.getByText('Produk berjaya disimpan.')).toBeVisible();

    const productRow = page.getByRole('row', { name: new RegExp(`^${PRODUCT_NAME}`) });
    await expect(productRow).toBeVisible();
    await expect(productRow.getByText(`RM${PRODUCT_PRICE}`)).toBeVisible();
    await expect(productRow.getByText(PRODUCT_STOCK, { exact: true })).toBeVisible();
    await expect(productRow.getByText('Aktif', { exact: true })).toBeVisible();
});

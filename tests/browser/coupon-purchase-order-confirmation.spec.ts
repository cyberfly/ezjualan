import { test, expect, type Locator } from '@playwright/test';

/**
 * End-to-end flow: guest buys a product using a coupon, an admin confirms
 * the order, then both the dashboard and the coupon list are checked to
 * reflect the new order / increased coupon usage.
 *
 * This hits the real app database (no RefreshDatabase) — each run creates a
 * new customer/order and permanently increments the "FREE" coupon's
 * used_count, same as the manual click-through it was generated from.
 */

const ADMIN_EMAIL = 'owner@ezjual.test';
// Seeded local dev password; override via env for other environments.
const ADMIN_PASSWORD = process.env.EZJUAL_ADMIN_PASSWORD ?? 'password';

const PRODUCT_SLUG = 'roti-canai';
const COUPON_CODE = 'FREE';

test('guest purchase with coupon is confirmed by admin and reflected in dashboard + coupon usage', async ({ page }) => {
    // --- Admin: read baseline coupon usage before the purchase ---
    await page.goto('/login');
    await page.getByLabel('Email address').fill(ADMIN_EMAIL);
    await page.getByRole('textbox', { name: 'Password' }).fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);

    await page.goto('/sales/coupons');
    const couponRow = page.getByRole('row', { name: new RegExp(`^${COUPON_CODE}\\b`) });
    const usageBefore = await readCouponUsage(couponRow);

    // Log out of the admin session so the purchase below runs as a guest,
    // matching the original flow (public /pesan/{slug} page needs no auth).
    await page.context().clearCookies();

    // --- Guest: browse to the product and place an order using the coupon ---
    await page.goto(`/pesan/${PRODUCT_SLUG}`);

    await page.getByLabel('Kod Kupon (pilihan)').fill(COUPON_CODE);
    await page.getByRole('button', { name: 'Guna' }).click();
    await expect(page.getByText(`${COUPON_CODE} digunakan`)).toBeVisible();

    await page.getByLabel('Nama Penuh').fill('Ali Bin Ahmad');
    await page.getByLabel('No. Telefon').fill('012-3456789');
    await page.getByRole('button', { name: 'Hantar Tempahan' }).click();

    // Redirected to /pesanan/{order_number} on success.
    await expect(page).toHaveURL(/\/pesanan\/(ORD-[\w-]+)/);
    await expect(page.getByText('Terima kasih! Pesanan anda telah diterima.')).toBeVisible();

    const orderNumber = new URL(page.url()).pathname.split('/').pop() as string;
    await expect(page.getByText('Menunggu')).toBeVisible();

    // --- Admin: log back in and confirm the new order ---
    await page.goto('/login');
    await page.getByLabel('Email address').fill(ADMIN_EMAIL);
    await page.getByRole('textbox', { name: 'Password' }).fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);

    await page.goto('/sales/orders');
    await page.getByRole('link', { name: orderNumber }).click();
    await expect(page).toHaveURL(new RegExp(`/sales/orders/${orderNumber}$`));

    page.once('dialog', (dialog) => dialog.accept());
    await page.getByRole('button', { name: 'Disahkan' }).click();

    // The action button becomes the next allowed transition once confirmed.
    await expect(page.getByRole('button', { name: 'Dihantar' })).toBeVisible();
    await expect(page.getByText('Status pesanan telah dikemaskini.')).toBeVisible();

    // --- Admin: dashboard shows the order as confirmed ---
    await page.goto('/dashboard');
    const dashboardRow = page.getByRole('row', { name: new RegExp(orderNumber) });
    await expect(dashboardRow).toBeVisible();
    await expect(dashboardRow.getByText('Disahkan')).toBeVisible();

    // --- Admin: coupon usage increased by exactly one ---
    await page.goto('/sales/coupons');
    const usageAfter = await readCouponUsage(couponRow);
    expect(usageAfter).toBe(usageBefore + 1);
});

/**
 * Parses the "Penggunaan" cell (e.g. "2 / ∞" or "2 / 50") into its used count.
 */
async function readCouponUsage(row: Locator): Promise<number> {
    const cells = (await row.getByRole('cell').allTextContents()).map((text) => text.trim());
    const usageCell = cells.find((text: string) => /^\d+\s*\//.test(text));

    if (!usageCell) {
        throw new Error(`Could not find a usage cell (e.g. "2 / ∞") in row: ${cells.join(', ')}`);
    }

    return Number.parseInt(usageCell, 10);
}

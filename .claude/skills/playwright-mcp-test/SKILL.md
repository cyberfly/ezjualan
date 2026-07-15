---
name: playwright-mcp-test
description: "Turns a Playwright MCP browser-automation session (login, checkout, form-fill, admin workflows) into a standalone, deterministic @playwright/test .spec.ts file for this Laravel/Herd/Flux UI app, so the flow can be replayed instantly next time without AI. Use this whenever the user asks to save, record, or turn a Playwright MCP session into a test or script, wants a browser flow made repeatable, or right after a multi-step Playwright MCP task (guest checkout, admin login + approve, coupon usage, etc.) succeeds and re-running AI for the same flow next time would be wasteful. Also trigger if the user mentions '.spec.ts', 'playwright test script', or asks to regression-test a UI flow we just clicked through by hand. This is specific to this repo's stack (Herd-served Laravel app, Livewire/Flux UI components, Pest test suite) — it knows the Flux UI locator gotchas and where to put files so Pest doesn't pick them up."
---

# Playwright MCP session → @playwright/test script

Converts a successful Playwright MCP browser session into a real `@playwright/test` spec. The MCP tools are great for exploring and getting a flow *right*, but every re-run costs AI tokens and time and can drift if the model takes a slightly different path. Once a flow works, capture it as code — a plain script that runs the same way every time in seconds.

## When this applies

- The user just finished (or is finishing) a flow using the Playwright MCP tools (`browser_navigate`, `browser_click`, `browser_type`, `browser_fill_form`, `browser_select_option`, `browser_handle_dialog`, etc.) and it worked — a redirect, success toast, or new page state confirmed it.
- The user says "save this", "make a test out of this", "turn this into a script", or similar.
- This app is Laravel + Livewire + Flux UI, served by Herd at a `.test` domain, tested with Pest. Keep that in mind when picking locators and file locations (see below) — generic Playwright advice misses this stack's specific traps.

## Step 1 — Make sure the project can actually run the script

Before writing anything, check these exist:

```bash
npm ls @playwright/test 2>&1 | head -3
find . -maxdepth 1 -iname "playwright.config.*"
```

If either is missing, **ask the user before installing** (this project's conventions call for approval before dependency changes) — don't silently add packages. Once approved:

```bash
npm install -D @playwright/test
npx playwright install chromium
```

Then create `playwright.config.ts` at the repo root if it doesn't exist:

```ts
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/browser',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    reporter: 'list',
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://<project>.test',
        trace: 'on-first-retry',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
```

Get the actual `.test` domain from `get-absolute-url` (Boost) rather than guessing.

## Step 2 — Where to save the spec

Use **`tests/browser/<slug>.spec.ts`** — lowercase, not `tests/Browser/`. This matters here specifically: Pest's native browser testing (see the `pest-testing` skill) also uses a `tests/Browser/` directory with PHP specs, and PHPUnit/Pest scan `tests/Unit` and `tests/Feature` per `phpunit.xml`. `tests/browser/` (lowercase) keeps the generated JS/TS specs out of both of those worlds — check `phpunit.xml`'s `<testsuites>` if the project structure looks different before assuming this is still safe.

`<slug>` is a kebab-case name describing the flow (e.g. `guest-checkout-with-coupon`, `admin-approves-order`), not `eval-0` or similar.

## Step 3 — Reconstruct the flow as a script

While replaying the MCP session in your head (or from the transcript), log every meaningful action by its **accessible role + name** as shown in `browser_snapshot` output — never the MCP `ref` (e.g. `"e12"`), which only exists inside that MCP session and means nothing to a standalone script.

See [references/locator-mapping.md](references/locator-mapping.md) for the full MCP-tool → Playwright-API table and locator priority order. Collapse trial-and-error (typos fixed, wrong buttons clicked) into the single correct path — the script should reflect what *should* happen, not the exploration that got you there.

### Gotchas specific to this stack

These are not generic Playwright advice — they come from Flux UI's actual markup and this app's Livewire patterns, and will bite you if skipped:

- **Password fields resolve to 2 elements with `getByLabel`.** Flux's password input renders the `<input>` and a "Toggle password visibility" `<button>` under the same label group, so `page.getByLabel('Password')` throws a strict-mode violation (matches both). Use `page.getByRole('textbox', { name: 'Password' })` instead — it only matches the input.
- **`<flux:heading>` does not expose an accessible `heading` role.** It renders as a plain styled element, not an `<h1>`–`<h6>`, so `page.getByRole('heading', { name: '...' })` won't find it even though it looks and reads like a heading. Use `page.getByText('...')` for section titles rendered with `<flux:heading>` (confirm via `browser_snapshot` — if it shows up as `generic` rather than `heading`, that's this case).
- **Table cell text has stray whitespace.** Flux table cells (`<flux:table.cell>`) render with surrounding newlines/indentation. If you're parsing a cell's value (e.g. `"2 / ∞"` for a usage count), always `.trim()` the text before matching a regex against it — an anchored `/^\d+/` will silently fail to match `"\n    2 / ∞\n"`.
- **Destructive/status-changing buttons use `wire:confirm`**, which fires a native browser `confirm()` dialog, not a custom modal. Register the handler *before* the click that triggers it: `page.once('dialog', (d) => d.accept())`, then `await page.getByRole('button', { name: '...' }).click()`.
- **Guest (public) routes need no auth.** If a flow mixes an authenticated admin part with a guest-facing part (e.g. catalog/checkout pages under `routes/catalog.php` have no `auth` middleware), and the original MCP session tested the guest part as truly logged-out, clear cookies (`await page.context().clearCookies()`) before that section rather than letting an admin session leak into it.
- **Secrets**: never hardcode a password/token literal in the spec. Read it from `process.env.SOME_VAR` with a fallback to the known local dev seed value and a comment explaining it's a seeded dev credential, e.g.:
  ```ts
  const ADMIN_PASSWORD = process.env.EZJUAL_ADMIN_PASSWORD ?? 'password'; // seeded local dev credential
  ```
- **Dynamic identifiers** (order numbers, generated codes) can't be hardcoded since a re-run produces new ones. Capture them at runtime instead — e.g. `new URL(page.url()).pathname.split('/').pop()` after a redirect — and reuse that captured value in later assertions/locators.

### Template skeleton

```ts
import { test, expect } from '@playwright/test';

test('<slug in words>', async ({ page }) => {
    await page.goto('<path>');

    await page.getByLabel('<field label>').fill('<value>');
    await page.getByRole('button', { name: '<button>' }).click();

    await expect(page.getByText('<success text observed in the MCP session>')).toBeVisible();
});
```

If the flow spans multiple pages or roles (guest → admin), split it into commented sections per page/step rather than one dense block — it reads like the story of what happened.

## Step 4 — Verify it actually passes standalone

```bash
scripts/verify-test.sh <path-to-spec>
```

(bundled in this skill — run from the repo root). It shells out to `npx playwright test <spec> --reporter=list`.

If it fails, the error almost always means a locator's accessible name doesn't exactly match what's rendered (whitespace, icon-only buttons changing the name, ambiguous matches like the password case above). Fix and re-verify. After 2 failed fix attempts on the same locator, stop and show the user the failure plus the script as-is instead of guessing further.

**Heads up on side effects**: unlike Pest's `RefreshDatabase` feature tests, this script hits the real app and real database (no test DB by default) — every run creates real records and can mutate real state (stock, coupon usage, order counts). That's often exactly the point (verifying the real integration), but say so explicitly when handing the script back, and consider whether a dedicated test coupon/product/account should be used instead of production-adjacent seed data if this will run repeatedly (e.g. in CI).

## Step 5 — Report back

Tell the user:
- The saved file path
- The exact rerun command: `npx playwright test tests/browser/<slug>.spec.ts`
- Any side effects the run causes (new DB records, incremented counters) so they know what to expect on repeat runs
- That next time, running the script directly skips the AI/MCP exploration entirely

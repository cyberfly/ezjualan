# Browser Tests (Playwright)

End-to-end browser tests using `@playwright/test`. These run against the real
app (Laravel Herd, `http://ezjual.test`) and hit the real database — no
`RefreshDatabase`. Each run creates real records (orders, customers) and
permanently increments coupon usage, same as clicking through the app by hand.

Config: `playwright.config.ts` · Spec files: `tests/browser/`

## Run all browser tests

```bash
npx playwright test
```

## Run a specific spec

```bash
npx playwright test tests/browser/coupon-purchase-order-confirmation.spec.ts
```

## Useful flags

```bash
npx playwright test --headed        # watch it run in a real browser window
npx playwright test --debug         # step through with the Playwright inspector
npx playwright show-report          # open the HTML report from the last run
```

## Config

- `EZJUAL_ADMIN_PASSWORD` — admin password used by tests (defaults to the local
  dev seed password if unset).
- `PLAYWRIGHT_BASE_URL` — override the base URL (defaults to `http://ezjual.test`).

## Specs

- `coupon-purchase-order-confirmation.spec.ts` — guest buys a product using a
  coupon, admin confirms the order, then checks the dashboard shows the new
  order and the coupon's usage count increased by one.
- `admin-search-customer-details.spec.ts` — admin searches the customer list
  and opens a customer's order history. Read-only, no side effects.

## Generating new specs

Use the `playwright-mcp-test` skill (`.claude/skills/playwright-mcp-test/`) to
turn a Playwright MCP browser session into a new spec here — it knows this
project's Flux UI locator gotchas and where files should go.

# Reference: MCP session → Playwright Test

## Locator priority (most to least stable)

1. `getByRole(role, { name })` — from the accessible role + accessible name shown in `browser_snapshot`
2. `getByLabel(text)` — form fields with an associated `<label>` (but see the Password gotcha in SKILL.md — verify it resolves to exactly one element before using it)
3. `getByPlaceholder(text)`
4. `getByTestId(id)` — only if the snapshot/HTML shows a `data-testid` (or configured test-id attribute)
5. `getByText(text)` — exact text match; last resort, and only for elements with stable, non-dynamic text
6. CSS/XPath selector — avoid; use only when nothing above uniquely identifies the element

Never use the MCP tool's internal `ref` (e.g. `"e12"`) in the generated script. It is an id into that specific `browser_snapshot` call and does not exist outside the MCP session.

## MCP tool → Playwright Test API mapping

| MCP tool call | Playwright Test equivalent |
|---|---|
| `browser_navigate(url)` | `await page.goto(url)` |
| `browser_click(element, ref)` | `await page.getByRole(role, { name }).click()` |
| `browser_type(element, ref, text)` | `await page.getByRole(role, { name }).fill(text)` |
| `browser_fill_form(fields[])` | one `.fill()` / `.check()` / `.selectOption()` per field |
| `browser_select_option(element, ref, values)` | `await page.getByRole('combobox', { name }).selectOption(values)` |
| `browser_press_key(key)` | `await page.keyboard.press(key)` |
| `browser_wait_for({ text })` | `await expect(page.getByText(text)).toBeVisible()` |
| `browser_wait_for({ textGone })` | `await expect(page.getByText(textGone)).toBeHidden()` |
| `browser_hover(element, ref)` | `await page.getByRole(role, { name }).hover()` |
| `browser_drag(startRef, endRef)` | `await source.dragTo(target)` |
| `browser_file_upload(paths)` | `await page.getByLabel(name).setInputFiles(paths)` |
| `browser_handle_dialog({ accept })` | `page.once('dialog', (d) => d.accept())` registered *before* the triggering click (see `wire:confirm` gotcha) |
| `browser_snapshot` (used to confirm state) | `await expect(locator).toBeVisible()` / `.toHaveValue()` / `.toHaveURL()` as appropriate |

Collapse consecutive MCP calls that target the same field (e.g. several `browser_type` calls while correcting a typo) into a single final `.fill()` — the script should reflect the *correct* path, not the trial-and-error.

## Reading table cell values

Flux `<flux:table>` cells carry surrounding whitespace/newlines in their text content. When extracting a value to assert on or compare (e.g. a usage counter like `"2 / ∞"`):

```ts
const cells = (await row.getByRole('cell').allTextContents()).map((t) => t.trim());
const usageCell = cells.find((t) => /^\d+\s*\//.test(t));
```

Always `.trim()` before pattern-matching — an anchored regex against untrimmed text silently fails.

## Capturing dynamic identifiers

Order numbers, generated codes, and similar values differ on every run. Capture them at the point they're first shown instead of hardcoding:

```ts
await expect(page).toHaveURL(/\/pesanan\/(ORD-[\w-]+)/);
const orderNumber = new URL(page.url()).pathname.split('/').pop() as string;
// ...later...
await page.getByRole('link', { name: orderNumber }).click();
```

---
name: create-user-manual
description: "Generates a step-by-step user manual (in Bahasa Melayu, markdown) for a feature or flow in the ezjual app by actually driving the app with the Playwright MCP browser tools and screenshotting each meaningful step. Use this whenever the user asks to buat/jana/hasilkan panduan pengguna, user manual, user guide, how-to guide, or step-by-step documentation with screenshots for a feature, page, or flow in this app — e.g. 'buatkan user manual macam mana nak tambah produk', 'generate a guide for the checkout flow with screenshots', 'saya nak dokumen panduan admin approve order'. Also trigger if the user mentions wanting a manual/guide that includes real screenshots of the app rather than just written text, or references SOP/panduan kerja documentation for staff/customers. This is specific to this app — it knows the Herd `.test` domain, the admin/customer roles, and where to save the manual (docs/user-manual/). Not for turning a session into a reusable @playwright/test script (use playwright-mcp-test for that) and not for plain-language flow explanations without screenshots (use explain-human for that)."
---

# Create User Manual

Produces a real, usable user manual — the kind a staff member or customer could follow without ever seeing the code — by physically walking through the flow in a browser with the Playwright MCP tools and capturing a screenshot at each meaningful step. The output is a markdown file with embedded screenshots, written in casual Bahasa Melayu, saved under `docs/user-manual/`.

The point of actually driving the browser (instead of writing generic instructions from memory) is accuracy: button labels, page order, and confirmation messages drift as the app changes, and a manual with a wrong button name is worse than no manual. Every screenshot and every instruction in the output should come from something you actually saw on screen this run.

## Step 1 — Pin down the flow, the role, and starting data

If the user named a clear flow ("macam mana nak tambah produk baru", "guest checkout with coupon", "admin approve pesanan"), go straight to Step 2. If it's vague ("buat manual untuk app ni"), ask them to name one concrete flow — one manual per flow, done properly, not the whole app crammed into one document.

Work out before you start:
- **Role**: guest/customer, or an authenticated admin? If admin, you'll need to log in first — check `routes/*.php` and existing seeded credentials (same convention as the `playwright-mcp-test` skill: read from `process.env.EZJUAL_ADMIN_PASSWORD` with a fallback to the known local dev seed value, e.g. `password` — never hardcode a real secret in the manual itself).
- **Starting data**: does the flow need an existing product, order, or coupon to exist first? If nothing suitable exists, create realistic dummy data as part of the walkthrough rather than asking the user to pre-seed it — and say so plainly in the manual's intro, since this app has no separate test database and the walkthrough will leave real-looking records behind (e.g. "Panduan ini guna produk contoh 'Kemeja Testing' yang dicipta semasa demo").

## Step 2 — Get the real URL, don't guess it

Use the Boost `get-absolute-url` tool to resolve the correct `.test` domain and path before navigating. Never hand-construct or guess the domain.

## Step 3 — Walk the flow, screenshotting each meaningful step

Drive the app with the Playwright MCP tools (`browser_navigate`, `browser_snapshot`, `browser_click`, `browser_type`, `browser_fill_form`, `browser_select_option`, `browser_handle_dialog`, etc.), using `browser_snapshot` to find the correct element before every action — same as any Playwright MCP session. This app is Livewire + Flux UI; if you hit anything surprising (a password field matching twice, a native `confirm()` dialog from `wire:confirm`), see the gotchas list in [playwright-mcp-test/SKILL.md](../playwright-mcp-test/SKILL.md) — same stack, same traps.

A manual is not a frame-by-frame recording. Screenshot the moments a reader actually needs to see to follow along and know they're on the right track — typically:
- The starting page/state before the action
- A filled-in form right before submitting (so the reader can compare their own input)
- The result: success message, new page, or updated list

Skip screenshotting pure navigation clicks with no visible decision or result. A typical flow runs **5-12 screenshots**; if you're well past that, you're probably narrating clicks instead of steps — group them under fewer, more meaningful captures instead.

Call `browser_take_screenshot` with `filename` set directly to the final project-relative path — e.g. `filename: "docs/user-manual/<slug>/images/01-senarai-produk.png"`. Playwright writes the file straight there and creates any missing parent directories itself, so there's no separate copy/move step needed; the tool's response confirms the path it wrote to, which should match what you asked for.

Number images in the order they'll appear in the manual (`01-`, `02-`, ...) and give each one a short description of what it shows — that's what makes the markdown easy to write and easy to re-check afterward.

## Step 4 — Write the manual

Save to `docs/user-manual/<slug>.md`, where `<slug>` is a short kebab-case name for the flow (`tambah-produk-baru.md`, `guest-checkout-kupon.md`, `admin-lulus-pesanan.md`) — screenshots live one level down in `docs/user-manual/<slug>/images/`, so image links in the markdown are `<slug>/images/01-...png`, not just `images/01-...png`. Write in casual Bahasa Melayu — the same register as this project's `explain-human` skill uses, not stiff textbook Malay — since the reader is a staff member or customer, not a developer.

```markdown
# [Nama flow, dalam ayat mudah]

## Sebelum mula
[Apa yang reader perlukan dulu: role/akaun, kebenaran akses, data sedia ada
yang diperlukan (contoh: mesti ada sekurang-kurangnya satu produk). Nyatakan
kalau panduan ni guna data contoh/demo.]

## Langkah demi langkah

### 1. [Tindakan pertama dalam ayat mudah]
[Arahan ringkas, imperative — "Klik butang 'Tambah Produk' di penjuru kanan atas."]

![Langkah 1: <apa yang screenshot ni tunjukkan>](<slug>/images/01-<deskripsi>.png)

### 2. [Tindakan seterusnya]
...

## Selesai
[Apa reader patut nampak bila berjaya — mesej kejayaan, keadaan akhir.]

## Masalah lazim (kalau ada)
[Optional — hanya kalau kau nampak sendiri satu error/edge case semasa walkthrough,
contoh mesej validation bila field kosong. Jangan reka-reka masalah yang tak diuji.]
```

Each numbered step gets exactly one screenshot placed right after its instruction — don't batch several steps under one image, and don't caption an image with anything you didn't actually verify happened.

## Step 5 — Maintain the index

Maintain `docs/user-manual/README.md` as a running index, same pattern as `docs/explain/README.md`. Create it on first use with a one-line intro, append one bullet per manual on every subsequent run:

```markdown
# Panduan Pengguna (User Manual)

Senarai panduan pengguna app ni, dihasilkan guna skill `create-user-manual`.

- [Macam mana nak tambah produk baru](tambah-produk-baru.md)
```

## Step 6 — Report back

Per this project's standing instruction, always close a Playwright MCP task with a summary of what you did and what came out of it. Tell the user:
- The manual's file path and the number of screenshots captured
- That screenshots render automatically when the markdown is opened in VS Code or viewed on GitHub — no extra tooling needed
- Any real data the walkthrough created or changed (new product, order, coupon usage) — since this hits the real app and database, not a sandboxed test DB
- If anything in the flow surprised you (a validation message, an unexpected redirect) that you documented in "Masalah lazim" — flag it explicitly so they know it's a real observation, not a guess

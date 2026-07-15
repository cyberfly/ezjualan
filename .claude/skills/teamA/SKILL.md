---
name: teamA
description: "Runs a combined 'team review' by calling the three project-specific review subagents in parallel — security-reviewer, database-reviewer, and code-smell — then merges their findings into one report. Use this whenever the user explicitly invokes it by name: '/teamA', 'guna teamA', 'run teamA review', 'team review', 'jalankan teamA kat branch ni', or asks for a full review covering security, database, and code quality all at once before a PR or merge. Do not trigger automatically just because code changed — this only runs when the user names it or clearly asks for all three review angles together. For a single angle only, use the individual agent instead (security-reviewer, database-reviewer, or code-smell) rather than this skill."
---

# teamA

A combined review pass that runs this project's three specialist review agents — `security-reviewer`, `database-reviewer`, `code-smell` — at the same time, then merges what they find into one report. Think of it as convening the whole review team at once instead of asking each reviewer one after another.

The reason to run them together rather than sequentially: none of the three depend on each other's findings (a security issue doesn't change what a schema issue looks like), so there's no benefit to making the user wait for them in series. Running them in parallel gets a full review back in the time of the slowest single agent, not the sum of all three.

## Step 1 — Work out the scope

If the user named specific files, a PR, or a branch, use that scope for all three agents.

Otherwise, default to the current branch's changes:
```bash
git status --short
git diff main...HEAD --stat
```
Pass the same scope description to all three agents so they're reviewing the same surface — don't let one agent default to a different scope than the others.

## Step 2 — Launch all three agents in parallel

Call the `Agent` tool three times **in the same message** (not one after another) — this is what makes it parallel instead of sequential:

- `subagent_type: security-reviewer` — brief it on the scope (changed files / diff) and ask it to review per its own definition.
- `subagent_type: database-reviewer` — same scope, focused on schema/relationships/migrations.
- `subagent_type: code-smell` — same scope, focused on best practices and project conventions.

Each agent already knows its own domain and output format from its own definition (`.claude/agents/security-reviewer.md`, `database-reviewer.md`, `code-smell.md`) — the prompt here just needs to hand it the scope, not re-explain what to look for.

## Step 3 — Merge into one team report

Wait for all three to finish, then present a single combined report — don't just paste three separate agent outputs back to back, actually merge them so the user gets one prioritized view:

```
# Team Review — <scope>

## Summary
<one line per agent: e.g. "Security: 1 critical, 0 high. Database: 0 issues. Code quality: 2 medium.">
<one overall line: e.g. "1 blocking issue before merge (security)." or "No blocking issues.">

## 🔒 Security (security-reviewer)
<that agent's findings verbatim, or "No issues found.">

## 🗄️ Database (database-reviewer)
<that agent's findings verbatim, or "No issues found.">

## 🧹 Code quality (code-smell)
<that agent's findings verbatim, or "No issues found.">
```

Order findings within the summary by real severity across all three agents combined (a Critical from security-reviewer outranks a Medium from code-smell), so the user knows what to fix first without having to cross-reference three sections themselves.

## Notes

- All three agents are read-only reviewers — they report, they don't edit code. teamA doesn't apply any fixes either; if the user wants fixes applied after reviewing the report, that's a separate, explicit follow-up step.
- If the user's scope is empty (no changes on the branch), say so and skip spawning the agents rather than running a review with nothing to review.

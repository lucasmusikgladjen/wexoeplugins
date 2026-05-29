# wexoebuilder PR#54 mergeability fixes

This repo checkout is `wexoeplugins`; shell access to `github.com/lucasmusikgladjen/wexoebuilder.git` was blocked by the environment proxy (`CONNECT tunnel failed, response 403`). I therefore prepared a patch file that can be applied on top of PR#54's head branch `codex/evaluate-website-structure-post-airtable-migration`.

## What the patch fixes

1. **Product Area section copy safety**
   - Strips managed backlink fields such as `product_page_ids` before creating copied section records.
   - Strips parent `section_ids` from the copied Product Area and then writes only the fresh section IDs.
   - Keeps Product/Solution links shared by reference, matching the PR's intended v1 semantics.
   - Keeps Product Area writes snake_case-only (`name`, `slug`, `section_ids`).

2. **Landing create preflight validation**
   - Validates `_tabClientIndex` download references before the first Airtable create, reducing partial LP/tab records when Claude returns invalid download references.

3. **Landing update deletion safety**
   - Deletes downloads belonging to tabs that are removed before deleting those tabs.
   - Counts those deletions in `downloadsDeleted` so the API response remains truthful.

4. **Landing delete helper reuse**
   - Reuses the same download-ID collection helper for full landing deletes, reducing divergent relation cleanup logic.

## Apply command in wexoebuilder

```bash
git checkout codex/evaluate-website-structure-post-airtable-migration
git apply /path/to/reviews/wexoebuilder-pr54-mergeable-fixes.patch
npm test
npm run build
npx tsc --noEmit
```

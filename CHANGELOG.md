# Changelog

All notable changes to this project are documented here.

## 0.1.0

- Initial release.
- Upgrades the native Media Library search field with typo-tolerant, content-aware search (intercepts `s` in grid and list views).
- Dedicated Loupe-backed media index (title, filename, alt text, caption, description), separate from Loupe Search's post indexes.
- Folder-aware: search is scoped to the selected folder, or the whole library via "All Media".
- Automatic incremental indexing on upload, edit, delete, and folder change.
- Background full rebuild via Action Scheduler, with status and a "Rebuild media index" button in **VMF Settings → Search**.
- REST API: `index-status`, `rebuild`, and `search` under `vmfa-search/v1`.
- Graceful fallback to native WordPress search until the index is built, and when the Loupe Search plugin is inactive.

# Changelog

All notable changes to this project are documented here.

## 1.0.1

- Fixed: Prevent a fatal error when the "Virtual Media Folders" parent plugin is missing or older than 2.0.0; show an admin notice instead.

## 1.0.0

- Initial release.
- Upgrades the native Media Library search field with typo-tolerant, content-aware search (intercepts `s` in grid and list views).
- Dedicated Loupe-backed media index (title, filename, alt text, caption, description), separate from Loupe Search's post indexes.
- Library-wide: entering a search term searches the whole library, even while a folder is selected.
- Automatic incremental indexing on upload, edit, delete, and folder change.
- Background full rebuild via Action Scheduler, with status and a "Rebuild media index" button in **VMF Settings → Search**.
- REST API: `index-status`, `rebuild`, and `search` under `vmfa-search/v1`.
- Graceful fallback to native WordPress search until the index is built, and when the Loupe Search plugin is inactive.

# Virtual Media Folders - Search

Fast, typo-tolerant search for the WordPress Media Library, powered by the [Loupe Search](https://github.com/soderlind/loupe-search) engine. Add-on for [Virtual Media Folders](https://github.com/soderlind/virtual-media-folders).

## What it does

Supercharges the **native** Media Library search field. Type in the standard
search box and results are matched against title, filename, alt text, caption,
and description — with typo tolerance — instead of WordPress' default `LIKE`
search. No extra UI is added.

Search composes with folders: when a folder is selected, results are scoped to
that folder; select **All Media** to search the whole library.

## Requirements

- PHP 8.3+
- [Virtual Media Folders](https://github.com/soderlind/virtual-media-folders) (active)
- [Loupe Search](https://github.com/soderlind/loupe-search) (active — provides the search engine)

## How it works

The add-on owns a dedicated Loupe/SQLite index of media items (`wp-content/vmfa-search-db/`), kept separate from Loupe Search's own post-type indexes. See [docs/adr/0001](docs/adr/0001-vmfa-search-owns-a-dedicated-media-index.md) for why.

- **Indexing** – incremental on `add_attachment`, `edit_attachment`, `delete_attachment`, and `vmfo_folder_assigned`; full rebuild runs as a batched Action Scheduler job.
- **Search** – the native Media Library search term (`s`) is intercepted server-side (grid via `ajax_query_attachments_args`, list via the main query) and resolved to attachment IDs (`post__in`, relevance order). Interception starts once the index is built; before then the native search is left as a fallback.
- **UI** – none in the grid; a status panel and **Rebuild media index** button live in **Media > VMF Settings > Search**.

## REST API

Namespace `vmfa-search/v1`:

| Endpoint | Method | Capability | Purpose |
| --- | --- | --- | --- |
| `/index-status` | GET | `upload_files` | Index freshness/progress |
| `/rebuild` | POST | `manage_options` | Start a full rebuild |
| `/search` | GET | `upload_files` | Return matching attachment IDs |

## Extending

- `vmfa_search_document` — filter the indexed document per media item.
- `vmfa_search_searchable_attributes` / `vmfa_search_filterable_attributes` — adjust the index schema.
- `vmfa_search_db_path` — change the index directory.

## Development

```bash
composer install
npm install
composer test   # Pest + Brain\Monkey
npm test        # Vitest
composer lint   # PHPCS (WPCS)
```

## License

GPL-2.0-or-later

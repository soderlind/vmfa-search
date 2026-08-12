# Virtual Media Folders - Search

Fast, typo-tolerant search for the WordPress Media Library, powered by the [Loupe Search](https://github.com/soderlind/loupe-search) engine. Add-on for [Virtual Media Folders](https://github.com/soderlind/virtual-media-folders).

## What it does

Supercharges the **native** Media Library search field. Type in the standard
search box and results are matched against title, filename, alt text, caption,
and description — with typo tolerance — instead of WordPress' default `LIKE`
search. No extra UI is added.

Search is **library-wide**: entering a term searches the whole library, even
while a folder is selected. Clear the search to return to the folder view.

## Requirements

- PHP 8.3+
- [Virtual Media Folders](https://github.com/soderlind/virtual-media-folders) (active)
- [Loupe Search](https://github.com/soderlind/loupe-search) (active — provides the search engine)

## How it works

The add-on owns a dedicated Loupe/SQLite index of media items (`wp-content/vmfa-search-db/`), kept separate from Loupe Search's own post-type indexes. See [docs/adr/0001](docs/adr/0001-vmfa-search-owns-a-dedicated-media-index.md) for why.

- **Indexing** – incremental on `add_attachment`, `edit_attachment`, `delete_attachment`, and `vmfo_folder_assigned`; full rebuild runs as a batched Action Scheduler job.
- **Search** – the native Media Library search term (`s`) is intercepted server-side (grid via `ajax_query_attachments_args`, list via the main query) and resolved to attachment IDs (`post__in`, relevance order). Search is library-wide: the active folder constraint is dropped while a term is present. Interception starts once the index is built; before then the native search is left as a fallback.
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

### Example: index EXIF / IPTC

EXIF/IPTC is not indexed by default. To make descriptive metadata (e.g. IPTC
keywords, camera, credit) searchable, add the fields to the document and register
them as searchable, then rebuild the index from **Media → VMF Settings → Search**.

```php
// Add EXIF/IPTC fields to each media document.
add_filter( 'vmfa_search_document', function ( array $doc, WP_Post $post ): array {
    $meta = wp_get_attachment_metadata( $post->ID );
    $exif = is_array( $meta ) ? ( $meta['image_meta'] ?? array() ) : array();

    $keywords        = $exif['keywords'] ?? array();
    $doc['keywords'] = is_array( $keywords ) ? implode( ' ', $keywords ) : (string) $keywords;
    $doc['camera']   = (string) ( $exif['camera'] ?? '' );
    $doc['credit']   = (string) ( $exif['credit'] ?? '' );

    return $doc;
}, 10, 2 );

// Register the new fields as searchable (values must be strings/numbers).
add_filter( 'vmfa_search_searchable_attributes', function ( array $fields ): array {
    return array_merge( $fields, array( 'keywords', 'camera', 'credit' ) );
} );
```

Schema changes take effect on the next full rebuild.

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

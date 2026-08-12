=== Virtual Media Folders - Search ===
Contributors: PerS
Tags: media, search, media library, folders, loupe
Requires at least: 6.8
Tested up to: 7.1
Stable tag: 1.0.1
Requires PHP: 8.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fast, typo-tolerant search for the WordPress Media Library, powered by the Loupe Search engine. Add-on for Virtual Media Folders.

== Description ==

Virtual Media Folders - Search makes the Media Library's built-in search field typo-tolerant and content-aware. Type in the standard search box to find media items by title, filename, alt text, caption, or description — powered by [Loupe Search](https://github.com/soderlind/loupe-search).

Search is **library-wide**: entering a term searches your whole library, even while a folder is selected. Clear the search to return to the folder view.

= Features =

* **Typo-tolerant search** across title, filename, alt text, caption, and description.
* **Native field** – upgrades the existing Media Library search box; no extra UI.
* **Library-wide** – searches the whole library, not just the current folder.
* **Automatic indexing** – uploads, edits, deletions, and folder moves stay in sync.
* **Background rebuild** – reindex large libraries without timeouts (Action Scheduler).
* **Admin-only** – the media index is never exposed to front-end site search.

= Requirements =

This is an add-on and requires two plugins to be active:

* [Virtual Media Folders](https://github.com/soderlind/virtual-media-folders)
* [Loupe Search](https://github.com/soderlind/loupe-search)

== Installation ==

1. Install and activate **Virtual Media Folders** and **Loupe Search**.
2. Install and activate **Virtual Media Folders - Search**.
3. Go to **Media > VMF Settings > Search** and click **Rebuild media index**.
4. Open **Media > Library** and use the Media Library search box as usual.

== Frequently Asked Questions ==

= Why do I need to build an index? =

Search runs against a dedicated index of your media items. Build it once from the Search settings tab; after that, uploads and edits are indexed automatically.

= Does this change front-end site search? =

No. The media index is admin-only and never leaks into public search results.

= What fields are searched? =

Title, filename, alt text, caption, and description. EXIF/IPTC (keywords, camera, etc.) is not indexed by default — see "Can I search by EXIF or IPTC?" below.

= Can I search by EXIF or IPTC (keywords, camera)? =

Not out of the box, but you can add it with the `vmfa_search_document` and `vmfa_search_searchable_attributes` filters, then rebuild the index. Example:

`add_filter( 'vmfa_search_document', function ( $doc, $post ) {`
`    $meta = wp_get_attachment_metadata( $post->ID );`
`    $exif = is_array( $meta ) ? ( $meta['image_meta'] ?? array() ) : array();`
`    $kw   = $exif['keywords'] ?? array();`
`    $doc['keywords'] = is_array( $kw ) ? implode( ' ', $kw ) : (string) $kw;`
`    $doc['camera']   = (string) ( $exif['camera'] ?? '' );`
`    return $doc;`
`}, 10, 2 );`
`add_filter( 'vmfa_search_searchable_attributes', function ( $f ) {`
`    return array_merge( $f, array( 'keywords', 'camera' ) );`
`} );`

Then go to **Media > VMF Settings > Search** and click **Rebuild media index**.

= What happens if I deactivate Loupe Search? =

Media search falls back to WordPress' default search and a notice is shown. Your index is kept, so reactivating Loupe Search restores typo-tolerant search without a rebuild.

== Changelog ==

= 1.0.1 =
* Fixed: Prevent a fatal error when the "Virtual Media Folders" parent plugin is missing or older than 2.0.0; show an admin notice instead.

= 1.0.0 =
* Initial release.
* Upgrades the native Media Library search field with typo-tolerant, content-aware search (title, filename, alt text, caption, description).
* Dedicated Loupe-backed media index, separate from Loupe Search's post indexes and hidden from front-end site search.
* Library-wide search: entering a term searches the whole library, even while a folder is selected.
* Search begins at the 2nd character (filterable via `vmfa_search_min_prefix_length`).
* Automatic incremental indexing on upload, edit, delete, and folder change; background rebuild via Action Scheduler.
* REST API: `index-status`, `rebuild`, and `search` under `vmfa-search/v1`.
* Graceful fallback to native WordPress search until the index is built, or when the Loupe Search plugin is inactive.

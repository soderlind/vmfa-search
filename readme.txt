=== Virtual Media Folders - Search ===
Contributors: PerS
Tags: media, search, media library, folders, loupe
Requires at least: 6.8
Tested up to: 7.1
Stable tag: 0.1.0
Requires PHP: 8.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fast, typo-tolerant search for the WordPress Media Library, powered by the Loupe Search engine. Add-on for Virtual Media Folders.

== Description ==

Virtual Media Folders - Search makes the Media Library's built-in search field typo-tolerant and content-aware. Type in the standard search box to find media items by title, filename, alt text, caption, or description — powered by [Loupe Search](https://github.com/soderlind/loupe-search).

Search composes with your folders: when a folder is selected, results are scoped to it; select All Media to search the whole library.

= Features =

* **Typo-tolerant search** across title, filename, alt text, caption, and description.
* **Native field** – upgrades the existing Media Library search box; no extra UI.
* **Folder-aware** – scoped to the selected folder, or the whole library.
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

= What happens if I deactivate Loupe Search? =

The search box is disabled and a notice is shown. Your index is kept, so reactivating Loupe Search restores search without a rebuild.

== Changelog ==

= 0.1.0 =
* Initial release.

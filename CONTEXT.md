# VMFA Search

Add-on for Virtual Media Folders that makes media items easy to find in the WordPress
Media Library using typo-tolerant full-text search, powered by the Loupe Search engine.

## Language

**Media Item**:
A single entry in the WordPress Media Library (a WordPress `attachment`). The thing a
user searches for and that VMFA Search indexes.
_Avoid_: File (the bytes on disk — VMF never touches those), asset, media file.

**Media Index**:
The dedicated Loupe/SQLite index of media items that this add-on owns and maintains,
kept separate from Loupe Search's own post-type indexes.
_Avoid_: Search database, cache.

**Search Engine**:
The Loupe Search plugin (`Soderlind\Plugin\WPLoupe`) and the underlying Loupe library.
VMFA Search reuses its infrastructure rather than embedding Loupe directly.
_Avoid_: Loupe (the raw library, distinct from the plugin), backend.

**Indexing**:
Writing or updating a media item's document in the Media Index in response to a library
change (upload, edit, delete).
_Avoid_: Sync, crawl.

**Backfill**:
A one-time or on-demand pass that indexes media items that already existed before the
add-on was active, or rebuilds the whole Media Index.
_Avoid_: Full reindex (reserve "reindex" for Loupe Search's own post reindex).

**Facet**:
A structured dimension the user can narrow a search by. VMFA Search offers **folder**
and **mime type** facets.
_Avoid_: Filter (overloaded — used for WordPress hooks too), category.

**Folder**:
A `vmfo_folder` taxonomy term owned by Virtual Media Folders. A media item belongs to
at most one folder. VMFA Search references folders by ID; it does not own them.
_Avoid_: Directory, tag.

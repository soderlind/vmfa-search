# VMFA Search owns a dedicated media index

VMFA Search maintains its own Loupe index for media items (attachments) rather than
registering `attachment` into Loupe Search's post-type indexing. Loupe Search's built-in
indexer only indexes `post_status => publish` (its reindex query and `save_post` hooks),
and its settings UI explicitly excludes `attachment`; media items are `inherit` status, so
they can never be backfilled through Loupe Search's own path. We therefore build a
dedicated Media Index via `WP_Loupe_Factory::create_loupe_instance()` for the `attachment`
type and manage its documents ourselves.

Keeping the index dedicated also isolates media items from Loupe Search's front-end site
search, so uploaded files never leak into public search results.

## Consequences

- VMFA Search hard-depends on the Loupe Search plugin (for `WP_Loupe_Factory`, `WP_Loupe_DB`,
  schema/config plumbing) but owns the attachment document schema and indexing lifecycle.
- We must provide our own indexing triggers (`add_attachment` / `edit_attachment` /
  `delete_attachment`) and a backfill/rebuild path for existing libraries.

# Intercept the native Media Library search field

VMFA Search does not add its own search box. Instead it intercepts WordPress's
native Media Library search term (`s`) — in the grid via
`ajax_query_attachments_args` and in the list view via the main query — and
routes it through Loupe, injecting `post__in` in relevance order.

This keeps the UI native (no injected DOM in the wp.media sidebar), works in
both grid and list views, and covers the Gutenberg media modal's search field
for free.

Search is **library-wide**: when a search term is present we drop the active
folder constraint (VMF's `vmfo_folder` tax query), so a search always spans the
whole library regardless of the selected folder. Users expect a search box to
search everything, not just the current folder.

## Consequences

- The selected folder is ignored while a search term is active; clearing the
  search restores the normal folder view.
- Interception only applies once the media index is built; before then the
  native WordPress search is left untouched as a fallback.

# Intercept the native Media Library search field

VMFA Search does not add its own search box. Instead it intercepts WordPress's
native Media Library search term (`s`) — in the grid via
`ajax_query_attachments_args` and in the list view via the main query — and
routes it through Loupe, injecting `post__in` in relevance order.

This keeps the UI native (no injected DOM in the wp.media sidebar), works in
both grid and list views, and covers the Gutenberg media modal's search field
for free. Folder scoping is handled by the existing folder filter composing with
our `post__in`, so search is naturally scoped to the current folder view.

## Consequences

- No explicit "This folder / All folders" scope toggle. To search across the
  whole library the user selects "All Media" first; within a folder, search is
  scoped to that folder automatically.
- Interception only applies once the media index is built; before then the
  native WordPress search is left untouched as a fallback.

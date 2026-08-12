<?php
/**
 * Intercepts the native Media Library search and routes it through Loupe.
 *
 * @package VmfaSearch
 */

declare(strict_types=1);

namespace VmfaSearch\Search;

defined( 'ABSPATH' ) || exit;

use VmfaSearch\Index\IndexStatus;
use VmfaSearch\Index\MediaIndex;

/**
 * Replaces the WordPress media search (`s`) with typo-tolerant Loupe results.
 *
 * WordPress already exposes the Media Library search term as `s` — in the grid
 * via `ajax_query_attachments_args`, and in the list view via the main query.
 * We resolve that term to a set of attachment IDs and inject `post__in`,
 * preserving Loupe's relevance order.
 *
 * Search is library-wide: when a term is present we drop the active folder
 * constraint (VMF's `vmfo_folder` tax query), so results span the whole library
 * regardless of the selected folder.
 *
 * Search begins at the 2nd character. Shorter terms are ignored while the index
 * is active (the current view is shown unfiltered), and interception only
 * happens once the index is built; before then the native WordPress search is
 * left untouched as a fallback.
 */
final class MediaQueryFilter {

	/**
	 * The folder taxonomy owned by Virtual Media Folders.
	 */
	private const FOLDER_TAXONOMY = 'vmfo_folder';

	private SearchService $search;
	private MediaIndex $index;
	private IndexStatus $status;

	/**
	 * Constructor.
	 *
	 * @param SearchService $search Search service.
	 * @param MediaIndex    $index  Index store.
	 * @param IndexStatus   $status Status store.
	 */
	public function __construct( SearchService $search, MediaIndex $index, IndexStatus $status ) {
		$this->search = $search;
		$this->index  = $index;
		$this->status = $status;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'ajax_query_attachments_args', [ $this, 'filter_ajax_query' ], 20 );
		add_action( 'pre_get_posts', [ $this, 'filter_list_view_query' ], 20 );
	}

	/**
	 * Filter the grid (AJAX) attachment query.
	 *
	 * @param array<string, mixed> $query_args Query arguments.
	 * @return array<string, mixed>
	 */
	public function filter_ajax_query( array $query_args ): array {
		if ( ! $this->should_intercept() ) {
			return $query_args;
		}

		$term = isset( $query_args['s'] ) ? sanitize_text_field( (string) $query_args['s'] ) : '';

		if ( '' === trim( $term ) ) {
			return $query_args;
		}

		if ( ! $this->is_searchable_term( $term ) ) {
			// Below the threshold: hold off searching and show the current view.
			unset( $query_args['s'] );
			return $query_args;
		}

		// Hand the term to Loupe instead of WordPress' default LIKE search.
		unset( $query_args['s'] );

		// Search is library-wide: drop the active folder constraint.
		$query_args = $this->remove_folder_scope( $query_args );

		return $this->constrain( $query_args, $term );
	}

	/**
	 * Filter the list view (upload.php) main query.
	 *
	 * @param \WP_Query $query Query object.
	 * @return void
	 */
	public function filter_list_view_query( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || ! $this->should_intercept() ) {
			return;
		}

		global $pagenow;
		if ( 'upload.php' !== $pagenow ) {
			return;
		}

		$term = sanitize_text_field( (string) $query->get( 's' ) );

		if ( '' === trim( $term ) ) {
			return;
		}

		if ( ! $this->is_searchable_term( $term ) ) {
			// Below the threshold: hold off searching and show the current view.
			$query->set( 's', '' );
			return;
		}

		$ids = $this->search->search( $term );
		$ids = empty( $ids ) ? [ 0 ] : $ids;

		// Replace the default search with our result set (library-wide).
		$tax_query = $query->get( 'tax_query' );
		if ( is_array( $tax_query ) && ! empty( $tax_query ) ) {
			$query->set( 'tax_query', $this->strip_folder_tax_query( $tax_query ) );
		}

		$query->set( 's', '' );
		$query->set( 'post__in', $ids );
		$query->set( 'orderby', 'post__in' );
	}

	/**
	 * Whether the media index is ready to serve searches.
	 *
	 * @return bool
	 */
	private function should_intercept(): bool {
		return $this->index->is_available() && $this->status->is_built();
	}

	/**
	 * Whether a term is long enough to search.
	 *
	 * @param string $term Search term.
	 * @return bool
	 */
	private function is_searchable_term( string $term ): bool {
		return mb_strlen( trim( $term ) ) >= SearchService::min_query_length();
	}

	/**
	 * Remove Virtual Media Folders' folder constraint from grid query args.
	 *
	 * @param array<string, mixed> $query_args Query arguments.
	 * @return array<string, mixed>
	 */
	private function remove_folder_scope( array $query_args ): array {
		unset( $query_args['vmfo_folder'], $query_args['vmfo_folder_exclude'] );

		if ( ! empty( $query_args['tax_query'] ) && is_array( $query_args['tax_query'] ) ) {
			$stripped = $this->strip_folder_tax_query( $query_args['tax_query'] );

			if ( empty( $stripped ) ) {
				unset( $query_args['tax_query'] );
			} else {
				// Narrowing an existing tax query, not introducing one.
				$query_args['tax_query'] = $stripped; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			}
		}

		return $query_args;
	}

	/**
	 * Remove folder-taxonomy clauses from a tax_query, keeping any others.
	 *
	 * @param array<int|string, mixed> $tax_query Tax query array.
	 * @return array<int|string, mixed>
	 */
	private function strip_folder_tax_query( array $tax_query ): array {
		$filtered = [];
		$relation = null;

		foreach ( $tax_query as $key => $clause ) {
			if ( 'relation' === $key ) {
				$relation = $clause;
				continue;
			}

			if ( is_array( $clause ) && isset( $clause['taxonomy'] ) && self::FOLDER_TAXONOMY === $clause['taxonomy'] ) {
				continue;
			}

			$filtered[] = $clause;
		}

		if ( count( $filtered ) > 1 && null !== $relation ) {
			$filtered['relation'] = $relation;
		}

		return $filtered;
	}

	/**
	 * Intersect the query with the search hits.
	 *
	 * @param array<string, mixed> $query_args Query arguments.
	 * @param string               $term       Search term.
	 * @return array<string, mixed>
	 */
	private function constrain( array $query_args, string $term ): array {
		$ids = $this->search->search( $term );

		if ( empty( $ids ) ) {
			$query_args['post__in'] = [ 0 ];
			return $query_args;
		}

		if ( ! empty( $query_args['post__in'] ) && is_array( $query_args['post__in'] ) ) {
			$intersection = array_values( array_intersect( $ids, array_map( 'intval', $query_args['post__in'] ) ) );
			$ids          = empty( $intersection ) ? [ 0 ] : $intersection;
		}

		$query_args['post__in'] = $ids;
		$query_args['orderby']  = 'post__in';

		return $query_args;
	}
}

<?php
/**
 * Media search service.
 *
 * @package VmfaSearch
 */

declare(strict_types=1);

namespace VmfaSearch\Search;

defined( 'ABSPATH' ) || exit;

use Loupe\Loupe\SearchParameters;
use VmfaSearch\Index\MediaIndex;

/**
 * Runs typo-tolerant queries against the media index and returns attachment IDs
 * in relevance order.
 */
final class SearchService {

	/**
	 * Default minimum query / prefix length.
	 */
	public const DEFAULT_MIN_QUERY_LENGTH = 2;

	/**
	 * Maximum number of hits returned.
	 */
	private const MAX_HITS = 1000;

	private MediaIndex $index;

	/**
	 * Constructor.
	 *
	 * @param MediaIndex $index Index store.
	 */
	public function __construct( MediaIndex $index ) {
		$this->index = $index;
	}

	/**
	 * Minimum query length before Loupe search runs.
	 *
	 * This value is also used as Loupe's minimum token length for prefix search,
	 * so the index and the query guard stay in sync. Changing it requires a full
	 * index rebuild.
	 *
	 * @return int
	 */
	public static function min_query_length(): int {
		/**
		 * Filters the minimum query / prefix length for media search.
		 *
		 * @param int $length Minimum number of characters. Default 2.
		 */
		$length = (int) apply_filters( 'vmfa_search_min_prefix_length', self::DEFAULT_MIN_QUERY_LENGTH );

		return max( 1, $length );
	}

	/**
	 * Search the media index.
	 *
	 * @param string $query Raw user query.
	 * @param int    $limit Optional hit limit.
	 * @return int[] Attachment IDs, ordered by relevance.
	 */
	public function search( string $query, int $limit = self::MAX_HITS ): array {
		$query = trim( $query );

		if ( ! $this->index->is_available() || mb_strlen( $query ) < self::min_query_length() ) {
			return [];
		}

		try {
			$params = SearchParameters::create()
				->withQuery( $query )
				->withAttributesToRetrieve( [ 'id' ] )
				->withLimit( max( 1, min( self::MAX_HITS, $limit ) ) );

			$result = $this->index->get_loupe()->search( $params )->toArray();
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[vmfa-search] search failed: ' . $e->getMessage() );
			}
			return [];
		}

		$hits = isset( $result['hits'] ) && is_array( $result['hits'] ) ? $result['hits'] : [];

		$ids = [];
		foreach ( $hits as $hit ) {
			if ( isset( $hit['id'] ) ) {
				$ids[] = (int) $hit['id'];
			}
		}

		return $ids;
	}
}

<?php
/**
 * REST controller for media search status and maintenance.
 *
 * @package VmfaSearch
 */

declare(strict_types=1);

namespace VmfaSearch\REST;

defined( 'ABSPATH' ) || exit;

use VmfaSearch\Index\IndexStatus;
use VmfaSearch\Index\MediaIndex;
use VmfaSearch\Index\MediaIndexer;
use VmfaSearch\Search\SearchService;

/**
 * Exposes index status, rebuild, and a search endpoint under vmfa-search/v1.
 */
final class SearchController {

	private const NAMESPACE = 'vmfa-search/v1';

	private MediaIndex $index;
	private MediaIndexer $indexer;
	private IndexStatus $status;
	private SearchService $search;

	/**
	 * Constructor.
	 *
	 * @param MediaIndex    $index   Index store.
	 * @param MediaIndexer  $indexer Indexer.
	 * @param IndexStatus   $status  Status store.
	 * @param SearchService $search  Search service.
	 */
	public function __construct( MediaIndex $index, MediaIndexer $indexer, IndexStatus $status, SearchService $search ) {
		$this->index   = $index;
		$this->indexer = $indexer;
		$this->status  = $status;
		$this->search  = $search;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/index-status',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_status' ],
				'permission_callback' => [ $this, 'can_search' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/rebuild',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'rebuild' ],
				'permission_callback' => [ $this, 'can_manage' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/search',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'do_search' ],
				'permission_callback' => [ $this, 'can_search' ],
				'args'                => [
					'query' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	/**
	 * GET /index-status.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_status(): \WP_REST_Response {
		return rest_ensure_response( $this->status_payload() );
	}

	/**
	 * POST /rebuild.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rebuild() {
		if ( ! $this->index->is_available() ) {
			return new \WP_Error(
				'vmfa_search_engine_unavailable',
				__( 'The Loupe Search engine is not active.', 'vmfa-search' ),
				[ 'status' => 409 ]
			);
		}

		$this->indexer->rebuild();

		return rest_ensure_response( $this->status_payload() );
	}

	/**
	 * GET /search.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function do_search( \WP_REST_Request $request ): \WP_REST_Response {
		$ids = $this->search->search( (string) $request->get_param( 'query' ) );

		return rest_ensure_response(
			[
				'ids'   => $ids,
				'total' => count( $ids ),
			]
		);
	}

	/**
	 * Permission callback for reading/searching.
	 *
	 * @return bool
	 */
	public function can_search(): bool {
		return current_user_can( 'upload_files' );
	}

	/**
	 * Permission callback for maintenance actions.
	 *
	 * @return bool
	 */
	public function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Build the status response payload.
	 *
	 * @return array<string, mixed>
	 */
	private function status_payload(): array {
		$status = $this->status->get();

		return [
			'available' => $this->index->is_available(),
			'built'     => $this->status->is_built(),
			'building'  => (bool) $status['building'],
			'processed' => (int) $status['processed'],
			'total'     => (int) $status['total'],
			'count'     => (int) $status['count'],
			'lastBuilt' => (int) $status['last_built'],
		];
	}
}

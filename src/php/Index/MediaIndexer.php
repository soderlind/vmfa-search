<?php
/**
 * Media indexer: incremental indexing and background backfill.
 *
 * @package VmfaSearch
 */

declare(strict_types=1);

namespace VmfaSearch\Index;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the media index in sync with the library and runs full rebuilds.
 */
final class MediaIndexer {

	/**
	 * Action Scheduler hook for a single backfill batch.
	 */
	public const BATCH_HOOK = 'vmfa_search_index_batch';

	/**
	 * Action Scheduler group.
	 */
	private const GROUP = 'vmfa-search';

	/**
	 * Documents processed per batch.
	 */
	private const BATCH_SIZE = 200;

	private MediaIndex $index;
	private MediaSchema $schema;
	private IndexStatus $status;

	/**
	 * Constructor.
	 *
	 * @param MediaIndex  $index  Index store.
	 * @param MediaSchema $schema Document builder.
	 * @param IndexStatus $status Status store.
	 */
	public function __construct( MediaIndex $index, MediaSchema $schema, IndexStatus $status ) {
		$this->index  = $index;
		$this->schema = $schema;
		$this->status = $status;
	}

	/**
	 * Register indexing hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'add_attachment', [ $this, 'index_attachment' ] );
		add_action( 'edit_attachment', [ $this, 'index_attachment' ] );
		add_action( 'delete_attachment', [ $this, 'remove_attachment' ] );

		// Folder changes (fired by Virtual Media Folders after (re)assignment).
		add_action( 'vmfo_folder_assigned', [ $this, 'index_attachment' ] );

		// Background backfill batch.
		add_action( self::BATCH_HOOK, [ $this, 'run_batch' ], 10, 1 );
	}

	/**
	 * Index (create/update) a single attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return void
	 */
	public function index_attachment( int $attachment_id ): void {
		if ( ! $this->index->is_available() ) {
			return;
		}

		$post = get_post( $attachment_id );

		if ( ! $post instanceof \WP_Post || 'attachment' !== $post->post_type ) {
			return;
		}

		try {
			$this->index->upsert( $this->schema->build_document( $post ) );
			$this->status->update( [ 'count' => $this->index->count() ] );
		} catch ( \Throwable $e ) {
			$this->log( 'index_attachment failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Remove a single attachment from the index.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return void
	 */
	public function remove_attachment( int $attachment_id ): void {
		if ( ! $this->index->is_available() ) {
			return;
		}

		try {
			$this->index->delete( $attachment_id );
			$this->status->update( [ 'count' => $this->index->count() ] );
		} catch ( \Throwable $e ) {
			$this->log( 'remove_attachment failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Start a full rebuild of the media index.
	 *
	 * Clears the index and schedules the first background batch. Falls back to a
	 * synchronous rebuild when Action Scheduler is unavailable.
	 *
	 * @return bool True when a rebuild was started.
	 */
	public function rebuild(): bool {
		if ( ! $this->index->is_available() ) {
			return false;
		}

		$this->index->delete_index();

		$this->status->update(
			[
				'building'  => true,
				'processed' => 0,
				'total'     => $this->count_attachments(),
				'count'     => 0,
			]
		);

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::BATCH_HOOK, [ 'after' => 0 ], self::GROUP );
			return true;
		}

		// Fallback: synchronous best-effort rebuild.
		$after = 0;
		do {
			$after = $this->process_from( $after );
		} while ( $after > 0 );

		return true;
	}

	/**
	 * Run one backfill batch and schedule the next if needed.
	 *
	 * @param int $after Index attachments with ID greater than this cursor.
	 * @return void
	 */
	public function run_batch( int $after = 0 ): void {
		if ( ! $this->index->is_available() ) {
			$this->status->update( [ 'building' => false ] );
			return;
		}

		$next = $this->process_from( $after );

		if ( $next > 0 && function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::BATCH_HOOK, [ 'after' => $next ], self::GROUP );
		}
	}

	/**
	 * Index a single batch of attachments after the given cursor.
	 *
	 * @param int $after Cursor (attachment ID).
	 * @return int Next cursor, or 0 when finished.
	 */
	private function process_from( int $after ): int {
		$ids = $this->get_attachment_ids_after( $after, self::BATCH_SIZE );

		if ( empty( $ids ) ) {
			$this->finish();
			return 0;
		}

		$documents = [];
		$last_id   = $after;

		foreach ( $ids as $id ) {
			$post = get_post( $id );
			if ( $post instanceof \WP_Post ) {
				$documents[] = $this->schema->build_document( $post );
			}
			$last_id = $id;
		}

		try {
			$this->index->upsert_many( $documents );
		} catch ( \Throwable $e ) {
			$this->log( 'batch upsert failed: ' . $e->getMessage() );
		}

		$status = $this->status->get();
		$this->status->update(
			[
				'processed' => $status['processed'] + count( $ids ),
				'count'     => $this->index->count(),
			]
		);

		// Fewer results than requested means this was the final batch.
		if ( count( $ids ) < self::BATCH_SIZE ) {
			$this->finish();
			return 0;
		}

		return $last_id;
	}

	/**
	 * Mark the rebuild complete.
	 *
	 * @return void
	 */
	private function finish(): void {
		$this->status->update(
			[
				'building'   => false,
				'count'      => $this->index->count(),
				'last_built' => time(),
			]
		);
	}

	/**
	 * Fetch attachment IDs after a cursor, ascending.
	 *
	 * @param int $after Cursor.
	 * @param int $limit Batch size.
	 * @return int[]
	 */
	private function get_attachment_ids_after( int $after, int $limit ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND ID > %d ORDER BY ID ASC LIMIT %d",
				$after,
				$limit
			)
		);

		return array_map( 'intval', (array) $ids );
	}

	/**
	 * Count attachments in the library.
	 *
	 * @return int
	 */
	private function count_attachments(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment'" );
	}

	/**
	 * Write a debug log line when WP_DEBUG is on.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	private function log( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[vmfa-search] ' . $message );
		}
	}
}

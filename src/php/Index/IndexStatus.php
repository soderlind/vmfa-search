<?php
/**
 * Media index status store.
 *
 * @package VmfaSearch
 */

declare(strict_types=1);

namespace VmfaSearch\Index;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the persisted state of the media index (progress + freshness).
 */
final class IndexStatus {

	private const OPTION = 'vmfa_search_index_status';

	/**
	 * Get the full status array with defaults applied.
	 *
	 * @return array{building: bool, processed: int, total: int, count: int, last_built: int}
	 */
	public function get(): array {
		$defaults = [
			'building'   => false,
			'processed'  => 0,
			'total'      => 0,
			'count'      => 0,
			'last_built' => 0,
		];

		$stored = get_option( self::OPTION, [] );

		return array_merge( $defaults, is_array( $stored ) ? $stored : [] );
	}

	/**
	 * Merge and persist partial status changes.
	 *
	 * @param array<string, mixed> $changes Partial status.
	 * @return void
	 */
	public function update( array $changes ): void {
		update_option( self::OPTION, array_merge( $this->get(), $changes ), false );
	}

	/**
	 * Whether a rebuild is currently running.
	 *
	 * @return bool
	 */
	public function is_building(): bool {
		return (bool) $this->get()['building'];
	}

	/**
	 * Whether the index has been built and holds documents.
	 *
	 * @return bool
	 */
	public function is_built(): bool {
		$status = $this->get();

		return ! $status['building'] && $status['last_built'] > 0;
	}

	/**
	 * Remove the stored status.
	 *
	 * @return void
	 */
	public function delete(): void {
		delete_option( self::OPTION );
	}
}

<?php
/**
 * Enqueues the media search gate script.
 *
 * @package VmfaSearch
 */

declare(strict_types=1);

namespace VmfaSearch\Admin;

defined( 'ABSPATH' ) || exit;

use VmfaSearch\Index\MediaIndex;
use VmfaSearch\Search\SearchService;

/**
 * Loads the client-side gate that delays the native media search until the
 * search threshold is reached, avoiding a per-keystroke grid re-render.
 */
final class GridSearchGate {

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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	/**
	 * Enqueue the gate script on Media Library screens.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, [ 'upload.php', 'media-new.php' ], true ) ) {
			return;
		}

		if ( ! $this->index->is_available() ) {
			return;
		}

		wp_enqueue_script(
			'vmfa-search-gate',
			VMFA_SEARCH_URL . 'assets/js/media-search-gate.js',
			[],
			VMFA_SEARCH_VERSION,
			true
		);

		wp_localize_script(
			'vmfa-search-gate',
			'vmfaSearchGate',
			[ 'minChars' => SearchService::MIN_QUERY_LENGTH ]
		);
	}
}

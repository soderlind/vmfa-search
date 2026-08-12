<?php
/**
 * Media Library admin notices.
 *
 * @package VmfaSearch
 */

declare(strict_types=1);

namespace VmfaSearch\Admin;

defined( 'ABSPATH' ) || exit;

use VmfaSearch\Index\IndexStatus;
use VmfaSearch\Index\MediaIndex;

/**
 * Surfaces index status notices on the Media Library screen.
 *
 * There is no custom search UI: the native Media Library search field is
 * intercepted server-side once the index is built. These notices explain when
 * that has not happened yet, or when the search engine is unavailable.
 */
final class MediaLibraryNotices {

	private MediaIndex $index;
	private IndexStatus $status;

	/**
	 * Constructor.
	 *
	 * @param MediaIndex  $index  Index store.
	 * @param IndexStatus $status Status store.
	 */
	public function __construct( MediaIndex $index, IndexStatus $status ) {
		$this->index  = $index;
		$this->status = $status;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_notices', [ $this, 'render_notices' ] );
	}

	/**
	 * Render admin notices on Media Library screens.
	 *
	 * @return void
	 */
	public function render_notices(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( null === $screen || 'upload' !== $screen->id ) {
			return;
		}

		if ( ! $this->index->is_available() ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html__( 'Virtual Media Folders – Search needs the Loupe Search plugin active to search media items.', 'vmfa-search' )
			);
			return;
		}

		if ( ! $this->status->is_built() && ! $this->status->is_building() ) {
			$url = SettingsTab::get_url();
			printf(
				'<div class="notice notice-info"><p>%s %s</p></div>',
				esc_html__( 'Media search is using the default WordPress search until the media index is built.', 'vmfa-search' ),
				'' !== $url
					? '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Build the index now.', 'vmfa-search' ) . '</a>'
					: ''
			);
		}
	}
}

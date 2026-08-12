<?php
/**
 * Settings tab: media index status and rebuild.
 *
 * @package VmfaSearch
 */

declare(strict_types=1);

namespace VmfaSearch\Admin;

defined( 'ABSPATH' ) || exit;

use VmfaSearch\Index\IndexStatus;
use VmfaSearch\Index\MediaIndex;

/**
 * Renders the "Search" tab inside VMF Settings and wires its assets.
 */
final class SettingsTab {

	private const TAB_SLUG = 'search';

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
	 * URL of the Search settings tab, or empty string when unavailable.
	 *
	 * @return string
	 */
	public static function get_url(): string {
		if ( ! class_exists( \VirtualMediaFolders\Settings::class ) ) {
			return '';
		}

		return add_query_arg(
			[
				'page' => \VirtualMediaFolders\Settings::PAGE_SLUG,
				'tab'  => self::TAB_SLUG,
			],
			admin_url( 'upload.php' )
		);
	}

	/**
	 * Register the tab with the parent plugin.
	 *
	 * @param array<string, mixed> $tabs Existing tabs.
	 * @return array<string, mixed>
	 */
	public function register_tab( array $tabs ): array {
		$tabs[ self::TAB_SLUG ] = [
			'title'    => __( 'Search', 'vmfa-search' ),
			'callback' => [ $this, 'render' ],
		];

		return $tabs;
	}

	/**
	 * Enqueue tab assets when the Search tab is active.
	 *
	 * @param string $active_tab    Active tab slug.
	 * @param string $active_subtab Active subtab slug.
	 * @return void
	 */
	public function enqueue_scripts( string $active_tab, string $active_subtab ): void {
		if ( self::TAB_SLUG !== $active_tab ) {
			return;
		}

		wp_enqueue_style(
			'vmfa-search-settings',
			VMFA_SEARCH_URL . 'assets/css/settings.css',
			[],
			VMFA_SEARCH_VERSION
		);

		wp_enqueue_script(
			'vmfa-search-settings',
			VMFA_SEARCH_URL . 'assets/js/settings.js',
			[ 'wp-i18n' ],
			VMFA_SEARCH_VERSION,
			true
		);

		wp_localize_script(
			'vmfa-search-settings',
			'vmfaSearchSettings',
			[
				'restUrl' => esc_url_raw( rest_url( 'vmfa-search/v1/' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	/**
	 * Render the tab body.
	 *
	 * @param string $active_tab    Active tab slug.
	 * @param string $active_subtab Active subtab slug.
	 * @return void
	 */
	public function render( string $active_tab = '', string $active_subtab = '' ): void {
		$available = $this->index->is_available();
		?>
		<div class="vmfa-search-settings">
			<h2><?php esc_html_e( 'Media Library Search', 'vmfa-search' ); ?></h2>

			<?php if ( ! $available ) : ?>
				<div class="notice notice-error inline">
					<p>
						<?php esc_html_e( 'The Loupe Search plugin is not active. Activate it to enable media search.', 'vmfa-search' ); ?>
					</p>
				</div>
			<?php else : ?>
				<p class="description">
					<?php esc_html_e( 'Build and maintain the search index for your media items. Uploads and edits are indexed automatically; use Rebuild to (re)index the whole library.', 'vmfa-search' ); ?>
				</p>

				<div id="vmfa-search-status" class="vmfa-search-status" aria-live="polite"></div>

				<p>
					<button type="button" class="button button-primary" id="vmfa-search-rebuild">
						<?php esc_html_e( 'Rebuild media index', 'vmfa-search' ); ?>
					</button>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}

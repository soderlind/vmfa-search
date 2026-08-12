<?php
/**
 * Main plugin class.
 *
 * @package VmfaSearch
 */

declare(strict_types=1);

namespace VmfaSearch;

defined( 'ABSPATH' ) || exit;

use VirtualMediaFolders\Addon\AbstractPlugin;
use VmfaSearch\Admin\MediaLibraryNotices;
use VmfaSearch\Admin\SettingsTab;
use VmfaSearch\Index\IndexStatus;
use VmfaSearch\Index\MediaIndex;
use VmfaSearch\Index\MediaIndexer;
use VmfaSearch\Index\MediaSchema;
use VmfaSearch\REST\SearchController;
use VmfaSearch\Search\MediaQueryFilter;
use VmfaSearch\Search\SearchService;

/**
 * Plugin bootstrap.
 */
final class Plugin extends AbstractPlugin {

	private ?MediaIndex $index              = null;
	private ?IndexStatus $status            = null;
	private ?MediaIndexer $indexer          = null;
	private ?SearchService $search          = null;
	private ?MediaQueryFilter $query_filter = null;
	private ?SettingsTab $settings_tab      = null;
	private ?MediaLibraryNotices $notices   = null;

	/**
	 * Plugin text domain.
	 */
	protected function get_text_domain(): string {
		return 'vmfa-search';
	}

	/**
	 * Absolute path to the main plugin file.
	 */
	protected function get_plugin_file(): string {
		return VMFA_SEARCH_FILE;
	}

	/**
	 * Create service objects.
	 */
	protected function init_services(): void {
		$this->index        = new MediaIndex();
		$this->status       = new IndexStatus();
		$this->indexer      = new MediaIndexer( $this->index, new MediaSchema(), $this->status );
		$this->search       = new SearchService( $this->index );
		$this->query_filter = new MediaQueryFilter( $this->search, $this->index, $this->status );
		$this->settings_tab = new SettingsTab( $this->index, $this->status );
		$this->notices      = new MediaLibraryNotices( $this->index, $this->status );
	}

	/**
	 * Register WordPress hooks.
	 */
	protected function init_hooks(): void {
		// Indexing lifecycle (incremental + backfill batches).
		$this->indexer->register_hooks();

		// Media Library query interception.
		$this->query_filter->register_hooks();

		// REST API.
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		if ( is_admin() ) {
			$this->notices->register_hooks();

			if ( $this->supports_parent_tabs() ) {
				add_filter( 'vmfo_settings_tabs', [ $this->settings_tab, 'register_tab' ] );
				add_action( 'vmfo_settings_enqueue_scripts', [ $this->settings_tab, 'enqueue_scripts' ], 10, 2 );
			}
		}
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$controller = new SearchController( $this->index, $this->indexer, $this->status, $this->search );
		$controller->register_routes();
	}
}

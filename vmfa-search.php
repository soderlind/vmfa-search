<?php
/**
 * Plugin Name:       Virtual Media Folders - Search
 * Plugin URI:        https://github.com/soderlind/vmfa-search
 * Description:       Fast, typo-tolerant search for the Media Library, powered by the Loupe Search engine. Add-on for Virtual Media Folders.
 * Version:           1.0.0
 * Requires at least: 6.8
 * Requires PHP:      8.3
 * Requires Plugins:  virtual-media-folders, loupe-search
 * Author:            Per Soderlind
 * Author URI:        https://soderlind.no
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vmfa-search
 * Domain Path:       /languages
 *
 * @package VmfaSearch
 */

declare(strict_types=1);

namespace VmfaSearch;

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'VMFA_SEARCH_VERSION', '1.0.0' );
define( 'VMFA_SEARCH_FILE', __FILE__ );
define( 'VMFA_SEARCH_PATH', plugin_dir_path( __FILE__ ) );
define( 'VMFA_SEARCH_URL', plugin_dir_url( __FILE__ ) );
define( 'VMFA_SEARCH_BASENAME', plugin_basename( __FILE__ ) );

// Require Composer autoloader.
if ( file_exists( VMFA_SEARCH_PATH . 'vendor/autoload.php' ) ) {
	require_once VMFA_SEARCH_PATH . 'vendor/autoload.php';
}

// Initialize Action Scheduler early (must load before plugins_loaded fires).
if ( class_exists( \VirtualMediaFolders\Addon\ActionSchedulerLoader::class ) ) {
	\VirtualMediaFolders\Addon\ActionSchedulerLoader::maybe_load( VMFA_SEARCH_PATH );
}

/**
 * Boot the plugin.
 *
 * @return void
 */
function init(): void {
	// Update checker via GitHub releases.
	if ( ! class_exists( \Soderlind\WordPress\GitHubUpdater::class ) ) {
		require_once __DIR__ . '/class-github-updater.php';
	}
	\Soderlind\WordPress\GitHubUpdater::init(
		github_url: 'https://github.com/soderlind/vmfa-search',
		plugin_file: VMFA_SEARCH_FILE,
		plugin_slug: 'vmfa-search',
		name_regex: '/vmfa-search\.zip/',
		branch: 'main',
	);

	Plugin::get_instance()->init();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init', 15 );

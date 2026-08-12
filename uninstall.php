<?php
/**
 * Uninstall handler.
 *
 * Removes the media search index directory and plugin options.
 *
 * @package VmfaSearch
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Remove stored status.
delete_option( 'vmfa_search_index_status' );

// Remove the index directory.
if ( defined( 'WP_CONTENT_DIR' ) ) {
	$vmfa_search_dir = WP_CONTENT_DIR . '/vmfa-search-db';

	if ( is_dir( $vmfa_search_dir ) ) {
		if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		}

		$vmfa_search_fs = new WP_Filesystem_Direct( false );
		$vmfa_search_fs->rmdir( $vmfa_search_dir, true );
	}
}

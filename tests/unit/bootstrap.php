<?php
/**
 * PHPUnit bootstrap for Brain Monkey unit tests.
 *
 * @package VmfaSearch\Tests
 */

declare(strict_types=1);

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

// Minimal WP_Post stub for schema tests.
if ( ! class_exists( 'WP_Post' ) ) {
	// phpcs:ignore
	class WP_Post {
		public $ID = 0;
		public $post_title = '';
		public $post_excerpt = '';
		public $post_content = '';
		public $post_mime_type = '';
		public $post_type = 'attachment';

		public function __construct( array $props = array() ) {
			foreach ( $props as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

// Minimal WP_REST_Server stub.
if ( ! class_exists( 'WP_REST_Server' ) ) {
	// phpcs:ignore
	class WP_REST_Server {
		const READABLE  = 'GET';
		const CREATABLE = 'POST';
		const EDITABLE  = 'POST, PUT, PATCH';
		const DELETABLE = 'DELETE';
	}
}

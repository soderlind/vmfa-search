<?php
/**
 * Dedicated Loupe index for media items.
 *
 * @package VmfaSearch
 */

declare(strict_types=1);

namespace VmfaSearch\Index;

defined( 'ABSPATH' ) || exit;

use Loupe\Loupe\Configuration;
use Loupe\Loupe\Loupe;
use Loupe\Loupe\LoupeFactory;

/**
 * Owns the media-item search index.
 *
 * The index is a Loupe/SQLite store kept separate from Loupe Search's own
 * post-type indexes (see docs/adr/0001). It uses the Loupe library provided by
 * the Loupe Search plugin, but manages its own schema, directory, and lifecycle.
 */
final class MediaIndex {

	private ?Loupe $loupe = null;

	/**
	 * Whether the underlying Loupe engine is available.
	 *
	 * Returns false when the Loupe Search plugin (which ships the library) is
	 * inactive, so callers can degrade gracefully.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return class_exists( LoupeFactory::class ) && class_exists( Configuration::class );
	}

	/**
	 * Absolute path to the index directory.
	 *
	 * @return string
	 */
	public function get_index_path(): string {
		$default = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR . '/vmfa-search-db' : '';

		/**
		 * Filters the base directory for the media search index.
		 *
		 * @param string $path Absolute path.
		 */
		$path = (string) apply_filters( 'vmfa_search_db_path', $default );
		$path = '' !== trim( $path ) ? rtrim( $path, '/' ) : $default;

		return $path . '/attachment';
	}

	/**
	 * Get (and lazily create) the Loupe instance for media items.
	 *
	 * @return \Loupe\Loupe\Loupe
	 */
	public function get_loupe(): Loupe {
		if ( $this->loupe instanceof Loupe ) {
			return $this->loupe;
		}

		$path = $this->get_index_path();
		wp_mkdir_p( $path );

		$configuration = Configuration::create()
			->withPrimaryKey( 'id' )
			->withSearchableAttributes( $this->get_searchable_attributes() )
			->withFilterableAttributes( $this->get_filterable_attributes() )
			->withSortableAttributes( [] );

		$this->loupe = ( new LoupeFactory() )->create( $path, $configuration );

		return $this->loupe;
	}

	/**
	 * Add or update a single document.
	 *
	 * @param array<string, mixed> $document Document.
	 * @return void
	 */
	public function upsert( array $document ): void {
		$this->get_loupe()->addDocument( $document );
	}

	/**
	 * Add or update many documents in one batch.
	 *
	 * @param array<int, array<string, mixed>> $documents Documents.
	 * @return void
	 */
	public function upsert_many( array $documents ): void {
		if ( empty( $documents ) ) {
			return;
		}
		$this->get_loupe()->addDocuments( array_values( $documents ) );
	}

	/**
	 * Delete a document by id.
	 *
	 * @param int $id Attachment ID.
	 * @return void
	 */
	public function delete( int $id ): void {
		$this->get_loupe()->deleteDocument( $id );
	}

	/**
	 * Number of documents currently indexed.
	 *
	 * @return int
	 */
	public function count(): int {
		try {
			return (int) $this->get_loupe()->countDocuments();
		} catch ( \Throwable $e ) {
			return 0;
		}
	}

	/**
	 * Delete the whole index directory and reset the in-memory instance.
	 *
	 * @return void
	 */
	public function delete_index(): void {
		$this->loupe = null;

		if ( ! class_exists( \WP_Filesystem_Direct::class ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		}

		$fs   = new \WP_Filesystem_Direct( false );
		$path = $this->get_index_path();

		if ( $fs->is_dir( $path ) ) {
			$fs->rmdir( $path, true );
		}
	}

	/**
	 * Searchable attributes for the Loupe configuration.
	 *
	 * @return string[]
	 */
	private function get_searchable_attributes(): array {
		/**
		 * Filters the searchable attributes of the media index.
		 *
		 * @param string[] $attributes Attribute names.
		 */
		return (array) apply_filters( 'vmfa_search_searchable_attributes', MediaSchema::SEARCHABLE );
	}

	/**
	 * Filterable attributes for the Loupe configuration.
	 *
	 * @return string[]
	 */
	private function get_filterable_attributes(): array {
		/**
		 * Filters the filterable attributes of the media index.
		 *
		 * @param string[] $attributes Attribute names.
		 */
		return (array) apply_filters( 'vmfa_search_filterable_attributes', MediaSchema::FILTERABLE );
	}
}

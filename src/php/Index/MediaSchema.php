<?php
/**
 * Media document schema.
 *
 * @package VmfaSearch
 */

declare(strict_types=1);

namespace VmfaSearch\Index;

defined( 'ABSPATH' ) || exit;

/**
 * Defines the searchable/filterable shape of a media item document and builds
 * documents from WordPress attachments.
 */
final class MediaSchema {

	/**
	 * Searchable attributes, ordered by descending weight.
	 *
	 * Loupe ranks matches by the order of searchable attributes, so title and
	 * filename (most identifying) come first, then alt, then the long-form text.
	 *
	 * @var string[]
	 */
	public const SEARCHABLE = [ 'title', 'filename', 'alt', 'caption', 'description' ];

	/**
	 * Filterable attributes (not searched, used for faceting/scoping).
	 *
	 * @var string[]
	 */
	public const FILTERABLE = [ 'folder', 'mime_type' ];

	/**
	 * The folder taxonomy owned by Virtual Media Folders.
	 */
	private const FOLDER_TAXONOMY = 'vmfo_folder';

	/**
	 * Build a Loupe document from an attachment.
	 *
	 * @param \WP_Post $post Attachment post object.
	 * @return array<string, mixed>
	 */
	public function build_document( \WP_Post $post ): array {
		$attached_file = (string) get_post_meta( $post->ID, '_wp_attached_file', true );
		$alt           = (string) get_post_meta( $post->ID, '_wp_attachment_image_alt', true );

		$document = [
			'id'          => (int) $post->ID,
			'title'       => (string) $post->post_title,
			'filename'    => '' !== $attached_file ? wp_basename( $attached_file ) : '',
			'alt'         => $alt,
			'caption'     => (string) $post->post_excerpt,
			'description' => (string) $post->post_content,
			'folder'      => $this->get_folder_id( $post->ID ),
			'mime_type'   => (string) $post->post_mime_type,
		];

		/**
		 * Filters the indexed document for a media item.
		 *
		 * Add-ons can enrich the document with extra fields. New searchable or
		 * filterable keys must also be registered via the matching filters in
		 * {@see MediaIndex}.
		 *
		 * @param array<string, mixed> $document The document.
		 * @param \WP_Post             $post     The attachment.
		 */
		return (array) apply_filters( 'vmfa_search_document', $document, $post );
	}

	/**
	 * Resolve the single folder term id an attachment belongs to (0 = uncategorized).
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return int
	 */
	private function get_folder_id( int $attachment_id ): int {
		$terms = get_the_terms( $attachment_id, self::FOLDER_TAXONOMY );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return 0;
		}

		return (int) $terms[0]->term_id;
	}
}

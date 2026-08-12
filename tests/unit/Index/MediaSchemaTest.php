<?php
/**
 * Tests for MediaSchema document building.
 *
 * @package VmfaSearch\Tests
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use VmfaSearch\Index\MediaSchema;

it( 'builds a document from an attachment', function () {
	Functions\when( 'get_post_meta' )->alias(
		function ( $id, $key ) {
			return match ( $key ) {
				'_wp_attached_file'         => '2024/05/beach-sunset.jpg',
				'_wp_attachment_image_alt'  => 'A sunset over the sea',
				default                     => '',
			};
		}
	);
	Functions\when( 'get_the_terms' )->justReturn( array( (object) array( 'term_id' => 12 ) ) );
	Functions\when( 'is_wp_error' )->justReturn( false );
	Functions\when( 'wp_basename' )->alias( fn( $path ) => basename( (string) $path ) );
	// apply_filters( $tag, $value, ... ) → return the value (2nd arg).
	Functions\when( 'apply_filters' )->returnArg( 2 );

	$post = new WP_Post(
		array(
			'ID'             => 5,
			'post_title'     => 'Beach',
			'post_excerpt'   => 'A caption',
			'post_content'   => 'A longer description',
			'post_mime_type' => 'image/jpeg',
		)
	);

	$document = ( new MediaSchema() )->build_document( $post );

	expect( $document['id'] )->toBe( 5 );
	expect( $document['title'] )->toBe( 'Beach' );
	expect( $document['filename'] )->toBe( 'beach-sunset.jpg' );
	expect( $document['alt'] )->toBe( 'A sunset over the sea' );
	expect( $document['caption'] )->toBe( 'A caption' );
	expect( $document['description'] )->toBe( 'A longer description' );
	expect( $document['folder'] )->toBe( 12 );
	expect( $document['mime_type'] )->toBe( 'image/jpeg' );
} );

it( 'defaults the folder to 0 when uncategorized', function () {
	Functions\when( 'get_post_meta' )->justReturn( '' );
	Functions\when( 'get_the_terms' )->justReturn( array() );
	Functions\when( 'is_wp_error' )->justReturn( false );
	Functions\when( 'wp_basename' )->alias( fn( $path ) => basename( (string) $path ) );
	Functions\when( 'apply_filters' )->returnArg( 2 );

	$post = new WP_Post( array( 'ID' => 7, 'post_title' => 'Untitled' ) );

	$document = ( new MediaSchema() )->build_document( $post );

	expect( $document['folder'] )->toBe( 0 );
	expect( $document['filename'] )->toBe( '' );
} );

<?php
/**
 * Tests for MediaQueryFilter interception guards.
 *
 * @package VmfaSearch\Tests
 */

declare(strict_types=1);

use VmfaSearch\Index\IndexStatus;
use VmfaSearch\Index\MediaIndex;
use VmfaSearch\Search\MediaQueryFilter;
use VmfaSearch\Search\SearchService;

it( 'leaves the grid query untouched when the search engine is unavailable', function () {
	$index  = new MediaIndex();
	$status = new IndexStatus();
	$filter = new MediaQueryFilter( new SearchService( $index ), $index, $status );

	$args = array(
		's'         => 'sunset',
		'post_type' => 'attachment',
	);

	// Loupe is not loaded in the unit environment, so interception is skipped.
	expect( $filter->filter_ajax_query( $args ) )->toBe( $args );
} );

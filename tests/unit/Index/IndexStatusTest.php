<?php
/**
 * Tests for IndexStatus.
 *
 * @package VmfaSearch\Tests
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use VmfaSearch\Index\IndexStatus;

it( 'reports built when last_built is set and not building', function () {
	Functions\when( 'get_option' )->justReturn(
		array(
			'building'   => false,
			'last_built' => 123456,
			'count'      => 5,
		)
	);

	expect( ( new IndexStatus() )->is_built() )->toBeTrue();
} );

it( 'reports not built while a rebuild is running', function () {
	Functions\when( 'get_option' )->justReturn(
		array(
			'building'   => true,
			'last_built' => 0,
		)
	);

	expect( ( new IndexStatus() )->is_built() )->toBeFalse();
	expect( ( new IndexStatus() )->is_building() )->toBeTrue();
} );

it( 'applies defaults when nothing is stored', function () {
	Functions\when( 'get_option' )->justReturn( array() );

	$status = ( new IndexStatus() )->get();

	expect( $status['building'] )->toBeFalse();
	expect( $status['processed'] )->toBe( 0 );
	expect( $status['total'] )->toBe( 0 );
	expect( $status['count'] )->toBe( 0 );
	expect( $status['last_built'] )->toBe( 0 );
} );

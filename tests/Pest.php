<?php
/**
 * Pest configuration.
 *
 * @package VmfaSearch\Tests
 */

declare(strict_types=1);

use Brain\Monkey;

uses()
	->beforeEach( function () {
		Monkey\setUp();
	} )
	->afterEach( function () {
		Monkey\tearDown();
	} )
	->in( 'unit' );

/**
 * Tests for the media search gate.
 *
 * @package VmfaSearch
 */

import { beforeEach, describe, expect, it } from 'vitest';

describe( 'media search gate', () => {
	beforeEach( () => {
		document.body.innerHTML = '<div class="media-frame"><input class="search" type="search"></div>';
		window.vmfaSearchGate = { minChars: 3 };
	} );

	it( 'blocks sub-threshold input and allows the threshold and empty value', async () => {
		await import( '../../assets/js/media-search-gate.js' );

		const input = document.querySelector( 'input.search' );
		const received = [];

		// Stand-in for core's delegated (bubble-phase) handler.
		document.addEventListener( 'input', ( event ) => received.push( event.target.value ), false );

		const type = ( value ) => {
			input.value = value;
			input.dispatchEvent( new window.Event( 'input', { bubbles: true } ) );
		};

		type( 'a' ); // 1 char — blocked
		type( 'ab' ); // 2 chars — blocked
		type( 'abc' ); // 3 chars — allowed
		type( '' ); // cleared — allowed

		expect( received ).toEqual( [ 'abc', '' ] );
	} );

	it( 'ignores search inputs outside a media frame', async () => {
		await import( '../../assets/js/media-search-gate.js' );

		document.body.innerHTML = '<input class="search" type="search">';
		const input = document.querySelector( 'input.search' );
		const received = [];
		document.addEventListener( 'input', ( event ) => received.push( event.target.value ), false );

		input.value = 'ab';
		input.dispatchEvent( new window.Event( 'input', { bubbles: true } ) );

		expect( received ).toEqual( [ 'ab' ] );
	} );
} );

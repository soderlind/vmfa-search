/**
 * Settings tab: index status + rebuild control.
 *
 * @package VmfaSearch
 */

/* global wp, vmfaSearchSettings */
( function () {
	'use strict';

	var cfg = window.vmfaSearchSettings || {};
	var i18n = wp.i18n;
	var __ = i18n.__;
	var sprintf = i18n.sprintf;

	var statusEl = document.getElementById( 'vmfa-search-status' );
	var button = document.getElementById( 'vmfa-search-rebuild' );

	if ( ! statusEl ) {
		return;
	}

	var pollTimer;

	/**
	 * Call a REST endpoint.
	 *
	 * @param {string} path   Endpoint path.
	 * @param {string} method HTTP method.
	 * @return {Promise<Object>} Parsed JSON.
	 */
	function api( path, method ) {
		return window
			.fetch( cfg.restUrl + path, {
				method: method || 'GET',
				headers: {
					'X-WP-Nonce': cfg.nonce,
					'Content-Type': 'application/json',
				},
			} )
			.then( function ( res ) {
				return res.json();
			} );
	}

	/**
	 * Render the status payload.
	 *
	 * @param {Object} status Status payload.
	 */
	function render( status ) {
		if ( ! status || ! status.available ) {
			statusEl.textContent = __( 'Search engine unavailable.', 'vmfa-search' );
			return;
		}

		if ( status.building ) {
			if ( button ) {
				button.disabled = true;
			}
			statusEl.textContent = sprintf(
				/* translators: 1: processed count, 2: total count */
				__( 'Building index… %1$d of %2$d', 'vmfa-search' ),
				status.processed,
				status.total
			);
			return;
		}

		if ( button ) {
			button.disabled = false;
		}

		if ( status.built ) {
			var parts = [
				sprintf(
					/* translators: %d: number of indexed media items */
					__( 'Indexed %d media items.', 'vmfa-search' ),
					status.count
				),
			];
			if ( status.lastBuilt ) {
				parts.push(
					sprintf(
						/* translators: %s: date/time */
						__( 'Last built: %s', 'vmfa-search' ),
						new Date( status.lastBuilt * 1000 ).toLocaleString()
					)
				);
			}
			statusEl.textContent = parts.join( ' ' );
		} else {
			statusEl.textContent = __( 'Index not built yet.', 'vmfa-search' );
		}
	}

	/**
	 * Poll while a rebuild runs.
	 */
	function poll() {
		window.clearTimeout( pollTimer );
		pollTimer = window.setTimeout( function () {
			api( 'index-status' ).then( function ( status ) {
				render( status );
				if ( status && status.building ) {
					poll();
				}
			} );
		}, 2000 );
	}

	if ( button ) {
		button.addEventListener( 'click', function () {
			button.disabled = true;
			api( 'rebuild', 'POST' ).then( function ( status ) {
				render( status );
				if ( status && status.building ) {
					poll();
				}
			} );
		} );
	}

	api( 'index-status' ).then( render );
} )();

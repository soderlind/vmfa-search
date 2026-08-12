/**
 * Gate the native wp.media search so it waits for the search threshold.
 *
 * WordPress core fires the media search at 2+ characters (debounced), which
 * re-renders the grid for terms we don't search yet — a visible flash. We
 * intercept the input event in the capture phase and stop it for sub-threshold
 * terms, so core's handler never runs and the grid stays put until the
 * threshold is reached. An empty value is always allowed through (to clear).
 *
 * @package VmfaSearch
 */

/* global vmfaSearchGate */
( function () {
	'use strict';

	var min = ( window.vmfaSearchGate && window.vmfaSearchGate.minChars ) || 3;

	document.addEventListener(
		'input',
		function ( event ) {
			var target = event.target;

			if ( ! target || 'INPUT' !== target.tagName || ! target.classList.contains( 'search' ) ) {
				return;
			}

			// Only gate the media frame's search field.
			if ( ! target.closest || ! target.closest( '.media-frame' ) ) {
				return;
			}

			var length = ( target.value || '' ).trim().length;

			if ( length > 0 && length < min ) {
				event.stopImmediatePropagation();
			}
		},
		true // Capture phase: run before core's delegated handler.
	);
} )();


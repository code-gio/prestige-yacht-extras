/* Prestige Yacht Extras — boat archive filtering (vanilla JS, no dependencies). */
( function () {
	'use strict';

	var cfg = window.pyeArchive || {};

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	ready( function () {
		var roots = document.querySelectorAll( '.pye-archive' );
		Array.prototype.forEach.call( roots, init );
	} );

	function init( root ) {
		var form = root.querySelector( '.pye-filters' );
		var results = root.querySelector( '.pye-results' );
		var grid = root.querySelector( '.pye-grid' );
		var empty = root.querySelector( '.pye-empty' );
		var errEl = root.querySelector( '.pye-error' );
		var loadWrap = root.querySelector( '.pye-loadmore-wrap' );
		var loadBtn = root.querySelector( '.pye-loadmore' );
		var paged = 1;

		// Segmented toggles set the active option (search runs on submit / Load More).
		Array.prototype.forEach.call( root.querySelectorAll( '.pye-seg' ), function ( group ) {
			group.addEventListener( 'click', function ( e ) {
				var btn = e.target.closest( '.pye-seg__btn' );
				if ( ! btn ) {
					return;
				}
				Array.prototype.forEach.call( group.querySelectorAll( '.pye-seg__btn' ), function ( b ) {
					b.classList.remove( 'is-active' );
				} );
				btn.classList.add( 'is-active' );
			} );
		} );

		if ( form ) {
			form.addEventListener( 'submit', function ( e ) {
				e.preventDefault();
				paged = 1;
				search( false );
			} );
		}

		if ( loadBtn ) {
			loadBtn.addEventListener( 'click', function () {
				paged += 1;
				search( true );
			} );
		}

		// Back/forward: the initial page is server-rendered from the URL, so reloading
		// keeps the controls and grid in sync with the restored filter state.
		window.addEventListener( 'popstate', function () {
			window.location.reload();
		} );

		function collect() {
			var p = { paged: paged, per_page: cfg.perPage || 12 };

			Array.prototype.forEach.call( root.querySelectorAll( '.pye-seg' ), function ( group ) {
				var active = group.querySelector( '.pye-seg__btn.is-active' );
				if ( active ) {
					p[ active.getAttribute( 'data-filter' ) ] = active.getAttribute( 'data-value' ) || '';
				}
			} );

			Array.prototype.forEach.call( root.querySelectorAll( '[data-filter]' ), function ( el ) {
				if ( el.classList.contains( 'pye-seg__btn' ) ) {
					return;
				}
				p[ el.getAttribute( 'data-filter' ) ] = el.value || '';
			} );

			return p;
		}

		function search( append ) {
			var params = collect();
			results.classList.add( 'is-loading' );
			if ( errEl ) {
				errEl.hidden = true;
			}
			if ( loadBtn ) {
				loadBtn.disabled = true;
			}

			var body = new URLSearchParams();
			body.append( 'action', cfg.action );
			body.append( 'nonce', cfg.nonce );
			Object.keys( params ).forEach( function ( k ) {
				body.append( k, params[ k ] );
			} );

			fetch( cfg.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body.toString(),
			} )
				.then( function ( r ) {
					return r.json();
				} )
				.then( function ( res ) {
					if ( ! res || ! res.success ) {
						throw new Error( 'request failed' );
					}
					var d = res.data;
					if ( append ) {
						grid.insertAdjacentHTML( 'beforeend', d.html );
					} else {
						grid.innerHTML = d.html;
					}
					if ( empty ) {
						empty.hidden = d.total > 0;
					}
					if ( loadWrap ) {
						loadWrap.hidden = ! d.has_more;
					}
					updateUrl( params );
				} )
				.catch( function () {
					if ( errEl ) {
						errEl.hidden = false;
					}
					if ( append ) {
						paged -= 1; // roll back the failed page.
					}
				} )
				.then( function () {
					results.classList.remove( 'is-loading' );
					if ( loadBtn ) {
						loadBtn.disabled = false;
					}
				} );
		}

		function updateUrl( params ) {
			if ( ! window.history || ! window.history.pushState ) {
				return;
			}
			var url = new URL( window.location.href );
			[ 'category', 'condition', 'mfr', 'len_min', 'len_max', 'price_min', 'price_max' ].forEach( function ( k ) {
				if ( params[ k ] ) {
					url.searchParams.set( k, params[ k ] );
				} else {
					url.searchParams.delete( k );
				}
			} );
			url.searchParams.delete( 'paged' );
			window.history.pushState( {}, '', url.toString() );
		}
	}
} )();

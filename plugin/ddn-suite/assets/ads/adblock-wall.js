( function () {
	'use strict';

	var cfg = window.ddnAdblock || {};
	var WALL_ID = 'ddn-adblock-wall';

	function detect( done ) {
		var bait = document.createElement( 'div' );
		bait.className = 'adsbox ad-banner ad-placement ad-unit adsbygoogle pub_300x250 pub_728x90 text-ad textAd text_ads text-ads';
		bait.setAttribute( 'aria-hidden', 'true' );
		bait.style.cssText = 'position:absolute!important;left:-9999px!important;top:-9999px!important;width:10px!important;height:10px!important;';
		document.body.appendChild( bait );

		window.setTimeout( function () {
			var style = window.getComputedStyle( bait );
			var blocked = ! bait.parentNode || 0 === bait.offsetHeight || 'none' === style.display || 'hidden' === style.visibility;
			if ( bait.parentNode ) {
				bait.parentNode.removeChild( bait );
			}
			done( blocked );
		}, 150 );
	}

	function build() {
		var wall = document.createElement( 'div' );
		wall.id = WALL_ID;
		wall.setAttribute( 'role', 'dialog' );
		wall.setAttribute( 'aria-modal', 'true' );
		wall.setAttribute( 'aria-labelledby', WALL_ID + '-title' );

		var card = document.createElement( 'div' );
		card.className = 'ddn-adblock-wall__card';

		var title = document.createElement( 'h2' );
		title.id = WALL_ID + '-title';
		title.textContent = cfg.title || '';

		var text = document.createElement( 'p' );
		text.textContent = cfg.text || '';

		var button = document.createElement( 'button' );
		button.type = 'button';
		button.textContent = cfg.button || '';
		button.addEventListener( 'click', function () {
			window.location.reload();
		} );

		card.appendChild( title );
		card.appendChild( text );
		card.appendChild( button );
		wall.appendChild( card );

		return wall;
	}

	function show() {
		var wall = build();
		document.documentElement.classList.add( 'ddn-adblock-open' );
		document.body.appendChild( wall );
		wall.querySelector( 'button' ).focus();

		// Si alguien quita el aviso del DOM, se vuelve a poner.
		new MutationObserver( function () {
			if ( ! document.getElementById( WALL_ID ) ) {
				document.body.appendChild( wall );
			}
		} ).observe( document.body, { childList: true } );
	}

	function start() {
		detect( function ( blocked ) {
			if ( blocked ) {
				show();
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );

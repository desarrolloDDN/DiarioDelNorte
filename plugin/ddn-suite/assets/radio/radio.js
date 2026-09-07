/**
 * Reproductor de radio flotante · DDN Suite
 *
 * El marcado lo imprime PHP en el pie. Este script cablea play/pausa por
 * emisora, minimizar/expandir y cerrar, y recuerda el estado en
 * localStorage para que siga igual al cambiar de página.
 */
( function () {
	'use strict';

	var KEY = {
		stream: 'ddn-radio:stream',
		paused: 'ddn-radio:paused',
		mini: 'ddn-radio:minimized'
	};

	function read( k ) {
		try {
			return window.localStorage.getItem( k );
		} catch ( e ) {
			return null;
		}
	}
	function store( k, v ) {
		try {
			window.localStorage.setItem( k, v );
		} catch ( e ) {}
	}

	function init() {
		var root = document.getElementById( 'ddn-radio' );
		if ( ! root ) {
			return;
		}

		var audio = root.querySelector( '[data-radio-audio]' );
		var stations = Array.prototype.slice.call( root.querySelectorAll( '.ddn-radio__station' ) );
		var minBtn = root.querySelector( '[data-radio-min]' );
		var closeBtn = root.querySelector( '[data-radio-close]' );

		if ( ! audio || ! stations.length ) {
			return;
		}

		function resetAll() {
			stations.forEach( function ( s ) {
				s.classList.remove( 'is-active' );
				var state = s.querySelector( '[data-radio-state]' );
				var play = s.querySelector( '[data-radio-play]' );
				if ( state ) {
					state.textContent = state.getAttribute( 'data-label-idle' ) || '';
				}
				if ( play ) {
					play.textContent = '▶';
				}
			} );
		}

		function activate( station ) {
			station.classList.add( 'is-active' );
			var state = station.querySelector( '[data-radio-state]' );
			var play = station.querySelector( '[data-radio-play]' );
			if ( state ) {
				state.textContent = state.getAttribute( 'data-label-live' ) || '';
			}
			if ( play ) {
				play.textContent = '⏸';
			}
		}

		stations.forEach( function ( station ) {
			var btn = station.querySelector( '[data-radio-play]' );
			var stream = station.getAttribute( 'data-stream' );
			if ( ! btn || ! stream ) {
				return;
			}

			btn.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				var playingThis = audio.src === stream && ! audio.paused;

				resetAll();

				if ( playingThis ) {
					audio.pause();
					store( KEY.paused, 'true' );
					return;
				}

				if ( audio.src !== stream ) {
					audio.src = stream;
				}
				audio.play().catch( function () {} );
				activate( station );
				store( KEY.stream, stream );
				store( KEY.paused, 'false' );
			} );
		} );

		if ( minBtn ) {
			minBtn.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				root.classList.add( 'is-minimized' );
				root.classList.remove( 'is-expanded' );
				store( KEY.mini, 'true' );
			} );
		}

		root.addEventListener( 'click', function () {
			if ( root.classList.contains( 'is-minimized' ) ) {
				root.classList.remove( 'is-minimized' );
				root.classList.add( 'is-expanded' );
				store( KEY.mini, 'false' );
			}
		} );

		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				audio.pause();
				root.parentNode.removeChild( root );
			} );
		}

		/* --- Restaurar estado --- */
		if ( read( KEY.mini ) === 'true' ) {
			root.classList.add( 'is-minimized' );
			root.classList.remove( 'is-expanded' );
		}

		var savedStream = read( KEY.stream );
		var wasPaused = read( KEY.paused ) === 'true';

		if ( savedStream ) {
			stations.forEach( function ( station ) {
				if ( station.getAttribute( 'data-stream' ) !== savedStream ) {
					return;
				}
				audio.src = savedStream;
				if ( wasPaused ) {
					return;
				}
				audio
					.play()
					.then( function () {
						resetAll();
						activate( station );
					} )
					.catch( function () {} );
			} );
		}

		root.hidden = false;
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );

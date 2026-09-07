/**
 * Reproductor de radio flotante · DDN Suite
 *
 * El marcado lo imprime PHP en el pie. Aquí: play/pausa por emisora con
 * estados de conexión y reintento, volumen/silencio, minimizar/expandir,
 * cerrar, «sonando ahora», controles del sistema (Media Session) y conteo
 * de escuchas. El estado se recuerda en localStorage.
 */
( function () {
	'use strict';

	var CFG = window.ddnRadio || {};
	var T = CFG.i18n || {};
	var KEY = {
		stream: 'ddn-radio:stream',
		paused: 'ddn-radio:paused',
		mini: 'ddn-radio:minimized',
		volume: 'ddn-radio:volume',
		muted: 'ddn-radio:muted'
	};
	var BEAT_MS = 30000;
	var POLL_MS = 20000;
	var MAX_RETRY = 30000;

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
		var muteBtn = root.querySelector( '[data-radio-mute]' );
		var volInput = root.querySelector( '[data-radio-volume]' );
		if ( ! audio || ! stations.length ) {
			return;
		}

		var current = null; // .ddn-radio__station en curso
		var retryMs = 2000;
		var retryTimer = null;
		var beatTimer = null;
		var pollTimer = null;
		var fadeTimer = null;

		/* ---------- utilidades de estado visual ---------- */

		function setState( station, text ) {
			var el = station && station.querySelector( '[data-radio-state]' );
			if ( el ) {
				el.textContent = text;
			}
		}

		function markPlaying( on ) {
			root.classList.toggle( 'is-playing', !! on );
			if ( 'mediaSession' in navigator ) {
				navigator.mediaSession.playbackState = on ? 'playing' : 'paused';
			}
		}

		function resetAll() {
			stations.forEach( function ( s ) {
				s.classList.remove( 'is-active', 'is-loading', 'is-blocked' );
				setState( s, T.idle || 'Detenida' );
				var play = s.querySelector( '[data-radio-play]' );
				if ( play ) {
					play.textContent = '▶';
					play.setAttribute( 'aria-pressed', 'false' );
				}
			} );
		}

		/* ---------- Media Session ---------- */

		function updateMediaSession( station, title ) {
			if ( ! ( 'mediaSession' in navigator ) || ! station ) {
				return;
			}
			var name = station.querySelector( 'strong' );
			var art = ( CFG.artwork || [] )[ Number( station.getAttribute( 'data-station' ) ) ] || '';
			try {
				navigator.mediaSession.metadata = new window.MediaMetadata( {
					title: title || ( T.live || 'En vivo' ),
					artist: name ? name.textContent : 'Diario del Norte',
					album: 'Diario del Norte',
					artwork: art ? [ { src: art, sizes: '96x96', type: 'image/png' } ] : []
				} );
			} catch ( e ) {}
		}

		if ( 'mediaSession' in navigator ) {
			try {
				navigator.mediaSession.setActionHandler( 'play', function () {
					if ( current ) {
						play( current );
					}
				} );
				navigator.mediaSession.setActionHandler( 'pause', function () {
					audio.pause();
				} );
				navigator.mediaSession.setActionHandler( 'stop', function () {
					audio.pause();
				} );
			} catch ( e ) {}
		}

		/* ---------- escuchas (REST) ---------- */

		function tick( kind ) {
			if ( ! CFG.rest || ! current ) {
				return;
			}
			var body = JSON.stringify( {
				station: Number( current.getAttribute( 'data-station' ) ),
				kind: kind
			} );
			try {
				fetch( CFG.rest + '/tick', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce || '' },
					body: body,
					keepalive: true
				} ).catch( function () {} );
			} catch ( e ) {}
		}

		function startBeats() {
			stopBeats();
			tick( 'start' );
			beatTimer = window.setInterval( function () {
				if ( ! audio.paused ) {
					tick( 'beat' );
				}
			}, BEAT_MS );
		}
		function stopBeats() {
			if ( beatTimer ) {
				window.clearInterval( beatTimer );
				beatTimer = null;
			}
		}

		/* ---------- «sonando ahora» ---------- */

		function poll() {
			stopPoll();
			if ( ! CFG.rest || ! current ) {
				return;
			}
			var idx = Number( current.getAttribute( 'data-station' ) );
			var run = function () {
				fetch( CFG.rest + '/nowplaying?station=' + idx )
					.then( function ( r ) {
						return r.ok ? r.json() : null;
					} )
					.then( function ( data ) {
						if ( ! data || ! current || Number( current.getAttribute( 'data-station' ) ) !== idx ) {
							return;
						}
						if ( audio.paused ) {
							return;
						}
						var label = data.title ? data.title : ( T.live || 'En vivo' );
						if ( typeof data.listeners === 'number' && data.listeners > 0 ) {
							label += ' · ' + data.listeners;
						}
						setState( current, label );
						updateMediaSession( current, data.title || '' );
					} )
					.catch( function () {} );
			};
			run();
			pollTimer = window.setInterval( run, POLL_MS );
		}
		function stopPoll() {
			if ( pollTimer ) {
				window.clearInterval( pollTimer );
				pollTimer = null;
			}
		}

		/* ---------- volumen ---------- */

		function targetVolume() {
			var v = parseInt( read( KEY.volume ), 10 );
			return isNaN( v ) ? 1 : Math.min( 1, Math.max( 0, v / 100 ) );
		}

		function applyVolume() {
			var muted = read( KEY.muted ) === 'true';
			audio.muted = muted;
			audio.volume = targetVolume();
			if ( volInput ) {
				volInput.value = String( Math.round( targetVolume() * 100 ) );
			}
			if ( muteBtn ) {
				muteBtn.textContent = muted || targetVolume() === 0 ? '🔇' : '🔊';
				muteBtn.setAttribute( 'aria-pressed', muted ? 'true' : 'false' );
			}
		}

		if ( volInput ) {
			volInput.addEventListener( 'input', function () {
				store( KEY.volume, volInput.value );
				store( KEY.muted, 'false' );
				if ( ! fadeTimer ) {
					audio.volume = targetVolume();
				}
				applyVolume();
			} );
		}
		if ( muteBtn ) {
			muteBtn.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				store( KEY.muted, read( KEY.muted ) === 'true' ? 'false' : 'true' );
				applyVolume();
			} );
		}

		/* ---------- fundido al cambiar de emisora ---------- */

		function fadeSwitch( to ) {
			if ( fadeTimer ) {
				window.clearInterval( fadeTimer );
			}
			var step = 0.12;
			fadeTimer = window.setInterval( function () {
				if ( audio.volume > step ) {
					audio.volume = Math.max( 0, audio.volume - step );
				} else {
					window.clearInterval( fadeTimer );
					fadeTimer = null;
					to();
					audio.volume = 0;
					var up = window.setInterval( function () {
						var t = targetVolume();
						if ( audio.volume < t - step ) {
							audio.volume = Math.min( t, audio.volume + step );
						} else {
							audio.volume = t;
							window.clearInterval( up );
						}
					}, 40 );
				}
			}, 40 );
		}

		/* ---------- reproducción ---------- */

		function clearRetry() {
			if ( retryTimer ) {
				window.clearTimeout( retryTimer );
				retryTimer = null;
			}
			retryMs = 2000;
		}

		function scheduleRetry() {
			if ( retryTimer || ! current ) {
				return;
			}
			setState( current, T.error || 'Sin señal, reintentando…' );
			retryTimer = window.setTimeout( function () {
				retryTimer = null;
				if ( current && audio.paused === false ) {
					return;
				}
				if ( current ) {
					play( current, true );
				}
				retryMs = Math.min( MAX_RETRY, retryMs * 2 );
			}, retryMs );
		}

		function play( station, isRetry ) {
			var stream = station.getAttribute( 'data-stream' );
			if ( ! stream ) {
				return;
			}

			var switching = current && current !== station;
			resetAll();
			current = station;
			clearRetry();

			station.classList.add( 'is-active', 'is-loading' );
			var playBtn = station.querySelector( '[data-radio-play]' );
			if ( playBtn ) {
				playBtn.setAttribute( 'aria-pressed', 'true' );
			}
			setState( station, T.connecting || 'Conectando…' );

			var go = function () {
				if ( audio.src !== stream ) {
					audio.src = stream;
				}
				applyVolume();
				audio.play().then( function () {
					store( KEY.stream, stream );
					store( KEY.paused, 'false' );
				} ).catch( function () {
					// Autoplay bloqueado o error de red.
					station.classList.remove( 'is-loading' );
					if ( isRetry ) {
						scheduleRetry();
					} else {
						station.classList.add( 'is-blocked' );
						setState( station, T.resume || 'Pulsa para reanudar' );
					}
				} );
			};

			if ( switching && ! audio.paused ) {
				fadeSwitch( go );
			} else {
				go();
			}
		}

		function toggle( station ) {
			var stream = station.getAttribute( 'data-stream' );
			if ( current === station && ! audio.paused && ! station.classList.contains( 'is-blocked' ) ) {
				audio.pause();
				store( KEY.paused, 'true' );
				return;
			}
			play( station );
		}

		stations.forEach( function ( station ) {
			var btn = station.querySelector( '[data-radio-play]' );
			if ( btn ) {
				btn.addEventListener( 'click', function ( e ) {
					e.stopPropagation();
					toggle( station );
				} );
			}
		} );

		/* ---------- eventos del <audio> ---------- */

		audio.addEventListener( 'playing', function () {
			clearRetry();
			markPlaying( true );
			if ( current ) {
				current.classList.remove( 'is-loading', 'is-blocked' );
				current.classList.add( 'is-active' );
				setState( current, T.live || 'En vivo' );
				var onBtn = current.querySelector( '[data-radio-play]' );
				if ( onBtn ) {
					onBtn.textContent = '⏸';
				}
				updateMediaSession( current, '' );
			}
			startBeats();
			poll();
		} );

		audio.addEventListener( 'pause', function () {
			markPlaying( false );
			stopBeats();
			stopPoll();
			if ( current ) {
				current.classList.remove( 'is-loading' );
				setState( current, T.idle || 'Detenida' );
				var offBtn = current.querySelector( '[data-radio-play]' );
				if ( offBtn ) {
					offBtn.textContent = '▶';
					offBtn.setAttribute( 'aria-pressed', 'false' );
				}
			}
		} );

		audio.addEventListener( 'waiting', function () {
			if ( current && ! audio.paused ) {
				current.classList.add( 'is-loading' );
				setState( current, T.buffering || 'Cargando…' );
			}
		} );

		audio.addEventListener( 'stalled', scheduleRetry );
		audio.addEventListener( 'error', function () {
			if ( current && ! audio.paused ) {
				markPlaying( false );
				scheduleRetry();
			}
		} );

		/* ---------- minimizar / expandir ---------- */

		function setMinimized( on ) {
			root.classList.toggle( 'is-minimized', on );
			root.classList.toggle( 'is-expanded', ! on );
			store( KEY.mini, on ? 'true' : 'false' );
			if ( on ) {
				root.setAttribute( 'role', 'button' );
				root.setAttribute( 'tabindex', '0' );
				root.setAttribute( 'aria-label', T.open || 'Abrir la radio' );
			} else {
				root.removeAttribute( 'role' );
				root.removeAttribute( 'tabindex' );
				root.removeAttribute( 'aria-label' );
			}
		}

		if ( minBtn ) {
			minBtn.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				setMinimized( true );
			} );
		}

		root.addEventListener( 'click', function () {
			if ( root.classList.contains( 'is-minimized' ) ) {
				setMinimized( false );
			}
		} );
		root.addEventListener( 'keydown', function ( e ) {
			if ( root.classList.contains( 'is-minimized' ) && ( e.key === 'Enter' || e.key === ' ' ) ) {
				e.preventDefault();
				setMinimized( false );
			}
		} );

		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				audio.pause();
				stopBeats();
				stopPoll();
				clearRetry();
				root.parentNode.removeChild( root );
			} );
		}

		/* ---------- restaurar estado ---------- */

		applyVolume();

		if ( read( KEY.mini ) === 'true' || ( read( KEY.mini ) === null && CFG.startMinimized ) ) {
			setMinimized( true );
		}

		var savedStream = read( KEY.stream );
		var wasPaused = read( KEY.paused ) === 'true';
		var restore = null;

		stations.forEach( function ( station ) {
			if ( savedStream && station.getAttribute( 'data-stream' ) === savedStream ) {
				restore = station;
			}
		} );

		if ( restore && savedStream && ! wasPaused ) {
			// Venía sonando: reanudar (si el navegador lo bloquea, queda «Pulsa para reanudar»).
			play( restore, false );
		} else {
			if ( ! restore && CFG.defaultStation >= 0 && stations[ CFG.defaultStation ] ) {
				restore = stations[ CFG.defaultStation ];
			}
			if ( restore ) {
				current = restore;
				restore.classList.add( 'is-active' );
				setState( restore, T.idle || 'Detenida' );
			}
		}

		root.hidden = false;
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );

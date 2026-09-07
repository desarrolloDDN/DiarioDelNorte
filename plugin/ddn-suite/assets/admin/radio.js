/**
 * Página «Radio» de DDN Suite: filas de emisora repetibles, selector de
 * logo, botón «Probar» y aviso de contenido mixto (http:// en una web
 * https://).
 */
( function ( $ ) {
	'use strict';

	var A = window.ddnRadioAdmin || {};
	var T = A.i18n || {};

	function checkMixed( $row ) {
		var url = String( $row.find( '.ddn-radio-row__stream' ).val() || '' ).trim();
		var $warn = $row.find( '.ddn-radio-row__warn' );
		if ( A.siteIsHttps && /^http:\/\//i.test( url ) ) {
			$warn.text( T.mixed || 'URL http:// en una web https://.' ).show();
		} else {
			$warn.hide();
		}
	}

	function testStream( $row ) {
		var url = String( $row.find( '.ddn-radio-row__stream' ).val() || '' ).trim();
		var $out = $row.find( '.ddn-radio-row__result' );
		if ( ! url ) {
			return;
		}
		$out.css( 'color', '#646970' ).text( T.testing || 'Probando…' );

		var audio = new Audio();
		audio.preload = 'auto';
		var done = false;
		var finish = function ( ok ) {
			if ( done ) {
				return;
			}
			done = true;
			try {
				audio.pause();
				audio.src = '';
			} catch ( e ) {}
			$out.css( 'color', ok ? '#00713b' : '#b32d2e' ).text( ok ? ( T.ok || 'OK' ) : ( T.fail || 'Falla' ) );
		};
		audio.addEventListener( 'canplay', function () {
			finish( true );
		} );
		audio.addEventListener( 'playing', function () {
			finish( true );
		} );
		audio.addEventListener( 'error', function () {
			finish( false );
		} );
		window.setTimeout( function () {
			finish( false );
		}, 8000 );
		audio.src = url;
		audio.play().catch( function () {} );
	}

	function bindRow( $row ) {
		$row.find( '.ddn-radio-row__pick' )
			.off( 'click' )
			.on( 'click', function ( e ) {
				e.preventDefault();
				var frame = wp.media( {
					title: T.pickLogo || 'Logo',
					button: { text: T.useLogo || 'Usar' },
					multiple: false,
					library: { type: 'image' }
				} );
				frame.on( 'select', function () {
					var att = frame.state().get( 'selection' ).first().toJSON();
					var url = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
					$row.find( '.ddn-radio-row__id' ).val( att.id );
					$row.find( '.ddn-radio-row__img' ).attr( 'src', url ).show();
					$row.find( '.ddn-radio-row__clear' ).show();
				} );
				frame.open();
			} );

		$row.find( '.ddn-radio-row__clear' )
			.off( 'click' )
			.on( 'click', function ( e ) {
				e.preventDefault();
				$row.find( '.ddn-radio-row__id' ).val( 0 );
				$row.find( '.ddn-radio-row__img' ).attr( 'src', '' ).hide();
				$( this ).hide();
			} );

		$row.find( '.ddn-radio-row__del' )
			.off( 'click' )
			.on( 'click', function ( e ) {
				e.preventDefault();
				$row.remove();
			} );

		$row.find( '.ddn-radio-row__test' )
			.off( 'click' )
			.on( 'click', function ( e ) {
				e.preventDefault();
				testStream( $row );
			} );

		$row.find( '.ddn-radio-row__stream' )
			.off( 'input blur' )
			.on( 'input blur', function () {
				checkMixed( $row );
			} );

		checkMixed( $row );
	}

	$( function () {
		var $body = $( '#ddn-radio-rows tbody' );
		if ( ! $body.length ) {
			return;
		}

		$body.find( '.ddn-radio-row' ).each( function () {
			bindRow( $( this ) );
		} );

		$( '#ddn-radio-add' ).on( 'click', function ( e ) {
			e.preventDefault();
			var index = 'n' + Date.now();
			var html = $( '#ddn-radio-row-tpl' ).html().replace( /__i__/g, index );
			var $row = $( $.parseHTML( html ) ).filter( 'tr' );
			$body.append( $row );
			bindRow( $row );
		} );
	} );
}( jQuery ) );

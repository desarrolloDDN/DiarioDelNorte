/**
 * Página «Radio» de DDN Suite: filas de emisora repetibles y selector de
 * logo desde la biblioteca de medios.
 */
( function ( $ ) {
	'use strict';

	function bindRow( $row ) {
		$row.find( '.ddn-radio-row__pick' )
			.off( 'click' )
			.on( 'click', function ( e ) {
				e.preventDefault();
				var frame = wp.media( {
					title: 'Logo de la emisora',
					button: { text: 'Usar este logo' },
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

/* global jQuery, wp */
( function ( $ ) {
	'use strict';

	$( function () {
		var $wrap = $( '.ddn-author-photo' );
		if ( ! $wrap.length || ! wp || ! wp.media ) {
			return;
		}

		var $input = $wrap.find( 'input[type=hidden]' );
		var $img = $wrap.find( 'img' );
		var $clear = $wrap.find( '.ddn-author-photo__clear' );
		var frame;

		$wrap.on( 'click', '.ddn-author-photo__choose', function ( e ) {
			e.preventDefault();
			frame = frame || wp.media( {
				title: 'Foto de perfil',
				multiple: false,
				library: { type: 'image' },
				button: { text: 'Usar esta foto' }
			} );
			frame.off( 'select' ).on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				var url = ( att.sizes && att.sizes.thumbnail ) ? att.sizes.thumbnail.url : att.url;
				$input.val( att.id );
				$img.attr( 'src', url ).show();
				$clear.show();
			} );
			frame.open();
		} );

		$clear.on( 'click', function ( e ) {
			e.preventDefault();
			$input.val( '' );
			$img.attr( 'src', '' ).hide();
			$( this ).hide();
		} );
	} );
}( jQuery ) );

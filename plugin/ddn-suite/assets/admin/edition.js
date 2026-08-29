/* global jQuery, wp */
( function ( $ ) {
	'use strict';

	$( function () {
		var $wrap = $( '.ddn-edition-pdf' );
		if ( ! $wrap.length || ! wp || ! wp.media ) {
			return;
		}

		var $input = $wrap.find( 'input[type=hidden]' );
		var $name = $wrap.find( '.ddn-edition-pdf__name' );
		var $clear = $wrap.find( '.ddn-edition-pdf__clear' );
		var frame;

		$wrap.on( 'click', '.ddn-edition-pdf__choose', function ( e ) {
			e.preventDefault();
			frame = frame || wp.media( {
				title: 'Selecciona o sube el PDF de la edición',
				library: { type: 'application/pdf' },
				multiple: false,
				button: { text: 'Usar este PDF' }
			} );
			frame.off( 'select' ).on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				$input.val( att.id );
				$name.text( att.filename || att.url );
				$clear.show();
			} );
			frame.open();
		} );

		$clear.on( 'click', function ( e ) {
			e.preventDefault();
			$input.val( '' );
			$name.text( 'Ningún PDF cargado.' );
			$( this ).hide();
		} );
	} );
}( jQuery ) );

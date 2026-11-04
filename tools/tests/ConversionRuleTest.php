<?php
/**
 * Pruebas de qué formatos de imagen se convierten al subirlos.
 */

declare(strict_types=1);

use DiarioDelNorte\Suite\Media\Support\ConversionRule;

ddn_test(
	'ConversionRule: HEIC, TIFF y BMP siempre van a JPG',
	static function (): void {
		foreach ( array( 'image/heic', 'image/heif', 'image/tiff', 'image/bmp', 'image/x-ms-bmp' ) as $mime ) {
			ddn_assert( 'image/jpeg' === ConversionRule::target( $mime, false ), "{$mime} -> JPG" );
		}
		ddn_assert( 'image/jpeg' === ConversionRule::target( 'IMAGE/HEIC', false ), 'el tipo se compara sin importar mayúsculas' );
	}
);

ddn_test(
	'ConversionRule: WebP y AVIF van a JPG, o a PNG si traen transparencia',
	static function (): void {
		ddn_assert( 'image/jpeg' === ConversionRule::target( 'image/webp', false ), 'WebP sin transparencia -> JPG' );
		ddn_assert( 'image/png' === ConversionRule::target( 'image/webp', true ), 'WebP con transparencia -> PNG (no pierde el fondo)' );
		ddn_assert( 'image/jpeg' === ConversionRule::target( 'image/avif', false ), 'AVIF sin transparencia -> JPG' );
		ddn_assert( 'image/png' === ConversionRule::target( 'image/avif', true ), 'AVIF con transparencia -> PNG' );
	}
);

ddn_test(
	'ConversionRule: JPG, PNG, GIF y SVG no se tocan',
	static function (): void {
		foreach ( array( 'image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'application/pdf', '' ) as $mime ) {
			ddn_assert( null === ConversionRule::target( $mime, true ), "'{$mime}' queda como está" );
		}
		ddn_assert( 'png' === ConversionRule::extension( 'image/png' ) && 'jpg' === ConversionRule::extension( 'image/jpeg' ), 'extensión según el destino' );
	}
);

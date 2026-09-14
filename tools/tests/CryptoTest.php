<?php
declare(strict_types=1);

use DiarioDelNorte\Suite\Subscribers\Support\Crypto;

ddn_test(
	'Crypto: cifra y descifra de vuelta el mismo valor (round-trip real con libsodium)',
	static function (): void {
		$secret    = 'un-secreto-de-prueba-no-el-real';
		$encrypted = Crypto::encrypt( '1004567890', $secret );

		ddn_assert( '1004567890' !== $encrypted, 'el valor guardado no es el texto plano' );
		ddn_assert( str_starts_with( $encrypted, 'ddn:sbx1:' ), 'lleva el prefijo de versión del cifrado' );
		ddn_assert( '1004567890' === Crypto::decrypt( $encrypted, $secret ), 'se descifra de vuelta al original' );
	}
);

ddn_test(
	'Crypto: con una clave distinta no logra descifrar (no revienta, devuelve vacío)',
	static function (): void {
		$encrypted = Crypto::encrypt( '1004567890', 'secreto-a' );
		ddn_assert( '' === Crypto::decrypt( $encrypted, 'secreto-b' ), 'clave equivocada: cadena vacía, no un error' );
	}
);

ddn_test(
	'Crypto: valores preexistentes en texto plano (de antes del cifrado) se leen tal cual',
	static function (): void {
		ddn_assert( '1004567890' === Crypto::decrypt( '1004567890', 'cualquier-secreto' ), 'sin el prefijo, se devuelve igual' );
	}
);

ddn_test(
	'Crypto: sin la extensión sodium, degrada a texto plano en vez de reventar',
	static function (): void {
		$plain = Crypto::encrypt( '1004567890', 'secreto', false );
		ddn_assert( '1004567890' === $plain, 'sin sodium, encrypt() guarda tal cual' );

		// Un valor SÍ cifrado (con sodium disponible) que luego se intenta
		// leer sin sodium: vacío, nunca un error fatal.
		$encrypted = Crypto::encrypt( '1004567890', 'secreto', true );
		ddn_assert( '' === Crypto::decrypt( $encrypted, 'secreto', false ), 'sin sodium no puede leer lo cifrado: vacío, no revienta' );
	}
);

ddn_test(
	'Crypto: una cadena vacía se queda vacía (nada que cifrar)',
	static function (): void {
		ddn_assert( '' === Crypto::encrypt( '', 'secreto' ), 'encrypt de vacío es vacío' );
		ddn_assert( '' === Crypto::decrypt( '', 'secreto' ), 'decrypt de vacío es vacío' );
	}
);

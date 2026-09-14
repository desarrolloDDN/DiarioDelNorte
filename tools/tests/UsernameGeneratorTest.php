<?php
declare(strict_types=1);

use DiarioDelNorte\Suite\Subscribers\Support\UsernameGenerator;

ddn_test(
	'UsernameGenerator: resuelve colisiones con un sufijo numérico',
	static function (): void {
		$existing = array( 'ana.perez', 'ana.perez2' );
		$exists   = static fn ( string $c ): bool => in_array( $c, $existing, true );

		$username = UsernameGenerator::generate( 'ana.perez@example.com', $exists );

		ddn_assert( 'ana.perez3' === $username, "genera ana.perez3 saltando las que ya existen (vino: {$username})" );
	}
);

ddn_test(
	'UsernameGenerator: a partir de un correo, usa solo la parte antes de la @ (no arrastra el dominio)',
	static function (): void {
		$username = UsernameGenerator::generate( 'ana.perez@example.com', static fn (): bool => false );
		ddn_assert( 'ana.perez' === $username, "no debe incluir el dominio (vino: {$username})" );
	}
);

ddn_test(
	'UsernameGenerator: sin colisión, no agrega sufijo',
	static function (): void {
		$username = UsernameGenerator::generate( 'nueva@example.com', static fn (): bool => false );
		ddn_assert( 'nueva' === $username, "usa el nombre base tal cual (vino: {$username})" );
	}
);

ddn_test(
	'UsernameGenerator::slugify: quita acentos y ñ, minúsculas, sin caracteres raros',
	static function (): void {
		ddn_assert( 'nino.pena' === UsernameGenerator::slugify( 'Niño Peña' ), 'acentos y ñ resueltos' );
		ddn_assert( 'jose.perez' === UsernameGenerator::slugify( 'José! Pérez#' ), 'símbolos fuera' );
	}
);

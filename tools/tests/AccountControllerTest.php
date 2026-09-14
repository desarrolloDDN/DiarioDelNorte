<?php
declare(strict_types=1);

use DiarioDelNorte\Suite\Subscribers\AccountController;

ddn_test(
	'AccountController::verify_password: contraseña actual incorrecta se rechaza (no se cambia nada)',
	static function (): void {
		// $checker simula wp_check_password($provided, $hash): en producción
		// compara contra el hash real; aquí, contra un valor fijo.
		$checker = static fn ( string $provided, string $hash ): bool => 'la-correcta' === $provided;

		ddn_assert(
			false === AccountController::verify_password( 'lo-que-sea', 'hash-guardado', $checker ),
			'contraseña actual incorrecta: false'
		);
		ddn_assert(
			true === AccountController::verify_password( 'la-correcta', 'hash-guardado', $checker ),
			'contraseña actual correcta: true'
		);
	}
);

ddn_test(
	'AccountController::verify_password: mismo mecanismo lo usa eliminar cuenta con contraseña incorrecta (no debe borrar nada)',
	static function (): void {
		// handle_delete_account() llama a este mismo método antes de tocar
		// wp_delete_user(): si devuelve false, la petición se corta ahí (ver
		// el "if (!self::verify_password(...)) { back_with(...); }" del
		// controlador) y wp_delete_user() nunca se invoca.
		$checker = static fn ( string $provided, string $hash ): bool => 'clave-real' === $provided;

		ddn_assert(
			false === AccountController::verify_password( 'clave-inventada', 'hash', $checker ),
			'contraseña incorrecta al eliminar: se rechaza antes de borrar'
		);
	}
);

ddn_test(
	'AccountController::build_update_args: sin contraseña nueva, no incluye user_pass',
	static function (): void {
		$args = AccountController::build_update_args( 42, 'Ana Pérez', null );

		ddn_assert( 42 === $args['ID'], 'incluye el ID del usuario' );
		ddn_assert( 'Ana Pérez' === $args['display_name'], 'incluye el nombre' );
		ddn_assert( ! array_key_exists( 'user_pass', $args ), 'sin cambio de contraseña, no toca user_pass' );
	}
);

ddn_test(
	'AccountController::build_update_args: la nueva contraseña va en el MISMO array que el resto de datos (no en una llamada aparte)',
	static function (): void {
		// Esto es lo que evita el bug de "cambiar la propia contraseña
		// cierra la sesión a medio camino": AccountController pasa este
		// array completo a UNA sola llamada a wp_update_user(). Al traer
		// el ID del usuario autenticado y user_pass en el mismo array,
		// WordPress detecta que el propio usuario se actualizó a sí mismo
		// y refresca su cookie de sesión él solo — nunca hay una llamada
		// aparte a wp_set_password() para la sesión activa.
		$args = AccountController::build_update_args( 42, 'Ana Pérez', 'nueva-clave-123' );

		ddn_assert( array_key_exists( 'user_pass', $args ), 'user_pass está en el mismo array' );
		ddn_assert( 'nueva-clave-123' === $args['user_pass'], 'con el valor correcto' );
		ddn_assert( 42 === $args['ID'], 'y sigue siendo el mismo array con el ID del usuario' );
	}
);

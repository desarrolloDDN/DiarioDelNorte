<?php
/**
 * Alarga la sesión de wp-admin para el personal de redacción
 * (administradores, editores, autores…): por defecto WordPress la cierra
 * a los 2 días si no se marca «Recuérdame» al entrar (14 si se marca),
 * lo que obliga a iniciar sesión otra vez cada pocos días de trabajo.
 * Solo alcanza a quien puede editar contenido; las cuentas de
 * suscriptor (sin esa capacidad) siguen con la duración normal.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SessionDuration {

	/** Días mínimos de sesión para quien puede editar contenido. */
	private const DAYS = 30;

	public function register(): void {
		add_filter( 'auth_cookie_expiration', array( $this, 'extend' ), 10, 3 );
	}

	public function extend( int $expiration, int $user_id, bool $remember ): int { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! user_can( $user_id, 'edit_posts' ) ) {
			return $expiration;
		}

		return max( $expiration, self::DAYS * DAY_IN_SECONDS );
	}
}

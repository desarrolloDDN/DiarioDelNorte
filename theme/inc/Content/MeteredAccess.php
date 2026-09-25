<?php
/**
 * Muro de medición para visitantes sin sesión: pueden leer un número
 * limitado de notas (las que no estén ya marcadas «exclusiva para
 * suscriptores», que se bloquean aparte) antes de tener que suscribirse.
 * Se lleva la cuenta en una cookie con los IDs ya leídos — releer una
 * misma nota no consume otro cupo — sin depender de sesión ni de la
 * base de datos.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MeteredAccess {

	/** Notas gratis que puede leer un visitante sin sesión antes de tener que suscribirse. */
	private const LIMIT = 5;

	private const COOKIE      = 'ddn_read';
	private const COOKIE_DAYS = 30;

	// Por defecto se puede leer: solo `decide()` lo cierra, y solo cuando
	// de verdad aplica (visitante sin sesión, en una nota no restringida
	// aparte, y ya sin cupo).
	private static bool $can_read = true;

	public function register(): void {
		add_action( 'template_redirect', array( $this, 'decide' ) );
	}

	/**
	 * Se decide una sola vez por petición, antes de que se envíe cualquier
	 * salida (para poder fijar la cookie): `reader_can_view()` solo lee el
	 * resultado ya calculado aquí.
	 */
	public function decide(): void {
		if ( is_user_logged_in() || ! is_singular( 'post' ) ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( $post_id <= 0 || SubscriberOnly::is_restricted( $post_id ) ) {
			return; // Ya la bloquea el muro de «exclusiva»; no consume cupo.
		}

		$read_ids = self::read_cookie();
		if ( in_array( $post_id, $read_ids, true ) ) {
			return; // Releer una nota ya contada no gasta cupo.
		}

		if ( count( $read_ids ) >= self::LIMIT ) {
			self::$can_read = false;
			return;
		}

		$read_ids[] = $post_id;
		self::write_cookie( $read_ids );
	}

	public static function reader_can_view(): bool {
		return self::$can_read;
	}

	/** @return int[] */
	private static function read_cookie(): array {
		if ( ! isset( $_COOKIE[ self::COOKIE ] ) ) {
			return array();
		}

		$raw = substr( sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) ), 0, 200 );
		$ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );

		// Defensa ante una cookie manipulada a mano: nunca se procesan más
		// IDs de los que el límite permitiría guardar.
		return array_slice( array_values( array_unique( $ids ) ), 0, self::LIMIT );
	}

	/** @param int[] $ids */
	private static function write_cookie( array $ids ): void {
		if ( headers_sent() ) {
			return;
		}

		setcookie(
			self::COOKIE,
			implode( ',', $ids ),
			array(
				'expires'  => time() + self::COOKIE_DAYS * DAY_IN_SECONDS,
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => false,
				'samesite' => 'Lax',
			)
		);
	}
}

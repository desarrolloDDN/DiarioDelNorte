<?php
/**
 * Límite de intentos fallidos por clave (ventana deslizante simple).
 * Lógica pura, sin WordPress: la usa LoginController con una clave
 * derivada de la IP real de la conexión (nunca del nombre de usuario —
 * así nadie puede bloquear la cuenta de otra persona fallando su
 * contraseña a propósito) y un almacén de transients (ver
 * TransientRateLimitStore); las pruebas usan un almacén en memoria.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RateLimiter {

	public function __construct(
		private readonly RateLimitStore $store,
		private readonly int $max_attempts = 5,
		private readonly int $window_seconds = 900,
	) {}

	public function is_locked( string $key, ?int $now = null ): bool {
		return count( $this->recent( $key, $now ) ) >= $this->max_attempts;
	}

	public function record_failure( string $key, ?int $now = null ): void {
		$now    = $now ?? time();
		$recent = $this->recent( $key, $now );

		$recent[] = $now;
		$this->store->put( $key, $recent, $this->window_seconds );
	}

	public function reset( string $key ): void {
		$this->store->forget( $key );
	}

	/** @return int[] Intentos dentro de la ventana, más viejos ya descartados. */
	private function recent( string $key, ?int $now ): array {
		$now    = $now ?? time();
		$cutoff = $now - $this->window_seconds;

		return array_values(
			array_filter(
				$this->store->get( $key ),
				static fn ( int $t ): bool => $t > $cutoff
			)
		);
	}
}

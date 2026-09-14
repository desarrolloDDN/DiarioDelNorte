<?php
declare(strict_types=1);

use DiarioDelNorte\Suite\Subscribers\Support\RateLimiter;
use DiarioDelNorte\Suite\Subscribers\Support\RateLimitStore;

final class InMemoryRateLimitStore implements RateLimitStore {
	/** @var array<string,int[]> */
	private array $data = array();

	public function get( string $key ): array {
		return $this->data[ $key ] ?? array();
	}

	public function put( string $key, array $timestamps, int $ttl_seconds ): void {
		$this->data[ $key ] = $timestamps;
	}

	public function forget( string $key ): void {
		unset( $this->data[ $key ] );
	}
}

ddn_test(
	'RateLimiter: bloquea al 5.º fallo y cuenta por clave (IP), no por usuario',
	static function (): void {
		$store = new InMemoryRateLimitStore();
		$rl    = new RateLimiter( $store, 5, 900 );

		$now = 1000;
		for ( $i = 0; $i < 4; $i++ ) {
			$rl->record_failure( 'ip:1.2.3.4', $now + $i );
		}
		ddn_assert( ! $rl->is_locked( 'ip:1.2.3.4', $now + 4 ), 'con 4 fallos todavía no bloquea' );

		$rl->record_failure( 'ip:1.2.3.4', $now + 4 );
		ddn_assert( $rl->is_locked( 'ip:1.2.3.4', $now + 5 ), 'al 5.º fallo bloquea esa clave' );

		// El llamador real (LoginController) usa la IP como clave, nunca el
		// usuario: fallar la contraseña de "otra persona" desde otra IP
		// nunca debe bloquear a un tercero.
		ddn_assert( ! $rl->is_locked( 'ip:9.9.9.9', $now + 5 ), 'otra IP no queda bloqueada por los fallos de la primera' );
	}
);

ddn_test(
	'RateLimiter: los intentos fuera de la ventana de 15 min ya no cuentan',
	static function (): void {
		$store = new InMemoryRateLimitStore();
		$rl    = new RateLimiter( $store, 5, 900 );

		$now = 10000;
		for ( $i = 0; $i < 5; $i++ ) {
			$rl->record_failure( 'ip:1.2.3.4', $now + $i );
		}
		ddn_assert( $rl->is_locked( 'ip:1.2.3.4', $now + 5 ), 'bloqueada justo después de los 5 fallos' );
		ddn_assert( ! $rl->is_locked( 'ip:1.2.3.4', $now + 5 + 901 ), 'pasados los 15 minutos, ya no está bloqueada' );
	}
);

ddn_test(
	'RateLimiter: un login correcto reinicia el contador',
	static function (): void {
		$store = new InMemoryRateLimitStore();
		$rl    = new RateLimiter( $store, 5, 900 );

		$now = 500;
		for ( $i = 0; $i < 5; $i++ ) {
			$rl->record_failure( 'ip:1.2.3.4', $now + $i );
		}
		ddn_assert( $rl->is_locked( 'ip:1.2.3.4', $now + 5 ), 'bloqueada tras 5 fallos' );

		$rl->reset( 'ip:1.2.3.4' );
		ddn_assert( ! $rl->is_locked( 'ip:1.2.3.4', $now + 5 ), 'reset (login correcto) desbloquea de inmediato' );
	}
);

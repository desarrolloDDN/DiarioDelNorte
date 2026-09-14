<?php
/**
 * Almacén de intentos fallidos para RateLimiter. Interfaz mínima para
 * poder probar RateLimiter con un almacén en memoria, sin WordPress.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface RateLimitStore {

	/** @return int[] Marcas de tiempo (epoch) de intentos fallidos guardados para esta clave. */
	public function get( string $key ): array;

	/** @param int[] $timestamps */
	public function put( string $key, array $timestamps, int $ttl_seconds ): void;

	public function forget( string $key ): void;
}

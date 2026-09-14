<?php
/**
 * Adaptador de RateLimitStore sobre transients de WordPress.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TransientRateLimitStore implements RateLimitStore {

	private const PREFIX = 'ddn_rl_';

	public function get( string $key ): array {
		$value = get_transient( self::PREFIX . $key );

		return is_array( $value ) ? array_map( 'intval', $value ) : array();
	}

	public function put( string $key, array $timestamps, int $ttl_seconds ): void {
		set_transient( self::PREFIX . $key, $timestamps, $ttl_seconds );
	}

	public function forget( string $key ): void {
		delete_transient( self::PREFIX . $key );
	}
}

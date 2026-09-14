<?php
/**
 * Cifrado en reposo para datos sensibles del suscriptor (número de
 * identificación, credenciales OAuth). AES no hace falta: libsodium ya
 * trae XSalsa20-Poly1305 autenticado (`sodium_crypto_secretbox`), que es
 * el cifrado simétrico recomendado por PHP para esto.
 *
 * - Si la extensión sodium no está disponible, nunca revienta: degrada a
 *   texto plano (ver `$sodium_available` en cada método, para pruebas).
 * - Los valores cifrados llevan un prefijo de versión (`self::PREFIX`)
 *   para poder distinguirlos de texto plano guardado antes de introducir
 *   el cifrado, y seguir leyéndolo sin fallar.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Crypto {

	private const PREFIX = 'ddn:sbx1:';

	/**
	 * @param bool|null $sodium_available Forzado solo en pruebas; en
	 *                                    producción se detecta solo.
	 */
	public static function encrypt( string $plaintext, string $secret, ?bool $sodium_available = null ): string {
		$available = $sodium_available ?? self::available();

		if ( '' === $plaintext || ! $available ) {
			return $plaintext;
		}

		$key   = self::key( $secret );
		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$box   = sodium_crypto_secretbox( $plaintext, $nonce, $key );

		return self::PREFIX . base64_encode( $nonce . $box ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- transporte binario→texto del cifrado, no ofuscación.
	}

	/**
	 * @param bool|null $sodium_available Forzado solo en pruebas; en
	 *                                    producción se detecta solo.
	 */
	public static function decrypt( string $stored, string $secret, ?bool $sodium_available = null ): string {
		if ( '' === $stored || ! str_starts_with( $stored, self::PREFIX ) ) {
			// Vacío, o texto plano preexistente de antes del cifrado.
			return $stored;
		}

		$available = $sodium_available ?? self::available();
		if ( ! $available ) {
			return '';
		}

		$raw = base64_decode( substr( $stored, strlen( self::PREFIX ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- transporte binario→texto del cifrado, no ofuscación.
		if ( false === $raw || strlen( $raw ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			return '';
		}

		$nonce = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$box   = substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$key   = self::key( $secret );
		$plain = sodium_crypto_secretbox_open( $box, $nonce, $key );

		return false !== $plain ? $plain : '';
	}

	public static function available(): bool {
		return function_exists( 'sodium_crypto_secretbox' );
	}

	/** Clave de 32 bytes derivada del secreto del sitio (nunca se guarda). */
	private static function key( string $secret ): string {
		return sodium_crypto_generichash( $secret, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
	}
}

<?php
/**
 * Qué formatos de imagen se convierten al subirlos y a cuál. Lógica pura
 * (sin llamadas a WordPress) para poder probarla de verdad.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Media\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ConversionRule {

	/**
	 * Formatos que los navegadores no muestran bien (HEIC de iPhone, TIFF,
	 * BMP) o que no queremos mantener tal cual (WebP, AVIF): se convierten
	 * a JPG, salvo WebP/AVIF con transparencia, que van a PNG para no
	 * perder el fondo transparente. JPG, PNG y GIF (por si es animado) se
	 * dejan como están.
	 *
	 * @return string|null Tipo MIME destino, o null si no hay que convertir.
	 */
	public static function target( string $mime, bool $has_alpha ): ?string {
		return match ( strtolower( trim( $mime ) ) ) {
			'image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence', 'image/tiff', 'image/bmp', 'image/x-ms-bmp', 'image/x-bmp' => 'image/jpeg',
			'image/webp', 'image/avif' => $has_alpha ? 'image/png' : 'image/jpeg',
			default => null,
		};
	}

	public static function extension( string $target_mime ): string {
		return 'image/png' === $target_mime ? 'png' : 'jpg';
	}
}

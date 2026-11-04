<?php
/**
 * Convierte a JPG/PNG las imágenes que se suben en otros formatos (HEIC,
 * TIFF, BMP, WebP, AVIF) para que se vean en cualquier navegador y WordPress
 * pueda generar sus tamaños. Si el servidor no logra leer el archivo, la
 * subida se rechaza con un mensaje claro en vez de guardar una imagen que
 * luego no se ve.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Media;

use DiarioDelNorte\Suite\Media\Support\ConversionRule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UploadConverter {

	private const QUALITY = 90;

	public function register(): void {
		add_filter( 'wp_handle_upload', array( $this, 'convert' ), 10, 2 );
	}

	/**
	 * @param array{file?:string,url?:string,type?:string,error?:string} $upload
	 * @return array<string,string>
	 */
	public function convert( array $upload, string $context = 'upload' ): array {
		if ( 'upload' !== $context || ! empty( $upload['error'] ) || empty( $upload['file'] ) || empty( $upload['type'] ) ) {
			return $upload;
		}

		$file = (string) $upload['file'];
		$mime = (string) $upload['type'];

		$target = ConversionRule::target( $mime, $this->has_alpha( $file, $mime ) );
		if ( null === $target ) {
			return $upload;
		}

		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			return $this->reject( $upload, $file );
		}

		// Las fotos de celular traen la orientación en los metadatos.
		$editor->maybe_exif_rotate();
		$editor->set_quality( self::QUALITY );

		$dir  = dirname( $file );
		$name = wp_unique_filename( $dir, pathinfo( $file, PATHINFO_FILENAME ) . '.' . ConversionRule::extension( $target ) );
		$path = $dir . '/' . $name;

		$saved = $editor->save( $path, $target );
		if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
			return $this->reject( $upload, $file );
		}

		wp_delete_file( $file );

		$url = (string) ( $upload['url'] ?? '' );

		return array(
			'file' => (string) $saved['path'],
			'url'  => substr( $url, 0, (int) strrpos( $url, '/' ) + 1 ) . basename( (string) $saved['path'] ),
			'type' => $target,
		);
	}

	/** Solo importa para WebP/AVIF (decide JPG o PNG); con Imagick se mira de verdad. */
	private function has_alpha( string $file, string $mime ): bool {
		if ( ! in_array( strtolower( $mime ), array( 'image/webp', 'image/avif' ), true ) ) {
			return false;
		}

		if ( class_exists( '\Imagick' ) ) {
			try {
				$image = new \Imagick( $file . '[0]' );

				return (bool) $image->getImageAlphaChannel();
			} catch ( \Throwable $e ) {
				// Sin poder mirarlo: se asume que puede tener transparencia.
				return true;
			}
		}

		// Sin Imagick no se puede saber: PNG es el lado seguro (nunca pierde
		// la transparencia; solo pesa más).
		return true;
	}

	/**
	 * @param array<string,string> $upload
	 * @return array<string,string>
	 */
	private function reject( array $upload, string $file ): array {
		wp_delete_file( $file );

		return array(
			'error' => __( 'El servidor no puede procesar este formato de imagen. Conviértela a JPG o PNG e inténtalo de nuevo.', 'ddn-suite' ),
		);
	}
}

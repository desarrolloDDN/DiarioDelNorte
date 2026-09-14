<?php
/**
 * CSV con BOM UTF-8 (para que Excel muestre bien los acentos al abrirlo
 * con doble clic) de todo lo que cumpla el filtro activo.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CsvExporter {

	/**
	 * Envía el CSV directamente a la salida (streaming) y termina la
	 * petición; el llamador no debe imprimir nada más.
	 *
	 * @param array<int,array<string,mixed>> $rows
	 * @param array<string,string>           $columns clave => encabezado
	 */
	public static function stream( array $rows, array $columns, string $filename ): void {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$out = fopen( 'php://output', 'w' );
		if ( false === $out ) {
			exit;
		}

		// BOM UTF-8: Excel necesita esto para no romper los acentos al
		// abrir el archivo con doble clic.
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- streaming a php://output, no al sistema de archivos de WP.

		fputcsv( $out, array_values( $columns ) );
		foreach ( $rows as $row ) {
			$line = array();
			foreach ( array_keys( $columns ) as $key ) {
				$line[] = self::format( $row[ $key ] ?? '' );
			}
			fputcsv( $out, $line );
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- idem, php://output.
		exit;
	}

	private static function format( mixed $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? __( 'Sí', 'ddn-suite' ) : __( 'No', 'ddn-suite' );
		}

		return (string) $value;
	}
}

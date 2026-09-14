<?php
/**
 * Escritor mínimo de .xlsx real (una sola hoja, cadenas en línea, sin
 * estilos) usando ZipArchive — un .xlsx es un .zip de XML. No hace falta
 * traer una librería completa de lectura/escritura solo para generar un
 * archivo nuevo.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Admin;

use ZipArchive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class XlsxWriter {

	public static function available(): bool {
		return class_exists( ZipArchive::class );
	}

	/**
	 * @param array<int,array<string,mixed>> $rows
	 * @param array<string,string>           $columns clave => encabezado
	 * @return string Bytes del .xlsx, o '' si ZipArchive no está disponible.
	 */
	public static function build( array $rows, array $columns ): string {
		if ( ! self::available() ) {
			return '';
		}

		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$tmp = wp_tempnam( 'ddn-suscriptores.xlsx' );

		$zip = new ZipArchive();
		if ( true !== $zip->open( $tmp, ZipArchive::OVERWRITE ) ) {
			wp_delete_file( $tmp );
			return '';
		}

		$zip->addFromString( '[Content_Types].xml', self::content_types() );
		$zip->addFromString( '_rels/.rels', self::root_rels() );
		$zip->addFromString( 'xl/workbook.xml', self::workbook() );
		$zip->addFromString( 'xl/_rels/workbook.xml.rels', self::workbook_rels() );
		$zip->addFromString( 'xl/worksheets/sheet1.xml', self::sheet( $rows, $columns ) );
		$zip->close();

		$bytes = (string) file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lee el .zip temporal que se acaba de escribir aquí mismo, no una URL remota.
		wp_delete_file( $tmp );

		return $bytes;
	}

	private static function content_types(): string {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
			. '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
			. '<Default Extension="xml" ContentType="application/xml"/>'
			. '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
			. '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
			. '</Types>';
	}

	private static function root_rels(): string {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
			. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
			. '</Relationships>';
	}

	private static function workbook(): string {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
			. '<sheets><sheet name="Suscriptores" sheetId="1" r:id="rId1"/></sheets>'
			. '</workbook>';
	}

	private static function workbook_rels(): string {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
			. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
			. '</Relationships>';
	}

	/**
	 * @param array<int,array<string,mixed>> $rows
	 * @param array<string,string>           $columns
	 */
	private static function sheet( array $rows, array $columns ): string {
		$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

		$xml .= self::row( 1, array_values( $columns ) );

		$r = 2;
		foreach ( $rows as $row ) {
			$cells = array();
			foreach ( array_keys( $columns ) as $key ) {
				$value = $row[ $key ] ?? '';
				if ( is_bool( $value ) ) {
					$value = $value ? __( 'Sí', 'ddn-suite' ) : __( 'No', 'ddn-suite' );
				}
				$cells[] = (string) $value;
			}
			$xml .= self::row( $r, $cells );
			++$r;
		}

		return $xml . '</sheetData></worksheet>';
	}

	/** @param string[] $values */
	private static function row( int $index, array $values ): string {
		$cells = '';
		foreach ( $values as $i => $value ) {
			$ref    = self::column_letter( $i + 1 ) . $index;
			$cells .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . self::escape( $value ) . '</t></is></c>';
		}

		return '<row r="' . $index . '">' . $cells . '</row>';
	}

	private static function escape( string $value ): string {
		return htmlspecialchars( $value, ENT_QUOTES | ENT_XML1, 'UTF-8' );
	}

	private static function column_letter( int $index ): string {
		$letter = '';
		while ( $index > 0 ) {
			$mod    = ( $index - 1 ) % 26;
			$letter = chr( 65 + $mod ) . $letter;
			$index  = (int) ( ( $index - $mod ) / 26 );
		}

		return $letter;
	}
}

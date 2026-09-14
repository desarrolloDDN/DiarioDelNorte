<?php
declare(strict_types=1);

use DiarioDelNorte\Suite\Subscribers\Admin\XlsxWriter;

ddn_test(
	'XlsxWriter: produce un .xlsx real (zip válido con las piezas OOXML mínimas), no un CSV renombrado',
	static function (): void {
		if ( ! XlsxWriter::available() ) {
			echo "  (omitida: ZipArchive no disponible en este entorno)\n";
			return;
		}

		$columns = array(
			'name'  => 'Nombre',
			'email' => 'Correo',
			'wa'    => 'WhatsApp',
		);
		$rows = array(
			array(
				'name'  => 'Ana Pérez & Cía "La Guajira"',
				'email' => 'ana@example.com',
				'wa'    => true,
			),
			array(
				'name'  => 'José <script>',
				'email' => 'jose@example.com',
				'wa'    => false,
			),
		);

		$bytes = XlsxWriter::build( $rows, $columns );
		ddn_assert( '' !== $bytes, 'build() devuelve bytes' );
		ddn_assert( str_starts_with( $bytes, 'PK' ), 'empieza con la firma de un archivo zip (PK)' );

		$tmp = (string) tempnam( sys_get_temp_dir(), 'ddn-xlsx-test-' );
		file_put_contents( $tmp, $bytes );

		$zip = new ZipArchive();
		$opened = $zip->open( $tmp );
		ddn_assert( true === $opened, 'ZipArchive puede abrir el archivo generado' );

		if ( true === $opened ) {
			foreach ( array( '[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/worksheets/sheet1.xml' ) as $entry ) {
				ddn_assert( false !== $zip->locateName( $entry ), "trae la pieza obligatoria {$entry}" );
			}

			$sheet_xml = $zip->getFromName( 'xl/worksheets/sheet1.xml' );
			ddn_assert( is_string( $sheet_xml ) && '' !== $sheet_xml, 'la hoja tiene contenido' );

			if ( is_string( $sheet_xml ) ) {
				$doc      = new DOMDocument();
				$is_valid = $doc->loadXML( $sheet_xml );
				ddn_assert( false !== $is_valid, 'la hoja es XML bien formado (los & y < de los datos van bien escapados)' );
				ddn_assert( str_contains( $sheet_xml, 'Ana P' ), 'el contenido de las filas quedó en la hoja' );
			}

			$zip->close();
		}

		unlink( $tmp );
	}
);

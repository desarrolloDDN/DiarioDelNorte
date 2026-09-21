<?php
/**
 * Pruebas del rango de fechas de las estadísticas de lectura.
 */

declare(strict_types=1);

use DiarioDelNorte\Suite\Analytics\Support\DateRange;

ddn_test(
	'DateRange: sin rango válido, usa los últimos 7 días terminando hoy',
	static function (): void {
		$r = DateRange::resolve( '', '', '2026-09-21' );
		ddn_assert( '2026-09-15' === $r['from'], 'desde: hace 6 días (7 días con hoy)' );
		ddn_assert( '2026-09-21' === $r['to'], 'hasta: hoy' );
		$r = DateRange::resolve( 'basura', '2026-13-45', '2026-09-21' );
		ddn_assert( '2026-09-15' === $r['from'] && '2026-09-21' === $r['to'], 'texto o fechas imposibles se ignoran' );
	}
);

ddn_test(
	'DateRange: un rango válido se respeta y uno invertido se ordena',
	static function (): void {
		$r = DateRange::resolve( '2026-09-01', '2026-09-10', '2026-09-21' );
		ddn_assert( '2026-09-01' === $r['from'] && '2026-09-10' === $r['to'], 'rango normal intacto' );
		$r = DateRange::resolve( '2026-09-10', '2026-09-01', '2026-09-21' );
		ddn_assert( '2026-09-01' === $r['from'] && '2026-09-10' === $r['to'], 'desde > hasta: se intercambian' );
	}
);

ddn_test(
	'DateRange: nunca pasa de hoy ni supera el tope de días',
	static function (): void {
		$r = DateRange::resolve( '2026-09-01', '2027-01-01', '2026-09-21' );
		ddn_assert( '2026-09-21' === $r['to'], 'el fin futuro se recorta a hoy' );
		$r = DateRange::resolve( '2030-01-01', '2030-02-01', '2026-09-21' );
		ddn_assert( '2026-09-21' === $r['from'] && '2026-09-21' === $r['to'], 'todo en el futuro: queda solo hoy' );
		$r = DateRange::resolve( '2020-01-01', '2026-09-21', '2026-09-21', 400 );
		ddn_assert( '2025-08-18' === $r['from'], 'un rango de años se acota a 400 días (vino: ' . $r['from'] . ')' );
	}
);

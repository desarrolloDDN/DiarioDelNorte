<?php
/**
 * Rango de fechas de las estadísticas: valida lo que llega por la URL y lo
 * acota (sin fechas futuras, orden correcto, tope de días). Sin llamadas a
 * WordPress, para poder probarlo de verdad.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Analytics\Support;

use DateTimeImmutable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DateRange {

	/**
	 * @param string $from         Y-m-d o cualquier otra cosa (se ignora).
	 * @param string $to           Y-m-d o cualquier otra cosa (se ignora).
	 * @param string $today        Y-m-d de hoy, en la zona horaria del sitio.
	 * @param int    $max_days     Tope de días del rango.
	 * @param int    $default_days Días por defecto si no llega un rango válido.
	 * @return array{from:string,to:string}
	 */
	public static function resolve( string $from, string $to, string $today, int $max_days = 400, int $default_days = 7 ): array {
		$today_dt = self::parse( $today ) ?? new DateTimeImmutable( 'today' );
		$end      = self::parse( $to ) ?? $today_dt;
		$start    = self::parse( $from ) ?? $end->modify( '-' . ( $default_days - 1 ) . ' days' );

		if ( $start > $end ) {
			$swap  = $start;
			$start = $end;
			$end   = $swap;
		}
		if ( $end > $today_dt ) {
			$end = $today_dt;
		}
		if ( $start > $end ) {
			$start = $end;
		}

		$earliest = $end->modify( '-' . ( $max_days - 1 ) . ' days' );
		if ( $start < $earliest ) {
			$start = $earliest;
		}

		return array(
			'from' => $start->format( 'Y-m-d' ),
			'to'   => $end->format( 'Y-m-d' ),
		);
	}

	private static function parse( string $date ): ?DateTimeImmutable {
		if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return null;
		}

		$parsed = DateTimeImmutable::createFromFormat( '!Y-m-d', $date );

		return ( $parsed instanceof DateTimeImmutable && $parsed->format( 'Y-m-d' ) === $date ) ? $parsed : null;
	}
}

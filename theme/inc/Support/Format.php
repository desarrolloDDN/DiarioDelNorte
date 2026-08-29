<?php
/**
 * Utilidades de presentación reutilizables por las plantillas.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Support;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Format {

	/**
	 * «Hace 3 horas», «Ayer», «12 ago». Español propio (human_time_diff
	 * de WordPress depende del paquete de idioma y a veces mezcla inglés).
	 */
	public static function time_ago( int|WP_Post|null $post = null ): string {
		$timestamp = (int) get_post_time( 'U', true, $post );
		if ( $timestamp <= 0 ) {
			return '';
		}

		$diff = time() - $timestamp;

		if ( $diff < MINUTE_IN_SECONDS ) {
			return __( 'Ahora mismo', 'diario-del-norte' );
		}
		if ( $diff < HOUR_IN_SECONDS ) {
			$m = (int) round( $diff / MINUTE_IN_SECONDS );
			/* translators: %d: minutos. */
			return sprintf( _n( 'Hace %d minuto', 'Hace %d minutos', $m, 'diario-del-norte' ), $m );
		}
		if ( $diff < DAY_IN_SECONDS ) {
			$h = (int) round( $diff / HOUR_IN_SECONDS );
			/* translators: %d: horas. */
			return sprintf( _n( 'Hace %d hora', 'Hace %d horas', $h, 'diario-del-norte' ), $h );
		}
		if ( $diff < 2 * DAY_IN_SECONDS ) {
			return __( 'Ayer', 'diario-del-norte' );
		}
		if ( $diff < 7 * DAY_IN_SECONDS ) {
			$d = (int) round( $diff / DAY_IN_SECONDS );
			/* translators: %d: días. */
			return sprintf( _n( 'Hace %d día', 'Hace %d días', $d, 'diario-del-norte' ), $d );
		}

		return (string) get_the_date( 'j M', $post );
	}

	/** Minutos de lectura estimados a partir del contenido de la entrada. */
	public static function reading_minutes( int|WP_Post|null $post = null ): int {
		$content = (string) get_post_field( 'post_content', $post ?? get_the_ID() );
		$words   = max( 1, str_word_count( wp_strip_all_tags( $content ) ) );

		return (int) max( 1, (int) ceil( $words / 200 ) );
	}

	/** Etiqueta de sección principal de una entrada (la primera categoría). */
	public static function primary_category( int|WP_Post|null $post = null ): ?\WP_Term {
		$id   = $post instanceof WP_Post ? $post->ID : (int) ( $post ?? get_the_ID() );
		$cats = get_the_category( $id );

		return $cats[0] ?? null;
	}
}

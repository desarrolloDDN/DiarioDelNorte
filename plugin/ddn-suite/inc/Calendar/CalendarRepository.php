<?php
/**
 * Datos del calendario editorial: qué noticias caen en cada día de un mes
 * (publicadas, programadas o borradores). Solo capa de datos.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Calendar;

use WP_Post;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CalendarRepository {

	/**
	 * @return array<string, list<array{id:int,title:string,status:string,time:string,edit_link:string}>>
	 *         Clave: 'Y-m-d'.
	 */
	public function month( int $year, int $month ): array {
		$query = new WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => array( 'publish', 'future', 'draft', 'pending' ),
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'date_query'     => array(
					array(
						'year'   => $year,
						'month'  => $month,
						'column' => 'post_date',
					),
				),
			)
		);

		$by_day = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			$day = get_the_date( 'Y-m-d', $post );
			if ( ! is_string( $day ) || '' === $day ) {
				continue;
			}

			$by_day[ $day ][] = array(
				'id'        => $post->ID,
				'title'     => get_the_title( $post ),
				'status'    => $post->post_status,
				'time'      => get_the_date( 'H:i', $post ),
				'edit_link' => (string) get_edit_post_link( $post->ID, 'raw' ),
			);
		}

		return $by_day;
	}
}

<?php
/**
 * Lectura de las ediciones impresas. Se expone al tema por el filtro
 * `ddn/print_edition`, que devuelve la edición vigente (la más reciente
 * ya publicada) o null.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\PrintEdition;

use WP_Post;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EditionRepository {

	private const META_PDF = '_ddn_edition_pdf';

	public function register(): void {
		add_filter( 'ddn/print_edition', array( $this, 'current' ) );
	}

	/**
	 * @return array{date:string,cover_id:int,pdf_url:string,edit_link:string}|null
	 */
	public function current(): ?array {
		$query = new WP_Query(
			array(
				'post_type'      => EditionPostType::TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		$post = $query->posts[0] ?? null;
		if ( ! $post instanceof WP_Post ) {
			return null;
		}

		$pdf_id = (int) get_post_meta( $post->ID, self::META_PDF, true );

		return array(
			'date'      => (string) get_the_date( 'Y-m-d', $post ),
			'cover_id'  => (int) get_post_thumbnail_id( $post ),
			'pdf_url'   => $pdf_id ? (string) wp_get_attachment_url( $pdf_id ) : '',
			'edit_link' => (string) get_edit_post_link( $post->ID, 'raw' ),
		);
	}
}

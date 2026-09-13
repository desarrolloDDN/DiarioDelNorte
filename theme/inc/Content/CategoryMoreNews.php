<?php
/**
 * REST: siguiente lote de «Más noticias» en el archivo de categoría, para
 * el botón «Cargar más noticias» (carga progresiva sin recargar la página
 * ni depender de /page/N/).
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Content;

use WP_Error;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CategoryMoreNews {

	private const NS         = 'diario-del-norte/v1';
	private const BATCH_SIZE = 12;

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	public function routes(): void {
		register_rest_route(
			self::NS,
			'/category-more',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array(
					'term_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'offset'  => array(
						'type'              => 'integer',
						'required'          => false,
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
				'callback'            => array( $this, 'more' ),
			)
		);
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function more( WP_REST_Request $request ) {
		$term = get_term( (int) $request->get_param( 'term_id' ), 'category' );
		if ( ! $term instanceof WP_Term ) {
			return new WP_Error( 'ddn_cat_not_found', __( 'Sección no encontrada.', 'diario-del-norte' ), array( 'status' => 404 ) );
		}

		$query = new WP_Query(
			array(
				'category__in'        => array( $term->term_id ),
				// Uno de más que el lote: si vuelve, sabemos que hay más sin
				// necesidad de una consulta de conteo aparte.
				'posts_per_page'      => self::BATCH_SIZE + 1,
				'offset'              => (int) $request->get_param( 'offset' ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'post_status'         => 'publish',
			)
		);

		$has_more = $query->post_count > self::BATCH_SIZE;
		/** @var WP_Post[] $posts */
		$posts = array_slice( $query->posts, 0, self::BATCH_SIZE );

		ob_start();
		global $post;
		foreach ( $posts as $post ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $post );
			get_template_part( 'template-parts/category/card', null, array( 'byline' => true ) );
		}
		wp_reset_postdata();
		$html = (string) ob_get_clean();

		return new WP_REST_Response(
			array(
				'html'     => $html,
				'count'    => count( $posts ),
				'has_more' => $has_more,
			)
		);
	}
}

<?php
/**
 * Bloque «Le puede interesar» intercalado tras el 4.º párrafo de la nota,
 * con dos noticias de la misma sección (categoría principal).
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Content;

use DiarioDelNorte\Support\Format;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class InlineRelated {

	/** Tras el cierre de este párrafo de nivel superior. */
	private const AFTER_PARAGRAPH = 4;

	/** Cuántas notas mostrar. */
	private const COUNT = 2;

	public function register(): void {
		// Después del anuncio intercalado del plugin (prioridad 12).
		add_filter( 'the_content', array( $this, 'inject' ), 13 );
	}

	public function inject( string $content ): string {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$cat = Format::primary_category();
		if ( null === $cat ) {
			return $content;
		}

		$related = new WP_Query(
			array(
				'category__in'        => array( $cat->term_id ),
				'post__not_in'        => array( get_the_ID() ),
				'posts_per_page'      => self::COUNT,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		if ( ! $related->have_posts() ) {
			return $content;
		}

		$block = $this->markup( $related );
		wp_reset_postdata();

		return $this->insert_after_paragraph( $content, $block, self::AFTER_PARAGRAPH );
	}

	private function markup( WP_Query $related ): string {
		$items = '';
		foreach ( $related->posts as $ddn_p ) {
			$items .= sprintf(
				'<li class="inline-related__item"><a class="inline-related__link" href="%1$s">'
				. '<span class="inline-related__icon" aria-hidden="true">'
				. '<svg viewBox="0 0 24 24" focusable="false"><path d="M5 12h13M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
				. '</span>'
				. '<span class="inline-related__title">%2$s</span></a></li>',
				esc_url( (string) get_permalink( $ddn_p ) ),
				esc_html( get_the_title( $ddn_p ) )
			);
		}

		return sprintf(
			'<aside class="inline-related" role="complementary" aria-label="%1$s">'
			. '<span class="inline-related__label">%1$s</span>'
			. '<ul class="inline-related__list">%2$s</ul>'
			. '</aside>',
			esc_attr__( 'Le puede interesar', 'diario-del-norte' ),
			$items
		);
	}

	private function insert_after_paragraph( string $content, string $block, int $n ): string {
		$parts = preg_split( '/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( ! is_array( $parts ) ) {
			return $content . $block;
		}

		$rebuilt = '';
		$closes  = 0;
		$done    = false;
		foreach ( $parts as $chunk ) {
			$rebuilt .= $chunk;
			if ( ! $done && '</p>' === strtolower( $chunk ) ) {
				++$closes;
				if ( $closes >= $n ) {
					$rebuilt .= $block;
					$done     = true;
				}
			}
		}

		// Menos de $n párrafos: el bloque va al final.
		return $done ? $rebuilt : $content . $block;
	}
}

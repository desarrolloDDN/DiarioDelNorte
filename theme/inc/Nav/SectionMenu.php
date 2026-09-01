<?php
/**
 * Dibuja la barra de secciones directamente desde código (10 visibles +
 * submenú «Más» con 9), resolviendo cada categoría por slug o por nombre.
 * Es el `fallback_cb` de wp_nav_menu para la ubicación «primary»: si un
 * editor asigna un menú propio a esa ubicación, ese menú manda; si no,
 * esta barra siempre sale bien.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Nav;

use DiarioDelNorte\Sections\DefaultSectionsInstaller as Sections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SectionMenu {

	/** @param array<string,mixed> $args Argumentos de wp_nav_menu (no se usan). */
	public static function render( array $args = array() ): void {
		unset( $args );

		echo '<div class="mainnav__inner"><ul class="mainnav__menu">';

		foreach ( Sections::VISIBLE as $slug => $name ) {
			self::item( $slug, $name );
		}

		$more_current = false;
		foreach ( array_keys( Sections::MORE ) as $slug ) {
			if ( self::is_current( $slug ) ) {
				$more_current = true;
				break;
			}
		}

		printf(
			'<li class="menu-item menu-item-has-children menu-item--more%s"><a href="%s">%s</a><ul class="sub-menu">',
			$more_current ? ' current-menu-parent current-menu-ancestor' : '',
			esc_url( home_url( '/' ) ),
			esc_html__( 'Más', 'diario-del-norte' )
		);
		foreach ( Sections::MORE as $slug => $name ) {
			self::item( $slug, $name );
		}
		echo '</ul></li>';

		echo '</ul></div>';
	}

	/**
	 * Lista vertical y plana de las 19 secciones (visibles + «Más»), para
	 * el panel que despliega el icono de menú en la plantilla de la nota.
	 */
	public static function render_list(): void {
		echo '<ul class="drawer-nav__list">';
		foreach ( Sections::VISIBLE + Sections::MORE as $slug => $name ) {
			$term    = Sections::category( $slug );
			$url     = $term ? get_category_link( $term ) : home_url( '/' . $slug . '/' );
			$current = self::is_current( $slug );

			printf(
				'<li class="drawer-nav__item"><a href="%s"%s>%s</a></li>',
				esc_url( $url ),
				$current ? ' aria-current="page" class="is-current"' : '',
				esc_html( $name )
			);
		}
		echo '</ul>';
	}

	private static function item( string $slug, string $name ): void {
		$term    = Sections::category( $slug );
		$url     = $term ? get_category_link( $term ) : home_url( '/' . $slug . '/' );
		$current = self::is_current( $slug );

		printf(
			'<li class="menu-item menu-item-object-category%s"><a href="%s"%s>%s</a></li>',
			$current ? ' current-menu-item current-cat' : '',
			esc_url( $url ),
			$current ? ' aria-current="page"' : '',
			esc_html( $name )
		);
	}

	private static function is_current( string $slug ): bool {
		$term = Sections::category( $slug );

		return $term instanceof \WP_Term && is_category( $term->term_id );
	}
}

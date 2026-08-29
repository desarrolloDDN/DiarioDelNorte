<?php
/**
 * Siembra las 19 secciones (categorías) del diario y arma el menú
 * principal con el submenú «Más». Idempotente: se puede llamar en cada
 * carga sin duplicar nada, y solo hace trabajo real una vez por versión.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Sections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DefaultSectionsInstaller {

	private const OPTION    = 'ddn_sections_version';
	private const VERSION   = '1';
	private const MENU_NAME = 'Secciones';

	/** Secciones visibles en la barra, en orden. slug => nombre. */
	private const VISIBLE = array(
		'la-guajira'      => 'La Guajira',
		'politica'        => 'Política',
		'judiciales'      => 'Judiciales',
		'caribe'          => 'Caribe',
		'nacion'          => 'Nación',
		'mundo'           => 'Mundo',
		'opinion'         => 'Opinión',
		'editorial'       => 'Editorial',
		'edicion-impresa' => 'Edición Impresa',
		'sociales'        => 'Sociales',
	);

	/** Secciones dentro del submenú «Más», en orden. slug => nombre. */
	private const MORE = array(
		'oraculos'        => 'Oráculos',
		'multimedia'      => 'Multimedia',
		'especiales'      => 'Especiales',
		'edictos'         => 'Edictos',
		'negocios'        => 'Negocios',
		'deportes'        => 'Deportes',
		'entretenimiento' => 'Entretenimiento',
		'notas-rosas'     => 'Notas Rosas',
		'tecnologia'      => 'Tecnología',
	);

	public function ensure(): void {
		if ( get_option( self::OPTION ) === self::VERSION ) {
			return;
		}

		$this->create_categories();
		$this->build_menu();

		update_option( self::OPTION, self::VERSION, false );
	}

	private function create_categories(): void {
		foreach ( array_merge( self::VISIBLE, self::MORE ) as $slug => $name ) {
			if ( term_exists( $slug, 'category' ) ) {
				continue;
			}
			wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		}
	}

	private function build_menu(): void {
		$menu    = wp_get_nav_menu_object( self::MENU_NAME );
		$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( self::MENU_NAME );

		if ( is_wp_error( $menu_id ) || 0 === $menu_id ) {
			return;
		}

		// Si el menú ya tiene ítems (lo tocó un editor), no lo pisamos.
		if ( wp_get_nav_menu_items( $menu_id ) ) {
			$this->assign_location( $menu_id );
			return;
		}

		foreach ( self::VISIBLE as $slug => $name ) {
			$this->add_category_item( $menu_id, $slug, $name );
		}

		$more_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'   => __( 'Más', 'diario-del-norte' ),
				'menu-item-url'     => '#',
				'menu-item-type'    => 'custom',
				'menu-item-status'  => 'publish',
				'menu-item-classes' => 'menu-item--more',
			)
		);

		if ( ! is_wp_error( $more_id ) && $more_id > 0 ) {
			foreach ( self::MORE as $slug => $name ) {
				$this->add_category_item( $menu_id, $slug, $name, (int) $more_id );
			}
		}

		$this->assign_location( $menu_id );
	}

	private function add_category_item( int $menu_id, string $slug, string $name, int $parent_id = 0 ): void {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( ! $term ) {
			return;
		}

		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $name,
				'menu-item-object'    => 'category',
				'menu-item-object-id' => (int) $term->term_id,
				'menu-item-type'      => 'taxonomy',
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $parent_id,
			)
		);
	}

	private function assign_location( int $menu_id ): void {
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		if ( empty( $locations['primary'] ) ) {
			$locations['primary'] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}
}

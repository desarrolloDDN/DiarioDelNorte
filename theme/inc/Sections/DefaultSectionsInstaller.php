<?php
/**
 * Deja listo el menú principal del diario: las 19 secciones en su orden,
 * con el submenú «Más». Solo crea las categorías que de verdad falten
 * (busca por nombre y por slug, así se adapta a un sitio que ya tiene el
 * contenido migrado — p. ej. «Judiciales» puede vivir en judiciales-2).
 *
 * Idempotente: solo trabaja cuando cambia self::VERSION. Al hacerlo,
 * reconstruye por completo el menú «Secciones» para propagar arreglos.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Sections;

use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DefaultSectionsInstaller {

	private const OPTION    = 'ddn_sections_version';
	private const VERSION   = '2';
	private const MENU_NAME = 'Secciones';

	/** Visibles en la barra, en orden. slug preferido => nombre. */
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

	/** Dentro del submenú «Más», en orden. slug preferido => nombre. */
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

		$this->create_missing_categories();
		$this->rebuild_menu();

		update_option( self::OPTION, self::VERSION, false );
	}

	/**
	 * Categoría de una sección conocida, tolerante a que el sitio migrado
	 * la tenga con otro slug (busca por slug preferido y luego por nombre).
	 * Devuelve null si no existe ninguna de las dos.
	 */
	public static function category( string $preferred_slug ): ?WP_Term {
		$name = self::VISIBLE[ $preferred_slug ] ?? self::MORE[ $preferred_slug ] ?? '';

		return self::resolve_term( $preferred_slug, $name );
	}

	private function create_missing_categories(): void {
		foreach ( array_merge( self::VISIBLE, self::MORE ) as $slug => $name ) {
			if ( null !== self::resolve_term( $slug, $name ) ) {
				continue;
			}
			wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		}
	}

	/** Categoría existente por slug preferido o, si no, por nombre exacto. */
	private static function resolve_term( string $slug, string $name ): ?WP_Term {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( $term instanceof WP_Term ) {
			return $term;
		}

		if ( '' !== $name ) {
			$term = get_term_by( 'name', $name, 'category' );
			if ( $term instanceof WP_Term ) {
				return $term;
			}
		}

		return null;
	}

	private function rebuild_menu(): void {
		$menu    = wp_get_nav_menu_object( self::MENU_NAME );
		$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( self::MENU_NAME );

		if ( is_wp_error( $menu_id ) || 0 === $menu_id ) {
			return;
		}

		// Vacía el menú para reconstruirlo desde cero (v0.1: no se
		// conservan personalizaciones manuales de este menú).
		$existing = wp_get_nav_menu_items( $menu_id );
		if ( is_array( $existing ) ) {
			foreach ( $existing as $item ) {
				wp_delete_post( (int) $item->ID, true );
			}
		}

		foreach ( self::VISIBLE as $slug => $name ) {
			$this->add_category_item( $menu_id, $slug, $name );
		}

		$more_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'   => __( 'Más', 'diario-del-norte' ),
				'menu-item-url'     => home_url( '/' ),
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
		$term = self::resolve_term( $slug, $name );
		if ( null === $term ) {
			return;
		}

		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $name,
				'menu-item-object'    => 'category',
				'menu-item-object-id' => $term->term_id,
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

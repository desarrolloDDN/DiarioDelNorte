<?php
/**
 * Siembra las 19 secciones (categorías) del diario. La barra de
 * navegación NO se guarda en la base de datos: la dibuja
 * DiarioDelNorte\Nav\SectionMenu directamente desde estas listas, así
 * siempre queda bien aunque el sitio venga migrado (p. ej. «Judiciales»
 * puede vivir en el slug judiciales-2).
 *
 * Idempotente: solo trabaja cuando cambia self::VERSION.
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

	private const OPTION      = 'ddn_sections_version';
	private const VERSION     = '4';
	private const LEGACY_MENU = 'Secciones';

	/** Visibles en la barra, en orden. slug preferido => nombre. */
	public const VISIBLE = array(
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

	/**
	 * Slugs alternativos vistos en el sitio migrado (el JNews actual usa
	 * los compuestos sin guion: «laguajira», etc.). Se prueban antes de
	 * buscar por nombre. slug preferido => lista de alternativos.
	 *
	 * @var array<string,list<string>>
	 */
	private const ALIASES = array(
		'la-guajira'      => array( 'laguajira' ),
		'edicion-impresa' => array( 'edicionimpresa' ),
		'notas-rosas'     => array( 'notasrosas' ),
	);

	/** Dentro del submenú «Más», en orden. slug preferido => nombre. */
	public const MORE = array(
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
		$this->retire_legacy_menu();

		update_option( self::OPTION, self::VERSION, false );
	}

	/**
	 * Categoría de una sección conocida, tolerante a que el sitio migrado
	 * la tenga con otro slug. Reúne los candidatos (slug preferido, slugs
	 * alternativos y coincidencia por nombre) y devuelve el que más
	 * entradas tiene: así, si la instalación sembró una «la-guajira» vacía
	 * junto a la «laguajira» migrada con notas, gana la que tiene contenido.
	 */
	public static function category( string $preferred_slug ): ?WP_Term {
		$name = self::VISIBLE[ $preferred_slug ] ?? self::MORE[ $preferred_slug ] ?? '';

		/** @var array<int,WP_Term> $candidates */
		$candidates = array();

		$slugs = array_merge( array( $preferred_slug ), self::ALIASES[ $preferred_slug ] ?? array() );
		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, 'category' );
			if ( $term instanceof WP_Term ) {
				$candidates[ $term->term_id ] = $term;
			}
		}

		if ( '' !== $name ) {
			$term = get_term_by( 'name', $name, 'category' );
			if ( $term instanceof WP_Term ) {
				$candidates[ $term->term_id ] = $term;
			}
		}

		if ( array() === $candidates ) {
			return null;
		}

		$candidates = array_values( $candidates );
		usort(
			$candidates,
			static fn ( WP_Term $a, WP_Term $b ): int => $b->count <=> $a->count
		);

		return $candidates[0];
	}

	private function create_missing_categories(): void {
		foreach ( array_merge( self::VISIBLE, self::MORE ) as $slug => $name ) {
			if ( null !== self::category( $slug ) ) {
				continue;
			}
			wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		}
	}

	/**
	 * Versiones anteriores creaban un menú «Secciones» en la base de datos
	 * y lo asignaban a la ubicación «primary». Ahora la barra la dibuja el
	 * tema por código: si ese menú autogenerado sigue asignado, se
	 * desasigna para que tome el relevo el menú por defecto (un menú que el
	 * editor haya asignado a mano se respeta y no se toca).
	 */
	private function retire_legacy_menu(): void {
		$menu = wp_get_nav_menu_object( self::LEGACY_MENU );
		if ( ! $menu ) {
			return;
		}

		$locations = get_theme_mod( 'nav_menu_locations', array() );
		if ( isset( $locations['primary'] ) && (int) $locations['primary'] === (int) $menu->term_id ) {
			unset( $locations['primary'] );
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}
}

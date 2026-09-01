<?php
/**
 * Clase principal del tema: theme supports, menús, tamaños de imagen,
 * editor clásico y registro de los subservicios (assets, secciones,
 * personalizador).
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte;

use DiarioDelNorte\Content\DatelineField;
use DiarioDelNorte\Customizer\SiteOptions;
use DiarioDelNorte\Sections\DefaultSectionsInstaller;
use DiarioDelNorte\Users\AuthorProfile;
use WP_Screen;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Theme {

	private static ?Theme $instance = null;

	private function __construct() {}

	public static function instance(): Theme {
		return self::$instance ??= new self();
	}

	public function boot(): void {
		add_action( 'after_setup_theme', array( $this, 'setup' ) );
		add_action( 'init', array( $this, 'ensure_sections' ), 20 );

		( new Assets() )->register();
		( new SiteOptions() )->register();
		( new AuthorProfile() )->register();
		( new DatelineField() )->register();

		// Editor clásico para las noticias (entradas). Las páginas conservan
		// el editor de bloques.
		add_filter( 'use_block_editor_for_post_type', array( $this, 'classic_editor_for_posts' ), 10, 2 );
		// El cuadro «Extracto» (bajada de la noticia) visible por defecto.
		add_filter( 'default_hidden_meta_boxes', array( $this, 'show_excerpt_metabox' ), 10, 2 );
		// El tema no ofrece comentarios de lectores en v0.1.
		add_filter( 'comments_open', '__return_false', 20 );
		add_filter( 'pings_open', '__return_false', 20 );

		add_filter( 'excerpt_more', static fn (): string => '…' );
		add_filter( 'excerpt_length', static fn (): int => 28 );
	}

	public function setup(): void {
		load_theme_textdomain( 'diario-del-norte', DDN_THEME_DIR . 'languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 149,
				'width'       => 700,
				'flex-width'  => true,
				'flex-height' => true,
			)
		);
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
		add_theme_support( 'editor-styles' );
		$editor_css = DDN_THEME_DIR . 'assets/dist/app.css';
		add_editor_style( 'assets/dist/app.css?ver=' . ( file_exists( $editor_css ) ? (string) filemtime( $editor_css ) : DDN_THEME_VERSION ) );

		register_nav_menus(
			array(
				'primary' => __( 'Menú principal (barra de secciones)', 'diario-del-norte' ),
				'footer'  => __( 'Menú de pie de página', 'diario-del-norte' ),
			)
		);

		// Tamaños de imagen del tema.
		add_image_size( 'ddn-lead', 1280, 800, true );      // Nota principal de portada / artículo.
		add_image_size( 'ddn-card', 768, 512, true );       // Tarjetas de sección.
		add_image_size( 'ddn-thumb', 208, 156, true );      // Listados compactos.
		set_post_thumbnail_size( 768, 512, true );

		// Ancho de contenido = columna de lectura del artículo (40rem @ 16px).
		if ( ! isset( $GLOBALS['content_width'] ) ) {
			$GLOBALS['content_width'] = 640; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	public function ensure_sections(): void {
		( new DefaultSectionsInstaller() )->ensure();
	}

	public function classic_editor_for_posts( bool $use_block_editor, string $post_type ): bool {
		return 'post' === $post_type ? false : $use_block_editor;
	}

	/**
	 * @param list<string> $hidden
	 * @return list<string>
	 */
	public function show_excerpt_metabox( array $hidden, WP_Screen $screen ): array {
		if ( 'post' !== $screen->base || 'post' !== $screen->post_type ) {
			return $hidden;
		}

		return array_values( array_diff( $hidden, array( 'postexcerpt' ) ) );
	}
}

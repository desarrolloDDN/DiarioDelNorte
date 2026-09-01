<?php
/**
 * Cabecera de la plantilla de la nota: barra roja compacta (icono de
 * menú + sección de la nota · «DIARIO DEL NORTE» · icono de búsqueda),
 * en lugar del cabezote con el logotipo. Va encima de la cinta «Lo último».
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Nav\SectionMenu;
use DiarioDelNorte\Support\Format;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_cat  = Format::primary_category();
$ddn_name = get_bloginfo( 'name' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#contenido"><?php esc_html_e( 'Saltar al contenido', 'diario-del-norte' ); ?></a>

<header class="topbar">
	<div class="topbar__inner">
		<button
			type="button"
			class="topbar__icon topbar__menu"
			data-drawer-toggle
			aria-controls="topbar-drawer"
			aria-expanded="false"
		>
			<span class="screen-reader-text"><?php esc_attr_e( 'Abrir secciones', 'diario-del-norte' ); ?></span>
			<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path d="M3 6h18M3 12h18M3 18h18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
			</svg>
		</button>

		<?php if ( $ddn_cat ) : ?>
			<a class="topbar__cat" href="<?php echo esc_url( (string) get_category_link( $ddn_cat ) ); ?>">
				<?php echo esc_html( $ddn_cat->name ); ?>
			</a>
		<?php endif; ?>

		<a class="topbar__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php echo esc_html( $ddn_name ); ?>
		</a>

		<button
			type="button"
			class="topbar__icon topbar__search-toggle"
			data-search-toggle
			aria-controls="topbar-search"
			aria-expanded="false"
		>
			<span class="screen-reader-text"><?php esc_attr_e( 'Buscar', 'diario-del-norte' ); ?></span>
			<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/>
				<path d="m20 20-3.5-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
			</svg>
		</button>
	</div>

	<div class="topbar-search" id="topbar-search" hidden>
		<div class="topbar-search__inner">
			<?php get_search_form(); ?>
		</div>
	</div>

	<nav class="topbar-drawer" id="topbar-drawer" aria-label="<?php esc_attr_e( 'Secciones', 'diario-del-norte' ); ?>" hidden>
		<div class="topbar-drawer__inner">
			<?php SectionMenu::render_list(); ?>
		</div>
	</nav>
</header>

<main id="contenido" class="site-main">

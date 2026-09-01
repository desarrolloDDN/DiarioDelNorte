<?php
/**
 * Cabecera del sitio: cabezote (fecha + logotipo) y barra de secciones.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Support\Ads;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

<header class="masthead">
	<div class="wrap">
		<div class="masthead__topline">
			<span><?php echo esc_html( wp_date( 'l j \d\e F \d\e Y' ) ); ?></span>
			<span class="masthead__edition"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
		</div>

		<div class="nameplate">
			<a class="nameplate__link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<?php
				if ( has_custom_logo() ) {
					the_custom_logo();
				} else {
					printf(
						'<img class="nameplate__logo" width="700" height="149" src="%s" alt="%s">',
						esc_url( DDN_THEME_URI . 'assets/img/logo.png' ),
						esc_attr( get_bloginfo( 'name' ) . ' — ' . get_bloginfo( 'description' ) )
					);
				}
				?>
			</a>
		</div>
	</div>
	<hr class="nameplate__rule">
</header>

<nav class="mainnav" aria-label="<?php esc_attr_e( 'Secciones', 'diario-del-norte' ); ?>">
	<?php
	// La barra de secciones la dibuja el tema (10 + submenú «Más»). Para
	// usar en su lugar un menú de Apariencia → Menús asignado a la
	// ubicación «Menú principal», devuelve true en el filtro `ddn/use_custom_nav`.
	if ( apply_filters( 'ddn/use_custom_nav', false ) && has_nav_menu( 'primary' ) ) {
		wp_nav_menu(
			array(
				'theme_location'  => 'primary',
				'container'       => 'div',
				'container_class' => 'mainnav__inner',
				'menu_class'      => 'mainnav__menu',
				'depth'           => 2,
			)
		);
	} else {
		DiarioDelNorte\Nav\SectionMenu::render();
	}
	?>
</nav>

<div class="wrap"><?php Ads::zone( 'header' ); ?></div>

<main id="contenido" class="site-main">

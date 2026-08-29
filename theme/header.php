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
	wp_nav_menu(
		array(
			'theme_location'  => 'primary',
			'container'       => 'div',
			'container_class' => 'mainnav__inner',
			'menu_class'      => 'mainnav__menu',
			'fallback_cb'     => static function (): void {
				echo '<div class="mainnav__inner"><ul class="mainnav__menu">';
				wp_list_categories(
					array(
						'title_li' => '',
						'number'   => 10,
						'orderby'  => 'count',
						'order'    => 'DESC',
					)
				);
				echo '</ul></div>';
			},
			'depth'           => 2,
		)
	);
	?>
</nav>

<div class="wrap"><?php Ads::zone( 'header' ); ?></div>

<main id="contenido" class="site-main">

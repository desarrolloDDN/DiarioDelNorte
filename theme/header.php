<?php
/**
 * Cabecera del sitio: cabezote (fecha + logotipo) y barra de secciones.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Support\Ads;
use DiarioDelNorte\Support\Social;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_city     = (string) get_theme_mod( 'ddn_dateline_city', 'Riohacha' );
$ddn_dnum     = (int) wp_date( 'j' );
$ddn_day      = 1 === $ddn_dnum ? '1°' : (string) $ddn_dnum;
$ddn_month    = ucfirst( wp_date( 'F' ) );
$ddn_dateline = sprintf(
	/* translators: 1: ciudad, 2: día, 3: mes, 4: año. */
	__( '%1$s - %2$s de %3$s de %4$s', 'diario-del-norte' ),
	$ddn_city,
	$ddn_day,
	$ddn_month,
	wp_date( 'Y' )
);
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
			<?php
			$ddn_social_out = Social::render( 'masthead__social' );
			if ( '' !== $ddn_social_out ) {
				echo $ddn_social_out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG ya escapado en Social::render().
			} else {
				echo '<span></span>';
			}
			?>
			<button
				type="button"
				class="masthead__search-toggle"
				data-search-toggle
				aria-controls="masthead-search"
				aria-expanded="false"
			>
				<span class="screen-reader-text"><?php esc_attr_e( 'Buscar', 'diario-del-norte' ); ?></span>
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/>
					<path d="m20 20-3.5-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</button>
		</div>

		<div class="masthead-search" id="masthead-search" hidden>
			<div class="masthead-search__inner"><?php get_search_form(); ?></div>
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

		<p class="masthead__dateline"><?php echo esc_html( $ddn_dateline ); ?></p>
	</div>
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

<?php
/**
 * Pie de página del sitio: identidad, redes sociales, aviso legal, menú
 * de secciones, enlaces legales y datos de contacto.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Sections\DefaultSectionsInstaller as Sections;
use DiarioDelNorte\Support\Social;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_legal_default = sprintf(
	/* translators: %s: nombre de la empresa editora. */
	__( '© %s Sistema Cardenal S.A.S. Todos los derechos reservados. Prohibida la reproducción total o parcial de los contenidos sin autorización previa, expresa y por escrito.', 'diario-del-norte' ),
	wp_date( 'Y' )
);

$ddn_legal        = (string) get_theme_mod( 'ddn_legal', $ddn_legal_default );
$ddn_address      = (string) get_theme_mod( 'ddn_address', 'Riohacha, La Guajira, Colombia' );
$ddn_phone        = (string) get_theme_mod( 'ddn_phone', '' );
$ddn_wa           = (string) get_theme_mod( 'ddn_whatsapp', '' );
$ddn_email        = (string) get_theme_mod( 'ddn_email', 'redaccion@diariodelnorte.net' );
$ddn_contact      = (string) get_theme_mod( 'ddn_contact_url', '' );
$ddn_credit       = (string) get_theme_mod( 'ddn_footer_credit', __( 'Diseñado por Delvis Ibáñez Sevilla', 'diario-del-norte' ) );
$ddn_credit_url   = (string) get_theme_mod( 'ddn_footer_credit_url', 'https://wa.me/573028033129' );
$ddn_contact_href = '' !== $ddn_contact ? $ddn_contact : ( '' !== $ddn_email ? 'mailto:' . $ddn_email : '' );

/**
 * Enlaces legales del pie. Sirven de reserva del menú «footer-legal»:
 * si no hay uno asignado en Apariencia → Menús, se muestran estos.
 *
 * @var array<string,string> $ddn_legal_links slug => etiqueta
 */
$ddn_legal_links = array(
	'terminos-y-condiciones'                    => __( 'Términos y Condiciones', 'diario-del-norte' ),
	'derechos-de-autor-y-propiedad-intelectual' => __( 'Derechos de Autor y Propiedad Intelectual', 'diario-del-norte' ),
	'politica-de-uso-de-cookies'                => __( 'Política de uso de cookies', 'diario-del-norte' ),
	'politica-de-tratamiento-de-datos'          => __( 'Política de Tratamiento de Datos', 'diario-del-norte' ),
	'directrices-editoriales'                   => __( 'Directrices Editoriales', 'diario-del-norte' ),
);

/** Datos de contacto, separados por « | ». */
$ddn_contact_bits = array_filter(
	array(
		'' !== $ddn_address ? sprintf( /* translators: %s: dirección física. */ __( 'Dirección: %s', 'diario-del-norte' ), $ddn_address ) : '',
		'' !== $ddn_phone ? sprintf( /* translators: %s: teléfono. */ __( 'Servicio al cliente: %s', 'diario-del-norte' ), $ddn_phone ) : '',
		'' !== $ddn_wa ? sprintf( /* translators: %s: número de WhatsApp. */ __( 'WhatsApp: %s', 'diario-del-norte' ), $ddn_wa ) : '',
	)
);
?>
</main><!-- #contenido -->

<footer class="site-footer">
	<div class="wrap">

		<div class="site-footer__top">
			<div class="site-footer__brand">
				<?php
				if ( has_custom_logo() ) {
					the_custom_logo();
					// El logotipo por defecto ya trae impreso el lema; con un
					// logo propio se muestra aparte.
					$ddn_tagline = get_bloginfo( 'description' );
					if ( $ddn_tagline ) {
						echo '<span class="site-footer__tagline">' . esc_html( $ddn_tagline ) . '</span>';
					}
				} else {
					printf(
						'<a class="site-footer__logo-link" href="%1$s" rel="home"><img class="site-footer__logo" width="700" height="149" src="%2$s" alt="%3$s"></a>',
						esc_url( home_url( '/' ) ),
						esc_url( DDN_THEME_URI . 'assets/img/logo.png' ),
						esc_attr( get_bloginfo( 'name' ) . ' — ' . get_bloginfo( 'description' ) )
					);
				}
				?>
			</div>

			<?php
			$ddn_social_out = Social::render();
			if ( '' !== $ddn_social_out ) :
				?>
				<div class="site-footer__social">
					<span class="site-footer__social-label"><?php esc_html_e( 'Síguenos en redes:', 'diario-del-norte' ); ?></span>
					<?php echo $ddn_social_out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG ya escapado en Social::render(). ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( '' !== $ddn_legal ) : ?>
			<p class="site-footer__legal"><?php echo esc_html( $ddn_legal ); ?></p>
		<?php endif; ?>

		<nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'Secciones', 'diario-del-norte' ); ?>">
			<?php
			if ( has_nav_menu( 'footer' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'site-footer__menu',
						'depth'          => 1,
					)
				);
			} else {
				echo '<ul class="site-footer__menu">';
				foreach ( Sections::VISIBLE as $ddn_slug => $ddn_name ) {
					$ddn_term = Sections::category( $ddn_slug );
					$ddn_link = $ddn_term ? get_category_link( $ddn_term ) : home_url( '/' . $ddn_slug . '/' );
					printf( '<li><a href="%s">%s</a></li>', esc_url( $ddn_link ), esc_html( $ddn_name ) );
				}
				if ( '' !== $ddn_contact_href ) {
					printf( '<li><a href="%s">%s</a></li>', esc_url( $ddn_contact_href ), esc_html__( 'Contacto', 'diario-del-norte' ) );
				}
				echo '</ul>';
			}
			?>
		</nav>

		<details class="site-footer__legal-nav">
			<summary class="site-footer__legal-summary"><?php esc_html_e( 'Términos y políticas', 'diario-del-norte' ); ?></summary>
			<?php
			if ( has_nav_menu( 'footer-legal' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'footer-legal',
						'container'      => false,
						'menu_class'     => 'site-footer__legal-links',
						'depth'          => 1,
					)
				);
			} else {
				echo '<ul class="site-footer__legal-links">';
				foreach ( $ddn_legal_links as $ddn_slug => $ddn_label ) {
					printf(
						'<li><a href="%s">%s</a></li>',
						esc_url( home_url( '/' . $ddn_slug . '/' ) ),
						esc_html( $ddn_label )
					);
				}
				echo '</ul>';
			}
			?>
		</details>

		<div class="site-footer__bar">
			<?php if ( '' !== $ddn_contact_href ) : ?>
				<a class="btn site-footer__contact-btn" href="<?php echo esc_url( $ddn_contact_href ); ?>"><?php esc_html_e( 'Contáctenos', 'diario-del-norte' ); ?></a>
			<?php endif; ?>

			<?php if ( array() !== $ddn_contact_bits ) : ?>
				<p class="site-footer__contact"><?php echo esc_html( implode( ' | ', $ddn_contact_bits ) ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( '' !== $ddn_credit ) : ?>
			<p class="site-footer__credit">
				<?php echo esc_html( $ddn_credit ); ?>
				<?php if ( '' !== $ddn_credit_url ) : ?>
					&mdash; <a href="<?php echo esc_url( $ddn_credit_url ); ?>" rel="noopener"><?php esc_html_e( 'Contacto', 'diario-del-norte' ); ?></a>
				<?php endif; ?>
			</p>
		<?php endif; ?>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>

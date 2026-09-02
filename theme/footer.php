<?php
/**
 * Pie de página del sitio: identidad, redes sociales, aviso legal, menú
 * de secciones y datos de contacto.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Sections\DefaultSectionsInstaller as Sections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Iconos SVG (viewBox 0 0 24 24) de cada red. */
$ddn_social = array(
	'facebook'  => array(
		'label' => __( 'Facebook', 'diario-del-norte' ),
		'path'  => 'M13 22v-8h2.9l.5-3.4H13V8.4c0-1 .3-1.6 1.7-1.6h1.8V3.8C17 3.7 16 3.6 14.8 3.6c-2.6 0-4.4 1.6-4.4 4.5v2.5H7.4V14h3V22H13Z',
	),
	'x'         => array(
		'label' => __( 'X', 'diario-del-norte' ),
		'path'  => 'M13.9 10.3 20 3h-1.6l-5.2 6.2L9 3H3.5l6.4 9.2L3.5 21H5l5.5-6.6L15 21h5.5l-6.6-10.7Zm-1.9 2.3-.6-.9-5-7.2h2.3l4.1 5.8.6.9 5.2 7.4h-2.3l-4.3-6Z',
	),
	'instagram' => array(
		'label' => __( 'Instagram', 'diario-del-norte' ),
		'path'  => 'M8 2h8a6 6 0 0 1 6 6v8a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6V8a6 6 0 0 1 6-6Zm0 2a4 4 0 0 0-4 4v8a4 4 0 0 0 4 4h8a4 4 0 0 0 4-4V8a4 4 0 0 0-4-4H8Zm4 3a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm5.2-3.1a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4Z',
	),
	'youtube'   => array(
		'label' => __( 'YouTube', 'diario-del-norte' ),
		'path'  => 'M23 12s0-3.5-.4-5.1a3 3 0 0 0-2.1-2.1C18.8 4.4 12 4.4 12 4.4s-6.8 0-8.5.4A3 3 0 0 0 1.4 6.9C1 8.5 1 12 1 12s0 3.5.4 5.1a3 3 0 0 0 2.1 2.1c1.7.4 8.5.4 8.5.4s6.8 0 8.5-.4a3 3 0 0 0 2.1-2.1C23 15.5 23 12 23 12ZM10 15.5v-7l6 3.5-6 3.5Z',
	),
	'whatsapp'  => array(
		'label' => __( 'WhatsApp', 'diario-del-norte' ),
		'path'  => 'M12 2a10 10 0 0 0-8.6 15l-1.3 4.8 4.9-1.3A10 10 0 1 0 12 2Zm5.3 13.6c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-1.7-.1-.4-.1-1-.3-1.6-.6-2.9-1.3-4.8-4.2-4.9-4.4-.2-.2-1.2-1.6-1.2-3s.7-2.1 1-2.4c.3-.3.6-.4.8-.4h.6c.2 0 .4 0 .7.5l.9 2.1c.1.2.1.4 0 .6l-.4.6-.4.4c-.2.2-.3.4-.1.7.2.3.9 1.4 1.9 2.3 1.3 1.1 2.3 1.5 2.6 1.6.3.1.5.1.7-.1l.9-1c.2-.3.4-.2.7-.1l2 1c.3.1.5.2.6.3.1.2.1.8-.1 1.4Z',
	),
);

$ddn_legal        = (string) get_theme_mod( 'ddn_legal', '' );
$ddn_address      = (string) get_theme_mod( 'ddn_address', '' );
$ddn_phone        = (string) get_theme_mod( 'ddn_phone', '' );
$ddn_wa           = (string) get_theme_mod( 'ddn_whatsapp', '' );
$ddn_email        = (string) get_theme_mod( 'ddn_email', '' );
$ddn_terms        = (string) get_theme_mod( 'ddn_terms_url', '' );
$ddn_contact      = (string) get_theme_mod( 'ddn_contact_url', '' );
$ddn_credit       = (string) get_theme_mod( 'ddn_footer_credit', '' );
$ddn_contact_href = '' !== $ddn_contact ? $ddn_contact : ( '' !== $ddn_email ? 'mailto:' . $ddn_email : '' );

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
			$ddn_social_out = '';
			foreach ( $ddn_social as $ddn_key => $ddn_net ) {
				$ddn_url = (string) get_theme_mod( "ddn_social_{$ddn_key}", '' );
				if ( '' === $ddn_url ) {
					continue;
				}
				$ddn_social_out .= sprintf(
					'<li><a href="%1$s" rel="noopener" aria-label="%2$s"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="%3$s"/></svg></a></li>',
					esc_url( $ddn_url ),
					esc_attr( $ddn_net['label'] ),
					esc_attr( $ddn_net['path'] )
				);
			}
			if ( '' !== $ddn_social_out ) :
				?>
				<div class="site-footer__social">
					<span class="site-footer__social-label"><?php esc_html_e( 'Síguenos en redes:', 'diario-del-norte' ); ?></span>
					<ul><?php echo $ddn_social_out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba. ?></ul>
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

		<div class="site-footer__bar">
			<?php if ( '' !== $ddn_terms ) : ?>
				<a class="site-footer__terms" href="<?php echo esc_url( $ddn_terms ); ?>"><?php esc_html_e( 'Términos y condiciones', 'diario-del-norte' ); ?></a>
			<?php endif; ?>

			<?php if ( '' !== $ddn_contact_href ) : ?>
				<a class="btn site-footer__contact-btn" href="<?php echo esc_url( $ddn_contact_href ); ?>"><?php esc_html_e( 'Contáctenos', 'diario-del-norte' ); ?></a>
			<?php endif; ?>

			<?php if ( array() !== $ddn_contact_bits ) : ?>
				<p class="site-footer__contact"><?php echo esc_html( implode( ' | ', $ddn_contact_bits ) ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( '' !== $ddn_credit ) : ?>
			<p class="site-footer__credit"><?php echo esc_html( $ddn_credit ); ?></p>
		<?php endif; ?>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>

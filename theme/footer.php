<?php
/**
 * Pie de página del sitio.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_networks = array(
	'facebook'  => 'Facebook',
	'x'         => 'X',
	'instagram' => 'Instagram',
	'youtube'   => 'YouTube',
	'whatsapp'  => 'WhatsApp',
);
?>
</main><!-- #contenido -->

<footer class="site-footer">
	<div class="wrap site-footer__grid">
		<div class="site-footer__brand">
			<p class="site-footer__name"><?php bloginfo( 'name' ); ?></p>
			<p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
			<?php
			$ddn_address = get_theme_mod( 'ddn_address', '' );
			if ( $ddn_address ) {
				echo '<p>' . esc_html( $ddn_address ) . '</p>';
			}
			$ddn_email = get_theme_mod( 'ddn_email', '' );
			if ( $ddn_email ) {
				printf( '<p><a href="mailto:%1$s">%1$s</a></p>', esc_attr( $ddn_email ) );
			}
			?>
			<ul class="site-footer__social">
				<?php
				foreach ( $ddn_networks as $ddn_key => $ddn_label ) {
					$ddn_url = get_theme_mod( "ddn_social_{$ddn_key}", '' );
					if ( $ddn_url ) {
						printf(
							'<li><a href="%s" rel="noopener">%s</a></li>',
							esc_url( $ddn_url ),
							esc_html( $ddn_label )
						);
					}
				}
				?>
			</ul>
		</div>

		<nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'Enlaces del pie', 'diario-del-norte' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'site-footer__menu',
					'depth'          => 1,
					'fallback_cb'    => '__return_empty_string',
				)
			);
			?>
		</nav>
	</div>

	<div class="wrap site-footer__legal">
		<?php echo esc_html( get_theme_mod( 'ddn_legal', '© ' . wp_date( 'Y' ) . ' ' . get_bloginfo( 'name' ) ) ); ?>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>

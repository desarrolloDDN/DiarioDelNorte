<?php
/**
 * Llamado a suscribirse al final de cada nota (solo visitantes sin
 * sesión). Los enlaces los aporta el plugin DDN Suite por filtros; sin el
 * plugin no hay a dónde llevar, así que no se imprime nada.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_register_url = (string) apply_filters( 'ddn/register_url', '' );
if ( '' === $ddn_register_url ) {
	return;
}

$ddn_login_url  = (string) apply_filters( 'ddn/login_url', '' );
$ddn_google_url = (string) apply_filters( 'ddn/google_signup_url', '', get_permalink() );
?>
<aside class="subscribe-cta" aria-labelledby="subscribe-cta-title">
	<p class="subscribe-cta__kicker"><?php esc_html_e( 'Suscríbete gratis', 'diario-del-norte' ); ?></p>
	<h2 class="subscribe-cta__title" id="subscribe-cta-title"><?php esc_html_e( 'Crea tu cuenta gratis y recibe artículos exclusivos para suscriptores y otros beneficios.', 'diario-del-norte' ); ?></h2>
	<div class="subscribe-cta__actions">
		<?php if ( '' !== $ddn_google_url ) : ?>
			<a class="subscribe-cta__btn subscribe-cta__btn--google" href="<?php echo esc_url( $ddn_google_url ); ?>">
				<svg viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.6 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.6 5.9c4.4-4.1 7-10.1 7-17.6z"/><path fill="#FBBC05" d="M10.5 28.7c-.5-1.4-.8-3-.8-4.7s.3-3.3.8-4.7l-7.9-6.1C.9 16.4 0 20.1 0 24s.9 7.6 2.6 10.8l7.9-6.1z"/><path fill="#34A853" d="M24 48c6.5 0 11.900-2.100 15.900-5.800l-7.600-5.900c-2.100 1.400-4.900 2.300-8.300 2.300-6.300 0-11.600-4.100-13.500-9.800l-7.900 6.100C6.500 42.600 14.600 48 24 48z"/></svg>
				<span><?php esc_html_e( 'Continuar con Google', 'diario-del-norte' ); ?></span>
			</a>
		<?php endif; ?>
		<a class="subscribe-cta__btn subscribe-cta__btn--primary" href="<?php echo esc_url( $ddn_register_url ); ?>"><?php esc_html_e( 'Crear cuenta gratis', 'diario-del-norte' ); ?></a>
	</div>
	<?php if ( '' !== $ddn_login_url ) : ?>
		<p class="subscribe-cta__login">
			<?php esc_html_e( '¿Ya tienes cuenta?', 'diario-del-norte' ); ?>
			<a href="<?php echo esc_url( $ddn_login_url ); ?>"><?php esc_html_e( 'Inicia sesión', 'diario-del-norte' ); ?></a>
		</p>
	<?php endif; ?>
</aside>

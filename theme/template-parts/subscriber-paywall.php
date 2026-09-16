<?php
/**
 * Aviso de contenido exclusivo para suscriptores, con enlaces a
 * inicio de sesión y registro (contrato con el plugin DDN Suite).
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_login_url    = (string) apply_filters( 'ddn/login_url', '' );
$ddn_register_url = (string) apply_filters( 'ddn/register_url', '' );

if ( '' === $ddn_login_url && '' === $ddn_register_url ) {
	return;
}
?>
<div class="subscriber-paywall">
	<p class="subscriber-paywall__text"><?php esc_html_e( 'Esta nota es exclusiva para suscriptores. Inicia sesión o crea una cuenta gratuita para leerla completa.', 'diario-del-norte' ); ?></p>
	<div class="subscriber-paywall__actions">
		<?php if ( '' !== $ddn_login_url ) : ?>
			<a class="btn btn--ghost" href="<?php echo esc_url( $ddn_login_url ); ?>"><?php esc_html_e( 'Iniciar sesión', 'diario-del-norte' ); ?></a>
		<?php endif; ?>
		<?php if ( '' !== $ddn_register_url ) : ?>
			<a class="btn" href="<?php echo esc_url( $ddn_register_url ); ?>"><?php esc_html_e( 'Crear cuenta gratis', 'diario-del-norte' ); ?></a>
		<?php endif; ?>
	</div>
</div>

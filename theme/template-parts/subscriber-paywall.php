<?php
/**
 * Aviso de contenido bloqueado para un visitante sin sesión, con enlaces
 * a inicio de sesión y registro (contrato con el plugin DDN Suite). El
 * texto cambia según el motivo: nota marcada «exclusiva para
 * suscriptores», o límite de notas gratis ya alcanzado.
 *
 * @param array{reason?:string} $args 'exclusive' (por defecto) o 'metered'.
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

$ddn_reason = isset( $args['reason'] ) ? (string) $args['reason'] : 'exclusive';
$ddn_text   = 'metered' === $ddn_reason
	? __( 'Ya leíste tus notas gratis. Inicia sesión o crea una cuenta gratuita para seguir leyendo sin límite.', 'diario-del-norte' )
	: __( 'Esta nota es exclusiva para suscriptores. Inicia sesión o crea una cuenta gratuita para leerla completa.', 'diario-del-norte' );
?>
<div class="subscriber-paywall">
	<p class="subscriber-paywall__text"><?php echo esc_html( $ddn_text ); ?></p>
	<div class="subscriber-paywall__actions">
		<?php if ( '' !== $ddn_login_url ) : ?>
			<a class="btn btn--ghost" href="<?php echo esc_url( $ddn_login_url ); ?>"><?php esc_html_e( 'Iniciar sesión', 'diario-del-norte' ); ?></a>
		<?php endif; ?>
		<?php if ( '' !== $ddn_register_url ) : ?>
			<a class="btn" href="<?php echo esc_url( $ddn_register_url ); ?>"><?php esc_html_e( 'Crear cuenta gratis', 'diario-del-norte' ); ?></a>
		<?php endif; ?>
	</div>
</div>

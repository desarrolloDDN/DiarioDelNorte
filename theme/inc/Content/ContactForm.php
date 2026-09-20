<?php
/**
 * Formulario de contacto (page-contacto.php): envía el mensaje por
 * wp_mail(), sin plugin ni servicio externo. Funciona sin JavaScript
 * (POST clásico a admin-post.php, con redirección de vuelta a la página).
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContactForm {

	private const ACTION     = 'ddn_contact_submit';
	private const DEFAULT_TO = 'gerenciageneral@gamezeditores.com';

	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $this, 'handle' ) );
	}

	/** Correo que recibe el formulario y que se muestra en «Datos de contacto». */
	public static function recipient(): string {
		$to = (string) get_theme_mod( 'ddn_contact_form_email', '' );

		return '' !== $to ? $to : self::DEFAULT_TO;
	}

	public static function action_url(): string {
		return admin_url( 'admin-post.php' );
	}

	public static function action_name(): string {
		return self::ACTION;
	}

	public function handle(): void {
		$redirect = wp_get_referer();
		if ( '' === (string) $redirect ) {
			$redirect = ContactPageInstaller::url();
		}
		if ( '' === (string) $redirect ) {
			$redirect = home_url( '/' );
		}

		$nonce = isset( $_POST['ddn_contact_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ddn_contact_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::ACTION ) ) {
			$this->redirect_with( (string) $redirect, 'error' );
		}

		// Trampa para robots: un campo oculto que un humano nunca llena.
		// Si viene lleno, se finge éxito para no delatar el filtro.
		if ( '' !== trim( (string) ( $_POST['ddn_contact_website'] ?? '' ) ) ) {
			$this->redirect_with( (string) $redirect, 'sent' );
		}

		$name    = sanitize_text_field( wp_unslash( $_POST['ddn_contact_name'] ?? '' ) );
		$email   = sanitize_email( wp_unslash( $_POST['ddn_contact_email'] ?? '' ) );
		$subject = sanitize_text_field( wp_unslash( $_POST['ddn_contact_subject'] ?? '' ) );
		$message = sanitize_textarea_field( wp_unslash( $_POST['ddn_contact_message'] ?? '' ) );

		if ( '' === $name || ! is_email( $email ) || '' === trim( $message ) ) {
			$this->redirect_with( (string) $redirect, 'error' );
		}

		$to = self::recipient();

		$subject_line = '' !== $subject ? $subject : __( 'Sin asunto', 'diario-del-norte' );
		$body         = sprintf(
			/* translators: 1: nombre, 2: correo, 3: asunto, 4: mensaje. */
			__( "Nombre: %1\$s\nCorreo: %2\$s\nAsunto: %3\$s\n\nMensaje:\n%4\$s", 'diario-del-norte' ),
			$name,
			$email,
			$subject_line,
			$message
		);

		$sent = wp_mail(
			$to,
			sprintf( '[%s] %s', __( 'Contacto', 'diario-del-norte' ), $subject_line ),
			$body,
			array( 'Reply-To: ' . $name . ' <' . $email . '>' )
		);

		$this->redirect_with( (string) $redirect, $sent ? 'sent' : 'error' );
	}

	private function redirect_with( string $url, string $status ): void {
		wp_safe_redirect( add_query_arg( 'ddn_contact', $status, $url ) . '#contact-form' );
		exit;
	}
}

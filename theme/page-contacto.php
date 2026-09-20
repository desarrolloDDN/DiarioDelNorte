<?php
/**
 * Página de Contacto. WordPress la usa automáticamente en la página con
 * slug «contacto» (creada sola, ver Content\ContactPageInstaller); el
 * botón «Contáctenos» del pie enlaza aquí.
 *
 * Formulario a la izquierda (envía por Content\ContactForm, sin JS ni
 * servicio externo); datos de contacto —dirección, correo, teléfonos de
 * cada dependencia— a la derecha.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Content\ContactForm;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo decide qué aviso mostrar, no cambia estado.
$ddn_status = isset( $_GET['ddn_contact'] ) ? sanitize_key( wp_unslash( $_GET['ddn_contact'] ) ) : '';

$ddn_address = (string) get_theme_mod( 'ddn_address', 'Riohacha, La Guajira, Colombia' );
$ddn_phone   = (string) get_theme_mod( 'ddn_phone', '' );
$ddn_wa      = (string) get_theme_mod( 'ddn_whatsapp', '' );
$ddn_email   = ContactForm::recipient();

/**
 * Teléfonos por dependencia, editables en Personalizar → Contacto.
 *
 * @var array<string,string> $ddn_lines etiqueta => número a mostrar
 */
$ddn_lines = array_filter(
	array(
		__( 'Gerencia General', 'diario-del-norte' )       => (string) get_theme_mod( 'ddn_contact_phone_gerencia', '+57 320 542 0459' ),
		__( 'Comercial Riohacha', 'diario-del-norte' )     => (string) get_theme_mod( 'ddn_contact_phone_comercial_rio', '+57 300 817 6610' ),
		__( 'Comercial Barranquilla', 'diario-del-norte' ) => (string) get_theme_mod( 'ddn_contact_phone_comercial_baq', '+57 320 565 9368' ),
	)
);
?>
<div class="wrap layout-contact">

	<header class="page-head">
		<h1 class="page-head__title"><?php the_title(); ?></h1>
	</header>

	<div class="contact-layout">

		<div class="contact-form" id="contact-form">
			<?php if ( 'sent' === $ddn_status ) : ?>
				<p class="contact-form__notice contact-form__notice--ok"><?php esc_html_e( 'Gracias, recibimos tu mensaje. Te responderemos pronto.', 'diario-del-norte' ); ?></p>
			<?php elseif ( 'error' === $ddn_status ) : ?>
				<p class="contact-form__notice contact-form__notice--error"><?php esc_html_e( 'No pudimos enviar tu mensaje. Revisa los datos e inténtalo de nuevo.', 'diario-del-norte' ); ?></p>
			<?php endif; ?>

			<form class="contact-form__fields" method="post" action="<?php echo esc_url( ContactForm::action_url() ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( ContactForm::action_name() ); ?>">
				<?php wp_nonce_field( ContactForm::action_name(), 'ddn_contact_nonce' ); ?>

				<p class="contact-form__hp" aria-hidden="true">
					<label for="ddn_contact_website"><?php esc_html_e( 'Sitio web (dejar en blanco)', 'diario-del-norte' ); ?></label>
					<input type="text" id="ddn_contact_website" name="ddn_contact_website" tabindex="-1" autocomplete="off">
				</p>

				<p class="contact-form__field">
					<label for="ddn_contact_name"><?php esc_html_e( 'Nombre', 'diario-del-norte' ); ?></label>
					<input type="text" id="ddn_contact_name" name="ddn_contact_name" required>
				</p>

				<p class="contact-form__field">
					<label for="ddn_contact_email"><?php esc_html_e( 'Correo electrónico', 'diario-del-norte' ); ?></label>
					<input type="email" id="ddn_contact_email" name="ddn_contact_email" required>
				</p>

				<p class="contact-form__field">
					<label for="ddn_contact_subject"><?php esc_html_e( 'Asunto', 'diario-del-norte' ); ?></label>
					<select id="ddn_contact_subject" name="ddn_contact_subject">
						<option value=""><?php esc_html_e( 'Selecciona una opción', 'diario-del-norte' ); ?></option>
						<option value="<?php esc_attr_e( 'Redacción', 'diario-del-norte' ); ?>"><?php esc_html_e( 'Redacción', 'diario-del-norte' ); ?></option>
						<?php foreach ( array_keys( $ddn_lines ) as $ddn_label ) : ?>
							<option value="<?php echo esc_attr( $ddn_label ); ?>"><?php echo esc_html( $ddn_label ); ?></option>
						<?php endforeach; ?>
						<option value="<?php esc_attr_e( 'Otro', 'diario-del-norte' ); ?>"><?php esc_html_e( 'Otro', 'diario-del-norte' ); ?></option>
					</select>
				</p>

				<p class="contact-form__field">
					<label for="ddn_contact_message"><?php esc_html_e( 'Mensaje', 'diario-del-norte' ); ?></label>
					<textarea id="ddn_contact_message" name="ddn_contact_message" rows="6" required></textarea>
				</p>

				<button type="submit" class="btn contact-form__submit"><?php esc_html_e( 'Enviar mensaje', 'diario-del-norte' ); ?></button>
			</form>
		</div>

		<aside class="contact-info">
			<h2 class="contact-info__title"><?php esc_html_e( 'Datos de contacto', 'diario-del-norte' ); ?></h2>

			<?php if ( '' !== $ddn_address ) : ?>
				<p class="contact-info__line">
					<span class="contact-info__label"><?php esc_html_e( 'Dirección', 'diario-del-norte' ); ?></span>
					<?php echo esc_html( $ddn_address ); ?>
				</p>
			<?php endif; ?>

			<?php if ( '' !== $ddn_email ) : ?>
				<p class="contact-info__line">
					<span class="contact-info__label"><?php esc_html_e( 'Correo', 'diario-del-norte' ); ?></span>
					<a href="mailto:<?php echo esc_attr( $ddn_email ); ?>"><?php echo esc_html( $ddn_email ); ?></a>
				</p>
			<?php endif; ?>

			<?php foreach ( $ddn_lines as $ddn_label => $ddn_number ) : ?>
				<p class="contact-info__line">
					<span class="contact-info__label"><?php echo esc_html( $ddn_label ); ?></span>
					<a href="tel:<?php echo esc_attr( str_replace( ' ', '', $ddn_number ) ); ?>"><?php echo esc_html( $ddn_number ); ?></a>
				</p>
			<?php endforeach; ?>

			<?php if ( '' !== $ddn_phone ) : ?>
				<p class="contact-info__line">
					<span class="contact-info__label"><?php esc_html_e( 'Servicio al cliente', 'diario-del-norte' ); ?></span>
					<a href="tel:<?php echo esc_attr( str_replace( ' ', '', $ddn_phone ) ); ?>"><?php echo esc_html( $ddn_phone ); ?></a>
				</p>
			<?php endif; ?>

			<?php if ( '' !== $ddn_wa ) : ?>
				<p class="contact-info__line">
					<span class="contact-info__label"><?php esc_html_e( 'WhatsApp', 'diario-del-norte' ); ?></span>
					<a href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D/', '', $ddn_wa ) ); ?>"><?php echo esc_html( $ddn_wa ); ?></a>
				</p>
			<?php endif; ?>
		</aside>

	</div>
</div>
<?php
get_footer();

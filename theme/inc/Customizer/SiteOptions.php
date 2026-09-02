<?php
/**
 * Opciones del sitio en el Personalizador: datos de contacto, pie de
 * página (texto legal, menú, redes) y edición impresa (portada + PDF).
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Customizer;

use WP_Customize_Image_Control;
use WP_Customize_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SiteOptions {

	public function register(): void {
		add_action( 'customize_register', array( $this, 'customize' ) );
	}

	public function customize( WP_Customize_Manager $wp_customize ): void {

		// --- Contacto (dirección y teléfonos, editables) -----------------
		$wp_customize->add_section(
			'ddn_contact',
			array(
				'title'       => __( 'Contacto', 'diario-del-norte' ),
				'description' => __( 'Datos que aparecen en el pie de página.', 'diario-del-norte' ),
				'priority'    => 119,
			)
		);

		$this->text( $wp_customize, 'ddn_address', __( 'Dirección', 'diario-del-norte' ), 'Riohacha, La Guajira, Colombia', 'sanitize_text_field', 'ddn_contact', __( 'Ej.: Calle 22 No. 7H-233, Riohacha, La Guajira.', 'diario-del-norte' ) );
		$this->text( $wp_customize, 'ddn_phone', __( 'Teléfono / servicio al cliente', 'diario-del-norte' ), '', 'sanitize_text_field', 'ddn_contact', __( 'Número tal como quieres que se vea. En blanco no se muestra.', 'diario-del-norte' ) );
		$this->text( $wp_customize, 'ddn_whatsapp', __( 'WhatsApp', 'diario-del-norte' ), '', 'sanitize_text_field', 'ddn_contact', __( 'Número visible de WhatsApp, p. ej. +57 300 000 0000. En blanco no se muestra.', 'diario-del-norte' ) );
		$this->text( $wp_customize, 'ddn_email', __( 'Correo de redacción', 'diario-del-norte' ), 'redaccion@diariodelnorte.net', 'sanitize_email', 'ddn_contact' );
		$this->text( $wp_customize, 'ddn_dateline_city', __( 'Ciudad por defecto en las firmas', 'diario-del-norte' ), 'Riohacha', 'sanitize_text_field', 'ddn_contact', __( 'Se usa en la firma de las notas cuando la entrada no trae un lugar propio.', 'diario-del-norte' ) );

		// --- Pie de página ---------------------------------------------
		$wp_customize->add_section(
			'ddn_footer',
			array(
				'title'    => __( 'Pie de página', 'diario-del-norte' ),
				'priority' => 120,
			)
		);

		$this->text( $wp_customize, 'ddn_legal', __( 'Texto legal / copyright', 'diario-del-norte' ), '© ' . gmdate( 'Y' ) . ' Sistema Cardenal S.A.S. Todos los derechos reservados. Prohibida la reproducción total o parcial de los contenidos sin autorización previa, expresa y por escrito.' );
		$this->text( $wp_customize, 'ddn_contact_url', __( 'Enlace del botón «Contáctenos»', 'diario-del-norte' ), '', 'esc_url_raw', 'ddn_footer', __( 'En blanco: abre el correo de redacción.', 'diario-del-norte' ) );
		$this->text( $wp_customize, 'ddn_footer_credit', __( 'Crédito al pie', 'diario-del-norte' ), __( 'Diseñado por Delvis Ibáñez Sevilla', 'diario-del-norte' ), 'sanitize_text_field', 'ddn_footer', __( 'Línea final centrada. En blanco no se muestra.', 'diario-del-norte' ) );
		$this->text( $wp_customize, 'ddn_footer_credit_url', __( 'Enlace de «Contacto» del crédito', 'diario-del-norte' ), 'https://wa.me/573028033129', 'esc_url_raw', 'ddn_footer', __( 'Si tiene enlace, tras el crédito aparece «— Contacto».', 'diario-del-norte' ) );

		foreach ( $this->networks() as $key => $label ) {
			$this->text( $wp_customize, "ddn_social_{$key}", $label, '', 'esc_url_raw' );
		}

		$wp_customize->add_section(
			'ddn_print',
			array(
				'title'    => __( 'Edición impresa', 'diario-del-norte' ),
				'priority' => 121,
			)
		);

		$wp_customize->add_setting( 'ddn_print_cover', array( 'sanitize_callback' => 'absint' ) );
		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				'ddn_print_cover',
				array(
					'label'   => __( 'Portada de la edición de hoy', 'diario-del-norte' ),
					'section' => 'ddn_print',
				)
			)
		);
		$this->text( $wp_customize, 'ddn_print_pdf', __( 'Enlace al PDF', 'diario-del-norte' ), '', 'esc_url_raw', 'ddn_print' );
		$this->text( $wp_customize, 'ddn_print_label', __( 'Texto bajo la portada', 'diario-del-norte' ), '', 'sanitize_text_field', 'ddn_print' );
	}

	/**
	 * @return array<string,string>
	 */
	private function networks(): array {
		return array(
			'facebook'  => __( 'Facebook (URL)', 'diario-del-norte' ),
			'x'         => __( 'X / Twitter (URL)', 'diario-del-norte' ),
			'instagram' => __( 'Instagram (URL)', 'diario-del-norte' ),
			'youtube'   => __( 'YouTube (URL)', 'diario-del-norte' ),
			'whatsapp'  => __( 'WhatsApp (URL)', 'diario-del-norte' ),
		);
	}

	private function text( WP_Customize_Manager $wp_customize, string $id, string $label, string $default_value = '', string $sanitize = 'sanitize_text_field', string $section = 'ddn_footer', string $description = '' ): void {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $default_value,
				'sanitize_callback' => $sanitize,
			)
		);
		$wp_customize->add_control(
			$id,
			array(
				'label'       => $label,
				'description' => $description,
				'section'     => $section,
				'type'        => 'text',
			)
		);
	}
}

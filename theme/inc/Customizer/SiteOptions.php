<?php
/**
 * Opciones del sitio en el Personalizador: pie de página (texto legal,
 * contacto, redes sociales) y edición impresa (portada + PDF).
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
		$wp_customize->add_section(
			'ddn_footer',
			array(
				'title'    => __( 'Pie de página y contacto', 'diario-del-norte' ),
				'priority' => 120,
			)
		);

		$this->text( $wp_customize, 'ddn_legal', __( 'Texto legal / copyright', 'diario-del-norte' ), '© ' . gmdate( 'Y' ) . ' Sistema Cardenal S.A.S. Todos los derechos reservados.' );
		$this->text( $wp_customize, 'ddn_address', __( 'Dirección y teléfono', 'diario-del-norte' ), 'Riohacha, La Guajira, Colombia' );
		$this->text( $wp_customize, 'ddn_email', __( 'Correo de redacción', 'diario-del-norte' ), 'redaccion@diariodelnorte.net', 'sanitize_email' );

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

	private function text( WP_Customize_Manager $wp_customize, string $id, string $label, string $default_value = '', string $sanitize = 'sanitize_text_field', string $section = 'ddn_footer' ): void {
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
				'label'   => $label,
				'section' => $section,
				'type'    => 'text',
			)
		);
	}
}

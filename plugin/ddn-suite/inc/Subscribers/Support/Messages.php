<?php
/**
 * Traduce los códigos de error de Validator (y de los controladores) a
 * texto para el usuario. Separado de Validator para que este último siga
 * siendo lógica pura sin llamadas de WordPress (`__()`).
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Messages {

	public static function registration( string $code ): string {
		return self::map()[ $code ] ?? self::generic();
	}

	public static function login( string $code ): string {
		$map = array(
			'invalid'         => __( 'Usuario/correo o contraseña incorrectos.', 'ddn-suite' ),
			'locked'          => __( 'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.', 'ddn-suite' ),
			'invalid_request' => self::generic(),
		);

		return $map[ $code ] ?? self::generic();
	}

	public static function profile( string $code ): string {
		$map = array(
			'full_name_required'        => __( 'Escribe tu nombre completo.', 'ddn-suite' ),
			'phone_required'            => __( 'Escribe tu celular.', 'ddn-suite' ),
			'department_invalid'        => __( 'Elige un departamento de la lista.', 'ddn-suite' ),
			'doc_type_invalid'          => __( 'Elige un tipo de documento de la lista.', 'ddn-suite' ),
			'doc_incomplete'            => __( 'Si indicas el tipo de documento, indica también el número (o deja los dos en blanco).', 'ddn-suite' ),
			'current_password_required' => __( 'Escribe tu contraseña actual para cambiarla.', 'ddn-suite' ),
			'current_password_invalid'  => __( 'La contraseña actual no es correcta.', 'ddn-suite' ),
			'password_too_short'        => __( 'La nueva contraseña debe tener al menos 8 caracteres.', 'ddn-suite' ),
			'password_mismatch'         => __( 'La nueva contraseña y la confirmación no coinciden.', 'ddn-suite' ),
			'invalid_request'           => self::generic(),
		);

		return $map[ $code ] ?? self::generic();
	}

	public static function delete_account( string $code ): string {
		$map = array(
			'password_required' => __( 'Escribe tu contraseña para eliminar la cuenta.', 'ddn-suite' ),
			'password_invalid'  => __( 'La contraseña no es correcta.', 'ddn-suite' ),
			'confirm_required'  => __( 'Confirma que entiendes que esta acción no se puede deshacer.', 'ddn-suite' ),
			'invalid_request'   => self::generic(),
		);

		return $map[ $code ] ?? self::generic();
	}

	private static function generic(): string {
		return __( 'No pudimos procesar la solicitud. Revisa los datos e inténtalo de nuevo.', 'ddn-suite' );
	}

	/** @return array<string,string> */
	private static function map(): array {
		return array(
			'full_name_required' => __( 'Escribe tu nombre completo.', 'ddn-suite' ),
			'phone_required'     => __( 'Escribe tu celular.', 'ddn-suite' ),
			'email_invalid'      => __( 'Escribe un correo válido.', 'ddn-suite' ),
			'email_taken'        => __( 'Ya existe una cuenta con ese correo.', 'ddn-suite' ),
			'password_too_short' => __( 'La contraseña debe tener al menos 8 caracteres.', 'ddn-suite' ),
			'department_invalid' => __( 'Elige un departamento de la lista.', 'ddn-suite' ),
			'doc_type_invalid'   => __( 'Elige un tipo de documento de la lista.', 'ddn-suite' ),
			'doc_incomplete'     => __( 'Si indicas el tipo de documento, indica también el número (o deja los dos en blanco).', 'ddn-suite' ),
			'terms_required'     => __( 'Debes aceptar los Términos y Condiciones.', 'ddn-suite' ),
			'privacy_required'   => __( 'Debes aceptar la Política de tratamiento de datos.', 'ddn-suite' ),
			'invalid_request'    => self::generic(),
			'server_error'       => self::generic(),
		);
	}
}

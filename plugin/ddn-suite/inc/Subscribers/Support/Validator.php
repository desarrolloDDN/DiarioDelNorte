<?php
/**
 * Validación de registro y edición de perfil de suscriptor. Lógica pura
 * (sin llamadas a WordPress): recibe datos ya extraídos de $_POST y
 * listas cerradas válidas, devuelve códigos de error — el llamador los
 * traduce a texto para el usuario. Así se puede probar sin arrancar
 * WordPress.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Validator {

	/**
	 * Registro directo: nombre, celular, correo, contraseña y su
	 * confirmación son obligatorios; ubicación e identificación son
	 * opcionales salvo que tipo y número de documento deben venir juntos
	 * o ninguno; los dos consentimientos de aceptación son obligatorios.
	 *
	 * @param array<string,mixed> $data              Campos ya extraídos de $_POST (sin sanear todavía).
	 * @param string[]            $valid_departments Lista cerrada de departamentos válidos.
	 * @param string[]            $valid_doc_types   Lista cerrada de tipos de documento válidos.
	 * @return string[] Códigos de error; vacío si es válido.
	 */
	public static function registration( array $data, array $valid_departments, array $valid_doc_types ): array {
		$errors = self::location_and_identification( $data, $valid_departments, $valid_doc_types );

		if ( '' === trim( (string) ( $data['full_name'] ?? '' ) ) ) {
			$errors[] = 'full_name_required';
		}
		if ( '' === trim( (string) ( $data['phone'] ?? '' ) ) ) {
			$errors[] = 'phone_required';
		}

		$email = trim( (string) ( $data['email'] ?? '' ) );
		if ( '' === $email || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			$errors[] = 'email_invalid';
		}

		$password         = (string) ( $data['password'] ?? '' );
		$password_confirm = (string) ( $data['password_confirm'] ?? '' );
		if ( strlen( $password ) < 8 ) {
			$errors[] = 'password_too_short';
		} elseif ( $password !== $password_confirm ) {
			$errors[] = 'password_mismatch';
		}

		if ( empty( $data['accept_terms'] ) ) {
			$errors[] = 'terms_required';
		}
		if ( empty( $data['accept_privacy'] ) ) {
			$errors[] = 'privacy_required';
		}

		return $errors;
	}

	/**
	 * Edición de perfil: mismas reglas de nombre/celular/ubicación e
	 * identificación; el correo no se edita aquí y la contraseña se valida
	 * aparte con password_change().
	 *
	 * @param array<string,mixed> $data
	 * @param string[]            $valid_departments
	 * @param string[]            $valid_doc_types
	 * @return string[]
	 */
	public static function profile( array $data, array $valid_departments, array $valid_doc_types ): array {
		$errors = self::location_and_identification( $data, $valid_departments, $valid_doc_types );

		if ( '' === trim( (string) ( $data['full_name'] ?? '' ) ) ) {
			$errors[] = 'full_name_required';
		}
		if ( '' === trim( (string) ( $data['phone'] ?? '' ) ) ) {
			$errors[] = 'phone_required';
		}

		return $errors;
	}

	/**
	 * Cambio de contraseña opcional en conjunto: si los tres campos vienen
	 * en blanco, no se toca la contraseña (`skip`). En cuanto se llena
	 * uno, los tres pasan a ser obligatorios.
	 *
	 * @return array{skip: bool, errors: string[]}
	 */
	public static function password_change( string $current, string $new_password, string $confirm ): array {
		if ( '' === $current && '' === $new_password && '' === $confirm ) {
			return array(
				'skip'   => true,
				'errors' => array(),
			);
		}

		$errors = array();
		if ( '' === $current ) {
			$errors[] = 'current_password_required';
		}
		if ( strlen( $new_password ) < 8 ) {
			$errors[] = 'password_too_short';
		}
		if ( $new_password !== $confirm ) {
			$errors[] = 'password_mismatch';
		}

		return array(
			'skip'   => false,
			'errors' => $errors,
		);
	}

	/**
	 * @param array<string,mixed> $data
	 * @param string[]            $valid_departments
	 * @param string[]            $valid_doc_types
	 * @return string[]
	 */
	private static function location_and_identification( array $data, array $valid_departments, array $valid_doc_types ): array {
		$errors = array();

		$department = trim( (string) ( $data['department'] ?? '' ) );
		if ( '' !== $department && ! in_array( $department, $valid_departments, true ) ) {
			$errors[] = 'department_invalid';
		}

		$doc_type   = trim( (string) ( $data['doc_type'] ?? '' ) );
		$doc_number = trim( (string) ( $data['doc_number'] ?? '' ) );

		if ( '' !== $doc_type && ! in_array( $doc_type, $valid_doc_types, true ) ) {
			$errors[] = 'doc_type_invalid';
		}
		// Uno sin el otro es inválido: tipo y número van juntos o ninguno.
		if ( ( '' === $doc_type ) !== ( '' === $doc_number ) ) {
			$errors[] = 'doc_incomplete';
		}

		return $errors;
	}
}

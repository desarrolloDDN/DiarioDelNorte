<?php
/**
 * Datos extendidos del suscriptor (usermeta): ubicación, identificación
 * —cifrada en reposo—, consentimientos, fechas de aceptación de
 * Términos/Política y origen de la cuenta. `wp_delete_user()` ya borra
 * todo este usermeta solo al borrar la cuenta (WordPress limpia sus
 * filas de `wp_usermeta` como parte del borrado), así que no hace falta
 * un método de purga aparte aquí.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers;

use DiarioDelNorte\Suite\Subscribers\Support\Crypto;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProfileRepository {

	private const PREFIX = 'ddn_sub_';

	public function __construct( private readonly string $secret ) {}

	/** @return array<string,mixed> */
	public function get( int $user_id ): array {
		return array(
			'phone'               => (string) get_user_meta( $user_id, self::PREFIX . 'phone', true ),
			'department'          => (string) get_user_meta( $user_id, self::PREFIX . 'department', true ),
			'city'                => (string) get_user_meta( $user_id, self::PREFIX . 'city', true ),
			'address'             => (string) get_user_meta( $user_id, self::PREFIX . 'address', true ),
			'doc_type'            => (string) get_user_meta( $user_id, self::PREFIX . 'doc_type', true ),
			'doc_number'          => Crypto::decrypt( (string) get_user_meta( $user_id, self::PREFIX . 'doc_number', true ), $this->secret ),
			'consent_email'       => '1' === get_user_meta( $user_id, self::PREFIX . 'consent_email', true ),
			'consent_whatsapp'    => '1' === get_user_meta( $user_id, self::PREFIX . 'consent_whatsapp', true ),
			'terms_accepted_at'   => (string) get_user_meta( $user_id, self::PREFIX . 'terms_accepted_at', true ),
			'privacy_accepted_at' => (string) get_user_meta( $user_id, self::PREFIX . 'privacy_accepted_at', true ),
			'origin'              => self::origin_or_default( $user_id ),
		);
	}

	/**
	 * Guarda nombre/celular/ubicación/identificación/consentimientos.
	 * Asume que los valores ya pasaron por Support\Validator.
	 *
	 * @param array<string,mixed> $data
	 */
	public function save( int $user_id, array $data ): void {
		update_user_meta( $user_id, self::PREFIX . 'phone', sanitize_text_field( (string) ( $data['phone'] ?? '' ) ) );
		update_user_meta( $user_id, self::PREFIX . 'department', sanitize_text_field( (string) ( $data['department'] ?? '' ) ) );
		update_user_meta( $user_id, self::PREFIX . 'city', sanitize_text_field( (string) ( $data['city'] ?? '' ) ) );
		update_user_meta( $user_id, self::PREFIX . 'address', sanitize_text_field( (string) ( $data['address'] ?? '' ) ) );
		update_user_meta( $user_id, self::PREFIX . 'doc_type', sanitize_text_field( (string) ( $data['doc_type'] ?? '' ) ) );

		$doc_number = sanitize_text_field( (string) ( $data['doc_number'] ?? '' ) );
		if ( '' === $doc_number ) {
			delete_user_meta( $user_id, self::PREFIX . 'doc_number' );
		} else {
			update_user_meta( $user_id, self::PREFIX . 'doc_number', Crypto::encrypt( $doc_number, $this->secret ) );
		}

		update_user_meta( $user_id, self::PREFIX . 'consent_email', empty( $data['consent_email'] ) ? '' : '1' );
		update_user_meta( $user_id, self::PREFIX . 'consent_whatsapp', empty( $data['consent_whatsapp'] ) ? '' : '1' );
	}

	/** Se llama una sola vez, al crear la cuenta (registro directo o social). */
	public function record_acceptance( int $user_id, string $origin ): void {
		$now = current_time( 'mysql' );
		update_user_meta( $user_id, self::PREFIX . 'terms_accepted_at', $now );
		update_user_meta( $user_id, self::PREFIX . 'privacy_accepted_at', $now );
		update_user_meta( $user_id, self::PREFIX . 'origin', $origin );
	}

	public function link_oauth_id( int $user_id, string $provider, string $provider_user_id ): void {
		update_user_meta( $user_id, self::PREFIX . 'oauth_' . $provider . '_id', sanitize_text_field( $provider_user_id ) );
	}

	/** Cuentas creadas antes de este módulo no tienen el meta de origen: cuentan como «directo». */
	private static function origin_or_default( int $user_id ): string {
		$origin = (string) get_user_meta( $user_id, self::PREFIX . 'origin', true );

		return '' !== $origin ? $origin : 'direct';
	}
}

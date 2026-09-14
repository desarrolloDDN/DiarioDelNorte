<?php
/**
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\OAuth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ProviderInterface {

	public function id(): string;

	public function label(): string;

	public function authorize_url( string $state, string $redirect_uri ): string;

	/**
	 * Intercambia el código por el perfil (correo, nombre, foto). `null`
	 * si el intercambio falla o no llega correo (no se puede vincular ni
	 * crear cuenta sin uno).
	 *
	 * @return array{email:string,name:string,avatar:string,provider_user_id:string}|null
	 */
	public function fetch_profile( string $code, string $redirect_uri ): ?array;
}

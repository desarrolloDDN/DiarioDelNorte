<?php
/**
 * Aísla formularios que autoenvían a la misma URL y se procesan con dos
 * manejadores distintos enganchados al mismo punto del ciclo de vida
 * (en «Mi cuenta»: actualizar perfil y eliminar cuenta, ambos en
 * `template_redirect`).
 *
 * Bug real que esto evita: si cada manejador corre incondicionalmente y
 * llama a check_admin_referer()/wp_verify_nonce() sin más, el envío del
 * OTRO formulario (que no trae su campo) hace fallar esa verificación y
 * puede cortar la petición (redirigir o morir) antes de que el
 * manejador correcto llegue a ejecutarse. La regla: cada manejador debe
 * llamar primero a `targets()` con SU propio campo marcador y, si no
 * está, devolver la petición intacta sin tocar nada — ni siquiera
 * verificar el nonce.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FormDispatch {

	/**
	 * @param array<string,mixed> $post Normalmente $_POST.
	 */
	public static function targets( array $post, string $marker_field ): bool {
		return isset( $post[ $marker_field ] ) && '' !== $post[ $marker_field ];
	}
}

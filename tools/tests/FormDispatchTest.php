<?php
/**
 * Prueba el bug real que describe AccountController: dos formularios en
 * la misma página («Mi cuenta»: actualizar perfil / eliminar cuenta), el
 * mismo punto del ciclo de vida, cada uno con su propio nonce. Enviar
 * uno no debe disparar la lógica del otro.
 */

declare(strict_types=1);

use DiarioDelNorte\Suite\Subscribers\Support\FormDispatch;

ddn_test(
	'FormDispatch: el envío del formulario de eliminar cuenta no activa el de actualizar perfil',
	static function (): void {
		$post = array(
			'ddn_delete_nonce'   => 'abc123',
			'ddn_delete_password' => 'algo',
			'ddn_delete_confirm' => '1',
		);

		ddn_assert( ! FormDispatch::targets( $post, 'ddn_profile_nonce' ), 'el manejador de perfil no se considera destinatario' );
		ddn_assert( FormDispatch::targets( $post, 'ddn_delete_nonce' ), 'el manejador de eliminar sí se considera destinatario' );
	}
);

ddn_test(
	'FormDispatch: el envío del formulario de actualizar perfil no activa el de eliminar cuenta',
	static function (): void {
		$post = array(
			'ddn_profile_nonce' => 'xyz789',
			'full_name'         => 'Ana',
			'phone'             => '3000000000',
		);

		ddn_assert( FormDispatch::targets( $post, 'ddn_profile_nonce' ), 'el manejador de perfil sí se considera destinatario' );
		ddn_assert( ! FormDispatch::targets( $post, 'ddn_delete_nonce' ), 'el manejador de eliminar no se considera destinatario' );
	}
);

ddn_test(
	'FormDispatch: un campo marcador vacío no cuenta como presente',
	static function (): void {
		ddn_assert( ! FormDispatch::targets( array( 'ddn_delete_nonce' => '' ), 'ddn_delete_nonce' ), 'valor vacío: no es un envío real de ese formulario' );
	}
);

ddn_test(
	'FormDispatch: un POST vacío (ninguno de los dos formularios) no activa ninguno',
	static function (): void {
		ddn_assert( ! FormDispatch::targets( array(), 'ddn_profile_nonce' ), 'sin POST, el de perfil no se activa' );
		ddn_assert( ! FormDispatch::targets( array(), 'ddn_delete_nonce' ), 'sin POST, el de eliminar no se activa' );
	}
);

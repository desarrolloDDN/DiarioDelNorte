<?php
declare(strict_types=1);

use DiarioDelNorte\Suite\Subscribers\Support\Validator;

$ddn_departments = array( 'La Guajira', 'Atlántico' );
$ddn_doc_types   = array( 'CC', 'CE' );

ddn_test(
	'Validator::registration rechaza campos incompletos',
	static function () use ( $ddn_departments, $ddn_doc_types ): void {
		$errors = Validator::registration( array(), $ddn_departments, $ddn_doc_types );

		ddn_assert( in_array( 'full_name_required', $errors, true ), 'exige nombre completo' );
		ddn_assert( in_array( 'phone_required', $errors, true ), 'exige celular' );
		ddn_assert( in_array( 'email_invalid', $errors, true ), 'exige correo válido' );
		ddn_assert( in_array( 'password_too_short', $errors, true ), 'exige contraseña de al menos 8' );
		ddn_assert( in_array( 'terms_required', $errors, true ), 'exige aceptar Términos y Condiciones' );
		ddn_assert( in_array( 'privacy_required', $errors, true ), 'exige aceptar la Política de datos' );
	}
);

ddn_test(
	'Validator::registration rechaza el registro si falta aceptar Términos, aunque todo lo demás esté bien',
	static function () use ( $ddn_departments, $ddn_doc_types ): void {
		$errors = Validator::registration(
			array(
				'full_name'      => 'Ana Pérez',
				'phone'          => '3001234567',
				'email'          => 'ana@example.com',
				'password'       => 'contraseña123',
				'accept_terms'   => '',
				'accept_privacy' => '1',
			),
			$ddn_departments,
			$ddn_doc_types
		);

		ddn_assert( in_array( 'terms_required', $errors, true ), 'falta aceptar T&C: registro rechazado' );
		ddn_assert( ! in_array( 'privacy_required', $errors, true ), 'la política sí estaba aceptada' );
		ddn_assert( ! in_array( 'full_name_required', $errors, true ), 'el resto de campos sí estaba completo' );
	}
);

ddn_test(
	'Validator::registration exige que la contraseña y su confirmación coincidan',
	static function () use ( $ddn_departments, $ddn_doc_types ): void {
		$distintas = Validator::registration(
			array(
				'full_name'       => 'Ana Pérez',
				'phone'           => '3001234567',
				'email'           => 'ana@example.com',
				'password'        => 'contraseña123',
				'password_confirm' => 'otradistinta',
				'accept_terms'    => '1',
				'accept_privacy'  => '1',
			),
			$ddn_departments,
			$ddn_doc_types
		);
		ddn_assert( in_array( 'password_mismatch', $distintas, true ), 'confirmación distinta: rechazado' );

		$iguales = Validator::registration(
			array(
				'full_name'       => 'Ana Pérez',
				'phone'           => '3001234567',
				'email'           => 'ana@example.com',
				'password'        => 'contraseña123',
				'password_confirm' => 'contraseña123',
				'accept_terms'    => '1',
				'accept_privacy'  => '1',
			),
			$ddn_departments,
			$ddn_doc_types
		);
		ddn_assert( ! in_array( 'password_mismatch', $iguales, true ), 'confirmación igual: válido' );
	}
);

ddn_test(
	'Validator: tipo y número de documento van juntos o ninguno',
	static function () use ( $ddn_departments, $ddn_doc_types ): void {
		$solo_tipo = Validator::profile(
			array(
				'full_name' => 'A',
				'phone'     => '1',
				'doc_type'  => 'CC',
				'doc_number' => '',
			),
			$ddn_departments,
			$ddn_doc_types
		);
		ddn_assert( in_array( 'doc_incomplete', $solo_tipo, true ), 'tipo sin número: identificación incompleta' );

		$solo_numero = Validator::profile(
			array(
				'full_name'  => 'A',
				'phone'      => '1',
				'doc_type'   => '',
				'doc_number' => '12345',
			),
			$ddn_departments,
			$ddn_doc_types
		);
		ddn_assert( in_array( 'doc_incomplete', $solo_numero, true ), 'número sin tipo: identificación incompleta' );

		$ambos = Validator::profile(
			array(
				'full_name'  => 'A',
				'phone'      => '1',
				'doc_type'   => 'CC',
				'doc_number' => '12345',
			),
			$ddn_departments,
			$ddn_doc_types
		);
		ddn_assert( ! in_array( 'doc_incomplete', $ambos, true ), 'los dos juntos: válido' );

		$ninguno = Validator::profile(
			array(
				'full_name'  => 'A',
				'phone'      => '1',
				'doc_type'   => '',
				'doc_number' => '',
			),
			$ddn_departments,
			$ddn_doc_types
		);
		ddn_assert( ! in_array( 'doc_incomplete', $ninguno, true ), 'ninguno de los dos: válido (son opcionales)' );
	}
);

ddn_test(
	'Validator: departamento y tipo de documento se validan contra la lista cerrada',
	static function () use ( $ddn_departments, $ddn_doc_types ): void {
		$errors = Validator::profile(
			array(
				'full_name'  => 'A',
				'phone'      => '1',
				'department' => 'Marte',
				'doc_type'   => 'XX',
				'doc_number' => '1',
			),
			$ddn_departments,
			$ddn_doc_types
		);
		ddn_assert( in_array( 'department_invalid', $errors, true ), 'departamento fuera de la lista: rechazado' );
		ddn_assert( in_array( 'doc_type_invalid', $errors, true ), 'tipo de documento fuera de la lista: rechazado' );
	}
);

ddn_test(
	'Validator::password_change: los tres en blanco no tocan la contraseña',
	static function (): void {
		$result = Validator::password_change( '', '', '' );
		ddn_assert( true === $result['skip'], 'los tres en blanco: se salta' );
		ddn_assert( array() === $result['errors'], 'sin errores' );
	}
);

ddn_test(
	'Validator::password_change: al llenar uno, los tres son obligatorios',
	static function (): void {
		$result = Validator::password_change( 'actual123', '', '' );
		ddn_assert( false === $result['skip'], 'ya no se salta la validación' );
		ddn_assert( in_array( 'password_too_short', $result['errors'], true ), 'nueva vacía: muy corta' );
	}
);

ddn_test(
	'Validator::password_change: exige que la nueva y la confirmación coincidan',
	static function (): void {
		$result = Validator::password_change( 'actual123', 'nuevaclave1', 'otradistinta' );
		ddn_assert( in_array( 'password_mismatch', $result['errors'], true ), 'no coinciden: rechazado' );
	}
);

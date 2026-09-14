<?php
declare(strict_types=1);

use DiarioDelNorte\Suite\Subscribers\Support\AdminAccessRule;

ddn_test(
	'AdminAccessRule: un suscriptor visitando una pantalla real de wp-admin se rebota',
	static function (): void {
		ddn_assert(
			AdminAccessRule::should_block( true, false, 'index.php' ),
			'sesión de suscriptor + wp-admin/index.php: se bloquea'
		);
		ddn_assert(
			AdminAccessRule::should_block( true, false, 'profile.php' ),
			'incluso su propio profile.php nativo: se bloquea (su perfil vive en la web)'
		);
	}
);

ddn_test(
	'AdminAccessRule: nunca bloquea admin-post.php ni admin-ajax.php (los usa el propio módulo)',
	static function (): void {
		ddn_assert(
			! AdminAccessRule::should_block( true, false, 'admin-post.php' ),
			'admin-post.php no se bloquea: ahí vive registro/login/eliminar cuenta'
		);
		ddn_assert(
			! AdminAccessRule::should_block( true, false, 'admin-ajax.php' ),
			'admin-ajax.php tampoco'
		);
	}
);

ddn_test(
	'AdminAccessRule: al personal de redacción (con edit_posts) nunca se le bloquea',
	static function (): void {
		ddn_assert(
			! AdminAccessRule::should_block( true, true, 'index.php' ),
			'editor/autor/administrador: acceso normal a wp-admin'
		);
	}
);

ddn_test(
	'AdminAccessRule: sin sesión iniciada, no hay nada que bloquear (wp-login.php ya lo maneja aparte)',
	static function (): void {
		ddn_assert(
			! AdminAccessRule::should_block( false, false, 'index.php' ),
			'visitante anónimo: no aplica'
		);
	}
);

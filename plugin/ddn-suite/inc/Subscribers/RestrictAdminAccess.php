<?php
/**
 * Los perfiles de suscriptor son solo para la web, nunca para
 * wp-admin: no pueden publicar ni borrar nada del sitio. Cualquier
 * intento de entrar a una pantalla de wp-admin (que no sea
 * admin-post.php/admin-ajax.php, que usan el propio módulo) rebota a
 * «Mi cuenta»; la barra de administración tampoco se muestra en la web.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers;

use DiarioDelNorte\Suite\Subscribers\Install\PageInstaller;
use DiarioDelNorte\Suite\Subscribers\Support\AdminAccessRule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RestrictAdminAccess {

	public function register(): void {
		add_action( 'admin_init', array( $this, 'maybe_block' ) );
		add_filter( 'show_admin_bar', array( $this, 'hide_admin_bar' ) );
	}

	public function maybe_block(): void {
		global $pagenow;

		$blocked = AdminAccessRule::should_block(
			is_user_logged_in(),
			current_user_can( 'edit_posts' ),
			(string) $pagenow
		);

		if ( ! $blocked ) {
			return;
		}

		$account_url = PageInstaller::url( PageInstaller::SLUG_ACCOUNT );
		wp_safe_redirect( '' !== $account_url ? $account_url : home_url( '/' ) );
		exit;
	}

	public function hide_admin_bar( bool $show ): bool {
		if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		return $show;
	}
}

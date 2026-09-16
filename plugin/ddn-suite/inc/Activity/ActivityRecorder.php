<?php
/**
 * Engancha los eventos clave de WordPress (inicio/cierre de sesión,
 * publicar/editar/borrar una nota, alta/baja/cambio de rol de un
 * usuario) y los manda a la bitácora. Cubre por igual al personal
 * (administradores, editores, autores) y a los suscriptores, porque
 * todos pasan por los mismos hooks nativos de WordPress.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Activity;

use WP_Post;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ActivityRecorder {

	/** Cuántos días se conserva la bitácora (WP-Cron diario la poda). */
	private const RETENTION_DAYS = 180;

	public function __construct( private readonly ActivityRepository $repo ) {}

	public function register(): void {
		add_action( 'wp_login', array( $this, 'on_login' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'on_logout' ) );
		add_action( 'transition_post_status', array( $this, 'on_post_status' ), 10, 3 );
		add_action( 'before_delete_post', array( $this, 'on_post_deleted' ), 10, 2 );
		add_action( 'user_register', array( $this, 'on_user_created' ) );
		add_action( 'set_user_role', array( $this, 'on_role_changed' ), 10, 3 );
		add_action( 'deleted_user', array( $this, 'on_user_deleted' ), 10, 3 );
		add_action( 'ddn_suite_prune_activity_log', array( $this, 'prune' ) );
	}

	public function on_login( string $user_login, WP_User $user ): void {
		$this->repo->log(
			'login',
			array(
				'user_id'    => $user->ID,
				'user_login' => $user_login,
				'user_role'  => $this->primary_role( $user ),
				'ip'         => $this->client_ip(),
			)
		);
	}

	public function on_logout( int $user_id ): void {
		if ( $user_id <= 0 ) {
			return;
		}
		$user = get_userdata( $user_id );
		$this->repo->log(
			'logout',
			array(
				'user_id'    => $user_id,
				'user_login' => $user instanceof WP_User ? $user->user_login : '',
				'user_role'  => $user instanceof WP_User ? $this->primary_role( $user ) : '',
				'ip'         => $this->client_ip(),
			)
		);
	}

	public function on_post_status( string $new_status, string $old_status, WP_Post $post ): void {
		if ( 'post' !== $post->post_type ) {
			return;
		}

		if ( 'publish' === $new_status && 'publish' !== $old_status ) {
			$event = 'post_published';
		} elseif ( 'publish' === $new_status && 'publish' === $old_status ) {
			$event = 'post_updated';
		} elseif ( 'trash' === $new_status && 'trash' !== $old_status ) {
			$event = 'post_trashed';
		} else {
			return;
		}

		$this->log_for_current_user( $event, 'post', $post->ID, $post->post_title );
	}

	public function on_post_deleted( int $post_id, WP_Post $post ): void {
		if ( 'post' !== $post->post_type ) {
			return;
		}
		$this->log_for_current_user( 'post_deleted', 'post', $post_id, $post->post_title );
	}

	public function on_user_created( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user instanceof WP_User ) {
			return;
		}
		$this->log_for_current_user( 'user_created', 'user', $user_id, $user->user_login );
	}

	/**
	 * @param string[] $old_roles
	 */
	public function on_role_changed( int $user_id, string $role, array $old_roles ): void {
		if ( array() === $old_roles ) {
			return; // Alta de un usuario nuevo: ya quedó como «user_created».
		}

		$user  = get_userdata( $user_id );
		$login = $user instanceof WP_User ? $user->user_login : (string) $user_id;
		$label = sprintf( '%1$s: %2$s → %3$s', $login, implode( ', ', $old_roles ), $role );

		$this->log_for_current_user( 'user_role_changed', 'user', $user_id, $label );
	}

	/**
	 * @param int|string $reassign
	 */
	public function on_user_deleted( int $id, $reassign, WP_User $user ): void {
		$this->log_for_current_user( 'user_deleted', 'user', $id, $user->user_login );
	}

	public function prune(): void {
		$this->repo->prune( self::RETENTION_DAYS );
	}

	private function log_for_current_user( string $event, string $object_type, int $object_id, string $object_label ): void {
		$user = wp_get_current_user();

		$this->repo->log(
			$event,
			array(
				'user_id'      => $user->ID,
				'user_login'   => $user->user_login,
				'user_role'    => $this->primary_role( $user ),
				'object_type'  => $object_type,
				'object_id'    => $object_id,
				'object_label' => $object_label,
				'ip'           => $this->client_ip(),
			)
		);
	}

	private function primary_role( WP_User $user ): string {
		return array() !== $user->roles ? (string) $user->roles[0] : '';
	}

	private function client_ip(): string {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}
}

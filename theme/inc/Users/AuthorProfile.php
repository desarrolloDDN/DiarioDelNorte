<?php
/**
 * Campos propios de cada autor: foto de perfil (selector de medios) y
 * cargo/rol para la firma («Directora», «Columnista», «Redacción
 * Riohacha»…). La foto sustituye a Gravatar en get_avatar().
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Users;

use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AuthorProfile {

	private const META_PHOTO = 'ddn_author_photo_id';
	private const META_ROLE  = 'ddn_author_role';
	private const NONCE      = 'ddn_author_profile';

	public function register(): void {
		add_action( 'show_user_profile', array( $this, 'fields' ) );
		add_action( 'edit_user_profile', array( $this, 'fields' ) );
		add_action( 'personal_options_update', array( $this, 'save' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'get_avatar_data', array( $this, 'filter_avatar' ), 10, 2 );
	}

	/** Cargo/rol de un autor para la firma. */
	public static function role( int $user_id ): string {
		$role = get_user_meta( $user_id, self::META_ROLE, true );

		return is_string( $role ) ? $role : '';
	}

	public function enqueue( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'profile.php', 'user-edit.php' ), true ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script(
			'ddn-author-profile',
			DDN_THEME_URI . 'assets/admin/author-profile.js',
			array( 'jquery' ),
			DDN_THEME_VERSION,
			true
		);
	}

	public function fields( WP_User $user ): void {
		$photo_id  = (int) get_user_meta( $user->ID, self::META_PHOTO, true );
		$photo_url = $photo_id ? (string) wp_get_attachment_image_url( $photo_id, 'thumbnail' ) : '';
		$role      = self::role( $user->ID );
		wp_nonce_field( self::NONCE, self::NONCE );
		?>
		<h2><?php esc_html_e( 'Perfil en Diario del Norte', 'diario-del-norte' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="ddn-author-role"><?php esc_html_e( 'Cargo para la firma', 'diario-del-norte' ); ?></label></th>
				<td>
					<input type="text" class="regular-text" id="ddn-author-role" name="<?php echo esc_attr( self::META_ROLE ); ?>" value="<?php echo esc_attr( $role ); ?>">
					<p class="description"><?php esc_html_e( 'Ej.: Directora, Columnista, Redacción Riohacha. Aparece bajo el nombre en la nota.', 'diario-del-norte' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Foto de perfil', 'diario-del-norte' ); ?></th>
				<td>
					<div class="ddn-author-photo">
						<img src="<?php echo esc_url( $photo_url ); ?>" alt="" style="max-width:96px;height:auto;border-radius:50%;<?php echo $photo_url ? '' : 'display:none'; ?>">
						<input type="hidden" name="<?php echo esc_attr( self::META_PHOTO ); ?>" value="<?php echo esc_attr( (string) $photo_id ); ?>">
						<p>
							<button type="button" class="button ddn-author-photo__choose"><?php esc_html_e( 'Elegir foto', 'diario-del-norte' ); ?></button>
							<button type="button" class="button-link ddn-author-photo__clear"<?php echo $photo_url ? '' : ' style="display:none"'; ?>><?php esc_html_e( 'Quitar', 'diario-del-norte' ); ?></button>
						</p>
						<p class="description"><?php esc_html_e( 'Sustituye a Gravatar en la firma y las tarjetas de opinión.', 'diario-del-norte' ); ?></p>
					</div>
				</td>
			</tr>
		</table>
		<?php
	}

	public function save( int $user_id ): void {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE ] ), self::NONCE ) ) {
			return;
		}

		$photo_id = isset( $_POST[ self::META_PHOTO ] ) ? absint( $_POST[ self::META_PHOTO ] ) : 0;
		$role     = isset( $_POST[ self::META_ROLE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::META_ROLE ] ) ) : '';

		if ( $photo_id > 0 ) {
			update_user_meta( $user_id, self::META_PHOTO, $photo_id );
		} else {
			delete_user_meta( $user_id, self::META_PHOTO );
		}

		if ( '' !== $role ) {
			update_user_meta( $user_id, self::META_ROLE, $role );
		} else {
			delete_user_meta( $user_id, self::META_ROLE );
		}
	}

	/**
	 * @param array<string,mixed> $args
	 * @param mixed               $id_or_email
	 * @return array<string,mixed>
	 */
	public function filter_avatar( array $args, $id_or_email ): array {
		$user_id = $this->resolve_user_id( $id_or_email );
		if ( 0 === $user_id ) {
			return $args;
		}

		$photo_id = (int) get_user_meta( $user_id, self::META_PHOTO, true );
		if ( $photo_id <= 0 ) {
			return $args;
		}

		$size = isset( $args['size'] ) ? (int) $args['size'] : 96;
		$url  = wp_get_attachment_image_url( $photo_id, array( $size, $size ) );
		if ( is_string( $url ) ) {
			$args['url']          = $url;
			$args['found_avatar'] = true;
		}

		return $args;
	}

	private function resolve_user_id( mixed $id_or_email ): int {
		if ( is_numeric( $id_or_email ) ) {
			return (int) $id_or_email;
		}
		if ( $id_or_email instanceof WP_User ) {
			return $id_or_email->ID;
		}
		if ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) ) {
			return (int) $id_or_email->user_id;
		}
		if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );

			return $user ? $user->ID : 0;
		}

		return 0;
	}
}

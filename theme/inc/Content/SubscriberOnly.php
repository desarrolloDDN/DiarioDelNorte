<?php
/**
 * Marca «Exclusivo para suscriptores»: casilla en el editor de la entrada
 * y ayudas para saber si el visitante actual puede leer el cuerpo completo.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Content;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SubscriberOnly {

	private const META = '_ddn_subscribers_only';
	// Distinto del name="" de la casilla (ver render()): si coincidieran,
	// el navegador manda los dos campos bajo la misma clave y el valor de
	// la casilla pisa el del nonce en $_POST — wp_verify_nonce() falla
	// siempre y save() no llega a guardar nada.
	private const NONCE = 'ddn_subscribers_only_nonce';

	public function register(): void {
		add_action( 'add_meta_boxes_post', array( $this, 'add_box' ) );
		add_action( 'save_post_post', array( $this, 'save' ) );
	}

	public static function is_restricted( int $post_id ): bool {
		return '1' === get_post_meta( $post_id, self::META, true );
	}

	/** Si la entrada no está restringida, o el visitante tiene sesión iniciada. */
	public static function reader_can_view( int $post_id ): bool {
		return ! self::is_restricted( $post_id ) || is_user_logged_in();
	}

	public function add_box(): void {
		add_meta_box( 'ddn_subscribers_only', __( 'Acceso', 'diario-del-norte' ), array( $this, 'render' ), 'post', 'side', 'high' );
	}

	public function render( WP_Post $post ): void {
		wp_nonce_field( self::NONCE, self::NONCE );
		$checked = self::is_restricted( $post->ID );
		?>
		<label>
			<input type="checkbox" name="ddn_subscribers_only" value="1" <?php checked( $checked ); ?>>
			<?php esc_html_e( 'Exclusiva para suscriptores', 'diario-del-norte' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Sin sesión iniciada, el lector solo verá la bajada y una invitación a registrarse o iniciar sesión.', 'diario-del-norte' ); ?></p>
		<?php
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE ] ), self::NONCE ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['ddn_subscribers_only'] ) ) {
			update_post_meta( $post_id, self::META, '1' );
		} else {
			delete_post_meta( $post_id, self::META );
		}
	}

	/** Distintivo para tarjetas de listado. Imprimir solo si is_restricted(). */
	public static function badge_markup(): string {
		return '<span class="badge-subscribers">' . esc_html__( 'Suscriptores', 'diario-del-norte' ) . '</span>';
	}
}

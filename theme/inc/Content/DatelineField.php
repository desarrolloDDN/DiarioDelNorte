<?php
/**
 * Campo «Lugar de la nota» para la firma (data line). Si la entrada no lo
 * trae, se usa la ciudad por defecto del Personalizador.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Content;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DatelineField {

	private const META  = '_ddn_place';
	private const NONCE = 'ddn_dateline';

	public function register(): void {
		add_action( 'add_meta_boxes_post', array( $this, 'add_box' ) );
		add_action( 'save_post_post', array( $this, 'save' ) );
	}

	/** Lugar para la firma de una entrada. */
	public static function place( int $post_id ): string {
		$place = get_post_meta( $post_id, self::META, true );
		if ( is_string( $place ) && '' !== $place ) {
			return $place;
		}

		$default = get_theme_mod( 'ddn_dateline_city', 'Riohacha' );

		return is_string( $default ) ? $default : 'Riohacha';
	}

	public function add_box(): void {
		add_meta_box( 'ddn_dateline', __( 'Lugar de la nota', 'diario-del-norte' ), array( $this, 'render' ), 'post', 'side' );
	}

	public function render( WP_Post $post ): void {
		wp_nonce_field( self::NONCE, self::NONCE );
		$value = (string) get_post_meta( $post->ID, self::META, true );
		?>
		<input type="text" class="widefat" name="ddn_place" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( get_theme_mod( 'ddn_dateline_city', 'Riohacha' ) ); ?>">
		<p class="description"><?php esc_html_e( 'Ciudad desde donde se firma. En blanco = la ciudad por defecto.', 'diario-del-norte' ); ?></p>
		<?php
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE ] ), self::NONCE ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$place = isset( $_POST['ddn_place'] ) ? sanitize_text_field( wp_unslash( $_POST['ddn_place'] ) ) : '';
		if ( '' !== $place ) {
			update_post_meta( $post_id, self::META, $place );
		} else {
			delete_post_meta( $post_id, self::META );
		}
	}
}

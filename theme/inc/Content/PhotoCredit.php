<?php
/**
 * Campo «Créditos de la foto» (derechos de la fotografía) de la entrada.
 * Se muestra bajo la imagen principal.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Content;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PhotoCredit {

	private const META  = '_ddn_photo_credit';
	private const NONCE = 'ddn_photo_credit';

	public function register(): void {
		add_action( 'add_meta_boxes_post', array( $this, 'add_box' ) );
		add_action( 'save_post_post', array( $this, 'save' ) );
	}

	/** Crédito tal cual lo escribió la redacción. */
	public static function get( int $post_id ): string {
		$value = get_post_meta( $post_id, self::META, true );

		return is_string( $value ) ? trim( $value ) : '';
	}

	/**
	 * Crédito con el prefijo «Foto:» salvo que ya empiece por «Foto»,
	 * «Crédito», «©» o «Fotografía».
	 */
	public static function formatted( int $post_id ): string {
		$value = self::get( $post_id );
		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '/^\s*(foto|fotograf|cr[eé]dito|©)/iu', $value ) ) {
			return $value;
		}

		return sprintf(
			/* translators: %s: autor o agencia de la fotografía. */
			__( 'Foto: %s', 'diario-del-norte' ),
			$value
		);
	}

	public function add_box(): void {
		add_meta_box( 'ddn_photo_credit', __( 'Créditos de la foto', 'diario-del-norte' ), array( $this, 'render' ), 'post', 'side' );
	}

	public function render( WP_Post $post ): void {
		wp_nonce_field( self::NONCE, self::NONCE );
		$value = (string) get_post_meta( $post->ID, self::META, true );
		?>
		<input type="text" class="widefat" name="ddn_photo_credit" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'Ej.: Cortesía Alcaldía de Uribia', 'diario-del-norte' ); ?>">
		<p class="description"><?php esc_html_e( 'Autor o agencia de la imagen principal. Se muestra bajo la foto.', 'diario-del-norte' ); ?></p>
		<?php
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE ] ), self::NONCE ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$value = isset( $_POST['ddn_photo_credit'] ) ? sanitize_text_field( wp_unslash( $_POST['ddn_photo_credit'] ) ) : '';
		if ( '' !== $value ) {
			update_post_meta( $post_id, self::META, $value );
		} else {
			delete_post_meta( $post_id, self::META );
		}
	}
}

<?php
/**
 * Módulo «Edición impresa»: un tipo de contenido propio para subir, por
 * fecha, la portada (imagen destacada) y el PDF de la edición del día.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\PrintEdition;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EditionPostType {

	public const TYPE      = 'ddn_edition';
	private const META_PDF = '_ddn_edition_pdf';
	private const NONCE    = 'ddn_edition_meta';

	public function register(): void {
		add_action( 'init', array( $this, 'register_type' ) );
		add_action( 'add_meta_boxes_' . self::TYPE, array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . self::TYPE, array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

		add_filter( 'manage_' . self::TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . self::TYPE . '_posts_custom_column', array( $this, 'column' ), 10, 2 );
		add_filter( 'default_hidden_columns', array( $this, 'hide_default_columns' ), 10, 2 );
	}

	public function register_type(): void {
		register_post_type(
			self::TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Ediciones impresas', 'ddn-suite' ),
					'singular_name'      => __( 'Edición impresa', 'ddn-suite' ),
					'add_new'            => __( 'Añadir edición', 'ddn-suite' ),
					'add_new_item'       => __( 'Añadir edición impresa', 'ddn-suite' ),
					'edit_item'          => __( 'Editar edición impresa', 'ddn-suite' ),
					'new_item'           => __( 'Nueva edición', 'ddn-suite' ),
					'view_item'          => __( 'Ver edición', 'ddn-suite' ),
					'search_items'       => __( 'Buscar ediciones', 'ddn-suite' ),
					'not_found'          => __( 'Sin ediciones todavía.', 'ddn-suite' ),
					'menu_name'          => __( 'Edición impresa', 'ddn-suite' ),
					'featured_image'     => __( 'Portada de la edición', 'ddn-suite' ),
					'set_featured_image' => __( 'Subir portada', 'ddn-suite' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => 'ddn-suite-calendario',
				'show_in_nav_menus'   => true,
				'show_in_rest'        => false,
				'menu_icon'           => 'dashicons-media-document',
				'supports'            => array( 'title', 'editor', 'thumbnail' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'has_archive'         => 'edicion-impresa',
				'rewrite'             => array(
					'slug'       => 'edicion-impresa',
					'with_front' => false,
				),
			)
		);
	}

	public function add_meta_box(): void {
		add_meta_box(
			'ddn_edition_pdf',
			__( 'Archivo PDF de la edición', 'ddn-suite' ),
			array( $this, 'render_meta_box' ),
			self::TYPE,
			'side',
			'high'
		);
	}

	public function render_meta_box( WP_Post $post ): void {
		$pdf_id  = (int) get_post_meta( $post->ID, self::META_PDF, true );
		$pdf_url = $pdf_id ? (string) wp_get_attachment_url( $pdf_id ) : '';
		wp_nonce_field( self::NONCE, self::NONCE );
		?>
		<div class="ddn-edition-pdf" data-current="<?php echo esc_attr( (string) $pdf_id ); ?>">
			<input type="hidden" name="ddn_edition_pdf" value="<?php echo esc_attr( (string) $pdf_id ); ?>">
			<p class="ddn-edition-pdf__name">
				<?php echo $pdf_url ? esc_html( wp_basename( $pdf_url ) ) : esc_html__( 'Ningún PDF cargado.', 'ddn-suite' ); ?>
			</p>
			<p>
				<button type="button" class="button ddn-edition-pdf__choose"><?php esc_html_e( 'Elegir / subir PDF', 'ddn-suite' ); ?></button>
				<button type="button" class="button-link ddn-edition-pdf__clear"<?php echo $pdf_url ? '' : ' style="display:none"'; ?>><?php esc_html_e( 'Quitar', 'ddn-suite' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'La fecha de publicación es la fecha de la edición. La portada se sube en «Portada de la edición» (imagen destacada).', 'ddn-suite' ); ?></p>
		</div>
		<?php
	}

	public function save( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE ] ), self::NONCE ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$pdf_id = isset( $_POST['ddn_edition_pdf'] ) ? absint( $_POST['ddn_edition_pdf'] ) : 0;
		if ( $pdf_id > 0 && 'application/pdf' === get_post_mime_type( $pdf_id ) ) {
			update_post_meta( $post_id, self::META_PDF, $pdf_id );
		} else {
			delete_post_meta( $post_id, self::META_PDF );
		}

		if ( '' === trim( (string) $post->post_title ) ) {
			$date = mysql2date( _x( 'j \d\e F \d\e Y', 'edición impresa', 'ddn-suite' ), $post->post_date );
			remove_action( 'save_post_' . self::TYPE, array( $this, 'save' ), 10 );
			wp_update_post(
				array(
					'ID'         => $post_id,
					/* translators: %s: fecha de la edición. */
					'post_title' => sprintf( __( 'Edición del %s', 'ddn-suite' ), $date ),
				)
			);
			add_action( 'save_post_' . self::TYPE, array( $this, 'save' ), 10, 2 );
		}
	}

	public function enqueue( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		if ( self::TYPE !== get_post_type() ) {
			return;
		}
		wp_enqueue_media();
		$js = DDN_SUITE_DIR . 'assets/admin/edition.js';
		wp_enqueue_script( 'ddn-edition', DDN_SUITE_URL . 'assets/admin/edition.js', array( 'jquery' ), file_exists( $js ) ? (string) filemtime( $js ) : DDN_SUITE_VERSION, true );
	}

	/**
	 * @param array<string,string> $columns
	 * @return array<string,string>
	 */
	public function columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['ddn_cover'] = __( 'Portada', 'ddn-suite' );
			}
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['ddn_pdf'] = __( 'PDF', 'ddn-suite' );
			}
		}

		return $new;
	}

	public function column( string $column, int $post_id ): void {
		if ( 'ddn_cover' === $column ) {
			$thumb = get_the_post_thumbnail( $post_id, array( 48, 64 ) );
			echo '' !== $thumb ? $thumb : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		if ( 'ddn_pdf' === $column ) {
			$pdf_id = (int) get_post_meta( $post_id, self::META_PDF, true );
			if ( $pdf_id ) {
				printf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( (string) wp_get_attachment_url( $pdf_id ) ), esc_html__( 'Ver PDF', 'ddn-suite' ) );
			} else {
				echo '&mdash;';
			}
		}
	}

	/**
	 * @param string[] $hidden
	 * @return string[]
	 */
	public function hide_default_columns( array $hidden, \WP_Screen $screen ): array {
		if ( 'edit-' . self::TYPE === $screen->id ) {
			$hidden[] = 'author';
		}

		return $hidden;
	}
}

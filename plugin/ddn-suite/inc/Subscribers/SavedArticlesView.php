<?php
/**
 * Botón «Guardar» / «Guardado»: en la nota (single.php del tema, vía la
 * acción `ddn/article_save_button`) y en la lista de «Guardados» de Mi
 * cuenta. Sin sesión, no imprime nada.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SavedArticlesView {

	public function __construct( private readonly SavedArticlesRepository $saved ) {}

	/** Enganchado a `ddn/article_save_button` (contrato con el tema). */
	public function render_button( int $post_id ): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$is_saved = $this->saved->is_saved( get_current_user_id(), $post_id );
		$this->button_markup( $post_id, $is_saved );
	}

	public function button_markup( int $post_id, bool $is_saved ): void {
		printf(
			'<button type="button" class="ddn-save-btn%1$s" data-ddn-save data-post-id="%2$d" data-rest-url="%3$s" data-nonce="%4$s" data-label-save="%5$s" data-label-saved="%6$s" aria-pressed="%7$s">%8$s<span>%9$s</span></button>',
			$is_saved ? ' is-saved' : '',
			absint( $post_id ),
			esc_url( rest_url( 'ddn-suite/v1/saved/toggle' ) ),
			esc_attr( wp_create_nonce( 'wp_rest' ) ),
			esc_attr__( 'Guardar', 'ddn-suite' ),
			esc_attr__( 'Guardado', 'ddn-suite' ),
			$is_saved ? 'true' : 'false',
			self::icon(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fijo, no viene de entrada de usuario.
			esc_html( $is_saved ? __( 'Guardado', 'ddn-suite' ) : __( 'Guardar', 'ddn-suite' ) )
		);
	}

	private static function icon(): string {
		return '<svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" focusable="false">'
			. '<path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1z" fill="currentColor"/>'
			. '</svg>';
	}
}

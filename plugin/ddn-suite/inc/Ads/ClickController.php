<?php
/**
 * Redirección de clic resuelta en el servidor: /ddn-anuncio/clic/{id}.
 * El destino se busca por ID en la base de datos; nunca se acepta una URL
 * por parámetro, para que no exista un open-redirect.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Ads;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ClickController {

	private const QUERY_VAR = 'ddn_ad_click';

	public function __construct(
		private readonly CampaignRepository $campaigns,
		private readonly StatsRepository $stats,
	) {}

	public static function url( int $campaign_id ): string {
		return home_url( '/ddn-anuncio/clic/' . $campaign_id );
	}

	public function register(): void {
		add_action( 'init', array( $this, 'rewrite' ) );
		add_filter( 'query_vars', array( $this, 'query_var' ) );
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ) );
	}

	public function rewrite(): void {
		add_rewrite_rule( '^ddn-anuncio/clic/([0-9]+)/?$', 'index.php?' . self::QUERY_VAR . '=$matches[1]', 'top' );
	}

	/**
	 * @param string[] $vars
	 * @return string[]
	 */
	public function query_var( array $vars ): array {
		$vars[] = self::QUERY_VAR;

		return $vars;
	}

	public function maybe_redirect(): void {
		$id = (int) get_query_var( self::QUERY_VAR );
		if ( $id <= 0 ) {
			return;
		}

		$campaign = $this->campaigns->find( $id );
		$target   = $campaign && '' !== $campaign->target_url ? $campaign->target_url : home_url( '/' );

		if ( $campaign ) {
			$this->stats->record( $campaign->id, 'click' );
		}

		// El destino lo fija un administrador al guardar la campaña (pasó
		// por esc_url_raw) y se resuelve por ID, no por parámetro de la
		// petición: es una redirección a un sitio externo por diseño.
		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		wp_redirect( $target, 302 );
		exit;
	}
}

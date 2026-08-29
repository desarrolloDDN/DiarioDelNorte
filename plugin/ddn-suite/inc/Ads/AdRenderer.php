<?php
/**
 * Convierte una campaña en HTML. Puro: campaña -> cadena, sin efectos
 * secundarios (el registro de impresión lo hace ZoneController).
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Ads;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdRenderer {

	public function render( Campaign $campaign ): string {
		$inner = match ( $campaign->type ) {
			CampaignType::Image   => $this->image( $campaign ),
			CampaignType::Html    => $campaign->creative, // ya pasó wp_kses_post al guardar.
			CampaignType::Adsense => $campaign->creative,
		};

		if ( '' === trim( $inner ) ) {
			return '';
		}

		return sprintf(
			'<aside class="ddn-ad ddn-ad--%1$s" aria-label="%2$s"><span class="ddn-ad__label">%2$s</span>%3$s</aside>',
			esc_attr( $campaign->zone->value ),
			esc_html__( 'Espacio publicitario', 'ddn-suite' ),
			$inner
		);
	}

	private function image( Campaign $campaign ): string {
		if ( '' === $campaign->creative ) {
			return '';
		}

		$alt = '' !== $campaign->advertiser ? $campaign->advertiser : $campaign->name;
		$img = sprintf(
			'<img src="%s" alt="%s" loading="lazy" decoding="async">',
			esc_url( $campaign->creative ),
			esc_attr( $alt )
		);

		if ( '' === $campaign->target_url ) {
			return $img;
		}

		return sprintf(
			'<a href="%s" rel="sponsored noopener" target="_blank">%s</a>',
			esc_url( ClickController::url( $campaign->id ) ),
			$img
		);
	}
}

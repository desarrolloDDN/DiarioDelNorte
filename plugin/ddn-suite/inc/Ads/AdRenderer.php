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

	public function render( Campaign $campaign, AdZone $zone ): string {
		$inner = match ( $campaign->type ) {
			CampaignType::Adsense   => $this->adsense( $campaign ),
			CampaignType::Gam       => $campaign->creative, // etiqueta GPT propia.
			CampaignType::Html      => $campaign->creative,  // ya pasó wp_kses_post al guardar.
			CampaignType::Image     => $this->image( $campaign ),
			CampaignType::Video     => $this->video( $campaign ),
			CampaignType::Sponsored => $campaign->creative,
		};

		if ( '' === trim( $inner ) ) {
			return '';
		}

		return sprintf(
			'<aside class="ddn-ad ddn-ad--%1$s" aria-label="%2$s"><span class="ddn-ad__label">%2$s</span>%3$s</aside>',
			esc_attr( $zone->value ),
			esc_html__( 'Espacio publicitario', 'ddn-suite' ),
			$inner
		);
	}

	private function adsense( Campaign $campaign ): string {
		if ( '' === $campaign->adsense_client || '' === $campaign->adsense_slot ) {
			return '';
		}

		return sprintf(
			'<ins class="adsbygoogle" style="display:block" data-ad-client="%1$s" data-ad-slot="%2$s" data-ad-format="auto" data-full-width-responsive="true"></ins>'
			. '<script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>',
			esc_attr( $campaign->adsense_client ),
			esc_attr( $campaign->adsense_slot )
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

	private function video( Campaign $campaign ): string {
		if ( '' === $campaign->creative ) {
			return '';
		}

		$video = sprintf(
			'<video src="%s" autoplay muted loop playsinline preload="metadata"></video>',
			esc_url( $campaign->creative )
		);

		if ( '' === $campaign->target_url ) {
			return $video;
		}

		return sprintf(
			'<a href="%s" rel="sponsored noopener" target="_blank">%s</a>',
			esc_url( ClickController::url( $campaign->id ) ),
			$video
		);
	}
}

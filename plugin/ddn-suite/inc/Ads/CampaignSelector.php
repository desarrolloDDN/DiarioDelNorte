<?php
/**
 * Elige qué campaña se muestra en una zona:
 *   1. descarta las que no apuntan a la categoría actual;
 *   2. se queda con el mejor nivel de prioridad (menor número);
 *   3. entre las que empatan, sorteo ponderado por «peso».
 *
 * Puro y sin estado: recibe la lista de campañas vigentes y devuelve una.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Ads;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignSelector {

	/**
	 * @param Campaign[]    $running       Campañas activas y vigentes de la zona.
	 * @param list<string>  $context_slugs Slugs de categoría de la vista actual.
	 */
	public function pick( array $running, array $context_slugs ): ?Campaign {
		$targeted = array_values(
			array_filter( $running, static fn ( Campaign $c ): bool => $c->targets_categories( $context_slugs ) )
		);
		if ( array() === $targeted ) {
			return null;
		}

		$best = min( array_map( static fn ( Campaign $c ): int => $c->priority, $targeted ) );
		$tier = array_values( array_filter( $targeted, static fn ( Campaign $c ): bool => $c->priority === $best ) );

		if ( 1 === count( $tier ) ) {
			return $tier[0];
		}

		$total = array_sum( array_map( static fn ( Campaign $c ): int => $c->weight, $tier ) );
		$roll  = wp_rand( 1, max( 1, $total ) );

		$acc = 0;
		foreach ( $tier as $campaign ) {
			$acc += $campaign->weight;
			if ( $roll <= $acc ) {
				return $campaign;
			}
		}

		return $tier[0];
	}
}

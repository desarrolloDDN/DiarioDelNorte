<?php
/**
 * Campaña publicitaria (objeto de valor inmutable).
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Ads;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Campaign {

	/**
	 * @param list<string> $category_slugs Slugs de categoría en las que se muestra; vacío = todas.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $name,
		public readonly string $advertiser,
		public readonly AdZone $zone,
		public readonly CampaignType $type,
		public readonly bool $active,
		public readonly int $priority,
		public readonly int $weight,
		public readonly array $category_slugs,
		public readonly string $creative,
		public readonly string $target_url,
		public readonly ?string $starts_at,
		public readonly ?string $ends_at,
	) {}

	/**
	 * @param array<string,mixed> $row
	 */
	public static function from_row( array $row ): self {
		return new self(
			id: (int) ( $row['id'] ?? 0 ),
			name: (string) ( $row['name'] ?? '' ),
			advertiser: (string) ( $row['advertiser'] ?? '' ),
			zone: AdZone::tryFrom( (string) ( $row['zone'] ?? '' ) ) ?? AdZone::Home,
			type: CampaignType::tryFrom( (string) ( $row['type'] ?? '' ) ) ?? CampaignType::Image,
			active: (bool) ( $row['active'] ?? false ),
			priority: (int) ( $row['priority'] ?? 10 ),
			weight: max( 1, (int) ( $row['weight'] ?? 1 ) ),
			category_slugs: self::split_slugs( (string) ( $row['category_slugs'] ?? '' ) ),
			creative: (string) ( $row['creative'] ?? '' ),
			target_url: (string) ( $row['target_url'] ?? '' ),
			starts_at: isset( $row['starts_at'] ) && $row['starts_at'] ? (string) $row['starts_at'] : null,
			ends_at: isset( $row['ends_at'] ) && $row['ends_at'] ? (string) $row['ends_at'] : null,
		);
	}

	/** @return list<string> */
	private static function split_slugs( string $csv ): array {
		return array_values( array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', $csv ) ) ) ) );
	}

	/** ¿Se muestra en el contexto de estas categorías? Sin restricción = siempre. */
	public function targets_categories( array $context_slugs ): bool {
		if ( array() === $this->category_slugs ) {
			return true;
		}

		return array() !== array_intersect( $this->category_slugs, $context_slugs );
	}

	public function is_running( int $now ): bool {
		if ( ! $this->active ) {
			return false;
		}
		if ( null !== $this->starts_at && strtotime( $this->starts_at ) > $now ) {
			return false;
		}
		if ( null !== $this->ends_at && strtotime( $this->ends_at ) < $now ) {
			return false;
		}

		return true;
	}
}

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

	public function __construct(
		public readonly int $id,
		public readonly string $name,
		public readonly string $advertiser,
		public readonly AdZone $zone,
		public readonly CampaignType $type,
		public readonly bool $active,
		public readonly int $priority,
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
			creative: (string) ( $row['creative'] ?? '' ),
			target_url: (string) ( $row['target_url'] ?? '' ),
			starts_at: isset( $row['starts_at'] ) && $row['starts_at'] ? (string) $row['starts_at'] : null,
			ends_at: isset( $row['ends_at'] ) && $row['ends_at'] ? (string) $row['ends_at'] : null,
		);
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

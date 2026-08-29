<?php
/**
 * Acceso a la tabla de campañas.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Ads;

use DiarioDelNorte\Suite\Support\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignRepository {

	/** @return Campaign[] */
	public function all(): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM %i ORDER BY active DESC, priority ASC, id DESC', Db::table( Db::CAMPAIGNS ) ),
			ARRAY_A
		);

		return array_map( array( Campaign::class, 'from_row' ), (array) $rows );
	}

	public function find( int $id ): ?Campaign {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', Db::table( Db::CAMPAIGNS ), $id ),
			ARRAY_A
		);

		return $row ? Campaign::from_row( $row ) : null;
	}

	/** @return Campaign[] Campañas activas y vigentes de una zona, por prioridad. */
	public function running_in( AdZone $zone ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE zone = %s AND active = 1 ORDER BY priority ASC, id DESC',
				Db::table( Db::CAMPAIGNS ),
				$zone->value
			),
			ARRAY_A
		);

		$now = time();

		return array_values(
			array_filter(
				array_map( array( Campaign::class, 'from_row' ), (array) $rows ),
				static fn ( Campaign $c ): bool => $c->is_running( $now )
			)
		);
	}

	/**
	 * @param array<string,mixed> $data
	 */
	public function save( array $data, int $id = 0 ): int {
		global $wpdb;
		$table = Db::table( Db::CAMPAIGNS );

		$fields = array(
			'name'           => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'advertiser'     => sanitize_text_field( (string) ( $data['advertiser'] ?? '' ) ),
			'zone'           => sanitize_text_field( (string) ( $data['zone'] ?? '' ) ),
			'type'           => sanitize_text_field( (string) ( $data['type'] ?? 'image' ) ),
			'active'         => ! empty( $data['active'] ) ? 1 : 0,
			'priority'       => (int) ( $data['priority'] ?? 10 ),
			'weight'         => max( 1, (int) ( $data['weight'] ?? 1 ) ),
			'category_slugs' => $this->clean_slugs( (string) ( $data['category_slugs'] ?? '' ) ),
			'creative'       => wp_kses_post( (string) ( $data['creative'] ?? '' ) ),
			'target_url'     => esc_url_raw( (string) ( $data['target_url'] ?? '' ) ),
			'starts_at'      => $this->date_or_null( $data['starts_at'] ?? null ),
			'ends_at'        => $this->date_or_null( $data['ends_at'] ?? null ),
		);

		if ( $id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update( $table, $fields, array( 'id' => $id ) );

			return $id;
		}

		$fields['created_at'] = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( $table, $fields );

		return (int) $wpdb->insert_id;
	}

	public function delete( int $id ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( Db::table( Db::CAMPAIGNS ), array( 'id' => $id ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( Db::table( Db::EVENTS ), array( 'campaign_id' => $id ) );
	}

	private function clean_slugs( string $csv ): string {
		$slugs = array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', $csv ) ) ) );

		return implode( ',', array_unique( $slugs ) );
	}

	private function date_or_null( mixed $value ): ?string {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( '' === $value ) {
			return null;
		}
		$ts = strtotime( $value );

		return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : null;
	}
}

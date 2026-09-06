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
				'SELECT * FROM %i WHERE active = 1 ORDER BY priority ASC, id DESC',
				Db::table( Db::CAMPAIGNS )
			),
			ARRAY_A
		);

		$now = time();

		return array_values(
			array_filter(
				array_map( array( Campaign::class, 'from_row' ), (array) $rows ),
				static fn ( Campaign $c ): bool => $c->in_zone( $zone ) && $c->is_running( $now )
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
			'zones'          => $this->clean_zones( $data['zones'] ?? array() ),
			'type'           => $this->clean_type( (string) ( $data['type'] ?? 'image' ) ),
			'active'         => ! empty( $data['active'] ) ? 1 : 0,
			'priority'       => max( 1, (int) ( $data['priority'] ?? 10 ) ),
			'weight'         => max( 1, (int) ( $data['weight'] ?? 1 ) ),
			'category_slugs' => $this->clean_slugs( (string) ( $data['category_slugs'] ?? '' ) ),
			'creative'       => wp_kses_post( (string) ( $data['creative'] ?? '' ) ),
			'target_url'     => esc_url_raw( (string) ( $data['target_url'] ?? '' ) ),
			'adsense_client' => sanitize_text_field( (string) ( $data['adsense_client'] ?? '' ) ),
			'adsense_slot'   => sanitize_text_field( (string) ( $data['adsense_slot'] ?? '' ) ),
			'starts_at'      => $this->date_or_null( $data['starts_at'] ?? null ),
			'ends_at'        => $this->date_or_null( $data['ends_at'] ?? null ),
		);

		if ( $id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update( $table, $fields, array( 'id' => $id ) );

			return $id;
		}

		$fields['evidence_ids'] = '';
		$fields['created_at']   = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( $table, $fields );

		return (int) $wpdb->insert_id;
	}

	public function set_active( int $id, bool $active ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( Db::table( Db::CAMPAIGNS ), array( 'active' => $active ? 1 : 0 ), array( 'id' => $id ) );
	}

	/**
	 * @param list<int> $attachment_ids
	 */
	public function set_evidence( int $id, array $attachment_ids ): void {
		global $wpdb;
		$clean = implode( ',', array_values( array_unique( array_filter( array_map( 'intval', $attachment_ids ) ) ) ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( Db::table( Db::CAMPAIGNS ), array( 'evidence_ids' => $clean ), array( 'id' => $id ) );
	}

	public function delete( int $id ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( Db::table( Db::CAMPAIGNS ), array( 'id' => $id ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( Db::table( Db::EVENTS ), array( 'campaign_id' => $id ) );
	}

	/**
	 * Lista de slugs de zona del formulario -> cadena «a,b,c» saneada.
	 *
	 * @param mixed $zones
	 */
	private function clean_zones( mixed $zones ): string {
		$zones = is_array( $zones ) ? $zones : array();
		$out   = array();
		foreach ( $zones as $value ) {
			$zone = AdZone::tryFrom( sanitize_text_field( (string) $value ) );
			if ( $zone instanceof AdZone ) {
				$out[ $zone->value ] = $zone->value;
			}
		}

		return implode( ',', array_values( $out ) );
	}

	private function clean_type( string $value ): string {
		return CampaignType::tryFrom( $value ) instanceof CampaignType ? $value : CampaignType::Image->value;
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

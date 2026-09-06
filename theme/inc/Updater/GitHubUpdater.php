<?php
/**
 * Actualización automática del tema desde los *releases* de GitHub (repo
 * público, sin token). Inyecta la versión nueva en el aviso de
 * actualizaciones de temas de WordPress; el paquete es el adjunto
 * `diario-del-norte-*.zip` del *release* más reciente.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Updater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GitHubUpdater {

	private const REPO      = 'desarrolloDDN/DiarioDelNorte';
	private const ASSET_RE  = '/^diario-del-norte-.*\.zip$/';
	private const TRANSIENT = 'ddn_gh_release';
	private const SLUG      = 'diario-del-norte';

	public function __construct( private readonly string $current_version ) {}

	public function register(): void {
		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'inject' ) );
		add_filter( 'themes_api', array( $this, 'info' ), 20, 3 );
		add_action( 'upgrader_process_complete', array( $this, 'flush' ), 10, 2 );
	}

	/**
	 * @param mixed $transient
	 * @return mixed
	 */
	public function inject( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->latest_release();
		if ( null === $release || version_compare( $release['version'], $this->current_version, '<=' ) ) {
			return $transient;
		}

		$transient->response             ??= array();
		$transient->response[ self::SLUG ] = array(
			'theme'       => self::SLUG,
			'new_version' => $release['version'],
			'url'         => 'https://github.com/' . self::REPO,
			'package'     => $release['zip'],
		);

		return $transient;
	}

	/**
	 * @param mixed  $result
	 * @param string $action
	 * @param object $args
	 * @return mixed
	 */
	public function info( $result, $action, $args ) {
		if ( 'theme_information' !== $action || ( $args->slug ?? '' ) !== self::SLUG ) {
			return $result;
		}

		$release = $this->latest_release();
		if ( null === $release ) {
			return $result;
		}

		return (object) array(
			'name'     => 'Diario del Norte',
			'slug'     => self::SLUG,
			'version'  => $release['version'],
			'homepage' => 'https://github.com/' . self::REPO,
			'sections' => array(
				'changelog' => wpautop( esc_html( $release['notes'] ) ),
			),
		);
	}

	/**
	 * @param object              $upgrader
	 * @param array<string,mixed> $data
	 */
	public function flush( $upgrader, $data ): void {
		unset( $upgrader );
		if ( 'plugin' === ( $data['type'] ?? '' ) || 'theme' === ( $data['type'] ?? '' ) ) {
			delete_transient( self::TRANSIENT );
		}
	}

	/**
	 * @return array{version:string,zip:string,notes:string}|null
	 */
	private function latest_release(): ?array {
		$cached = get_transient( self::TRANSIENT );
		if ( is_array( $cached ) ) {
			return $this->asset_for( $cached );
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::REPO . '/releases/latest',
			array(
				'timeout' => 8,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'Diario-del-Norte',
				),
			)
		);

		$body = ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) )
			? json_decode( wp_remote_retrieve_body( $response ), true )
			: null;

		$store = array(
			'version' => is_array( $body ) ? ltrim( (string) ( $body['tag_name'] ?? '' ), 'vV' ) : '',
			'notes'   => is_array( $body ) ? (string) ( $body['body'] ?? '' ) : '',
			'assets'  => array(),
		);
		foreach ( ( is_array( $body ) ? (array) ( $body['assets'] ?? array() ) : array() ) as $a ) {
			$store['assets'][] = array(
				'name' => (string) ( $a['name'] ?? '' ),
				'url'  => (string) ( $a['browser_download_url'] ?? '' ),
			);
		}

		$ok = '' !== $store['version'] && null !== $this->asset_for( $store );
		set_transient( self::TRANSIENT, $store, $ok ? 12 * HOUR_IN_SECONDS : 2 * HOUR_IN_SECONDS );

		return $this->asset_for( $store );
	}

	/**
	 * @param array<string,mixed> $store
	 * @return array{version:string,zip:string,notes:string}|null
	 */
	private function asset_for( array $store ): ?array {
		$version = (string) ( $store['version'] ?? '' );
		if ( '' === $version ) {
			return null;
		}
		foreach ( (array) ( $store['assets'] ?? array() ) as $asset ) {
			$name = (string) ( $asset['name'] ?? '' );
			$url  = (string) ( $asset['url'] ?? '' );
			if ( '' !== $url && preg_match( self::ASSET_RE, $name ) ) {
				return array(
					'version' => $version,
					'zip'     => $url,
					'notes'   => (string) ( $store['notes'] ?? '' ),
				);
			}
		}

		return null;
	}
}

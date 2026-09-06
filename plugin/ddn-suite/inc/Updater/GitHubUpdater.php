<?php
/**
 * Actualización automática desde los *releases* de GitHub (repo público,
 * sin token). Inyecta la versión nueva en el aviso de actualizaciones de
 * plugins de WordPress; el zip es el adjunto `ddn-suite-*.zip` del
 * *release* más reciente.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Updater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GitHubUpdater {

	private const REPO         = 'desarrolloDDN/DiarioDelNorte';
	private const ASSET_RE     = '/^ddn-suite-.*\.zip$/';
	private const TRANSIENT    = 'ddn_gh_release';
	private const SLUG         = 'ddn-suite';
	private const BASENAME     = 'ddn-suite/ddn-suite.php';
	private const CHECK_ACTION = 'ddn_suite_force_update_check';
	private const CHECK_NONCE  = 'ddn_suite_force_update_check';

	public function __construct( private readonly string $current_version ) {}

	public function register(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject' ) );
		add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
		add_action( 'upgrader_process_complete', array( $this, 'flush' ), 10, 2 );

		add_filter( 'plugin_action_links_' . self::BASENAME, array( $this, 'action_link' ) );
		add_action( 'admin_post_' . self::CHECK_ACTION, array( $this, 'handle_force_check' ) );
		add_action( 'admin_notices', array( $this, 'checked_notice' ) );
	}

	/**
	 * Enlace «Comprobar actualizaciones» en la fila del plugin.
	 *
	 * @param array<string,string> $links
	 * @return array<string,string>
	 */
	public function action_link( array $links ): array {
		$url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::CHECK_ACTION ),
			self::CHECK_NONCE
		);

		return array_merge(
			array( 'ddn-check' => '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Comprobar actualizaciones', 'ddn-suite' ) . '</a>' ),
			$links
		);
	}

	/**
	 * Fuerza una comprobación inmediata contra GitHub del tema y el plugin
	 * (vacía las cachés y pide a WordPress que vuelva a mirar).
	 */
	public function handle_force_check(): void {
		if ( ! current_user_can( 'update_plugins' ) || ! check_admin_referer( self::CHECK_NONCE ) ) {
			wp_die( esc_html__( 'Acción no permitida.', 'ddn-suite' ) );
		}

		delete_transient( self::TRANSIENT );
		delete_site_transient( 'update_plugins' );
		delete_site_transient( 'update_themes' );
		wp_update_plugins();
		wp_update_themes();

		wp_safe_redirect( add_query_arg( 'ddn-checked', '1', admin_url( 'plugins.php' ) ) );
		exit;
	}

	public function checked_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['ddn-checked'] ) || ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( null === $screen || 'plugins' !== $screen->id ) {
			return;
		}

		$plugins = get_site_transient( 'update_plugins' );
		$themes  = get_site_transient( 'update_themes' );
		$new     = '';
		if ( is_object( $plugins ) && isset( $plugins->response[ self::BASENAME ]->new_version ) ) {
			$new = (string) $plugins->response[ self::BASENAME ]->new_version;
		} elseif ( is_object( $themes ) && isset( $themes->response['diario-del-norte']['new_version'] ) ) {
			$new = (string) $themes->response['diario-del-norte']['new_version'];
		}

		if ( '' !== $new ) {
			printf(
				'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: %s: número de versión. */
					esc_html__( 'Diario del Norte: hay una actualización disponible (versión %s). Actualiza desde la lista de abajo o en Apariencia.', 'ddn-suite' ),
					esc_html( $new )
				)
			);
			return;
		}

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'Diario del Norte: ya tienes la última versión.', 'ddn-suite' )
		);
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

		$transient->response                 ??= array();
		$transient->response[ self::BASENAME ] = (object) array(
			'slug'        => self::SLUG,
			'plugin'      => self::BASENAME,
			'new_version' => $release['version'],
			'package'     => $release['zip'],
			'url'         => 'https://github.com/' . self::REPO,
			'tested'      => get_bloginfo( 'version' ),
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
		if ( 'plugin_information' !== $action || ( $args->slug ?? '' ) !== self::SLUG ) {
			return $result;
		}

		$release = $this->latest_release();
		if ( null === $release ) {
			return $result;
		}

		return (object) array(
			'name'          => 'DDN Suite',
			'slug'          => self::SLUG,
			'version'       => $release['version'],
			'author'        => 'Sistema Cardenal S.A.S.',
			'homepage'      => 'https://github.com/' . self::REPO,
			'download_link' => $release['zip'],
			'sections'      => array(
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
	 * Último *release* publicado, cacheado. Compartido con el tema por el
	 * mismo nombre de transient (una sola llamada a la API sirve a ambos).
	 *
	 * @return array{version:string,zip:string,notes:string}|null
	 */
	private function latest_release(): ?array {
		$cached = get_transient( self::TRANSIENT );
		if ( is_array( $cached ) ) {
			$asset = $this->asset_for( $cached );

			return null !== $asset ? $asset : null;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::REPO . '/releases/latest',
			array(
				'timeout' => 8,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'DDN-Suite',
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

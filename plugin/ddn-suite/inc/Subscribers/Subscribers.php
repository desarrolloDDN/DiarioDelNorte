<?php
/**
 * Arma y arranca todo el módulo de cuentas de suscriptor: registro,
 * login propio, login social (Google/Facebook), «Mi cuenta» y el panel
 * de administración.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers;

use DiarioDelNorte\Suite\Subscribers\Admin\OAuthSettingsPage;
use DiarioDelNorte\Suite\Subscribers\Admin\SubscribersPage;
use DiarioDelNorte\Suite\Subscribers\Admin\SubscribersRepository;
use DiarioDelNorte\Suite\Subscribers\Install\CapabilityInstaller;
use DiarioDelNorte\Suite\Subscribers\Install\PageInstaller;
use DiarioDelNorte\Suite\Subscribers\OAuth\CredentialsStore;
use DiarioDelNorte\Suite\Subscribers\OAuth\FacebookProvider;
use DiarioDelNorte\Suite\Subscribers\OAuth\GoogleProvider;
use DiarioDelNorte\Suite\Subscribers\OAuth\OAuthController;
use DiarioDelNorte\Suite\Subscribers\Support\RateLimiter;
use DiarioDelNorte\Suite\Subscribers\Support\TransientRateLimitStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Subscribers {

	public function boot(): void {
		add_action( 'init', array( $this, 'ensure' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		// Secreto ya disponible a nivel de sitio (deriva de las claves de
		// wp-config.php): no hace falta inventar uno nuevo que gestionar
		// aparte para cifrar identificación/credenciales OAuth en reposo.
		$secret = wp_salt( 'secure_auth' );

		$profiles    = new ProfileRepository( $secret );
		$credentials = new CredentialsStore( $secret );

		$oauth = new OAuthController(
			array(
				new GoogleProvider( $credentials ),
				new FacebookProvider( $credentials ),
			),
			$profiles,
			$credentials
		);
		$oauth->register();

		$rate_limiter = new RateLimiter( new TransientRateLimitStore() );

		// Los perfiles de suscriptor son solo para la web, nunca para
		// wp-admin: no publican ni borran nada del sitio.
		( new RestrictAdminAccess() )->register();

		$saved_repo   = new SavedArticlesRepository();
		$history_repo = new ReadingHistoryRepository();

		( new ReadingHistoryRecorder( $history_repo ) )->register();
		( new ReadingHistoryController( $history_repo ) )->register();

		$saved_controller = new SavedArticlesController( $saved_repo );
		$saved_controller->register();

		$saved_view = new SavedArticlesView( $saved_repo );
		add_action( 'ddn/article_save_button', array( $saved_view, 'render_button' ) );

		$registration = new RegistrationController( $profiles, $oauth );
		$registration->register();

		$login = new LoginController( $rate_limiter, $oauth );
		$login->register();

		$account = new AccountController( $profiles, $saved_repo, $history_repo, $saved_view );
		$account->register();

		add_action( 'ddn/subscribers_register', array( $registration, 'render' ) );
		add_action( 'ddn/subscribers_login', array( $login, 'render' ) );
		add_action( 'ddn/subscribers_account', array( $account, 'render' ) );

		// Contrato con el tema: URLs de «Mi cuenta» y «Registro» para el
		// enlace de la cabecera (a una o a otra, según haya sesión). Si el
		// plugin no está activo, los filtros no existen y el tema
		// simplemente no imprime el enlace — no hay error.
		add_filter( 'ddn/account_url', static fn (): string => PageInstaller::url( PageInstaller::SLUG_ACCOUNT ) );
		add_filter( 'ddn/register_url', static fn (): string => PageInstaller::url( PageInstaller::SLUG_REGISTER ) );

		if ( is_admin() ) {
			$repo = new SubscribersRepository( $profiles );
			( new SubscribersPage( $repo ) )->register();
			( new OAuthSettingsPage( $credentials ) )->register();
		}
	}

	public function ensure(): void {
		( new CapabilityInstaller() )->ensure();
		( new PageInstaller() )->ensure();
	}

	public function enqueue(): void {
		$auth_pages = array( PageInstaller::SLUG_REGISTER, PageInstaller::SLUG_LOGIN, PageInstaller::SLUG_ACCOUNT );

		if ( is_page( $auth_pages ) ) {
			$this->enqueue_asset( 'ddn-suite-subscribers', 'subscribers.css', 'subscribers.js' );
		}

		// Botón «Guardar»: en la nota, y de nuevo (con las listas) en Mi
		// cuenta.
		if ( is_singular( 'post' ) || is_page( PageInstaller::SLUG_ACCOUNT ) ) {
			$this->enqueue_asset( 'ddn-suite-saved-reading', 'saved-reading.css', 'saved-reading.js' );
		}
	}

	private function enqueue_asset( string $handle, string $css_file, string $js_file ): void {
		$css = DDN_SUITE_DIR . 'assets/subscribers/' . $css_file;
		wp_enqueue_style(
			$handle,
			DDN_SUITE_URL . 'assets/subscribers/' . $css_file,
			array(),
			file_exists( $css ) ? (string) filemtime( $css ) : DDN_SUITE_VERSION
		);

		$js = DDN_SUITE_DIR . 'assets/subscribers/' . $js_file;
		wp_enqueue_script(
			$handle,
			DDN_SUITE_URL . 'assets/subscribers/' . $js_file,
			array(),
			file_exists( $js ) ? (string) filemtime( $js ) : DDN_SUITE_VERSION,
			true
		);
	}
}

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

		$registration = new RegistrationController( $profiles, $oauth );
		$registration->register();

		$login = new LoginController( $rate_limiter, $oauth );
		$login->register();

		$account = new AccountController( $profiles );
		$account->register();

		add_action( 'ddn/subscribers_register', array( $registration, 'render' ) );
		add_action( 'ddn/subscribers_login', array( $login, 'render' ) );
		add_action( 'ddn/subscribers_account', array( $account, 'render' ) );

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
		if ( ! is_page( array( PageInstaller::SLUG_REGISTER, PageInstaller::SLUG_LOGIN, PageInstaller::SLUG_ACCOUNT ) ) ) {
			return;
		}

		$css = DDN_SUITE_DIR . 'assets/subscribers/subscribers.css';
		wp_enqueue_style(
			'ddn-suite-subscribers',
			DDN_SUITE_URL . 'assets/subscribers/subscribers.css',
			array(),
			file_exists( $css ) ? (string) filemtime( $css ) : DDN_SUITE_VERSION
		);

		$js = DDN_SUITE_DIR . 'assets/subscribers/subscribers.js';
		wp_enqueue_script(
			'ddn-suite-subscribers',
			DDN_SUITE_URL . 'assets/subscribers/subscribers.js',
			array(),
			file_exists( $js ) ? (string) filemtime( $js ) : DDN_SUITE_VERSION,
			true
		);
	}
}

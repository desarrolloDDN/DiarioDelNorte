<?php
/**
 * Reproductor de radio flotante en la web pública: cinta abajo a la
 * derecha con las emisoras configuradas. El visitante elige emisora,
 * regula el volumen y puede minimizarlo o cerrarlo; el estado se recuerda
 * en su navegador. «Sonando ahora» y las escuchas van por REST.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Radio;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RadioPlayer {

	public function __construct( private readonly RadioSettings $settings ) {}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'wp_resource_hints', array( $this, 'resource_hints' ), 10, 2 );
		add_action( 'wp_footer', array( $this, 'render' ) );
	}

	/**
	 * @return array{
	 *     enabled:bool,
	 *     start_minimized:bool,
	 *     default_station:int,
	 *     idle_title:string,
	 *     stations:array<int,array{name:string,brand:string,stream:string,logo_id:int,logo_url:string,meta_url:string}>
	 * }|null
	 */
	private function config(): ?array {
		if ( is_embed() ) {
			return null;
		}
		$data = $this->settings->get();

		return ( $data['enabled'] && array() !== $data['stations'] ) ? $data : null;
	}

	public function enqueue(): void {
		$config = $this->config();
		if ( null === $config ) {
			return;
		}

		$css = DDN_SUITE_DIR . 'assets/radio/radio.css';
		$js  = DDN_SUITE_DIR . 'assets/radio/radio.js';

		wp_enqueue_style(
			'ddn-radio',
			DDN_SUITE_URL . 'assets/radio/radio.css',
			array(),
			file_exists( $css ) ? (string) filemtime( $css ) : DDN_SUITE_VERSION
		);
		wp_enqueue_script(
			'ddn-radio',
			DDN_SUITE_URL . 'assets/radio/radio.js',
			array(),
			file_exists( $js ) ? (string) filemtime( $js ) : DDN_SUITE_VERSION,
			true
		);

		$artwork = array();
		foreach ( $config['stations'] as $station ) {
			$artwork[] = $station['logo_url'];
		}

		wp_add_inline_script(
			'ddn-radio',
			'window.ddnRadio=' . wp_json_encode(
				array(
					'rest'           => esc_url_raw( rest_url( 'ddn-suite/v1/radio' ) ),
					'nonce'          => wp_create_nonce( 'wp_rest' ),
					'startMinimized' => $config['start_minimized'],
					'defaultStation' => $config['default_station'],
					'artwork'        => $artwork,
					'i18n'           => array(
						'connecting' => __( 'Conectando&hellip;', 'ddn-suite' ),
						'buffering'  => __( 'Cargando&hellip;', 'ddn-suite' ),
						'live'       => __( 'En vivo', 'ddn-suite' ),
						'idle'       => __( 'Detenida', 'ddn-suite' ),
						'error'      => __( 'Sin señal, reintentando&hellip;', 'ddn-suite' ),
						'resume'     => __( 'Pulsa para reanudar', 'ddn-suite' ),
						'open'       => __( 'Abrir la radio', 'ddn-suite' ),
					),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Precarga la conexión con los hosts de streaming para acortar el
	 * arranque al pulsar play (y el re-buffer al cambiar de página).
	 *
	 * @param array<int,string> $hints
	 * @param string            $relation
	 * @return array<int,string>
	 */
	public function resource_hints( array $hints, string $relation ): array {
		if ( 'preconnect' !== $relation ) {
			return $hints;
		}
		$config = $this->config();
		if ( null === $config ) {
			return $hints;
		}

		foreach ( $config['stations'] as $station ) {
			$host = wp_parse_url( $station['stream'], PHP_URL_SCHEME ) . '://' . wp_parse_url( $station['stream'], PHP_URL_HOST );
			if ( ! in_array( $host, $hints, true ) ) {
				$hints[] = $host;
			}
		}

		return $hints;
	}

	public function render(): void {
		$config = $this->config();
		if ( null === $config ) {
			return;
		}

		$stations = $config['stations'];
		$idle     = __( 'Detenida', 'ddn-suite' );
		$live     = __( 'En vivo', 'ddn-suite' );
		?>
		<div id="ddn-radio" class="ddn-radio is-expanded" hidden>
			<div class="ddn-radio__bar">
				<span class="ddn-radio__brand" aria-hidden="true">&#127911;</span>
				<span class="ddn-radio__title" data-idle="<?php echo esc_attr( $config['idle_title'] ); ?>"><?php echo esc_html( $config['idle_title'] ); ?></span>
				<div class="ddn-radio__actions">
					<button type="button" class="ddn-radio__btn" data-radio-min aria-label="<?php esc_attr_e( 'Minimizar', 'ddn-suite' ); ?>">&#8211;</button>
					<button type="button" class="ddn-radio__btn" data-radio-close aria-label="<?php esc_attr_e( 'Cerrar', 'ddn-suite' ); ?>">&times;</button>
				</div>
			</div>

			<div class="ddn-radio__stations">
				<?php foreach ( $stations as $index => $station ) : ?>
					<div class="ddn-radio__station" data-stream="<?php echo esc_url( $station['stream'] ); ?>" data-station="<?php echo (int) $index; ?>" data-brand="<?php echo esc_attr( $station['brand'] ); ?>">
						<?php if ( '' !== $station['logo_url'] ) : ?>
							<img class="ddn-radio__logo" src="<?php echo esc_url( $station['logo_url'] ); ?>" alt="" width="40" height="40" loading="lazy" decoding="async">
						<?php else : ?>
							<span class="ddn-radio__logo ddn-radio__logo--blank" aria-hidden="true"></span>
						<?php endif; ?>
						<span class="ddn-radio__info">
							<strong><?php echo esc_html( $station['name'] ); ?></strong>
							<small
								class="ddn-radio__state"
								data-radio-state
								aria-live="polite"
								data-label-idle="<?php echo esc_attr( $idle ); ?>"
								data-label-live="<?php echo esc_attr( $live ); ?>"><?php echo esc_html( $idle ); ?></small>
						</span>
						<button
							type="button"
							class="ddn-radio__play"
							data-radio-play
							aria-pressed="false"
							aria-label="
							<?php
							/* translators: %s: nombre de la emisora. */
							echo esc_attr( sprintf( __( 'Reproducir %s', 'ddn-suite' ), $station['name'] ) );
							?>
							">&#9654;</button>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="ddn-radio__controls">
				<button type="button" class="ddn-radio__btn ddn-radio__mute" data-radio-mute aria-label="<?php esc_attr_e( 'Silenciar', 'ddn-suite' ); ?>" aria-pressed="false">&#128266;</button>
				<input
					type="range"
					class="ddn-radio__volume"
					data-radio-volume
					min="0" max="100" value="100" step="1"
					aria-label="<?php esc_attr_e( 'Volumen', 'ddn-suite' ); ?>">
			</div>

			<audio data-radio-audio preload="none"></audio>
		</div>
		<?php
	}
}

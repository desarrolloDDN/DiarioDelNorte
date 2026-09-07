<?php
/**
 * Reproductor de radio flotante en la web pública: cinta abajo a la
 * derecha con las emisoras configuradas. El visitante elige emisora y
 * puede minimizarlo o cerrarlo; el estado se recuerda en su navegador.
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
		add_action( 'wp_footer', array( $this, 'render' ) );
	}

	/**
	 * @return array<int,array{name:string,stream:string,logo_id:int,logo_url:string}>
	 */
	private function active_stations(): array {
		$data = $this->settings->get();

		return $data['enabled'] ? $data['stations'] : array();
	}

	public function enqueue(): void {
		if ( array() === $this->active_stations() ) {
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
	}

	public function render(): void {
		if ( is_embed() ) {
			return;
		}

		$stations = $this->active_stations();
		if ( array() === $stations ) {
			return;
		}

		$idle = __( 'Detenida', 'ddn-suite' );
		$live = __( 'En vivo', 'ddn-suite' );
		?>
		<div id="ddn-radio" class="ddn-radio is-expanded" hidden>
			<div class="ddn-radio__bar">
				<span class="ddn-radio__brand" aria-hidden="true">&#127911;</span>
				<span class="ddn-radio__title"><?php esc_html_e( 'Radio en vivo', 'ddn-suite' ); ?></span>
				<div class="ddn-radio__actions">
					<button type="button" class="ddn-radio__btn" data-radio-min aria-label="<?php esc_attr_e( 'Minimizar', 'ddn-suite' ); ?>">&#8211;</button>
					<button type="button" class="ddn-radio__btn" data-radio-close aria-label="<?php esc_attr_e( 'Cerrar', 'ddn-suite' ); ?>">&times;</button>
				</div>
			</div>

			<div class="ddn-radio__stations">
				<?php foreach ( $stations as $station ) : ?>
					<div class="ddn-radio__station" data-stream="<?php echo esc_url( $station['stream'] ); ?>">
						<?php if ( '' !== $station['logo_url'] ) : ?>
							<img class="ddn-radio__logo" src="<?php echo esc_url( $station['logo_url'] ); ?>" alt="" width="40" height="40" loading="lazy" decoding="async">
						<?php else : ?>
							<span class="ddn-radio__logo ddn-radio__logo--blank" aria-hidden="true"></span>
						<?php endif; ?>
						<span class="ddn-radio__info">
							<strong><?php echo esc_html( $station['name'] ); ?></strong>
							<small data-radio-state data-label-idle="<?php echo esc_attr( $idle ); ?>" data-label-live="<?php echo esc_attr( $live ); ?>"><?php echo esc_html( $idle ); ?></small>
						</span>
						<button
							type="button"
							class="ddn-radio__play"
							data-radio-play
							aria-label="
							<?php
							/* translators: %s: nombre de la emisora. */
							echo esc_attr( sprintf( __( 'Reproducir %s', 'ddn-suite' ), $station['name'] ) );
							?>
							">&#9654;</button>
					</div>
				<?php endforeach; ?>
			</div>

			<audio data-radio-audio preload="none"></audio>
		</div>
		<?php
	}
}

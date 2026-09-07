<?php
/**
 * Página «Radio» en el menú DDN Suite: activar el reproductor, arranque,
 * emisora por defecto, gestión de emisoras y resumen de escuchas.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Radio\Admin;

use DiarioDelNorte\Suite\Radio\RadioSettings;
use DiarioDelNorte\Suite\Radio\RadioStats;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RadioPage {

	public const SLUG = 'ddn-suite-radio';

	private const ACTION = 'ddn_suite_save_radio';
	private const NONCE  = 'ddn_suite_radio';

	public function __construct(
		private readonly RadioSettings $settings,
		private readonly RadioStats $stats,
	) {}

	public function register_hooks(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_save' ) );
	}

	public function enqueue( string $hook_suffix ): void {
		if ( ! str_ends_with( $hook_suffix, self::SLUG ) ) {
			return;
		}
		wp_enqueue_media();
		$js = DDN_SUITE_DIR . 'assets/admin/radio.js';
		wp_enqueue_script(
			'ddn-suite-radio-admin',
			DDN_SUITE_URL . 'assets/admin/radio.js',
			array( 'jquery' ),
			file_exists( $js ) ? (string) filemtime( $js ) : DDN_SUITE_VERSION,
			true
		);
		wp_add_inline_script(
			'ddn-suite-radio-admin',
			'window.ddnRadioAdmin=' . wp_json_encode(
				array(
					'siteIsHttps' => is_ssl(),
					'i18n'        => array(
						'testing'  => __( 'Probando&hellip;', 'ddn-suite' ),
						'ok'       => __( '✓ Suena', 'ddn-suite' ),
						'fail'     => __( '✗ No responde', 'ddn-suite' ),
						'mixed'    => __( 'Esta URL es http:// y la web es https:// — el navegador la bloqueará. Pide al proveedor el enlace https.', 'ddn-suite' ),
						'pickLogo' => __( 'Logo de la emisora', 'ddn-suite' ),
						'useLogo'  => __( 'Usar este logo', 'ddn-suite' ),
					),
				)
			) . ';',
			'before'
		);
	}

	public function handle_save(): void {
		check_admin_referer( self::NONCE );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acción no permitida.', 'ddn-suite' ) );
		}

		$this->settings->save( (array) wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- RadioSettings::save() sanea cada campo.

		wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'admin.php?page=' . self::SLUG ) ) );
		exit;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sin permisos suficientes.', 'ddn-suite' ) );
		}

		$data     = $this->settings->get();
		$stations = $data['stations'];
		if ( array() === $stations ) {
			$stations = array( $this->blank_station() );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$updated = isset( $_GET['updated'] );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Radio en vivo', 'ddn-suite' ); ?></h1>

			<?php if ( $updated ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Guardado.', 'ddn-suite' ); ?></p></div>
			<?php endif; ?>

			<p class="description">
				<?php esc_html_e( 'Un reproductor flotante aparece abajo a la derecha en toda la web. El visitante elige emisora, regula el volumen y puede minimizarlo (queda como una burbuja) o cerrarlo. El estado se recuerda al cambiar de página.', 'ddn-suite' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Reproductor', 'ddn-suite' ); ?></th>
						<td>
							<label><input type="checkbox" name="enabled" value="1" <?php checked( $data['enabled'] ); ?>> <?php esc_html_e( 'Mostrarlo en la web', 'ddn-suite' ); ?></label>
							<br>
							<label><input type="checkbox" name="start_minimized" value="1" <?php checked( $data['start_minimized'] ); ?>> <?php esc_html_e( 'Empezar minimizado (burbuja)', 'ddn-suite' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ddn-radio-default"><?php esc_html_e( 'Emisora por defecto', 'ddn-suite' ); ?></label></th>
						<td>
							<select name="default_station" id="ddn-radio-default">
								<option value="-1"><?php esc_html_e( '— Ninguna —', 'ddn-suite' ); ?></option>
								<?php foreach ( array_values( $data['stations'] ) as $i => $st ) : ?>
									<option value="<?php echo (int) $i; ?>" <?php selected( $data['default_station'], $i ); ?>><?php echo esc_html( $st['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Queda preseleccionada; no suena sola hasta que el visitante pulse play.', 'ddn-suite' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Emisoras', 'ddn-suite' ); ?></h2>
				<table class="widefat striped" id="ddn-radio-rows">
					<thead>
						<tr>
							<th style="width:90px"><?php esc_html_e( 'Logo', 'ddn-suite' ); ?></th>
							<th style="width:22%"><?php esc_html_e( 'Nombre', 'ddn-suite' ); ?></th>
							<th><?php esc_html_e( 'URL del stream (audio)', 'ddn-suite' ); ?></th>
							<th style="width:80px"></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( array_values( $stations ) as $i => $station ) : ?>
							<?php $this->row( (string) $i, $station ); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p><button type="button" class="button" id="ddn-radio-add"><?php esc_html_e( 'Añadir emisora', 'ddn-suite' ); ?></button></p>

				<?php submit_button( __( 'Guardar cambios', 'ddn-suite' ) ); ?>
			</form>

			<?php $this->listeners( $data['stations'] ); ?>

			<script type="text/html" id="ddn-radio-row-tpl">
				<?php $this->row( '__i__', $this->blank_station() ); ?>
			</script>
		</div>
		<?php
	}

	/**
	 * @return array{name:string,stream:string,logo_id:int,logo_url:string,meta_url:string}
	 */
	private function blank_station(): array {
		return array(
			'name'     => '',
			'stream'   => '',
			'logo_id'  => 0,
			'logo_url' => '',
			'meta_url' => '',
		);
	}

	/**
	 * @param array{name:string,stream:string,logo_id:int,logo_url:string,meta_url:string} $station
	 */
	private function row( string $index, array $station ): void {
		$field = static fn ( string $name ): string => 'stations[' . $index . '][' . $name . ']';
		?>
		<tr class="ddn-radio-row">
			<td>
				<img
					class="ddn-radio-row__img"
					src="<?php echo esc_url( $station['logo_url'] ); ?>"
					alt=""
					style="width:40px;height:40px;object-fit:contain;<?php echo '' === $station['logo_url'] ? 'display:none' : ''; ?>">
				<input type="hidden" class="ddn-radio-row__id" name="<?php echo esc_attr( $field( 'logo_id' ) ); ?>" value="<?php echo (int) $station['logo_id']; ?>">
				<button type="button" class="button-link ddn-radio-row__pick"><?php esc_html_e( 'Elegir', 'ddn-suite' ); ?></button>
				<button type="button" class="button-link ddn-radio-row__clear" style="<?php echo 0 === $station['logo_id'] ? 'display:none' : ''; ?>"><?php esc_html_e( 'Quitar', 'ddn-suite' ); ?></button>
			</td>
			<td><input type="text" class="regular-text" name="<?php echo esc_attr( $field( 'name' ) ); ?>" value="<?php echo esc_attr( $station['name'] ); ?>"></td>
			<td>
				<input type="url" class="large-text code ddn-radio-row__stream" name="<?php echo esc_attr( $field( 'stream' ) ); ?>" value="<?php echo esc_attr( $station['stream'] ); ?>" placeholder="https://&hellip;/stream">
				<button type="button" class="button button-small ddn-radio-row__test"><?php esc_html_e( 'Probar', 'ddn-suite' ); ?></button>
				<span class="ddn-radio-row__result" aria-live="polite"></span>
				<p class="ddn-radio-row__warn" style="color:#b32d2e;margin:4px 0 0;<?php echo str_starts_with( $station['stream'], 'http://' ) && is_ssl() ? '' : 'display:none'; ?>"></p>
				<details style="margin-top:4px">
					<summary style="cursor:pointer;color:#646970"><?php esc_html_e( 'Metadatos «sonando ahora» (opcional)', 'ddn-suite' ); ?></summary>
					<input type="url" class="large-text code" style="margin-top:4px" name="<?php echo esc_attr( $field( 'meta_url' ) ); ?>" value="<?php echo esc_attr( $station['meta_url'] ); ?>" placeholder="<?php esc_attr_e( 'Se detecta solo; solo si tu panel usa otra ruta', 'ddn-suite' ); ?>">
				</details>
			</td>
			<td><button type="button" class="button-link delete ddn-radio-row__del"><?php esc_html_e( 'Eliminar', 'ddn-suite' ); ?></button></td>
		</tr>
		<?php
	}

	/**
	 * @param array<int,array{name:string,stream:string,logo_id:int,logo_url:string,meta_url:string}> $stations
	 */
	private function listeners( array $stations ): void {
		if ( array() === $stations ) {
			return;
		}
		$summary = $this->stats->summary( 30 );
		?>
		<h2><?php esc_html_e( 'Escuchas (últimos 30 días)', 'ddn-suite' ); ?></h2>
		<table class="widefat striped" style="max-width:640px">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Emisora', 'ddn-suite' ); ?></th>
					<th><?php esc_html_e( 'Veces que se pulsó play', 'ddn-suite' ); ?></th>
					<th><?php esc_html_e( 'Horas escuchadas (aprox.)', 'ddn-suite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( array_values( $stations ) as $i => $station ) : ?>
					<?php
					$row   = $summary[ $i ] ?? array(
						'starts'  => 0,
						'seconds' => 0,
					);
					$hours = $row['seconds'] > 0 ? round( $row['seconds'] / 3600, 1 ) : 0;
					?>
					<tr>
						<td><?php echo esc_html( $station['name'] ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $row['starts'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $hours, 1 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'Sin datos personales: solo cuántas veces se dio a play y el tiempo acumulado (latido cada 30 s).', 'ddn-suite' ); ?></p>
		<?php
	}
}

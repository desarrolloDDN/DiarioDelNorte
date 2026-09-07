<?php
/**
 * Página «Radio» en el menú DDN Suite: activar el reproductor y gestionar
 * las emisoras (nombre, stream, logo).
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Radio\Admin;

use DiarioDelNorte\Suite\Radio\RadioSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RadioPage {

	public const SLUG = 'ddn-suite-radio';

	private const ACTION = 'ddn_suite_save_radio';
	private const NONCE  = 'ddn_suite_radio';

	public function __construct( private readonly RadioSettings $settings ) {}

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
			$stations = array(
				array(
					'name'     => '',
					'stream'   => '',
					'logo_id'  => 0,
					'logo_url' => '',
				),
			);
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
				<?php esc_html_e( 'Un reproductor flotante aparece abajo a la derecha en toda la web. El visitante elige la emisora y puede minimizarlo (queda como una burbuja) o cerrarlo.', 'ddn-suite' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Reproductor', 'ddn-suite' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="enabled" value="1" <?php checked( $data['enabled'] ); ?>>
								<?php esc_html_e( 'Mostrarlo en la web', 'ddn-suite' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Emisoras', 'ddn-suite' ); ?></h2>
				<table class="widefat striped" id="ddn-radio-rows">
					<thead>
						<tr>
							<th style="width:90px"><?php esc_html_e( 'Logo', 'ddn-suite' ); ?></th>
							<th style="width:26%"><?php esc_html_e( 'Nombre', 'ddn-suite' ); ?></th>
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

			<script type="text/html" id="ddn-radio-row-tpl">
				<?php
				$this->row(
					'__i__',
					array(
						'name'     => '',
						'stream'   => '',
						'logo_id'  => 0,
						'logo_url' => '',
					)
				);
				?>
			</script>
		</div>
		<?php
	}

	/**
	 * @param array{name:string,stream:string,logo_id:int,logo_url:string} $station
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
			<td><input type="url" class="large-text code" name="<?php echo esc_attr( $field( 'stream' ) ); ?>" value="<?php echo esc_attr( $station['stream'] ); ?>" placeholder="https://&hellip;/stream"></td>
			<td><button type="button" class="button-link delete ddn-radio-row__del"><?php esc_html_e( 'Eliminar', 'ddn-suite' ); ?></button></td>
		</tr>
		<?php
	}
}

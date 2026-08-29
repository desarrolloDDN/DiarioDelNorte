<?php
/**
 * Gestor de campañas publicitarias: listar, crear, editar, activar y
 * borrar, con impresiones y clics a la vista.
 *
 * Nota: el slug, el CSS/JS y las rutas evitan las palabras «ad»/«ads» y
 * «campaign» para que los bloqueadores de anuncios del navegador de quien
 * administra no corten las peticiones.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Ads\Admin;

use DiarioDelNorte\Suite\Ads\AdZone;
use DiarioDelNorte\Suite\Ads\CampaignRepository;
use DiarioDelNorte\Suite\Ads\CampaignType;
use DiarioDelNorte\Suite\Ads\StatsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignsPage {

	public const SLUG   = 'ddn-suite-espacios';
	public const ACTION = 'ddn_suite_save_placement';
	private const NONCE = 'ddn_suite_placement';

	public function __construct(
		private readonly CampaignRepository $campaigns,
		private readonly StatsRepository $stats,
	) {}

	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( self::NONCE ) ) {
			wp_die( esc_html__( 'Acción no permitida.', 'ddn-suite' ) );
		}

		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

		if ( isset( $_POST['delete'] ) && $id > 0 ) {
			$this->campaigns->delete( $id );
		} else {
			$this->campaigns->save( wp_unslash( $_POST ), $id ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- el repositorio sanea cada campo.
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&updated=1' ) );
		exit;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sin permisos suficientes.', 'ddn-suite' ) );
		}

		$editing = isset( $_GET['edit'] ) ? $this->campaigns->find( (int) $_GET['edit'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$totals  = $this->stats->totals();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Publicidad — espacios y campañas', 'ddn-suite' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Guardado.', 'ddn-suite' ); ?></p></div>
			<?php endif; ?>

			<h2><?php echo $editing ? esc_html__( 'Editar campaña', 'ddn-suite' ) : esc_html__( 'Nueva campaña', 'ddn-suite' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ddn-form">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<input type="hidden" name="id" value="<?php echo (int) ( $editing->id ?? 0 ); ?>">

				<table class="form-table" role="presentation">
					<tr>
						<th><label for="ddn-name"><?php esc_html_e( 'Nombre', 'ddn-suite' ); ?></label></th>
						<td><input class="regular-text" id="ddn-name" name="name" required value="<?php echo esc_attr( $editing->name ?? '' ); ?>"></td>
					</tr>
					<tr>
						<th><label for="ddn-adv"><?php esc_html_e( 'Anunciante', 'ddn-suite' ); ?></label></th>
						<td><input class="regular-text" id="ddn-adv" name="advertiser" value="<?php echo esc_attr( $editing->advertiser ?? '' ); ?>"></td>
					</tr>
					<tr>
						<th><label for="ddn-zone"><?php esc_html_e( 'Zona', 'ddn-suite' ); ?></label></th>
						<td>
							<select id="ddn-zone" name="zone">
								<?php foreach ( AdZone::options() as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $editing->zone->value ?? '', $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="ddn-type"><?php esc_html_e( 'Formato', 'ddn-suite' ); ?></label></th>
						<td>
							<select id="ddn-type" name="type">
								<?php foreach ( CampaignType::options() as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $editing->type->value ?? '', $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="ddn-creative"><?php esc_html_e( 'Creatividad', 'ddn-suite' ); ?></label></th>
						<td>
							<textarea class="large-text code" id="ddn-creative" name="creative" rows="4"><?php echo esc_textarea( $editing->creative ?? '' ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Imagen: URL del archivo. HTML/red: pega aquí el código.', 'ddn-suite' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="ddn-target"><?php esc_html_e( 'URL de destino', 'ddn-suite' ); ?></label></th>
						<td><input class="regular-text" type="url" id="ddn-target" name="target_url" value="<?php echo esc_attr( $editing->target_url ?? '' ); ?>"></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Vigencia', 'ddn-suite' ); ?></th>
						<td>
							<input type="datetime-local" name="starts_at" value="<?php echo esc_attr( $this->local( $editing->starts_at ?? null ) ); ?>">
							&rarr;
							<input type="datetime-local" name="ends_at" value="<?php echo esc_attr( $this->local( $editing->ends_at ?? null ) ); ?>">
						</td>
					</tr>
					<tr>
						<th><label for="ddn-cats"><?php esc_html_e( 'Categorías', 'ddn-suite' ); ?></label></th>
						<td>
							<input class="regular-text" id="ddn-cats" name="category_slugs" value="<?php echo esc_attr( implode( ', ', $editing->category_slugs ?? array() ) ); ?>">
							<p class="description"><?php esc_html_e( 'Slugs separados por comas (p. ej. la-guajira, judiciales). En blanco = todas.', 'ddn-suite' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Prioridad, peso y estado', 'ddn-suite' ); ?></th>
						<td>
							<label><?php esc_html_e( 'Prioridad', 'ddn-suite' ); ?>
								<input type="number" name="priority" min="1" max="100" value="<?php echo (int) ( $editing->priority ?? 10 ); ?>" style="width:5em">
							</label>
							&nbsp;
							<label><?php esc_html_e( 'Peso', 'ddn-suite' ); ?>
								<input type="number" name="weight" min="1" max="100" value="<?php echo (int) ( $editing->weight ?? 1 ); ?>" style="width:5em">
							</label>
							&nbsp;
							<label><input type="checkbox" name="active" value="1" <?php checked( $editing->active ?? true ); ?>> <?php esc_html_e( 'Activa', 'ddn-suite' ); ?></label>
							<p class="description"><?php esc_html_e( 'Compite primero la prioridad más baja; entre las que empatan, sorteo ponderado por peso.', 'ddn-suite' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( $editing ? __( 'Guardar cambios', 'ddn-suite' ) : __( 'Crear campaña', 'ddn-suite' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Campañas', 'ddn-suite' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Nombre', 'ddn-suite' ); ?></th>
						<th><?php esc_html_e( 'Zona', 'ddn-suite' ); ?></th>
						<th><?php esc_html_e( 'Estado', 'ddn-suite' ); ?></th>
						<th><?php esc_html_e( 'Impresiones', 'ddn-suite' ); ?></th>
						<th><?php esc_html_e( 'Clics', 'ddn-suite' ); ?></th>
						<th><?php esc_html_e( 'CTR', 'ddn-suite' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $this->campaigns->all() as $campaign ) : ?>
						<?php
						$imp = $totals[ $campaign->id ]['impression'] ?? 0;
						$clk = $totals[ $campaign->id ]['click'] ?? 0;
						$ctr = $imp > 0 ? number_format( $clk / $imp * 100, 2 ) . ' %' : '—';
						?>
						<tr>
							<td><strong><?php echo esc_html( $campaign->name ); ?></strong><br><span class="description"><?php echo esc_html( $campaign->advertiser ); ?></span></td>
							<td><?php echo esc_html( $campaign->zone->label() ); ?></td>
							<td><?php echo $campaign->active ? esc_html__( 'Activa', 'ddn-suite' ) : esc_html__( 'Pausada', 'ddn-suite' ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $imp ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $clk ) ); ?></td>
							<td><?php echo esc_html( $ctr ); ?></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&edit=' . $campaign->id ) ); ?>"><?php esc_html_e( 'Editar', 'ddn-suite' ); ?></a>
								|
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('<?php echo esc_js( __( '¿Borrar esta campaña?', 'ddn-suite' ) ); ?>')">
									<?php wp_nonce_field( self::NONCE ); ?>
									<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
									<input type="hidden" name="id" value="<?php echo (int) $campaign->id; ?>">
									<button type="submit" name="delete" value="1" class="button-link delete"><?php esc_html_e( 'Borrar', 'ddn-suite' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private function local( ?string $mysql_utc ): string {
		if ( null === $mysql_utc ) {
			return '';
		}
		$ts = strtotime( $mysql_utc . ' UTC' );

		return $ts ? wp_date( 'Y-m-d\TH:i', $ts ) : '';
	}
}

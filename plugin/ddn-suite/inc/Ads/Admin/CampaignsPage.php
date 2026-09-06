<?php
/**
 * Gestor de campañas publicitarias: listado en tabla, alta/edición en
 * ficha con vista previa, pestaña «Historial», subida de evidencia e
 * informe imprimible para el anunciante.
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
use DiarioDelNorte\Suite\Ads\Campaign;
use DiarioDelNorte\Suite\Ads\CampaignRepository;
use DiarioDelNorte\Suite\Ads\CampaignType;
use DiarioDelNorte\Suite\Ads\StatsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignsPage {

	public const SLUG            = 'ddn-suite-espacios';
	public const ACTION          = 'ddn_suite_save_placement';
	public const ACTION_TOGGLE   = 'ddn_suite_toggle_placement';
	public const ACTION_EVIDENCE = 'ddn_suite_evidence_placement';
	private const NONCE          = 'ddn_suite_placement';

	/** Pie del membrete de Sistema Cardenal S.A.S. para el informe. */
	private const MEMBRETE_FOOT = 'Tel. 313 503 8948  ·  Calle 110 Nº 100 # 75 A-620, Bg 4, Parque Ind. Río Norte  ·  Barranquilla, Atlántico';

	public function __construct(
		private readonly CampaignRepository $campaigns,
		private readonly StatsRepository $stats,
	) {}

	public function register_hooks(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_save' ) );
		add_action( 'admin_post_' . self::ACTION_TOGGLE, array( $this, 'handle_toggle' ) );
		add_action( 'admin_post_' . self::ACTION_EVIDENCE, array( $this, 'handle_evidence' ) );
	}

	public function enqueue( string $hook ): void {
		if ( false === strpos( $hook, self::SLUG ) ) {
			return;
		}
		wp_enqueue_media();
	}

	// -- Guardado -----------------------------------------------------------

	public function handle_save(): void {
		check_admin_referer( self::NONCE );
		$this->guard();

		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

		if ( isset( $_POST['delete'] ) && $id > 0 ) {
			$this->campaigns->delete( $id );
		} else {
			$this->campaigns->save( wp_unslash( $_POST ), $id ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- el repositorio sanea cada campo.
		}

		$this->redirect();
	}

	public function handle_toggle(): void {
		check_admin_referer( self::NONCE );
		$this->guard();

		$id     = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$active = ! empty( $_POST['active'] );
		if ( $id > 0 ) {
			$this->campaigns->set_active( $id, $active );
		}

		$this->redirect();
	}

	public function handle_evidence(): void {
		check_admin_referer( self::NONCE );
		$this->guard();

		$id  = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$raw = isset( $_POST['evidence_ids'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['evidence_ids'] ) ) : '';
		$ids = array_map( 'intval', array_filter( explode( ',', $raw ) ) );

		if ( $id > 0 ) {
			$this->campaigns->set_evidence( $id, $ids );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&evidence=' . $id . '&updated=1' ) );
		exit;
	}

	private function guard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acción no permitida.', 'ddn-suite' ) );
		}
	}

	private function redirect(): void {
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&updated=1' ) );
		exit;
	}

	// -- Vistas -----------------------------------------------------------

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sin permisos suficientes.', 'ddn-suite' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$report   = isset( $_GET['report'] ) ? (int) $_GET['report'] : 0;
		$evidence = isset( $_GET['evidence'] ) ? (int) $_GET['evidence'] : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $report > 0 ) {
			$this->render_report( $report );
			return;
		}
		if ( $evidence > 0 ) {
			$this->render_evidence( $evidence );
			return;
		}

		$this->render_main();
	}

	private function render_main(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$tab     = ( isset( $_GET['tab'] ) && 'historial' === $_GET['tab'] ) ? 'historial' : 'campanas';
		$editing = isset( $_GET['edit'] ) ? $this->campaigns->find( (int) $_GET['edit'] ) : null;
		$updated = isset( $_GET['updated'] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$now    = time();
		$totals = $this->stats->totals();
		$all    = $this->campaigns->all();
		$rows   = array_values(
			array_filter(
				$all,
				static fn ( Campaign $c ): bool => 'historial' === $tab
					? ( ! $c->active || $c->has_ended( $now ) )
					: ( $c->active && ! $c->has_ended( $now ) )
			)
		);
		?>
		<div class="wrap ddn-ads">
			<div class="ddn-ads__top">
				<h1><?php esc_html_e( 'Campañas publicitarias', 'ddn-suite' ); ?></h1>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '#ddn-form' ) ); ?>"><?php esc_html_e( 'Nueva campaña', 'ddn-suite' ); ?></a>
			</div>

			<?php if ( $updated ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Guardado.', 'ddn-suite' ); ?></p></div>
			<?php endif; ?>

			<nav class="ddn-tabs">
				<a class="<?php echo 'campanas' === $tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ); ?>"><?php esc_html_e( 'Campañas', 'ddn-suite' ); ?></a>
				<a class="<?php echo 'historial' === $tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&tab=historial' ) ); ?>"><?php esc_html_e( 'Historial', 'ddn-suite' ); ?></a>
			</nav>

			<table class="ddn-table widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Nombre', 'ddn-suite' ); ?></th>
						<th><?php esc_html_e( 'Anunciante', 'ddn-suite' ); ?></th>
						<th><?php esc_html_e( 'Tipo', 'ddn-suite' ); ?></th>
						<th><?php esc_html_e( 'Estado', 'ddn-suite' ); ?></th>
						<th><?php esc_html_e( 'Prioridad', 'ddn-suite' ); ?></th>
						<th><?php esc_html_e( 'Estadísticas', 'ddn-suite' ); ?></th>
						<th><?php esc_html_e( 'Acciones', 'ddn-suite' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( array() === $rows ) : ?>
						<tr><td colspan="7" class="ddn-empty"><?php esc_html_e( 'Nada por aquí todavía.', 'ddn-suite' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $rows as $c ) : ?>
						<?php
						$imp = $totals[ $c->id ]['impression'] ?? 0;
						$clk = $totals[ $c->id ]['click'] ?? 0;
						$ctr = $imp > 0 ? number_format( $clk / $imp * 100, 2 ) . '%' : '—';
						?>
						<tr>
							<td><strong><?php echo esc_html( $c->name ); ?></strong></td>
							<td><?php echo esc_html( $c->advertiser ); ?></td>
							<td><code><?php echo esc_html( $c->type->value ); ?></code></td>
							<td>
								<span class="ddn-pill ddn-pill--<?php echo $c->is_running( $now ) ? 'on' : 'off'; ?>">
									<?php echo esc_html( $this->status_label( $c, $now ) ); ?>
								</span>
							</td>
							<td><?php echo (int) $c->priority; ?></td>
							<td class="ddn-stats">
								<?php
								printf(
									/* translators: 1: impresiones, 2: clics, 3: CTR. */
									esc_html__( '%1$s impr. · %2$s clics · %3$s CTR', 'ddn-suite' ),
									esc_html( number_format_i18n( $imp ) ),
									esc_html( number_format_i18n( $clk ) ),
									esc_html( $ctr )
								);
								?>
							</td>
							<td class="ddn-actions">
								<a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&edit=' . $c->id . '#ddn-form' ) ); ?>"><?php esc_html_e( 'Editar', 'ddn-suite' ); ?></a>
								<?php $this->toggle_button( $c ); ?>
								<a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&evidence=' . $c->id ) ); ?>"><?php esc_html_e( 'Subir evidencia', 'ddn-suite' ); ?></a>
								<a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&report=' . $c->id ) ); ?>"><?php esc_html_e( 'Generar informe', 'ddn-suite' ); ?></a>
								<?php $this->delete_button( $c ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="ddn-editor" id="ddn-form">
				<?php $this->render_form( $editing ); ?>
				<?php $this->render_preview(); ?>
			</div>
		</div>
		<?php
		$this->styles();
		$this->script();
	}

	private function render_form( ?Campaign $editing ): void {
		$zones = $editing ? array_map( static fn ( AdZone $z ): string => $z->value, $editing->zones ) : array();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ddn-card ddn-card--form">
			<?php wp_nonce_field( self::NONCE ); ?>
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
			<input type="hidden" name="id" value="<?php echo (int) ( $editing->id ?? 0 ); ?>">

			<h2><?php echo $editing ? esc_html__( 'Editar campaña', 'ddn-suite' ) : esc_html__( 'Nueva campaña', 'ddn-suite' ); ?></h2>

			<p class="ddn-field">
				<label for="ddn-name"><?php esc_html_e( 'Nombre', 'ddn-suite' ); ?></label>
				<input id="ddn-name" name="name" required value="<?php echo esc_attr( $editing->name ?? '' ); ?>">
			</p>
			<p class="ddn-field">
				<label for="ddn-adv"><?php esc_html_e( 'Anunciante', 'ddn-suite' ); ?></label>
				<input id="ddn-adv" name="advertiser" value="<?php echo esc_attr( $editing->advertiser ?? '' ); ?>">
			</p>
			<p class="ddn-field">
				<label for="ddn-type"><?php esc_html_e( 'Tipo', 'ddn-suite' ); ?></label>
				<select id="ddn-type" name="type">
					<?php foreach ( CampaignType::values() as $value ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $editing->type->value ?? 'image', $value ); ?>><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<div class="ddn-field ddn-field--row">
				<span>
					<label for="ddn-priority"><?php esc_html_e( 'Prioridad', 'ddn-suite' ); ?></label>
					<input type="number" id="ddn-priority" name="priority" min="1" max="100" value="<?php echo (int) ( $editing->priority ?? 10 ); ?>">
				</span>
				<span>
					<label for="ddn-weight"><?php esc_html_e( 'Peso', 'ddn-suite' ); ?></label>
					<input type="number" id="ddn-weight" name="weight" min="1" max="100" value="<?php echo (int) ( $editing->weight ?? 1 ); ?>">
				</span>
				<span class="ddn-field--check">
					<label><input type="checkbox" name="active" value="1" <?php checked( $editing->active ?? true ); ?>> <?php esc_html_e( 'Activa', 'ddn-suite' ); ?></label>
				</span>
			</div>

			<fieldset class="ddn-field ddn-zones">
				<legend><?php esc_html_e( 'Zonas (donde puede aparecer)', 'ddn-suite' ); ?></legend>
				<?php foreach ( AdZone::options() as $value => $label ) : ?>
					<label>
						<input type="checkbox" name="zones[]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $zones, true ) ); ?>>
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</fieldset>

			<p class="ddn-field" data-when="image,video,html,sponsored,gam">
				<label for="ddn-creative"><?php esc_html_e( 'Creatividad', 'ddn-suite' ); ?></label>
				<textarea id="ddn-creative" name="creative" rows="3"><?php echo esc_textarea( $editing->creative ?? '' ); ?></textarea>
				<span class="description"><?php esc_html_e( 'Imagen / vídeo: pega la URL del archivo. HTML / patrocinado / GAM: pega el código.', 'ddn-suite' ); ?></span>
			</p>
			<p class="ddn-field" data-when="image,video,html,sponsored">
				<label for="ddn-target"><?php esc_html_e( 'URL de destino', 'ddn-suite' ); ?></label>
				<input type="url" id="ddn-target" name="target_url" value="<?php echo esc_attr( $editing->target_url ?? '' ); ?>">
			</p>

			<p class="ddn-field">
				<label for="ddn-cats"><?php esc_html_e( 'Categorías (opcional, separadas por coma; vacío = todas)', 'ddn-suite' ); ?></label>
				<input id="ddn-cats" name="category_slugs" value="<?php echo esc_attr( implode( ', ', $editing->category_slugs ?? array() ) ); ?>">
			</p>
			<div class="ddn-field ddn-field--row">
				<span>
					<label for="ddn-start"><?php esc_html_e( 'Empieza (opcional)', 'ddn-suite' ); ?></label>
					<input type="datetime-local" id="ddn-start" name="starts_at" value="<?php echo esc_attr( $this->local( $editing->starts_at ?? null ) ); ?>">
				</span>
				<span>
					<label for="ddn-end"><?php esc_html_e( 'Termina (opcional)', 'ddn-suite' ); ?></label>
					<input type="datetime-local" id="ddn-end" name="ends_at" value="<?php echo esc_attr( $this->local( $editing->ends_at ?? null ) ); ?>">
				</span>
			</div>

			<p class="ddn-field" data-when="adsense">
				<label for="ddn-client"><?php esc_html_e( 'Client ID de AdSense (ca-pub-…)', 'ddn-suite' ); ?></label>
				<input id="ddn-client" name="adsense_client" value="<?php echo esc_attr( $editing->adsense_client ?? '' ); ?>">
			</p>
			<p class="ddn-field" data-when="adsense">
				<label for="ddn-slot"><?php esc_html_e( 'Slot de AdSense', 'ddn-suite' ); ?></label>
				<input id="ddn-slot" name="adsense_slot" value="<?php echo esc_attr( $editing->adsense_slot ?? '' ); ?>">
			</p>

			<p class="ddn-buttons">
				<button type="submit" class="button button-primary"><?php echo $editing ? esc_html__( 'Guardar cambios', 'ddn-suite' ) : esc_html__( 'Crear campaña', 'ddn-suite' ); ?></button>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ); ?>"><?php esc_html_e( 'Cancelar', 'ddn-suite' ); ?></a>
			</p>
		</form>
		<?php
	}

	private function render_preview(): void {
		?>
		<div class="ddn-card ddn-card--preview">
			<h2><?php esc_html_e( 'Vista previa', 'ddn-suite' ); ?></h2>
			<div class="ddn-preview" id="ddn-preview">
				<p class="ddn-preview__hint"><?php esc_html_e( 'Elige un tipo y una creatividad para ver la vista previa.', 'ddn-suite' ); ?></p>
			</div>
		</div>
		<?php
	}

	private function render_evidence( int $id ): void {
		$campaign = $this->campaigns->find( $id );
		if ( ! $campaign instanceof Campaign ) {
			wp_die( esc_html__( 'Campaña no encontrada.', 'ddn-suite' ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$updated = isset( $_GET['updated'] );
		?>
		<div class="wrap ddn-ads">
			<h1>
				<?php
				printf(
					/* translators: %s: nombre de la campaña. */
					esc_html__( 'Evidencia de: %s', 'ddn-suite' ),
					esc_html( $campaign->name )
				);
				?>
			</h1>
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ); ?>">&larr; <?php esc_html_e( 'Volver a las campañas', 'ddn-suite' ); ?></a></p>

			<?php if ( $updated ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Evidencia guardada.', 'ddn-suite' ); ?></p></div>
			<?php endif; ?>

			<p class="description"><?php esc_html_e( 'Sube pantallazos del anuncio ya publicado. Aparecerán en el informe del anunciante.', 'ddn-suite' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ddn-card">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_EVIDENCE ); ?>">
				<input type="hidden" name="id" value="<?php echo (int) $campaign->id; ?>">
				<input type="hidden" name="evidence_ids" id="ddn-evidence-ids" value="<?php echo esc_attr( implode( ',', $campaign->evidence_ids ) ); ?>">

				<div class="ddn-evidence" id="ddn-evidence">
					<?php foreach ( $campaign->evidence_ids as $att_id ) : ?>
						<figure data-id="<?php echo (int) $att_id; ?>">
							<?php echo wp_get_attachment_image( $att_id, 'medium' ); ?>
							<button type="button" class="button-link ddn-evidence__remove"><?php esc_html_e( 'Quitar', 'ddn-suite' ); ?></button>
						</figure>
					<?php endforeach; ?>
				</div>

				<p>
					<button type="button" class="button" id="ddn-evidence-add"><?php esc_html_e( 'Añadir imagen', 'ddn-suite' ); ?></button>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Guardar evidencia', 'ddn-suite' ); ?></button>
				</p>
			</form>
		</div>
		<?php
		$this->styles();
		$this->evidence_script();
	}

	private function render_report( int $id ): void {
		$campaign = $this->campaigns->find( $id );
		if ( ! $campaign instanceof Campaign ) {
			wp_die( esc_html__( 'Campaña no encontrada.', 'ddn-suite' ) );
		}

		$totals = $this->stats->totals()[ $id ] ?? array(
			'impression' => 0,
			'click'      => 0,
		);
		$imp    = (int) $totals['impression'];
		$clk    = (int) $totals['click'];
		$ctr    = $imp > 0 ? number_format( $clk / $imp * 100, 2 ) . '%' : '—';
		$daily  = $this->stats->daily( $id );
		?>
		<div class="wrap ddn-report">
			<p class="ddn-report__toolbar no-print">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ); ?>">&larr; <?php esc_html_e( 'Volver', 'ddn-suite' ); ?></a>
				<button type="button" class="button button-primary" onclick="window.print()"><?php esc_html_e( 'Imprimir / Guardar como PDF', 'ddn-suite' ); ?></button>
			</p>

			<article class="ddn-report__sheet">
				<header class="ddn-report__head">
					<img class="ddn-report__logo" src="<?php echo esc_url( DDN_SUITE_URL . 'assets/img/membrete.png' ); ?>" alt="Sistema Cardenal S.A.S.">
					<h1><?php esc_html_e( 'Informe de campaña publicitaria', 'ddn-suite' ); ?></h1>
				</header>

				<table class="ddn-report__meta">
					<tr><th><?php esc_html_e( 'Campaña', 'ddn-suite' ); ?></th><td><?php echo esc_html( $campaign->name ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Anunciante', 'ddn-suite' ); ?></th><td><?php echo esc_html( $campaign->advertiser ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Periodo', 'ddn-suite' ); ?></th><td><?php echo esc_html( $this->period( $campaign ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Generado', 'ddn-suite' ); ?></th><td><?php echo esc_html( wp_date( 'j \d\e F \d\e Y' ) ); ?></td></tr>
				</table>

				<div class="ddn-report__kpis">
					<div><span><?php echo esc_html( number_format_i18n( $imp ) ); ?></span><?php esc_html_e( 'Impresiones', 'ddn-suite' ); ?></div>
					<div><span><?php echo esc_html( number_format_i18n( $clk ) ); ?></span><?php esc_html_e( 'Clics', 'ddn-suite' ); ?></div>
					<div><span><?php echo esc_html( $ctr ); ?></span><?php esc_html_e( 'CTR', 'ddn-suite' ); ?></div>
				</div>

				<?php if ( array() !== $daily ) : ?>
					<h2><?php esc_html_e( 'Detalle por día', 'ddn-suite' ); ?></h2>
					<table class="ddn-report__daily">
						<thead><tr><th><?php esc_html_e( 'Día', 'ddn-suite' ); ?></th><th><?php esc_html_e( 'Impresiones', 'ddn-suite' ); ?></th><th><?php esc_html_e( 'Clics', 'ddn-suite' ); ?></th></tr></thead>
						<tbody>
							<?php foreach ( $daily as $day => $d ) : ?>
								<tr>
									<td><?php echo esc_html( wp_date( 'j M Y', strtotime( $day ) ) ); ?></td>
									<td><?php echo esc_html( number_format_i18n( $d['impression'] ) ); ?></td>
									<td><?php echo esc_html( number_format_i18n( $d['click'] ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

				<?php if ( array() !== $campaign->evidence_ids ) : ?>
					<h2><?php esc_html_e( 'Evidencia de publicación', 'ddn-suite' ); ?></h2>
					<div class="ddn-report__evidence">
						<?php foreach ( $campaign->evidence_ids as $att_id ) : ?>
							<?php echo wp_get_attachment_image( $att_id, 'large' ); ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<footer class="ddn-report__foot">
					<?php echo esc_html( self::MEMBRETE_FOOT ); ?>
				</footer>
			</article>
		</div>
		<?php
		$this->report_styles();
	}

	// -- Piezas -----------------------------------------------------------

	private function toggle_button( Campaign $c ): void {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ddn-inline">
			<?php wp_nonce_field( self::NONCE ); ?>
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_TOGGLE ); ?>">
			<input type="hidden" name="id" value="<?php echo (int) $c->id; ?>">
			<?php if ( $c->active ) : ?>
				<button type="submit" name="active" value="0" class="button button-small"><?php esc_html_e( 'Desactivar', 'ddn-suite' ); ?></button>
			<?php else : ?>
				<button type="submit" name="active" value="1" class="button button-small"><?php esc_html_e( 'Activar', 'ddn-suite' ); ?></button>
			<?php endif; ?>
		</form>
		<?php
	}

	private function delete_button( Campaign $c ): void {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ddn-inline" onsubmit="return confirm('<?php echo esc_js( __( '¿Borrar esta campaña?', 'ddn-suite' ) ); ?>')">
			<?php wp_nonce_field( self::NONCE ); ?>
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
			<input type="hidden" name="id" value="<?php echo (int) $c->id; ?>">
			<button type="submit" name="delete" value="1" class="button button-small button-link-delete"><?php esc_html_e( 'Borrar', 'ddn-suite' ); ?></button>
		</form>
		<?php
	}

	private function status_label( Campaign $c, int $now ): string {
		if ( $c->has_ended( $now ) ) {
			return __( 'Terminada', 'ddn-suite' );
		}
		if ( ! $c->active ) {
			return __( 'Pausada', 'ddn-suite' );
		}
		if ( null !== $c->starts_at && strtotime( $c->starts_at ) > $now ) {
			return __( 'Programada', 'ddn-suite' );
		}

		return __( 'Activa', 'ddn-suite' );
	}

	private function period( Campaign $c ): string {
		$fmt = static fn ( ?string $d ): string => null === $d ? '' : wp_date( 'j M Y', strtotime( $d ) );
		$a   = $fmt( $c->starts_at );
		$b   = $fmt( $c->ends_at );
		if ( '' === $a && '' === $b ) {
			return __( 'Sin fechas definidas', 'ddn-suite' );
		}

		return trim( ( '' !== $a ? $a : '…' ) . ' – ' . ( '' !== $b ? $b : '…' ) );
	}

	private function local( ?string $mysql_utc ): string {
		if ( null === $mysql_utc ) {
			return '';
		}
		$ts = strtotime( $mysql_utc . ' UTC' );

		return $ts ? wp_date( 'Y-m-d\TH:i', $ts ) : '';
	}

	// -- CSS / JS -----------------------------------------------------------

	private function styles(): void {
		?>
		<style>
		.ddn-ads__top{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.5rem}
		.ddn-tabs{display:flex;gap:1.5rem;border-bottom:1px solid #dcdcde;margin:1rem 0 1.25rem}
		.ddn-tabs a{padding:.5rem .1rem;text-decoration:none;color:#50575e;border-bottom:2px solid transparent;font-weight:600}
		.ddn-tabs a.is-active{color:#1d2327;border-bottom-color:#3858e9}
		.ddn-table{margin-bottom:2rem;background:#fff;border:1px solid #dcdcde}
		.ddn-table th,.ddn-table td{padding:12px 14px;vertical-align:middle}
		.ddn-table thead th{font-size:11px;letter-spacing:.04em;text-transform:uppercase;color:#646970}
		.ddn-stats{color:#50575e;font-size:12px;white-space:nowrap}
		.ddn-empty{text-align:center;color:#646970;padding:2rem}
		.ddn-pill{display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600}
		.ddn-pill--on{background:#e6f4ea;color:#136c39}
		.ddn-pill--off{background:#f0f0f1;color:#646970}
		.ddn-actions{display:flex;flex-wrap:wrap;gap:6px}
		.ddn-inline{display:inline}
		.ddn-editor{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:1.5rem;align-items:start}
		.ddn-card{background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:1.25rem 1.5rem}
		.ddn-card h2{margin-top:0}
		.ddn-field{display:block;margin:0 0 1rem}
		.ddn-field>label,.ddn-zones legend{display:block;font-weight:600;margin-bottom:.35rem}
		.ddn-field input[type=text],.ddn-field input:not([type]),.ddn-field input[type=url],.ddn-field input[type=number],.ddn-field input[type=datetime-local],.ddn-field select,.ddn-field textarea{width:100%;max-width:100%}
		.ddn-field--row{display:flex;gap:1rem;flex-wrap:wrap}
		.ddn-field--row>span{flex:1 1 8rem}
		.ddn-field--check{display:flex;align-items:flex-end}
		.ddn-zones{border:1px solid #dcdcde;border-radius:6px;padding:1rem}
		.ddn-zones label{display:block;font-weight:400;margin:.35rem 0}
		.ddn-field .description{display:block;color:#646970;font-size:12px;margin-top:.25rem}
		.ddn-buttons{display:flex;gap:.75rem;margin-bottom:0}
		.ddn-card--preview{position:sticky;top:40px}
		.ddn-preview{border:1px dashed #c3c4c7;border-radius:6px;padding:1rem;min-height:120px;display:flex;align-items:center;justify-content:center;text-align:center;color:#646970;font-size:13px}
		.ddn-preview img,.ddn-preview video{max-width:100%;height:auto}
		.ddn-evidence{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:1rem;margin:1rem 0}
		.ddn-evidence figure{margin:0;border:1px solid #dcdcde;border-radius:6px;padding:.5rem;text-align:center}
		.ddn-evidence img{max-width:100%;height:auto;display:block}
		@media (max-width:1100px){.ddn-editor{grid-template-columns:1fr}.ddn-card--preview{position:static}}
		</style>
		<?php
	}

	private function report_styles(): void {
		?>
		<style>
		.ddn-report__toolbar{display:flex;align-items:center;gap:1rem;margin:1rem 0}
		.ddn-report__sheet{background:#fff;border:1px solid #dcdcde;max-width:820px;padding:3rem;margin:0 0 3rem;color:#1d2327}
		.ddn-report__head{border-bottom:2px solid #bf0202;padding-bottom:1.25rem;margin-bottom:1.75rem}
		.ddn-report__logo{display:block;width:200px;height:auto;margin-bottom:1rem}
		.ddn-report__sheet h1{margin:0;font-size:24px;font-weight:600}
		.ddn-report__meta{border-collapse:collapse;margin-bottom:1.5rem}
		.ddn-report__meta th{text-align:left;padding:4px 1.5rem 4px 0;color:#646970;font-weight:600;white-space:nowrap}
		.ddn-report__meta td{padding:4px 0}
		.ddn-report__kpis{display:flex;gap:1rem;margin:1.5rem 0}
		.ddn-report__kpis div{flex:1;border:1px solid #dcdcde;border-radius:6px;padding:1rem;text-align:center;font-size:12px;color:#646970;text-transform:uppercase;letter-spacing:.03em}
		.ddn-report__kpis span{display:block;font-size:28px;font-weight:700;color:#1d2327;margin-bottom:.25rem}
		.ddn-report__daily{border-collapse:collapse;width:100%;margin-bottom:1.5rem;font-size:13px}
		.ddn-report__daily th,.ddn-report__daily td{border:1px solid #dcdcde;padding:6px 10px;text-align:left}
		.ddn-report__evidence{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem}
		.ddn-report__evidence img{max-width:100%;height:auto;border:1px solid #dcdcde}
		.ddn-report__foot{margin-top:2.5rem;padding-top:1rem;border-top:1px solid #dcdcde;text-align:center;font-size:11px;letter-spacing:.02em;color:#646970;text-transform:uppercase}
		@media print{
			#adminmenumain,#wpadminbar,#wpfooter,#screen-meta,#screen-meta-links,.ddn-report__toolbar,.update-nag,.notice{display:none!important}
			html.wp-toolbar{padding-top:0!important}
			#wpcontent,#wpbody-content,#wpbody,.wrap{margin:0!important;padding:0!important}
			.ddn-report__sheet{border:0;max-width:none;padding:0 0 3cm}
			.ddn-report__foot{position:fixed;left:0;right:0;bottom:0;background:#fff;border-top:1px solid #bfbfbf;padding:.6rem 0;margin:0}
			@page{margin:1.4cm 1.4cm 2.6cm}
		}
		</style>
		<?php
	}

	private function script(): void {
		?>
		<script>
		( function () {
			var type = document.getElementById( 'ddn-type' );
			var creative = document.getElementById( 'ddn-creative' );
			var target = document.getElementById( 'ddn-target' );
			var preview = document.getElementById( 'ddn-preview' );
			if ( ! type || ! preview ) { return; }

			var NET = { adsense: 1, gam: 1 };

			function toggleFields() {
				document.querySelectorAll( '.ddn-field[data-when]' ).forEach( function ( el ) {
					var list = el.getAttribute( 'data-when' ).split( ',' );
					el.style.display = list.indexOf( type.value ) === -1 ? 'none' : '';
				} );
			}

			function render() {
				var t = type.value;
				var c = creative ? creative.value.trim() : '';
				if ( NET[ t ] ) {
					preview.innerHTML = '<p><?php echo esc_js( __( 'Vista previa no disponible para este tipo (Google la sirve directamente al publicarse).', 'ddn-suite' ) ); ?></p>';
					return;
				}
				if ( ! c ) {
					preview.innerHTML = '<p class="ddn-preview__hint"><?php echo esc_js( __( 'Elige un tipo y una creatividad para ver la vista previa.', 'ddn-suite' ) ); ?></p>';
					return;
				}
				if ( t === 'image' ) {
					var a = ( target && target.value.trim() ) ? '<a href="#" onclick="return false">' : '';
					var az = a ? '</a>' : '';
					preview.innerHTML = a + '<img src="' + encodeURI( c ) + '" alt="">' + az;
				} else if ( t === 'video' ) {
					preview.innerHTML = '<video src="' + encodeURI( c ) + '" muted controls></video>';
				} else {
					preview.innerHTML = c;
				}
			}

			type.addEventListener( 'change', function () { toggleFields(); render(); } );
			if ( creative ) { creative.addEventListener( 'input', render ); }
			if ( target ) { target.addEventListener( 'input', render ); }
			toggleFields();
			render();
		}() );
		</script>
		<?php
	}

	private function evidence_script(): void {
		?>
		<script>
		( function () {
			var addBtn = document.getElementById( 'ddn-evidence-add' );
			var grid = document.getElementById( 'ddn-evidence' );
			var hidden = document.getElementById( 'ddn-evidence-ids' );
			if ( ! addBtn || ! grid || ! hidden || ! window.wp || ! wp.media ) { return; }

			function sync() {
				var ids = [];
				grid.querySelectorAll( 'figure[data-id]' ).forEach( function ( f ) { ids.push( f.getAttribute( 'data-id' ) ); } );
				hidden.value = ids.join( ',' );
			}

			grid.addEventListener( 'click', function ( e ) {
				if ( e.target.classList.contains( 'ddn-evidence__remove' ) ) {
					e.target.closest( 'figure' ).remove();
					sync();
				}
			} );

			var frame = wp.media( { title: '<?php echo esc_js( __( 'Evidencia', 'ddn-suite' ) ); ?>', multiple: true, library: { type: 'image' } } );
			addBtn.addEventListener( 'click', function ( e ) { e.preventDefault(); frame.open(); } );
			frame.on( 'select', function () {
				frame.state().get( 'selection' ).forEach( function ( att ) {
					var a = att.toJSON();
					if ( grid.querySelector( 'figure[data-id="' + a.id + '"]' ) ) { return; }
					var url = ( a.sizes && a.sizes.medium ) ? a.sizes.medium.url : a.url;
					var fig = document.createElement( 'figure' );
					fig.setAttribute( 'data-id', a.id );
					fig.innerHTML = '<img src="' + url + '" alt=""><button type="button" class="button-link ddn-evidence__remove"><?php echo esc_js( __( 'Quitar', 'ddn-suite' ) ); ?></button>';
					grid.appendChild( fig );
				} );
				sync();
			} );
		}() );
		</script>
		<?php
	}
}

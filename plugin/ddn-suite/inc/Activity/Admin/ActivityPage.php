<?php
/**
 * Página «Actividad» del menú DDN Suite: bitácora de eventos clave y
 * reporte de publicaciones por autor.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Activity\Admin;

use DiarioDelNorte\Suite\Activity\AuthorReportRepository;
use DiarioDelNorte\Suite\Activity\Install\CapabilityInstaller;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ActivityPage {

	public const SLUG = 'ddn-suite-actividad';

	public function __construct(
		private readonly ActivityListTable $table,
		private readonly AuthorReportRepository $authors,
	) {}

	public function render(): void {
		if ( ! current_user_can( CapabilityInstaller::CAP ) ) {
			wp_die( esc_html__( 'Sin permisos suficientes.', 'ddn-suite' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo decide qué pestaña mostrar.
		$tab = ( isset( $_GET['tab'] ) && 'autores' === $_GET['tab'] ) ? 'autores' : 'actividad';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Actividad', 'ddn-suite' ); ?></h1>

			<nav class="ddn-tabs">
				<a class="<?php echo 'actividad' === $tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ); ?>"><?php esc_html_e( 'Eventos', 'ddn-suite' ); ?></a>
				<a class="<?php echo 'autores' === $tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&tab=autores' ) ); ?>"><?php esc_html_e( 'Publicaciones por autor', 'ddn-suite' ); ?></a>
			</nav>

			<?php
			if ( 'autores' === $tab ) {
				$this->render_authors();
			} else {
				$this->render_events();
			}
			?>
		</div>
		<?php
		$this->styles();
	}

	private function render_events(): void {
		$this->table->prepare_items();
		?>
		<form method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
			<?php $this->table->display(); ?>
		</form>
		<?php
	}

	private function render_authors(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- filtro de solo lectura de una pantalla ya protegida por capability.
		$from = isset( $_GET['ddn_from'] ) ? sanitize_text_field( wp_unslash( $_GET['ddn_from'] ) ) : gmdate( 'Y-m-d', time() - 30 * DAY_IN_SECONDS );
		$to   = isset( $_GET['ddn_to'] ) ? sanitize_text_field( wp_unslash( $_GET['ddn_to'] ) ) : gmdate( 'Y-m-d' );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$rows = $this->authors->summary( $from, $to );
		?>
		<form method="get" class="ddn-authors-filter">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
			<input type="hidden" name="tab" value="autores">
			<label><?php esc_html_e( 'Desde', 'ddn-suite' ); ?> <input type="date" name="ddn_from" value="<?php echo esc_attr( $from ); ?>"></label>
			<label><?php esc_html_e( 'Hasta', 'ddn-suite' ); ?> <input type="date" name="ddn_to" value="<?php echo esc_attr( $to ); ?>"></label>
			<?php submit_button( __( 'Filtrar', 'ddn-suite' ), '', '', false ); ?>
		</form>

		<table class="widefat striped ddn-authors-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Autor', 'ddn-suite' ); ?></th>
					<th><?php esc_html_e( 'Notas publicadas', 'ddn-suite' ); ?></th>
					<th><?php esc_html_e( 'Primera', 'ddn-suite' ); ?></th>
					<th><?php esc_html_e( 'Última', 'ddn-suite' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( array() === $rows ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'Sin notas publicadas en ese rango.', 'ddn-suite' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $rows as $ddn_row ) : ?>
					<tr>
						<td><?php echo esc_html( $ddn_row['name'] ); ?></td>
						<td><?php echo (int) $ddn_row['total']; ?></td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $ddn_row['first_at'] ) ); ?></td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $ddn_row['last_at'] ) ); ?></td>
						<td><a class="button button-small" href="<?php echo esc_url( admin_url( 'edit.php?post_type=post&author=' . $ddn_row['author_id'] ) ); ?>"><?php esc_html_e( 'Ver notas', 'ddn-suite' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function styles(): void {
		?>
		<style>
		.ddn-tabs{display:flex;gap:1.5rem;border-bottom:1px solid #dcdcde;margin:1rem 0 1.25rem}
		.ddn-tabs a{padding:.5rem .1rem;text-decoration:none;color:#50575e;border-bottom:2px solid transparent;font-weight:600}
		.ddn-tabs a.is-active{color:#1d2327;border-bottom-color:#3858e9}
		.ddn-authors-filter{display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;margin-bottom:1rem}
		.ddn-authors-filter label{display:flex;flex-direction:column;font-size:12px;font-weight:600;color:#1d2327;gap:.25rem}
		.ddn-authors-table{max-width:760px}
		</style>
		<?php
	}
}

<?php
/**
 * Página «Estadísticas» del menú DDN Suite: notas más leídas, autor más
 * leído y lecturas por día, para el rango de fechas elegido.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Analytics\Admin;

use DateTimeImmutable;
use DiarioDelNorte\Suite\Analytics\Install\CapabilityInstaller;
use DiarioDelNorte\Suite\Analytics\ReadershipRepository;
use DiarioDelNorte\Suite\Analytics\Support\DateRange;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ReadershipPage {

	public const SLUG = 'ddn-suite-estadisticas';

	private const TOP_POSTS   = 20;
	private const TOP_AUTHORS = 10;

	public function __construct( private readonly ReadershipRepository $repo ) {}

	public function render(): void {
		if ( ! current_user_can( CapabilityInstaller::CAP ) ) {
			wp_die( esc_html__( 'Sin permisos suficientes.', 'ddn-suite' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- filtro de solo lectura de una pantalla ya protegida por capability.
		$raw_from = isset( $_GET['ddn_from'] ) ? sanitize_text_field( wp_unslash( $_GET['ddn_from'] ) ) : '';
		$raw_to   = isset( $_GET['ddn_to'] ) ? sanitize_text_field( wp_unslash( $_GET['ddn_to'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$today = current_time( 'Y-m-d' );
		$range = DateRange::resolve( $raw_from, $raw_to, $today );
		$from  = $range['from'];
		$to    = $range['to'];

		$totals  = $this->repo->totals( $from, $to );
		$posts   = $this->repo->top_posts( $from, $to, self::TOP_POSTS );
		$authors = $this->repo->top_authors( $from, $to, self::TOP_AUTHORS );
		$daily   = $this->repo->daily( $from, $to );

		$average = $totals['posts'] > 0 ? $totals['views'] / $totals['posts'] : 0;
		$top     = $authors[0] ?? null;
		?>
		<div class="wrap ddn-stats">
			<h1><?php esc_html_e( 'Estadísticas de lectura', 'ddn-suite' ); ?></h1>

			<?php $this->filters( $from, $to, $today ); ?>

			<?php if ( 0 === $totals['views'] ) : ?>
				<div class="notice notice-info inline"><p><?php esc_html_e( 'Todavía no hay lecturas registradas en ese rango.', 'ddn-suite' ); ?></p></div>
			<?php endif; ?>

			<div class="ddn-stats__cards">
				<div class="ddn-stats__card">
					<span><?php echo esc_html( number_format_i18n( $totals['views'] ) ); ?></span>
					<?php esc_html_e( 'Lecturas', 'ddn-suite' ); ?>
				</div>
				<div class="ddn-stats__card">
					<span><?php echo esc_html( number_format_i18n( $totals['posts'] ) ); ?></span>
					<?php esc_html_e( 'Notas leídas', 'ddn-suite' ); ?>
				</div>
				<div class="ddn-stats__card">
					<span><?php echo esc_html( number_format_i18n( $average, 1 ) ); ?></span>
					<?php esc_html_e( 'Lecturas por nota', 'ddn-suite' ); ?>
				</div>
				<div class="ddn-stats__card ddn-stats__card--author">
					<span><?php echo esc_html( null !== $top ? $top['name'] : '—' ); ?></span>
					<?php
					echo esc_html(
						null !== $top
							/* translators: %s: número de lecturas del autor. */
							? sprintf( __( 'Autor más leído · %s lecturas', 'ddn-suite' ), number_format_i18n( $top['views'] ) )
							: __( 'Autor más leído', 'ddn-suite' )
					);
					?>
				</div>
			</div>

			<?php $this->chart( $from, $to, $daily ); ?>

			<div class="ddn-stats__tables">
				<section>
					<h2><?php esc_html_e( 'Notas más leídas', 'ddn-suite' ); ?></h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th class="ddn-stats__num">#</th>
								<th><?php esc_html_e( 'Nota', 'ddn-suite' ); ?></th>
								<th><?php esc_html_e( 'Autor', 'ddn-suite' ); ?></th>
								<th><?php esc_html_e( 'Sección', 'ddn-suite' ); ?></th>
								<th class="ddn-stats__num"><?php esc_html_e( 'Lecturas', 'ddn-suite' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( array() === $posts ) : ?>
								<tr><td colspan="5"><?php esc_html_e( 'Sin datos.', 'ddn-suite' ); ?></td></tr>
							<?php endif; ?>
							<?php foreach ( $posts as $ddn_i => $ddn_post ) : ?>
								<tr>
									<td class="ddn-stats__num"><?php echo (int) $ddn_i + 1; ?></td>
									<td>
										<a href="<?php echo esc_url( $ddn_post['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $ddn_post['title'] ); ?></a>
										<br><small><?php echo esc_html( $ddn_post['published'] ); ?></small>
									</td>
									<td><?php echo esc_html( $ddn_post['author'] ); ?></td>
									<td><?php echo esc_html( $ddn_post['category'] ); ?></td>
									<td class="ddn-stats__num"><strong><?php echo esc_html( number_format_i18n( $ddn_post['views'] ) ); ?></strong></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>

				<section>
					<h2><?php esc_html_e( 'Autores más leídos', 'ddn-suite' ); ?></h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th class="ddn-stats__num">#</th>
								<th><?php esc_html_e( 'Autor', 'ddn-suite' ); ?></th>
								<th class="ddn-stats__num"><?php esc_html_e( 'Notas', 'ddn-suite' ); ?></th>
								<th class="ddn-stats__num"><?php esc_html_e( 'Lecturas', 'ddn-suite' ); ?></th>
								<th class="ddn-stats__num"><?php esc_html_e( 'Por nota', 'ddn-suite' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( array() === $authors ) : ?>
								<tr><td colspan="5"><?php esc_html_e( 'Sin datos.', 'ddn-suite' ); ?></td></tr>
							<?php endif; ?>
							<?php foreach ( $authors as $ddn_i => $ddn_author ) : ?>
								<tr>
									<td class="ddn-stats__num"><?php echo (int) $ddn_i + 1; ?></td>
									<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=post&author=' . $ddn_author['author_id'] ) ); ?>"><?php echo esc_html( $ddn_author['name'] ); ?></a></td>
									<td class="ddn-stats__num"><?php echo esc_html( number_format_i18n( $ddn_author['posts'] ) ); ?></td>
									<td class="ddn-stats__num"><strong><?php echo esc_html( number_format_i18n( $ddn_author['views'] ) ); ?></strong></td>
									<td class="ddn-stats__num"><?php echo esc_html( number_format_i18n( $ddn_author['posts'] > 0 ? $ddn_author['views'] / $ddn_author['posts'] : 0, 1 ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			</div>

			<p class="description">
				<?php esc_html_e( 'Cuenta las lecturas de visitantes: no incluye al personal de redacción ni a los robots de búsqueda. Solo notas publicadas. Los datos se guardan 400 días y empiezan a acumularse desde que se activó este módulo.', 'ddn-suite' ); ?>
			</p>
		</div>
		<?php
		$this->styles();
	}

	private function filters( string $from, string $to, string $today ): void {
		$base    = admin_url( 'admin.php?page=' . self::SLUG );
		$presets = array(
			__( 'Hoy', 'ddn-suite' )      => 1,
			__( '7 días', 'ddn-suite' )   => 7,
			__( '30 días', 'ddn-suite' )  => 30,
			__( '90 días', 'ddn-suite' )  => 90,
			__( '12 meses', 'ddn-suite' ) => 365,
		);
		$end     = new DateTimeImmutable( $today );
		?>
		<div class="ddn-stats__filters">
			<nav class="ddn-stats__presets">
				<?php foreach ( $presets as $ddn_label => $ddn_days ) : ?>
					<?php
					$ddn_start  = $end->modify( '-' . ( $ddn_days - 1 ) . ' days' )->format( 'Y-m-d' );
					$ddn_active = ( $from === $ddn_start && $to === $today );
					$ddn_query  = array(
						'ddn_from' => $ddn_start,
						'ddn_to'   => $today,
					);
					?>
					<a class="button<?php echo $ddn_active ? ' button-primary' : ''; ?>" href="<?php echo esc_url( add_query_arg( $ddn_query, $base ) ); ?>"><?php echo esc_html( $ddn_label ); ?></a>
				<?php endforeach; ?>
			</nav>
			<form method="get" class="ddn-stats__range">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
				<label><?php esc_html_e( 'Desde', 'ddn-suite' ); ?> <input type="date" name="ddn_from" value="<?php echo esc_attr( $from ); ?>" max="<?php echo esc_attr( $today ); ?>"></label>
				<label><?php esc_html_e( 'Hasta', 'ddn-suite' ); ?> <input type="date" name="ddn_to" value="<?php echo esc_attr( $to ); ?>" max="<?php echo esc_attr( $today ); ?>"></label>
				<?php submit_button( __( 'Ver', 'ddn-suite' ), 'secondary', '', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * @param array<string,int> $daily
	 */
	private function chart( string $from, string $to, array $daily ): void {
		$day = new DateTimeImmutable( $from );
		$end = new DateTimeImmutable( $to );

		$series = array();
		while ( $day <= $end ) {
			$key            = $day->format( 'Y-m-d' );
			$series[ $key ] = $daily[ $key ] ?? 0;
			$day            = $day->modify( '+1 day' );
		}
		$max = max( 1, (int) max( $series ) );
		?>
		<section class="ddn-stats__chart">
			<h2><?php esc_html_e( 'Lecturas por día', 'ddn-suite' ); ?></h2>
			<div class="ddn-stats__bars" role="img" aria-label="<?php esc_attr_e( 'Gráfico de lecturas por día', 'ddn-suite' ); ?>">
				<?php foreach ( $series as $ddn_day => $ddn_views ) : ?>
					<span
						class="ddn-stats__bar"
						style="height:<?php echo esc_attr( (string) max( 2, (int) round( $ddn_views / $max * 100 ) ) ); ?>%"
						title="<?php echo esc_attr( mysql2date( get_option( 'date_format' ), $ddn_day ) . ': ' . number_format_i18n( $ddn_views ) ); ?>"
					></span>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	private function styles(): void {
		?>
		<style>
		.ddn-stats__filters{display:flex;flex-wrap:wrap;gap:1rem 2rem;align-items:flex-end;margin:1rem 0 1.25rem}
		.ddn-stats__presets{display:flex;gap:.4rem;flex-wrap:wrap}
		.ddn-stats__range{display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap}
		.ddn-stats__range label{display:flex;flex-direction:column;font-size:12px;font-weight:600;gap:.25rem}
		.ddn-stats__cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin:0 0 1.5rem}
		.ddn-stats__card{background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:1rem;font-size:12px;color:#646970;text-transform:uppercase;letter-spacing:.03em}
		.ddn-stats__card span{display:block;font-size:26px;font-weight:700;color:#1d2327;text-transform:none;letter-spacing:0;line-height:1.2;margin-bottom:.25rem;overflow-wrap:anywhere}
		.ddn-stats__card--author span{font-size:20px;color:#bf0202}
		.ddn-stats__chart{background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:1rem 1.25rem;margin-bottom:1.5rem}
		.ddn-stats__chart h2{margin-top:0}
		.ddn-stats__bars{display:flex;align-items:flex-end;gap:2px;height:140px}
		.ddn-stats__bar{flex:1 1 0;min-width:2px;background:#bf0202;opacity:.85;border-radius:2px 2px 0 0}
		.ddn-stats__bar:hover{opacity:1}
		.ddn-stats__tables{display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:1.5rem;align-items:start;margin-bottom:1rem}
		.ddn-stats__tables h2{margin-top:0}
		.ddn-stats__num{text-align:right;white-space:nowrap}
		@media (max-width:1100px){.ddn-stats__tables{grid-template-columns:1fr}}
		</style>
		<?php
	}
}

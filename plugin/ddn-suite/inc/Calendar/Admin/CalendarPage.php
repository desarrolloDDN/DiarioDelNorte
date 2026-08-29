<?php
/**
 * Página de administración: calendario mensual de noticias publicadas y
 * programadas, con enlace a editar cada nota.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Calendar\Admin;

use DiarioDelNorte\Suite\Calendar\CalendarRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CalendarPage {

	public const SLUG = 'ddn-suite-calendario';

	public function __construct( private readonly CalendarRepository $repository ) {}

	public function enqueue( string $hook_suffix ): void {
		if ( ! str_ends_with( $hook_suffix, self::SLUG ) ) {
			return;
		}
		$css = DDN_SUITE_DIR . 'assets/admin/calendar.css';
		wp_enqueue_style( 'ddn-suite-calendar', DDN_SUITE_URL . 'assets/admin/calendar.css', array(), file_exists( $css ) ? (string) filemtime( $css ) : DDN_SUITE_VERSION );
	}

	public function render(): void {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( esc_html__( 'Sin permisos suficientes.', 'ddn-suite' ) );
		}

		$now   = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$year  = isset( $_GET['ddn_y'] ) ? max( 2000, (int) $_GET['ddn_y'] ) : (int) wp_date( 'Y', $now ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$month = isset( $_GET['ddn_m'] ) ? min( 12, max( 1, (int) $_GET['ddn_m'] ) ) : (int) wp_date( 'n', $now ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$data       = $this->repository->month( $year, $month );
		$first_wday = (int) wp_date( 'N', mktime( 0, 0, 0, $month, 1, $year ) ); // 1 (lun) .. 7 (dom)
		$days       = (int) wp_date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
		$today      = wp_date( 'Y-m-d', $now );

		$prev = $month > 1 ? array( $year, $month - 1 ) : array( $year - 1, 12 );
		$next = $month < 12 ? array( $year, $month + 1 ) : array( $year + 1, 1 );
		$base = admin_url( 'admin.php?page=' . self::SLUG );
		?>
		<div class="wrap ddn-calendar">
			<h1><?php esc_html_e( 'Calendario editorial', 'ddn-suite' ); ?></h1>

			<p class="ddn-calendar__nav">
				<a class="button" href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'ddn_y' => $prev[0],
							'ddn_m' => $prev[1],
						),
						$base
					)
				);
				?>
										">&laquo; <?php esc_html_e( 'Mes anterior', 'ddn-suite' ); ?></a>
				<strong><?php echo esc_html( ucfirst( (string) wp_date( 'F Y', mktime( 0, 0, 0, $month, 1, $year ) ) ) ); ?></strong>
				<a class="button" href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'ddn_y' => $next[0],
							'ddn_m' => $next[1],
						),
						$base
					)
				);
				?>
										"><?php esc_html_e( 'Mes siguiente', 'ddn-suite' ); ?> &raquo;</a>
			</p>

			<table class="ddn-calendar__grid widefat">
				<thead>
					<tr>
						<?php foreach ( array( 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom' ) as $wd ) : ?>
							<th scope="col"><?php echo esc_html( $wd ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<tr>
						<?php for ( $i = 1; $i < $first_wday; $i++ ) : ?>
							<td class="is-empty"></td>
						<?php endfor; ?>
						<?php
						for ( $d = 1; $d <= $days; $d++ ) :
							$key   = sprintf( '%04d-%02d-%02d', $year, $month, $d );
							$items = $data[ $key ] ?? array();
							$wday  = ( $first_wday - 1 + $d - 1 ) % 7;
							if ( 0 === $wday && $d > 1 ) {
								echo '</tr><tr>';
							}
							?>
							<td class="ddn-calendar__day<?php echo $key === $today ? ' is-today' : ''; ?>">
								<span class="ddn-calendar__date"><?php echo esc_html( (string) $d ); ?></span>
								<span class="ddn-calendar__count"><?php echo esc_html( (string) count( $items ) ); ?></span>
								<ul>
									<?php foreach ( $items as $item ) : ?>
										<li class="status-<?php echo esc_attr( $item['status'] ); ?>">
											<a href="<?php echo esc_url( $item['edit_link'] ); ?>">
												<span class="ddn-calendar__time"><?php echo esc_html( $item['time'] ); ?></span>
												<?php echo esc_html( $item['title'] ); ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							</td>
							<?php
						endfor;
						$trailing = ( 7 - ( ( $first_wday - 1 + $days ) % 7 ) ) % 7;
						for ( $i = 0; $i < $trailing; $i++ ) {
							echo '<td class="is-empty"></td>';
						}
						?>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}
}

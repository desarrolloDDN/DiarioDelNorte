<?php
/**
 * Listado paginado (30 por página) de la bitácora de actividad, con
 * filtro por tipo de evento, usuario y rango de fechas.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Activity\Admin;

use DiarioDelNorte\Suite\Activity\ActivityRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class ActivityListTable extends \WP_List_Table {

	private const PER_PAGE = 30;

	public function __construct( private readonly ActivityRepository $repo ) {
		parent::__construct(
			array(
				'singular' => 'evento',
				'plural'   => 'eventos',
				'ajax'     => false,
			)
		);
	}

	/** @return array<string,string> */
	public function get_columns(): array {
		return array(
			'created_at' => __( 'Fecha', 'ddn-suite' ),
			'event_type' => __( 'Evento', 'ddn-suite' ),
			'user'       => __( 'Usuario', 'ddn-suite' ),
			'object'     => __( 'Detalle', 'ddn-suite' ),
			'ip'         => __( 'IP', 'ddn-suite' ),
		);
	}

	/** @return array{event_type:string,user:string,from:string,to:string} */
	public function current_filters(): array {
		return array(
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- filtro de solo lectura de una pantalla ya protegida por capability.
			'event_type' => isset( $_GET['ddn_event'] ) ? sanitize_key( wp_unslash( $_GET['ddn_event'] ) ) : '',
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- idem.
			'user'       => isset( $_GET['ddn_user'] ) ? sanitize_text_field( wp_unslash( $_GET['ddn_user'] ) ) : '',
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- idem.
			'from'       => isset( $_GET['ddn_from'] ) ? sanitize_text_field( wp_unslash( $_GET['ddn_from'] ) ) : '',
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- idem.
			'to'         => isset( $_GET['ddn_to'] ) ? sanitize_text_field( wp_unslash( $_GET['ddn_to'] ) ) : '',
		);
	}

	public function prepare_items(): void {
		$this->_column_headers = array( $this->get_columns(), array(), array() );

		$filters = $this->current_filters();
		$page    = $this->get_pagenum();

		$total       = $this->repo->count( $filters );
		$this->items = $this->repo->query( $filters, self::PER_PAGE, $page );

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => self::PER_PAGE,
				'total_pages' => (int) ceil( $total / self::PER_PAGE ),
			)
		);
	}

	/**
	 * @param array<string,mixed> $item
	 */
	public function column_default( $item, $column_name ): string { // phpcs:ignore Squiz.Commenting -- firma heredada de WP_List_Table.
		switch ( $column_name ) {
			case 'created_at':
				return esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (string) $item['created_at'] ) );
			case 'event_type':
				$labels = $this->repo->event_types();
				return esc_html( $labels[ $item['event_type'] ] ?? (string) $item['event_type'] );
			case 'user':
				$login = (string) $item['user_login'];
				$role  = (string) $item['user_role'];
				if ( '' === $login ) {
					return '—';
				}
				return esc_html( '' !== $role ? "{$login} ({$role})" : $login );
			case 'object':
				return '' !== (string) $item['object_label'] ? esc_html( (string) $item['object_label'] ) : '—';
			case 'ip':
				return '' !== (string) $item['ip'] ? esc_html( (string) $item['ip'] ) : '—';
			default:
				return isset( $item[ $column_name ] ) ? esc_html( (string) $item[ $column_name ] ) : '';
		}
	}

	protected function extra_tablenav( $which ): void { // phpcs:ignore Squiz.Commenting -- firma heredada de WP_List_Table.
		if ( 'top' !== $which ) {
			return;
		}

		$filters = $this->current_filters();
		?>
		<div class="alignleft actions">
			<select name="ddn_event">
				<option value=""><?php esc_html_e( 'Todos los eventos', 'ddn-suite' ); ?></option>
				<?php foreach ( $this->repo->event_types() as $ddn_key => $ddn_label ) : ?>
					<option value="<?php echo esc_attr( $ddn_key ); ?>" <?php selected( $filters['event_type'], $ddn_key ); ?>><?php echo esc_html( $ddn_label ); ?></option>
				<?php endforeach; ?>
			</select>
			<input type="text" name="ddn_user" placeholder="<?php esc_attr_e( 'Usuario', 'ddn-suite' ); ?>" value="<?php echo esc_attr( $filters['user'] ); ?>">
			<input type="date" name="ddn_from" value="<?php echo esc_attr( $filters['from'] ); ?>">
			<input type="date" name="ddn_to" value="<?php echo esc_attr( $filters['to'] ); ?>">
			<?php submit_button( __( 'Filtrar', 'ddn-suite' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}
}

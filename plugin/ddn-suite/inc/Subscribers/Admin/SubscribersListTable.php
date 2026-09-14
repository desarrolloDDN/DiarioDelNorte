<?php
/**
 * Listado paginado (20 por página) de suscriptores, con búsqueda por
 * nombre/correo y filtros de departamento, origen y autorización de
 * WhatsApp.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class SubscribersListTable extends \WP_List_Table {

	private const PER_PAGE = 20;

	public function __construct( private readonly SubscribersRepository $repo ) {
		parent::__construct(
			array(
				'singular' => 'suscriptor',
				'plural'   => 'suscriptores',
				'ajax'     => false,
			)
		);
	}

	/** @return array<string,string> */
	public function get_columns(): array {
		return array(
			'name'                => __( 'Nombre', 'ddn-suite' ),
			'email'               => __( 'Correo', 'ddn-suite' ),
			'phone'               => __( 'Teléfono', 'ddn-suite' ),
			'location'            => __( 'Ubicación', 'ddn-suite' ),
			'document'            => __( 'Identificación', 'ddn-suite' ),
			'consent_email'       => __( 'Correos', 'ddn-suite' ),
			'consent_whatsapp'    => __( 'WhatsApp', 'ddn-suite' ),
			'terms_accepted_at'   => __( 'Aceptó Términos', 'ddn-suite' ),
			'privacy_accepted_at' => __( 'Aceptó Política', 'ddn-suite' ),
			'origin'              => __( 'Origen', 'ddn-suite' ),
			'registered_at'       => __( 'Registro', 'ddn-suite' ),
		);
	}

	/** @return array{search:string,department:string,origin:string,whatsapp_only:bool} */
	public function current_filters(): array {
		return array(
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- filtros de solo lectura de una pantalla ya protegida por capability.
			'search'        => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '',
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- idem.
			'department'    => isset( $_GET['ddn_department'] ) ? sanitize_text_field( wp_unslash( $_GET['ddn_department'] ) ) : '',
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- idem.
			'origin'        => isset( $_GET['ddn_origin'] ) ? sanitize_key( wp_unslash( $_GET['ddn_origin'] ) ) : '',
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- idem.
			'whatsapp_only' => ! empty( $_GET['ddn_whatsapp_only'] ),
		);
	}

	public function prepare_items(): void {
		$this->_column_headers = array( $this->get_columns(), array(), array() );

		$filters = $this->current_filters();
		$page    = $this->get_pagenum();

		$total = $this->repo->count( $filters );
		$query = $this->repo->query( $filters, self::PER_PAGE, $page );

		$this->items = array_map(
			fn ( $user ): array => $this->repo->row( $user ),
			$query->get_results()
		);

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
			case 'location':
				$parts = array_filter( array( (string) $item['city'], (string) $item['department'] ) );
				return array() !== $parts ? esc_html( implode( ', ', $parts ) ) : '—';
			case 'document':
				return '' !== $item['doc_type'] ? esc_html( $item['doc_type'] . ' ' . $item['doc_number'] ) : '—';
			case 'consent_email':
			case 'consent_whatsapp':
				return $item[ $column_name ] ? esc_html__( 'Sí', 'ddn-suite' ) : esc_html__( 'No', 'ddn-suite' );
			case 'terms_accepted_at':
			case 'privacy_accepted_at':
				return '' !== $item[ $column_name ] ? esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (string) $item[ $column_name ] ) ) : '—';
			case 'registered_at':
				return esc_html( mysql2date( get_option( 'date_format' ), (string) $item[ $column_name ] ) );
			case 'origin':
				$labels = array(
					'direct'   => __( 'Directo', 'ddn-suite' ),
					'google'   => 'Google',
					'facebook' => 'Facebook',
				);
				return esc_html( $labels[ $item['origin'] ] ?? (string) $item['origin'] );
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
			<select name="ddn_department">
				<option value=""><?php esc_html_e( 'Todos los departamentos', 'ddn-suite' ); ?></option>
				<?php foreach ( $this->repo->departments_in_use() as $ddn_dep ) : ?>
					<option value="<?php echo esc_attr( $ddn_dep ); ?>" <?php selected( $filters['department'], $ddn_dep ); ?>><?php echo esc_html( $ddn_dep ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="ddn_origin">
				<option value=""><?php esc_html_e( 'Todos los orígenes', 'ddn-suite' ); ?></option>
				<option value="direct" <?php selected( $filters['origin'], 'direct' ); ?>><?php esc_html_e( 'Directo', 'ddn-suite' ); ?></option>
				<option value="google" <?php selected( $filters['origin'], 'google' ); ?>>Google</option>
				<option value="facebook" <?php selected( $filters['origin'], 'facebook' ); ?>>Facebook</option>
			</select>
			<label>
				<input type="checkbox" name="ddn_whatsapp_only" value="1" <?php checked( $filters['whatsapp_only'] ); ?>>
				<?php esc_html_e( 'Solo autorizan WhatsApp', 'ddn-suite' ); ?>
			</label>
			<?php submit_button( __( 'Filtrar', 'ddn-suite' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}
}

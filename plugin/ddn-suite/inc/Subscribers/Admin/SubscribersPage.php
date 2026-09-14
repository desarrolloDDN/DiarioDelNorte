<?php
/**
 * Menú «Suscriptores»: listado + exportación de todo lo que cumpla el
 * filtro activo. La descarga binaria va por su propia acción de
 * admin-post.php con su propio nonce, aparte de cualquier API REST/JSON.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Admin;

use DiarioDelNorte\Suite\Subscribers\Install\CapabilityInstaller;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SubscribersPage {

	public const SLUG           = 'ddn-subscribers';
	private const EXPORT_ACTION = 'ddn_subscribers_export';

	public function __construct( private readonly SubscribersRepository $repo ) {}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_' . self::EXPORT_ACTION, array( $this, 'handle_export' ) );
	}

	public function menu(): void {
		add_menu_page(
			__( 'Suscriptores', 'ddn-suite' ),
			__( 'Suscriptores', 'ddn-suite' ),
			CapabilityInstaller::CAP,
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-groups',
			58
		);
	}

	public function render(): void {
		if ( ! current_user_can( CapabilityInstaller::CAP ) ) {
			wp_die( esc_html__( 'No tienes permiso para ver esta página.', 'ddn-suite' ) );
		}

		$table = new SubscribersListTable( $this->repo );
		$table->prepare_items();
		$filters = $table->current_filters();

		$export_base = add_query_arg(
			array_merge( array( 'action' => self::EXPORT_ACTION ), $this->export_filter_args( $filters ) ),
			admin_url( 'admin-post.php' )
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Suscriptores', 'ddn-suite' ); ?></h1>

			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
				<?php $table->search_box( __( 'Buscar', 'ddn-suite' ), 'ddn-subscribers-search' ); ?>
				<?php $table->display(); ?>
			</form>

			<p>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'ddn_format', 'csv', $export_base ), self::EXPORT_ACTION ) ); ?>"><?php esc_html_e( 'Exportar CSV', 'ddn-suite' ); ?></a>
				<?php if ( XlsxWriter::available() ) : ?>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'ddn_format', 'xlsx', $export_base ), self::EXPORT_ACTION ) ); ?>"><?php esc_html_e( 'Exportar Excel (.xlsx)', 'ddn-suite' ); ?></a>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	/**
	 * @param array{search:string,department:string,origin:string,whatsapp_only:bool} $filters
	 * @return array<string,string>
	 */
	private function export_filter_args( array $filters ): array {
		return array(
			'ddn_search'        => $filters['search'],
			'ddn_department'    => $filters['department'],
			'ddn_origin'        => $filters['origin'],
			'ddn_whatsapp_only' => $filters['whatsapp_only'] ? '1' : '',
		);
	}

	public function handle_export(): void {
		if ( ! current_user_can( CapabilityInstaller::CAP ) ) {
			wp_die( esc_html__( 'No tienes permiso para hacer esto.', 'ddn-suite' ) );
		}
		check_admin_referer( self::EXPORT_ACTION );

		$filters = array(
			'search'        => isset( $_GET['ddn_search'] ) ? sanitize_text_field( wp_unslash( $_GET['ddn_search'] ) ) : '',
			'department'    => isset( $_GET['ddn_department'] ) ? sanitize_text_field( wp_unslash( $_GET['ddn_department'] ) ) : '',
			'origin'        => isset( $_GET['ddn_origin'] ) ? sanitize_key( wp_unslash( $_GET['ddn_origin'] ) ) : '',
			'whatsapp_only' => ! empty( $_GET['ddn_whatsapp_only'] ),
		);
		$format  = isset( $_GET['ddn_format'] ) ? sanitize_key( wp_unslash( $_GET['ddn_format'] ) ) : 'csv';

		$rows = array_map(
			fn ( $user ): array => $this->repo->row( $user ),
			$this->repo->all_matching( $filters )
		);

		$columns = array(
			'name'                => __( 'Nombre', 'ddn-suite' ),
			'email'               => __( 'Correo', 'ddn-suite' ),
			'phone'               => __( 'Teléfono', 'ddn-suite' ),
			'department'          => __( 'Departamento', 'ddn-suite' ),
			'city'                => __( 'Ciudad', 'ddn-suite' ),
			'address'             => __( 'Dirección', 'ddn-suite' ),
			'doc_type'            => __( 'Tipo de documento', 'ddn-suite' ),
			'doc_number'          => __( 'Número de documento', 'ddn-suite' ),
			'consent_email'       => __( 'Autoriza correos', 'ddn-suite' ),
			'consent_whatsapp'    => __( 'Autoriza WhatsApp', 'ddn-suite' ),
			'terms_accepted_at'   => __( 'Aceptó Términos', 'ddn-suite' ),
			'privacy_accepted_at' => __( 'Aceptó Política', 'ddn-suite' ),
			'origin'              => __( 'Origen', 'ddn-suite' ),
			'registered_at'       => __( 'Fecha de registro', 'ddn-suite' ),
		);

		if ( 'xlsx' === $format && XlsxWriter::available() ) {
			$bytes = XlsxWriter::build( $rows, $columns );
			if ( '' !== $bytes ) {
				nocache_headers();
				header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
				header( 'Content-Disposition: attachment; filename="suscriptores.xlsx"' );
				header( 'Content-Length: ' . strlen( $bytes ) );
				echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binario (.xlsx), no HTML.
				exit;
			}
		}

		CsvExporter::stream( $rows, $columns, 'suscriptores.csv' );
	}
}

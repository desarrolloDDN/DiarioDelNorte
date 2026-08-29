<?php
/**
 * Formulario de búsqueda.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_id = wp_unique_id( 'ddn-search-' );
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $ddn_id ); ?>"><?php esc_html_e( 'Buscar:', 'diario-del-norte' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $ddn_id ); ?>" class="search-form__field" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Buscar en Diario del Norte…', 'diario-del-norte' ); ?>" required>
	<button type="submit" class="search-form__submit"><?php esc_html_e( 'Buscar', 'diario-del-norte' ); ?></button>
</form>

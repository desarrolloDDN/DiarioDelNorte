<?php
/**
 * Página de Registro de suscriptor. WordPress la usa automáticamente en
 * la página con slug «registro» (creada sola por
 * Suite\Subscribers\Install\PageInstaller). Toda la lógica —validación,
 * creación de la cuenta, registro social— vive en el plugin: el tema
 * solo marca el punto de enganche `ddn/subscribers_register`, igual que
 * `Ads::zone()` marca las zonas de anuncio.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="wrap layout-page">
	<header class="page-head">
		<h1 class="page-head__title"><?php the_title(); ?></h1>
	</header>
	<?php do_action( 'ddn/subscribers_register' ); ?>
</div>
<?php
get_footer();

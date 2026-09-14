<?php
/**
 * Página de Ingreso de suscriptor (login propio del sitio — NUNCA
 * wp-login.php). WordPress la usa automáticamente en la página con slug
 * «ingresar» (creada sola por Suite\Subscribers\Install\PageInstaller).
 * Toda la lógica vive en el plugin: el tema solo marca el punto de
 * enganche `ddn/subscribers_login`.
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
	<?php do_action( 'ddn/subscribers_login' ); ?>
</div>
<?php
get_footer();

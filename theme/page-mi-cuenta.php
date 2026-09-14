<?php
/**
 * «Mi cuenta»: editar perfil y eliminar cuenta del suscriptor. WordPress
 * la usa automáticamente en la página con slug «mi-cuenta» (creada sola
 * por Suite\Subscribers\Install\PageInstaller). Toda la lógica —incluida
 * la de los dos formularios que viven aquí— está en el plugin: el tema
 * solo marca el punto de enganche `ddn/subscribers_account`.
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
	<?php do_action( 'ddn/subscribers_account' ); ?>
</div>
<?php
get_footer();

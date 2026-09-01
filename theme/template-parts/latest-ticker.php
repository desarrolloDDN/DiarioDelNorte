<?php
/**
 * Cinta «Lo último»: las 10 entradas publicadas más recientes, en
 * desplazamiento continuo de derecha a izquierda.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_ticker = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 10,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

if ( ! $ddn_ticker->have_posts() ) {
	wp_reset_postdata();
	return;
}

/** Imprime la lista de titulares. El grupo duplicado va oculto para lectores. */
$ddn_render_group = static function ( WP_Query $query, bool $is_clone ) {
	printf(
		'<ul class="ticker__group"%s>',
		$is_clone ? ' aria-hidden="true"' : ''
	);
	foreach ( $query->posts as $ddn_post ) {
		printf(
			'<li class="ticker__item"><a href="%1$s">%2$s</a><time datetime="%3$s">%4$s</time></li>',
			esc_url( (string) get_permalink( $ddn_post ) ),
			esc_html( get_the_title( $ddn_post ) ),
			esc_attr( (string) get_post_time( 'c', false, $ddn_post ) ),
			esc_html( (string) get_post_time( get_option( 'time_format' ), false, $ddn_post ) )
		);
	}
	echo '</ul>';
};
?>
<aside class="ticker" aria-label="<?php esc_attr_e( 'Lo último', 'diario-del-norte' ); ?>">
	<p class="ticker__label"><?php esc_html_e( 'Lo último', 'diario-del-norte' ); ?></p>
	<div class="ticker__viewport">
		<div class="ticker__marquee">
			<?php
			$ddn_render_group( $ddn_ticker, false );
			$ddn_render_group( $ddn_ticker, true );
			?>
		</div>
	</div>
</aside>
<?php
wp_reset_postdata();

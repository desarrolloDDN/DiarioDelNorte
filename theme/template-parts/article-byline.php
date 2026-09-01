<?php
/**
 * Firma de la nota: avatar + nombre del autor + lugar · fecha · hora.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Content\DatelineField;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_uid   = (int) get_the_author_meta( 'ID' );
$ddn_place = DatelineField::place( get_the_ID() );

$ddn_line = implode(
	' · ',
	array_filter(
		array(
			$ddn_place,
			get_the_date( 'j M Y' ),
			get_the_time( 'H:i' ) . ' COT',
		)
	)
);
?>
<div class="article__byline">
	<?php echo get_avatar( $ddn_uid, 96, '', get_the_author() . ' — avatar', array( 'class' => 'article__byline-avatar' ) ); ?>
	<div class="article__byline-text">
		<p class="article__byline-name"><?php echo esc_html( get_the_author() ); ?></p>
		<p class="article__byline-line"><?php echo esc_html( $ddn_line ); ?></p>
	</div>
</div>

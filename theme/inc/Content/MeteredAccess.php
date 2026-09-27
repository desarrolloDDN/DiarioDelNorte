<?php
/**
 * Muro de medición para visitantes sin sesión: pueden leer un número
 * limitado de notas (las que no estén ya marcadas «exclusiva para
 * suscriptores», que se bloquean aparte) antes de tener que suscribirse.
 *
 * La decisión se toma en el navegador (localStorage), no en el
 * servidor: el sitio tiene caché de página activo, así que el HTML de
 * una nota se sirve idéntico a cualquier visitante sin sesión — el
 * servidor no puede saber cuántas notas ha leído cada quien. Por eso el
 * contenido siempre se manda completo (ver single.php) y un script
 * mínimo, impreso muy arriba en el `<head>` para que corra antes de que
 * el cuerpo se pinte, decide con qué clase queda `<html>` y el CSS
 * (`_article.scss`) enseña un bloque u otro.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MeteredAccess {

	/** Notas gratis que puede leer un visitante sin sesión antes de tener que suscribirse. */
	private const LIMIT = 5;

	public function register(): void {
		add_action( 'wp_head', array( $this, 'print_bootstrap' ), 1 );
	}

	/**
	 * El propio marcado (id de la nota, límite) sale igual para cualquier
	 * visitante sin sesión — nada de esto depende de quién es, así que el
	 * caché de página no le hace daño a esta parte.
	 */
	public function print_bootstrap(): void {
		if ( is_user_logged_in() || ! is_singular( 'post' ) ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( $post_id <= 0 || SubscriberOnly::is_restricted( $post_id ) ) {
			return; // Ya la bloquea el muro de «exclusiva»; no consume cupo.
		}
		?>
		<script>
		( function () {
			try {
				var KEY   = 'ddnReadIds';
				var LIMIT = <?php echo (int) self::LIMIT; ?>;
				var id    = <?php echo (int) $post_id; ?>;
				var ids   = JSON.parse( window.localStorage.getItem( KEY ) || '[]' );
				if ( ! Array.isArray( ids ) ) {
					ids = [];
				}
				if ( ids.indexOf( id ) !== -1 ) {
					return; // Releer una nota ya contada no gasta cupo.
				}
				if ( ids.length >= LIMIT ) {
					document.documentElement.classList.add( 'ddn-metered-blocked' );
					return;
				}
				ids.push( id );
				window.localStorage.setItem( KEY, JSON.stringify( ids ) );
			} catch ( e ) {
				// Sin localStorage (modo privado estricto, etc.): no se bloquea.
			}
		}() );
		</script>
		<?php
	}
}

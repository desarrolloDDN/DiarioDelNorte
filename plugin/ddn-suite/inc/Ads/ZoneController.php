<?php
/**
 * Puente entre el tema y las campañas: se engancha a la acción
 * `ddn/ad_zone` del tema (zonas de plantilla) y a `the_content` (anuncio
 * intercalado tras el 3.er párrafo). Selecciona la campaña, la renderiza
 * y registra la impresión.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Ads;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ZoneController {

	/** @var array<string,string> Cache por petición: zona => HTML ya resuelto. */
	private array $resolved = array();

	public function __construct(
		private readonly CampaignRepository $campaigns,
		private readonly CampaignSelector $selector,
		private readonly AdRenderer $renderer,
		private readonly StatsRepository $stats,
	) {}

	public function register(): void {
		add_action( 'ddn/ad_zone', array( $this, 'render_zone' ) );
		add_filter( 'the_content', array( $this, 'insert_in_article' ), 12 );
	}

	public function render_zone( string $zone ): void {
		$ad = AdZone::tryFrom( $zone );
		if ( null === $ad ) {
			return;
		}

		echo $this->html_for( $ad ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- AdRenderer ya escapa.
	}

	public function insert_in_article( string $content ): string {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$html = $this->html_for( AdZone::InArticle );
		if ( '' === $html ) {
			return $content;
		}

		// Tras el cierre del tercer párrafo de nivel superior.
		$parts = preg_split( '/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( ! is_array( $parts ) || count( $parts ) < 6 ) {
			return $content;
		}

		$rebuilt = '';
		$closes  = 0;
		foreach ( $parts as $chunk ) {
			$rebuilt .= $chunk;
			if ( '</p>' === strtolower( $chunk ) ) {
				++$closes;
				if ( 3 === $closes ) {
					$rebuilt .= $html;
				}
			}
		}

		return $rebuilt;
	}

	private function html_for( AdZone $zone ): string {
		if ( isset( $this->resolved[ $zone->value ] ) ) {
			return $this->resolved[ $zone->value ];
		}

		$campaign = $this->selector->pick(
			$this->campaigns->running_in( $zone ),
			$this->context_categories()
		);

		$html = null !== $campaign ? $this->renderer->render( $campaign ) : '';

		if ( null !== $campaign && '' !== $html && ! is_admin() ) {
			$this->stats->record( $campaign->id, 'impression' );
		}

		$this->resolved[ $zone->value ] = $html;

		return $html;
	}

	/** @return list<string> Slugs de categoría de la vista actual. */
	private function context_categories(): array {
		if ( is_category() ) {
			$term = get_queried_object();

			return $term instanceof \WP_Term ? array( $term->slug ) : array();
		}

		if ( is_singular( 'post' ) ) {
			return array_values(
				array_map(
					static fn ( \WP_Term $t ): string => $t->slug,
					(array) get_the_category()
				)
			);
		}

		return array();
	}
}

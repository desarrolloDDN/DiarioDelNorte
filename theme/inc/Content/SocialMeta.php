<?php
/**
 * Etiquetas Open Graph / Twitter Card para que WhatsApp y las redes
 * muestren la imagen destacada, el titular y el resumen de cada nota.
 *
 * Si hay un plugin de SEO activo (Yoast, Rank Math…) se cede el control:
 * ese plugin ya imprime estas etiquetas y no conviene duplicarlas.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Content;

use WP_Post;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SocialMeta {

	private const DESC_LEN = 200;

	public function register(): void {
		add_action( 'wp_head', array( $this, 'render' ), 5 );
	}

	public function render(): void {
		if ( ! $this->should_run() ) {
			return;
		}

		$ctx = $this->context();

		echo "\n<!-- Diario del Norte · social meta -->\n";

		$this->tag( 'og:type', $ctx['type'] );
		$this->tag( 'og:site_name', get_bloginfo( 'name' ) );
		$this->tag( 'og:locale', 'es_CO' );
		$this->tag( 'og:title', $ctx['title'] );
		$this->tag( 'og:description', $ctx['description'] );
		$this->tag( 'og:url', $ctx['url'], true );

		if ( null !== $ctx['image'] ) {
			$img = $ctx['image'];
			$this->tag( 'og:image', $img['url'], true );
			$this->tag( 'og:image:secure_url', $img['url'], true );
			if ( $img['w'] > 0 ) {
				$this->tag( 'og:image:width', (string) $img['w'] );
			}
			if ( $img['h'] > 0 ) {
				$this->tag( 'og:image:height', (string) $img['h'] );
			}
			if ( '' !== $img['type'] ) {
				$this->tag( 'og:image:type', $img['type'] );
			}
			if ( '' !== $img['alt'] ) {
				$this->tag( 'og:image:alt', $img['alt'] );
			}
		}

		if ( 'article' === $ctx['type'] && $ctx['post'] instanceof WP_Post ) {
			$this->article_tags( $ctx['post'] );
		}

		// Twitter
		$this->name_tag( 'twitter:card', null !== $ctx['image'] ? 'summary_large_image' : 'summary' );
		$this->name_tag( 'twitter:title', $ctx['title'] );
		$this->name_tag( 'twitter:description', $ctx['description'] );
		if ( null !== $ctx['image'] ) {
			$this->name_tag( 'twitter:image', $ctx['image']['url'], true );
		}

		// El tema no imprime <meta name="description"> en ningún otro sitio.
		if ( '' !== $ctx['description'] && ! current_theme_supports( 'ddn-no-meta-description' ) ) {
			$this->name_tag( 'description', $ctx['description'] );
		}

		echo "<!-- /social meta -->\n";
	}

	// -- Contexto -----------------------------------------------------------

	/**
	 * @return array{type:string,title:string,description:string,url:string,post:WP_Post|null,image:array{url:string,w:int,h:int,alt:string,type:string}|null}
	 */
	private function context(): array {
		$object = get_queried_object();

		if ( is_singular() && $object instanceof WP_Post ) {
			$image = $this->image_for_post( $object );

			return array(
				'type'        => 'post' === $object->post_type ? 'article' : 'website',
				'title'       => wp_strip_all_tags( get_the_title( $object ) ),
				'description' => $this->description_for( $object ),
				'url'         => (string) get_permalink( $object ),
				'post'        => $object,
				'image'       => null !== $image ? $image : $this->default_image(),
			);
		}

		if ( ( is_category() || is_tag() || is_tax() ) && $object instanceof WP_Term ) {
			$desc = wp_strip_all_tags( (string) term_description( $object->term_id ) );

			return array(
				'type'        => 'website',
				'title'       => single_term_title( '', false ),
				'description' => '' !== $desc ? $this->clip( $desc ) : (string) get_bloginfo( 'description' ),
				'url'         => (string) get_term_link( $object ),
				'post'        => null,
				'image'       => $this->default_image(),
			);
		}

		if ( is_author() ) {
			$author_id = (int) get_query_var( 'author' );

			return array(
				'type'        => 'profile',
				'title'       => (string) get_the_author_meta( 'display_name', $author_id ),
				'description' => $this->clip( wp_strip_all_tags( (string) get_the_author_meta( 'description', $author_id ) ) ),
				'url'         => (string) get_author_posts_url( $author_id ),
				'post'        => null,
				'image'       => $this->default_image(),
			);
		}

		return array(
			'type'        => 'website',
			'title'       => (string) get_bloginfo( 'name' ),
			'description' => (string) get_bloginfo( 'description' ),
			'url'         => home_url( '/' ),
			'post'        => null,
			'image'       => $this->default_image(),
		);
	}

	private function description_for( WP_Post $post ): string {
		$raw = '' !== trim( (string) $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
		$raw = wp_strip_all_tags( strip_shortcodes( $raw ) );

		return $this->clip( $raw );
	}

	private function clip( string $text ): string {
		$text = trim( (string) preg_replace( '/\s+/', ' ', $text ) );
		if ( mb_strlen( $text ) > self::DESC_LEN ) {
			$text = rtrim( mb_substr( $text, 0, self::DESC_LEN ) ) . '…';
		}

		return $text;
	}

	// -- Imagen -----------------------------------------------------------

	/**
	 * @return array{url:string,w:int,h:int,alt:string,type:string}|null
	 */
	private function image_for_post( WP_Post $post ): ?array {
		$attachment_id = (int) get_post_thumbnail_id( $post );

		if ( 0 === $attachment_id && '' !== $post->post_content
			&& preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $m ) ) {
			$attachment_id = (int) attachment_url_to_postid( $m[1] );
		}

		return $attachment_id > 0 ? $this->image_from_attachment( $attachment_id ) : null;
	}

	/**
	 * @return array{url:string,w:int,h:int,alt:string,type:string}|null
	 */
	private function default_image(): ?array {
		$attachment_id = (int) get_theme_mod( 'ddn_social_image', 0 );
		if ( $attachment_id > 0 ) {
			$image = $this->image_from_attachment( $attachment_id );
			if ( null !== $image ) {
				return $image;
			}
		}

		$icon = get_site_icon_url( 512 );
		if ( '' !== (string) $icon ) {
			return array(
				'url'  => (string) $icon,
				'w'    => 512,
				'h'    => 512,
				'alt'  => (string) get_bloginfo( 'name' ),
				'type' => 'image/png',
			);
		}

		return null;
	}

	/**
	 * @return array{url:string,w:int,h:int,alt:string,type:string}|null
	 */
	private function image_from_attachment( int $attachment_id ): ?array {
		$src = false;
		foreach ( array( 'ddn-og', 'large', 'full' ) as $size ) {
			$candidate = wp_get_attachment_image_src( $attachment_id, $size );
			if ( is_array( $candidate ) && (int) $candidate[1] >= 200 ) {
				$src = $candidate;
				break;
			}
		}
		if ( ! is_array( $src ) ) {
			return null;
		}

		return array(
			'url'  => (string) $src[0],
			'w'    => (int) $src[1],
			'h'    => (int) $src[2],
			'alt'  => trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ),
			'type' => (string) get_post_mime_type( $attachment_id ),
		);
	}

	// -- Etiquetas de artículo -------------------------------------------

	private function article_tags( WP_Post $post ): void {
		$this->tag( 'article:published_time', (string) get_post_time( 'c', true, $post ) );
		$this->tag( 'article:modified_time', (string) get_post_modified_time( 'c', true, $post ) );

		$categories = get_the_category( $post->ID );
		if ( ! empty( $categories ) ) {
			$this->tag( 'article:section', $categories[0]->name );
		}

		foreach ( (array) get_the_tags( $post->ID ) as $tag ) {
			if ( $tag instanceof WP_Term ) {
				$this->tag( 'article:tag', $tag->name );
			}
		}
	}

	// -- Helpers de impresión -------------------------------------------

	private function tag( string $property, string $content, bool $is_url = false ): void {
		if ( '' === $content ) {
			return;
		}
		printf(
			"<meta property=\"%s\" content=\"%s\">\n",
			esc_attr( $property ),
			$is_url ? esc_url( $content ) : esc_attr( $content )
		);
	}

	private function name_tag( string $name, string $content, bool $is_url = false ): void {
		if ( '' === $content ) {
			return;
		}
		printf(
			"<meta name=\"%s\" content=\"%s\">\n",
			esc_attr( $name ),
			$is_url ? esc_url( $content ) : esc_attr( $content )
		);
	}

	private function should_run(): bool {
		if ( is_admin() || is_feed() || is_embed() || is_404() || is_paged() ) {
			return false;
		}

		$seo_plugin_active = defined( 'WPSEO_VERSION' )
			|| defined( 'SEOPRESS_VERSION' )
			|| defined( 'AIOSEO_VERSION' )
			|| defined( 'SLIM_SEO_VER' )
			|| defined( 'JETPACK__VERSION' )
			|| class_exists( 'RankMath' )
			|| function_exists( 'the_seo_framework' );

		if ( $seo_plugin_active ) {
			return false;
		}

		return (bool) apply_filters( 'ddn/social_meta', true );
	}
}

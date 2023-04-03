<?php

namespace Classifai\Watson;

/**
 * Normalize takes the post_content within a post and cleans it up for
 * sending to the Watson NLU API. Shortcodes, appreviations, HTML tags
 * are all stripped out here.
 *
 * A 'classifai_normalize' filter is provided to extend this to add
 * metadata or to perform additional cleanup.
 */
class Normalizer {

	/**
	 * Creates a plain text normalized version of the post's content.
	 * The post title is also included in the content to improve
	 * accuracy.
	 *
	 * @param int $post_id The post to normalize
	 * @return string
	 */
	public function normalize( $post_id ) {
		$post         = get_post( $post_id );
		$post_content = apply_filters( 'the_content', $post->post_content );
		$post_title   = apply_filters( 'the_title', $post->post_title );

		/* Strip shortcodes but keep internal caption text */
		$post_content = preg_replace( '#\[.+\](.+)\[/.+\]#', '$1', $post_content );

		$post_content = $this->normalize_content( $post_content, $post_title, $post_id );

		return $post_content;
	}

	/**
	 * Normalizes post_content into plain text.
	 *
	 * @param string $post_content The post content.
	 * @param string $post_title   The post title. Optional: append to content to improve accuracy.
	 * @param int    $post_id      The post id. Optional.
	 */
	public function normalize_content( $post_content, $post_title = '', $post_id = false ) {
		/* Strip HTML entities */
		$post_content = preg_replace( '/&#?[a-z0-9]{2,8};/i', '', $post_content );

		/* Strip abbreviations */
		$post_content = preg_replace( '/[A-Z][A-Z]+/', '', $post_content );

		/* Replace HTML linebreaks with newlines */
		$post_content = preg_replace( '#<br\s?/?>#', "\n\n", $post_content );

		/* Strip all HTML tags */
		$post_content = wp_strip_all_tags( $post_content );

		if ( ! empty( $post_title ) ) {
			/* Include title to improve relevancy */
			$post_content = $post_title . ".\n\n" . $post_content;
		}

		/**
		 * Filters the normalized content to allow for additional cleanup.
		 *
		 * @since 0.1.0
		 * @hook classifai_normalize
		 *
		 * @param {string} $post_content The normalized post content.
		 * @param {int}    $post_id      The ID of the post whose content is being normalized.
		 *
		 * @return {string} The filtered normalized post content.
		 */
		$post_content = apply_filters( 'classifai_normalize', trim( $post_content ), $post_id );

		return $post_content;
	}

}

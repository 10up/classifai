<?php
use \Classifai\Providers\Azure\TextToSpeech;

/**
 * Registers the Post to Audio block.
 */
function classifai_register_azure_text_to_speech_blocks() {
	$asset_file = include CLASSIFAI_PLUGIN_DIR . '/dist/post-audio-block.asset.php';

	wp_register_script(
		'post-audio-block',
		plugins_url( 'dist/post-audio-block.js', __FILE__ ),
		$asset_file['dependencies'],
		$asset_file['version']
	);

	register_block_type(
		CLASSIFAI_PLUGIN_DIR . '/includes/Classifai/Blocks/post-audio-block/block.json',
		array(
			'api_version'     => 2,
			'editor_script'   => 'post-audio-block',
			'render_callback' => 'classifai_render_post_audio_block',
		)
	);
}
add_action( 'init', 'classifai_register_azure_text_to_speech_blocks' );

/**
 * Dynamic block render callback for the Post to Audio block.
 */
function classifai_render_post_audio_block() {
	global $post;

	if ( ! $post ) {
		return;
	}

	$audio_attachment_id  = (int) get_post_meta( $post->ID, TextToSpeech::AUDIO_ID_KEY, true );
	$audio_timestamp      = (int) get_post_meta( $post->ID, TextToSpeech::AUDIO_TIMESTAMP_KEY, true );
	$audio_attachment_url = sprintf(
		'%1$s?ver=%2$s',
		wp_get_attachment_url( $audio_attachment_id ),
		filter_var( $audio_timestamp, FILTER_SANITIZE_NUMBER_INT )
	);

	ob_start();

	?>
		<div>
			<div class='classifai-listen-to-post-wrapper'>
				<div class="class-post-audio-controls">
					<span class="dashicons dashicons-controls-play"></span>
					<span class="dashicons dashicons-controls-pause"></span>
				</div>
				<div class='classifai-post-audio-heading'>Listen to this post</div>
			</div>
			<audio id="classifai-post-audio-player" src="<?php echo esc_url( home_url( $audio_attachment_url ) ); ?>"></audio>
		</div>
	<?php

	return ob_get_clean();
}

/**
 * Enqueues scripts required by the Post to Audio block on the front end.
 */
function classifai_post_audio_block_assets() {
	if ( ! has_block( 'classifai/post-audio-block' ) ) {
		return;
	}

	wp_enqueue_script(
		'classifai-post-audio-player',
		CLASSIFAI_PLUGIN_URL . '/dist/post-audio-controls.js',
		array(),
		CLASSIFAI_PLUGIN_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'classifai_post_audio_block_assets' );

/**
 * External dependencies.
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import {
	ToggleControl,
	BaseControl,
	Button,
	Icon,
} from '@wordpress/components';
import { useSelect, subscribe } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { store as postAudioStore } from './store';

const { ClassifaiEditorSettingPanel } = window;

/**
 * ClassifAI Text to Audio component.
 */
const TextToSpeechPlugin = () => {
	// State of the audio being previewed in PluginDocumentSettingPanel.
	const [ isPreviewing, setIsPreviewing ] = useState( false );

	const [ timestamp, setTimestamp ] = useState( new Date().getTime() );

	// Indicates whether speech synthesis is enabled for the current post.
	const isSynthesizeSpeech = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute(
			'classifai_synthesize_speech'
		)
	);

	// Indicates whether generated audio should be displayed on the frontend.
	const displayGeneratedAudio = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute(
			'classifai_display_generated_audio'
		)
	);

	// Post type label.
	const postTypeLabel = useSelect(
		( select ) =>
			( typeof select( 'core/editor' ).getPostTypeLabel !== 'undefined' &&
				select( 'core/editor' ).getPostTypeLabel() ) ||
			__( 'Post', 'classifai' )
	);

	// Says whether speech synthesis is in progress.
	const isProcessingAudio = useSelect( ( select ) =>
		select( postAudioStore ).getIsProcessing()
	);

	// The audio ID saved in the DB for the current post.
	const defaultAudioId = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute(
			'classifai_post_audio_id'
		)
	);

	// New audio ID in case it is regenerated manually or through publishing/updating the current post.
	const audioId =
		useSelect( ( select ) => select( postAudioStore ).getAudioId() ) ||
		defaultAudioId;

	// Get the attachment data by audio ID.
	const attachments = useSelect( ( select ) =>
		select( 'core' ).getEntityRecords( 'postType', 'attachment', {
			include: [ audioId ],
		} )
	);

	// Get URL for the attachment.
	const sourceUrl =
		attachments && attachments.length > 0 && attachments[ 0 ].source_url;

	const isProcessingAudioProgress = useRef( false );
	const isPostSavingInProgress = useRef( false );
	const { isSavingPost } = useSelect( ( select ) => {
		return {
			isSavingPost: select( 'core/editor' ).isSavingPost(),
		};
	} );
	const { isAutosavingPost } = useSelect( ( select ) => {
		return {
			isSavingPost: select( 'core/editor' ).isAutosavingPost(),
		};
	} );

	// Handles playing/pausing post audio during preview.
	useEffect( () => {
		const audioEl = document.getElementById( 'classifai-audio-preview' );

		if ( ! audioEl ) {
			return;
		}

		if ( isPreviewing ) {
			audioEl.play();
		} else {
			audioEl.pause();
		}
	}, [ isPreviewing ] );

	// Generates a unique timestamp to cache bust audio file.
	useEffect( () => {
		if ( isProcessingAudio ) {
			isProcessingAudioProgress.current = true;
		}

		if ( isProcessingAudioProgress.current && ! isProcessingAudio ) {
			setTimestamp( new Date().getTime() );
		}
	}, [ isProcessingAudio ] );

	useEffect( () => {
		// Code to run during post saving is in process.
		if (
			isSavingPost &&
			! isAutosavingPost &&
			! isPostSavingInProgress.current
		) {
			isPostSavingInProgress.current = true;
			if ( isSynthesizeSpeech ) {
				wp.data.dispatch( postAudioStore ).setIsProcessing( true );
			}
		}

		if (
			! isSavingPost &&
			! isAutosavingPost &&
			isPostSavingInProgress.current
		) {
			// Code to run after post is done saving.
			isPostSavingInProgress.current = false;
			wp.data.dispatch( postAudioStore ).setIsProcessing( false );
		}
	}, [ isSavingPost, isAutosavingPost, isSynthesizeSpeech ] );

	// Fetches the latest audio file to avoid disk cache.
	const cacheBustingUrl = `${ sourceUrl }?ver=${ timestamp }`;

	let audioIcon = 'controls-play';

	if ( isProcessingAudio ) {
		audioIcon = 'format-audio';
	} else if ( isPreviewing ) {
		audioIcon = 'controls-pause';
	}

	return (
		<ClassifaiEditorSettingPanel>
			<ToggleControl
				label={ __( 'Enable audio generation', 'classifai' ) }
				help={ sprintf(
					/** translators: %s is post type label. */
					__(
						'ClassifAI will generate audio for this %s when it is published or updated.',
						'classifai'
					),
					postTypeLabel
				) }
				checked={ isSynthesizeSpeech }
				onChange={ ( value ) => {
					wp.data.dispatch( 'core/editor' ).editPost( {
						classifai_synthesize_speech: value,
					} );
				} }
				disabled={ isProcessingAudio }
				isBusy={ isProcessingAudio }
			/>
			{ sourceUrl && (
				<>
					<ToggleControl
						label={ __( 'Display audio controls', 'classifai' ) }
						help={ __(
							'Controls the display of the audio player on the front-end.',
							'classifai'
						) }
						checked={ displayGeneratedAudio }
						onChange={ ( value ) => {
							wp.data.dispatch( 'core/editor' ).editPost( {
								classifai_display_generated_audio: value,
							} );
						} }
						disabled={ isProcessingAudio }
						isBusy={ isProcessingAudio }
					/>
					<BaseControl
						id="classifai-audio-preview-controls"
						help={
							isProcessingAudio
								? ''
								: __(
										'Preview the generated audio.',
										'classifai'
								  )
						}
					>
						<Button
							id="classifai-audio-controls__preview-btn"
							icon={ <Icon icon={ audioIcon } /> }
							variant="secondary"
							onClick={ () => setIsPreviewing( ! isPreviewing ) }
							disabled={ isProcessingAudio }
							isBusy={ isProcessingAudio }
						>
							{ isProcessingAudio
								? __( 'Generating audio..', 'classifai' )
								: __( 'Preview', 'classifai' ) }
						</Button>
					</BaseControl>
				</>
			) }
			{ sourceUrl && (
				<audio
					id="classifai-audio-preview"
					src={ cacheBustingUrl }
					onEnded={ () => setIsPreviewing( false ) }
				></audio>
			) }
		</ClassifaiEditorSettingPanel>
	);
};

registerPlugin( 'classifai-plugin-text-to-speech', {
	render: TextToSpeechPlugin,
} );

let saveHappened = false;
let showingNotice = false;

subscribe( () => {
	if ( saveHappened === false ) {
		saveHappened = wp.data.select( 'core/editor' ).isSavingPost() === true;
	}

	if (
		saveHappened &&
		wp.data.select( 'core/editor' ).isSavingPost() === false &&
		showingNotice === false
	) {
		const meta = wp.data
			.select( 'core/editor' )
			.getCurrentPostAttribute( 'meta' );
		if ( meta && meta._classifai_text_to_speech_error ) {
			showingNotice = true;
			const error = JSON.parse( meta._classifai_text_to_speech_error );
			wp.data
				.dispatch( 'core/notices' )
				.createErrorNotice(
					`Audio generation failed. Error: ${ error.code } - ${ error.message }`
				);
			saveHappened = false;
			showingNotice = false;
		}
	}
} );

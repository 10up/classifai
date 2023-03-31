/* eslint-disable no-unused-vars */
import { ReactComponent as icon } from '../../assets/img/block-icon.svg';
import { handleClick } from './helpers';
import { store as postAudioStore } from './store/register';

import { useState, useEffect, useRef } from '@wordpress/element';
import { store as noticesStore } from '@wordpress/notices';

const { useSelect, useDispatch } = wp.data;
const { PluginDocumentSettingPanel } = wp.editPost;
const { Icon, ToggleControl, Button, ButtonGroup, BaseControl } = wp.components;
const { __, sprintf } = wp.i18n;
const { registerPlugin } = wp.plugins;
const { classifaiPostData } = window;

/**
 * Create the ClassifAI icon
 */
const ClassifAIIcon = () => (
	<Icon className="components-panel__icon" icon={ icon } size={ 24 } />
);

/**
 * ClassifAIToggle Component.
 *
 */
const ClassifAIToggle = () => {
	// Use the datastore to retrieve all the meta for this post.
	const processContent = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute(
			'classifai_process_content'
		)
	);

	// Use the datastore to tell the post to update the meta.
	const { editPost } = useDispatch( 'core/editor' );
	const enabled = 'no' === processContent ? 'no' : 'yes';

	return (
		<ToggleControl
			label={ __( 'Process content on update', 'classifai' ) }
			help={
				'yes' === enabled
					? __(
						'ClassifAI language processing is enabled',
						'classifai'
					)
					: __(
						'ClassifAI language processing is disabled',
						'classifai'
					)
			}
			checked={ 'yes' === enabled }
			onChange={ ( value ) => {
				editPost( { classifai_process_content: value ? 'yes' : 'no' } );
			} }
		/>
	);
};

/**
 * Callback function to handle API response.
 *
 * @param {Object} resp
 */
const buttonClickCallBack = async ( resp ) => {
	const { select, dispatch } = wp.data;
	const postId = select( 'core/editor' ).getCurrentPostId();
	const postType = select( 'core/editor' ).getCurrentPostType();
	const postTypeLabel =
		select( 'core/editor' ).getPostTypeLabel() || __( 'Post', 'classifai' );

	if ( resp && resp.terms ) {
		let updateNeeded = false;
		const taxonomies = Object.keys( resp.terms );
		const taxTerms = {};
		taxonomies.forEach( ( taxonomy ) => {
			let tax = taxonomy;
			if ( 'post_tag' === taxonomy ) {
				tax = 'tags';
			}
			if ( 'category' === taxonomy ) {
				tax = 'categories';
			}

			const currentTerms =
				select( 'core/editor' ).getEditedPostAttribute( taxonomy ) ||
				[];
			const newTerms = [
				...currentTerms,
				...resp.terms[ taxonomy ].map( ( term ) =>
					Number.parseInt( term )
				),
			].filter( ( ele, i, a ) => a.indexOf( ele ) === i );

			if ( newTerms && newTerms.length ) {
				updateNeeded = true;
				taxTerms[ tax ] = newTerms;
			}
		} );

		if ( updateNeeded ) {
			// Check for edited values in post.
			const isDirty = await select( 'core/editor' ).isEditedPostDirty();
			await dispatch( 'core' ).editEntityRecord(
				'postType',
				postType,
				postId,
				taxTerms
			);
			// If no edited values in post trigger save.
			if ( ! isDirty ) {
				await dispatch( 'core' ).saveEditedEntityRecord(
					'postType',
					postType,
					postId
				);
			}

			// Display success notice.
			dispatch( 'core/notices' ).createSuccessNotice(
				sprintf(
					/** translators: %s is post type label. */
					__( '%s classified successfully.', 'classifai' ),
					postTypeLabel
				),
				{ type: 'snackbar' }
			);
		}
	}
};

/**
 *  Classify Post Button
 */
const ClassifAIGenerateTagsButton = () => {
	const processContent = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute(
			'classifai_process_content'
		)
	);
	// Display classify post button only when process content on update is disabled.
	const enabled = 'no' === processContent ? 'no' : 'yes';
	if ( 'yes' === enabled ) {
		return null;
	}

	const postId = wp.data.select( 'core/editor' ).getCurrentPostId();
	const postTypeLabel =
		wp.data.select( 'core/editor' ).getPostTypeLabel() ||
		__( 'Post', 'classifai' );
	const buttonText = sprintf(
		/** translators: %s Post type label */
		__( 'Classify %s', 'classifai' ),
		postTypeLabel
	);

	return (
		<>
			<Button
				variant={ 'secondary' }
				data-id={ postId }
				onClick={ ( e ) =>
					handleClick( {
						button: e.target,
						endpoint: '/classifai/v1/generate-tags/',
						callback: buttonClickCallBack,
						buttonText,
					} )
				}
			>
				{ buttonText }
			</Button>
			<span
				className="spinner"
				style={ { display: 'none', float: 'none' } }
			></span>
			<span
				className="error"
				style={ {
					display: 'none',
					color: '#bc0b0b',
					padding: '5px',
				} }
			></span>
		</>
	);
};

let errorCode = '';

/**
 * Calls the server-side method that synthesis speech for a post.
 *
 * @param {number} postId The Post ID
 * @return {boolean} Returns true on success.
 */
const synthesizeSpeech = async ( postId ) => {
	// Endpoint URL.
	const synthesizeSpeechUrl = `${ wpApiSettings.root }classifai/v1/synthesize-speech/${ postId }`;

	// Stores status of the synthensis process.
	const isProcessing = wp.data.select( postAudioStore ).getIsProcessing();

	// Return early if already processing.
	if ( isProcessing ) {
		return;
	}

	// Set state indicating the synthesis process has begun.
	wp.data.dispatch( postAudioStore ).setIsProcessing( true );

	const response = await fetch(
		synthesizeSpeechUrl,
		{
			headers: new Headers( {
				'X-WP-Nonce': wpApiSettings.nonce,
			} )
		}
	);

	// Return false if error.
	if ( 200 !== response.status ) {
		// Set state indicating the synthesis process has ended.
		wp.data.dispatch( postAudioStore ).setIsProcessing( false );
		return false;
	}

	const result = await response.json();

	if ( result.success ) {
		// Set audio ID state after successful synthesis.
		wp.data.dispatch( postAudioStore ).setAudioId( result.audio_id );

		// Set state indicating the synthesis process has ended.
		wp.data.dispatch( postAudioStore ).setIsProcessing( false );

		if ( errorCode ) {
			wp.data.dispatch( noticesStore ).removeNotice( errorCode );
			errorCode = '';
		}

		return true;
	} else {
		errorCode = result.code;
		wp.data.dispatch( 'core/notices' ).createErrorNotice( result.message, {
			id: errorCode
		} );
		wp.data.dispatch( postAudioStore ).setIsProcessing( false );
	}
};

/**
 * ClassifAI Text to Audio component.
 *
 * @param {Object} props Props object.
 */
const ClassifAITSpeechSynthesisToggle = ( props ) => {
	// State of the audio being previewed in PluginDocumentSettingPanel.
	const [ isPreviewing, setIsPreviewing ] = useState( false );

	const [ timestamp, setTimestamp ] = useState( new Date().getTime() );

	// Indicates whether speech synthesis is supported for the current post.
	let isFeatureSupported = false;

	// Indicates whether speech synthesis is enabled for the current post.
	const isSynthesizeSpeech = 'yes' === useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'classifai_synthesize_speech' ) );

	// Post type of the current post.
	const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType() );

	// Says if the post is currently being saved.
	const isSavingPost = useSelect( ( select ) => select( 'core/editor' ).isSavingPost() );

	// Says whether if speech synthesis is in progress.
	const isProcessingAudio = useSelect( ( select ) => select( postAudioStore ).getIsProcessing() );

	// Figure out if speech synthesis is supported by the current post.
	if ( classifaiTextToSpeechData && classifaiTextToSpeechData.supportedPostTypes.includes( postType ) ) {
		isFeatureSupported = true;
	}

	// The audio ID saved in the DB for the current post.
	const defaultAudioId = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'classifai_post_audio_id' ) );

	// New audio ID in case it is regenerated manually or through publishing/updating the current post.
	const audioId = props.audioId || defaultAudioId;

	// Get the attachment data by audio ID.
	const attachments = useSelect( ( select ) => select( 'core' ).getEntityRecords( 'postType', 'attachment', { include: [ audioId ] } ) );

	// Get URL for the attachment.
	const sourceUrl = attachments && attachments.length > 0 && attachments[ 0 ].source_url;

	const isProcessingAudioProgress = useRef( false );

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

	// Callback to refresh/regenrate audio manually.
	function refreshAudio() {
		if ( isSavingPost ) {
			return;
		}

		wp.data.dispatch( 'core/editor' ).savePost();
	}

	// Fetches the latest audio file to avoid disk cache.
	const cacheBustingUrl = `${ sourceUrl }?ver=${ timestamp }`;

	return (
		<>
			<ToggleControl
				label={ __( 'Generate audio for this post.' ) }
				help={ isFeatureSupported
					? __( 'ClassifAI will generate audio for the post when it is published or updated.', 'classifai' )
					: __( 'Text to Speech generation is disabled for this post type.', 'classifai' ) }
				checked={ isSynthesizeSpeech }
				onChange={ ( value ) => {
					wp.data.dispatch( 'core/editor' ).editPost( { classifai_synthesize_speech: value ? 'yes' : 'no' } );
				} }
				disabled={ ! isFeatureSupported }
			/>
			{ sourceUrl && <audio id="classifai-audio-preview" src={ cacheBustingUrl } onEnded={ () => setIsPreviewing( false ) }></audio> }
			{ sourceUrl && isSynthesizeSpeech && (
				<BaseControl
					id="classifai-audio-controls"
					label={ __( 'Audio controls', 'classifai' ) }
					help={ __( 'Helper controls to preview the audio and manually regenerate the audio without saving the post.', 'classifai' ) }
				>
					<div>
						<ButtonGroup>
							<Button
								icon={ <Icon icon="update" /> }
								variant="secondary"
								isBusy={ isProcessingAudio }
								onClick={ refreshAudio }
								disabled={ isPreviewing }
							>
								{ __( 'Refresh', 'classifai' ) }
							</Button>

							<Button
								icon={ <Icon icon={ isPreviewing ? 'controls-pause' : 'controls-play' } /> }
								variant="secondary"
								onClick={ () => setIsPreviewing( ! isPreviewing ) }
								disabled={ isProcessingAudio }
							>
								{ __( 'Preview', 'classifai' ) }
							</Button>
						</ButtonGroup>
					</div>
				</BaseControl>
			) }
		</>
	);
};

let isPostSavingInProgress = false;

// Synthesises audio for the post whenever a post is publish/updated.
wp.data.subscribe( function() {
	const isSynthesizeSpeech = 'yes' === wp.data.select( 'core/editor' ).getEditedPostAttribute( 'classifai_synthesize_speech' );

	if ( ! isSynthesizeSpeech ) {
		return;
	}

	// Says whether if post is saving?
	let isSavingPost = wp.data.select( 'core/editor' ).isSavingPost();

	// Says whether if post is autosaving?
	const isAutosavingPost = wp.data.select( 'core/editor' ).isAutosavingPost();

	// Current post ID.
	const postId = wp.data.select( 'core/editor' ).getCurrentPostId();

	// We want the speech synthesis to only happen on save and not autosave.
	isSavingPost = isSavingPost && ! isAutosavingPost;

	if ( isSavingPost && ! isPostSavingInProgress ) {
		isPostSavingInProgress = true;
	}

	if ( ! isSavingPost && isPostSavingInProgress ) {
		synthesizeSpeech( postId );
		isPostSavingInProgress = false;
	}
} );

/**
 * Add the ClassifAI panel to Gutenberg
 */
const ClassifAIPlugin = () => {
	const postType = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostType()
	);
	const postStatus = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostAttribute( 'status' )
	);

	const userHasPermissions = classifaiPostData && ! ( classifaiPostData.noPermissions && 1 === parseInt( classifaiPostData.noPermissions ) );
	const isLanguageProcessingEnabled = classifaiPostData && classifaiPostData.NLUEnabled;
	const isPosTypeSupported = classifaiPostData && classifaiPostData.supportedPostTypes && classifaiPostData.supportedPostTypes.includes( postType );
	const isPostStatusSupported = classifaiPostData && classifaiPostData.supportedPostStatues && classifaiPostData.supportedPostStatues.includes( postStatus );

	const defaultAudioId = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'classifai_post_audio_id' ) );
	const audioId = useSelect( ( select ) => select( postAudioStore ).getAudioId() ) || defaultAudioId;

	return (
		<PluginDocumentSettingPanel
			title={ __( 'ClassifAI', 'classifai' ) }
			icon={ ClassifAIIcon }
			className="classifai-panel"
		>
			<>
				{
					userHasPermissions &&
					isLanguageProcessingEnabled &&
					isPosTypeSupported &&
					isPostStatusSupported && (
						<>
							<ClassifAIToggle />
							<ClassifAIGenerateTagsButton />
						</>
					)
				}
			</>
			<ClassifAITSpeechSynthesisToggle audioId={ audioId } />
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'classifai-plugin', { render: ClassifAIPlugin } );

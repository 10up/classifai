/* eslint-disable no-unused-vars */
import { ReactComponent as icon } from '../../assets/img/block-icon.svg';
import { handleClick } from './helpers';
import { useSelect, useDispatch } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import {
	Button,
	Icon,
	ToggleControl,
	BaseControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { useState, useEffect, useRef } from '@wordpress/element';
import { store as postAudioStore } from './store/register';

const { classifaiEmbeddingData, classifaiPostData, classifaiTTSEnabled } =
	window;

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

/**
 * ClassifAI Text to Audio component.
 */
const ClassifAITTS = () => {
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
		<>
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
		</>
	);
};

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

	// Ensure that at least one feature is enabled.
	const isNLULanguageProcessingEnabled =
		classifaiPostData && classifaiPostData.NLUEnabled;

	const isEmbeddingProcessingEnabled =
		classifaiEmbeddingData && classifaiEmbeddingData.enabled;

	// Ensure we are on a supported post type, checking settings from all features.
	const isNLUPostTypeSupported =
		classifaiPostData &&
		classifaiPostData.supportedPostTypes &&
		classifaiPostData.supportedPostTypes.includes( postType );

	const isEmbeddingPostTypeSupported =
		classifaiEmbeddingData &&
		classifaiEmbeddingData.supportedPostTypes &&
		classifaiEmbeddingData.supportedPostTypes.includes( postType );

	// Ensure we are on a supported post status, checking settings from all features.
	const isNLUPostStatusSupported =
		classifaiPostData &&
		classifaiPostData.supportedPostStatues &&
		classifaiPostData.supportedPostStatues.includes( postStatus );

	const isEmbeddingPostStatusSupported =
		classifaiEmbeddingData &&
		classifaiEmbeddingData.supportedPostStatues &&
		classifaiEmbeddingData.supportedPostStatues.includes( postStatus );

	// Ensure the user has permissions to use the feature.
	const userHasNLUPermissions =
		classifaiPostData &&
		! (
			classifaiPostData.noPermissions &&
			1 === parseInt( classifaiPostData.noPermissions )
		);

	const userHasEmbeddingPermissions =
		classifaiEmbeddingData &&
		! (
			classifaiEmbeddingData.noPermissions &&
			1 === parseInt( classifaiEmbeddingData.noPermissions )
		);

	const nluPermissionCheck =
		userHasNLUPermissions &&
		isNLULanguageProcessingEnabled &&
		isNLUPostTypeSupported &&
		isNLUPostStatusSupported;

	const embeddingsPermissionCheck =
		userHasEmbeddingPermissions &&
		isEmbeddingProcessingEnabled &&
		isEmbeddingPostTypeSupported &&
		isEmbeddingPostStatusSupported;

	return (
		<PluginDocumentSettingPanel
			title={ __( 'ClassifAI', 'classifai' ) }
			icon={ ClassifAIIcon }
			className="classifai-panel"
		>
			<>
				{ ( nluPermissionCheck || embeddingsPermissionCheck ) && (
					<>
						<ClassifAIToggle />
						{ nluPermissionCheck && (
							<ClassifAIGenerateTagsButton />
						) }
					</>
				) }
				{ classifaiTTSEnabled && <ClassifAITTS /> }
			</>
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'classifai-plugin', { render: ClassifAIPlugin } );

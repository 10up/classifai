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
	Modal,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { useState, useEffect, useRef } from '@wordpress/element';
import { store as postAudioStore } from './store/register';
import TaxonomyControls from '../../includes/Classifai/Blocks/recommended-content-block/inspector-controls/taxonomy-controls';
import PrePubClassifyPost from './gutenberg-plugins/pre-publish-classify-post';

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
 *  Classify Post Button
 */
const ClassifAIGenerateTagsButton = () => {
	const processContent = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute(
			'classifai_process_content'
		)
	);

	const [ isLoading, setLoading ] = useState( false );
	const [ isOpen, setOpen ] = useState( false );
	const [ popupOpened, setPopupOpened ] = useState( false );
	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );

	const [ taxQuery, setTaxQuery ] = useState( [] );
	let [ taxTermsAI, setTaxTermsAI ] = useState( [] );

	const triggerCallRef = useRef( null );
	const triggerCallClick = () => {
		triggerCallRef?.current?.click();
		setLoading( true );
	};

	/**
	 * Callback function to handle API response.
	 *
	 * @param {Object} resp
	 */
	const buttonClickCallBack = async ( resp ) => {
		if ( resp && resp.terms ) {
			let termsReady = false;
			const taxonomies = resp.terms;
			const taxTerms = {};
			const taxTermsExisting = {};

			// get current terms of the post
			const { select } = wp.data;
			const postId = select( 'core/editor' ).getCurrentPostId();
			const postType = select( 'core/editor' ).getCurrentPostType();
			const currentTerms = select( 'core' ).getEntityRecord(
				'postType',
				postType,
				postId
			);

			Object.keys( taxonomies ).forEach( ( taxonomy ) => {
				let tax = taxonomy;
				if ( 'post_tag' === taxonomy ) {
					tax = 'tags';
				}
				if ( 'category' === taxonomy ) {
					tax = 'categories';
				}

				const currentTermsOfTaxonomy = currentTerms[ taxonomy ];
				if ( currentTermsOfTaxonomy ) {
					taxTermsExisting[ taxonomy ] = currentTermsOfTaxonomy;
				}

				const newTerms = Object.values( resp.terms[ taxonomy ] );
				if ( newTerms && Object.keys( newTerms ).length ) {
					termsReady = true;

					// Loop through each term and add in taxTermsAI if it does not exist in the post.
					taxTermsAI = taxTermsAI || {};
					Object( newTerms ).forEach( ( termId ) => {
						if ( taxTermsExisting[ tax ] ) {
							const matchedTerm = taxTermsExisting[ tax ].find(
								( termID ) => termID === termId
							);
							if ( ! matchedTerm ) {
								taxTermsAI[ tax ] = taxTermsAI[ tax ] || [];
								taxTermsAI[ tax ].push( termId );
							}
						}
					} );

					// update the taxTerms
					taxTerms[ tax ] = newTerms;
				}
			} );

			// Merge taxterms with taxTermsExisting and remove duplicates
			Object.keys( taxTermsExisting ).forEach( ( taxonomy ) => {
				if ( taxTerms[ taxonomy ] ) {
					// Merge taxTermsExisting into taxTerms
					taxTerms[ taxonomy ] = taxTerms[ taxonomy ].concat(
						taxTermsExisting[ taxonomy ]
					);
				} else {
					// Initialize taxTerms with taxTermsExisting if not already set
					taxTerms[ taxonomy ] = taxTermsExisting[ taxonomy ];
				}

				// Remove duplicate items from taxTerms
				taxTerms[ taxonomy ] = [ ...new Set( taxTerms[ taxonomy ] ) ];
			} );

			setTaxQuery( taxTerms );
			setTaxTermsAI( taxTermsAI );
		}
		setLoading( false );
	};

	/**
	 * Save the terms (Modal).
	 *
	 * @param {Object} taxTerms Taxonomy terms.
	 */
	const saveTerms = async ( taxTerms ) => {
		// Remove index values from the nested object
		// Convert the object into an array of key-value pairs
		const taxTermsArray = Object.entries( taxTerms );

		// Remove index values from the nested objects and convert back to an object
		const newtaxTerms = Object.fromEntries(
			taxTermsArray.map( ( [ key, value ] ) => {
				if ( typeof value === 'object' ) {
					return [ key, Object.values( value ) ];
				}
				return [ key, value ];
			} )
		);

		const { select, dispatch } = wp.data;
		const postId = select( 'core/editor' ).getCurrentPostId();
		const postType = select( 'core/editor' ).getCurrentPostType();
		const postTypeLabel =
			select( 'core/editor' ).getPostTypeLabel() ||
			__( 'Post', 'classifai' );

		await dispatch( 'core' ).editEntityRecord(
			'postType',
			postType,
			postId,
			newtaxTerms
		);

		// If no edited values in post trigger save.
		const isDirty = await select( 'core/editor' ).isEditedPostDirty();
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
		closeModal();
	};

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

	let updatedTaxQuery = Object.entries( taxQuery || {} ).reduce(
		( accumulator, [ taxonomySlug, terms ] ) => {
			accumulator[ taxonomySlug ] = terms;

			return accumulator;
		},
		{}
	);

	if ( updatedTaxQuery.taxQuery ) {
		updatedTaxQuery = updatedTaxQuery.taxQuery;
	}

	const modalData = (
		<>
			<TaxonomyControls
				onChange={ ( newTaxQuery ) => {
					setTaxQuery( newTaxQuery );
				} }
				query={ {
					contentPostType: 'page',
					taxQuery: updatedTaxQuery,
					taxTermsAI: taxTermsAI || {},
				} }
			/>
			<div className="classifai-modal__footer">
				<div className="classifai-modal__notes">
					{ __(
						'Note that the lists above include any pre-existing terms from this post.',
						'classifai'
					) }
					<br />
					{ __(
						'Al recommendations saved to this post will not include the "[AI]" text.',
						'classifai'
					) }
				</div>
				<Button
					variant={ 'secondary' }
					onClick={ () => saveTerms( updatedTaxQuery ) }
				>
					{ __( 'Save', 'classifai' ) }
				</Button>
			</div>
		</>
	);

	return (
		<div id='classify-post-componenet'>
			{ isOpen && (
				<Modal
					title={ __( 'Confirm Post Classification', 'classifai' ) }
					onRequestClose={ closeModal }
					isFullScreen={ false }
					className="classify-modal"
				>
					{ modalData }
				</Modal>
			) }
			<Button
				variant={ 'secondary' }
				data-id={ postId }
				onClick={ ( e ) => {
					handleClick( {
						button: e.target,
						endpoint: '/classifai/v1/generate-tags/',
						callback: buttonClickCallBack,
						buttonText,
						linkTerms: false,
					} );
					setPopupOpened( true );
					openModal();
				} }
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
			<Button
				variant={ 'secondary' }
				data-id={ postId }
				ref={ triggerCallRef }
				style={ { display: 'none' } }
				onClick={ ( e ) => {
					handleClick( {
						button: e.target,
						endpoint: '/classifai/v1/generate-tags/',
						callback: buttonClickCallBack,
						buttonText,
						linkTerms: false,
					} );
				} }
			>
				{ buttonText }
			</Button>
			<PrePubClassifyPost
				callback={ triggerCallClick }
				popupOpened={ popupOpened }
			>
				{ isLoading && (
					<span
						className="spinner classify is-active"
						style={ { float: 'none' } }
					></span>
				) }
				{ modalData }
			</PrePubClassifyPost>
		</div>
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

/* eslint-disable no-unused-vars */
import { ReactComponent as icon } from '../../assets/img/block-icon.svg';
import { handleClick } from './helpers';

import { useState, useEffect, useRef } from '@wordpress/element';

const { Icon } = wp.components;
const { useSelect, useDispatch } = wp.data;
const { PluginDocumentSettingPanel } = wp.editPost;
const { ToggleControl, Button, ClipboardButton, BaseControl, Spinner } = wp.components;
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
 * ClassifAI Text to Audio component.
 */
const ClassifAITSpeechSynthesisToggle = () => {

	const [ hasCopied, setHasCopied ] = useState( false );
	const [ isGeneratingAudio, setIsGeneratingAudio ] = useState( false );
	const { editPost } = useDispatch( 'core/editor' );

	const {
		synthesizeSpeech,
		audioFileUrl,
		isPostSaving,
		currentPostId,
		currentPostType,
	} = useSelect( ( select ) => {
		const {
			getEditedPostAttribute,
			isSavingPost,
			isAutosavingPost,
			getCurrentPostType,
		} = select( 'core/editor' );

		const { getCurrentPostId } = select( 'core/editor' );

		return {
			synthesizeSpeech: 'yes' === getEditedPostAttribute( 'classifai_synthesize_speech' ),
			audioFileUrl: getEditedPostAttribute( 'classifai_post_audio_url' ),
			isPostSaving: isSavingPost() && ! isAutosavingPost(),
			currentPostId: getCurrentPostId(),
			currentPostType: getCurrentPostType(),
		}
	} );

	const [ audioFileUrlState, setAudioFileUrlState ] = useState( audioFileUrl )

	let isFeatureEnabled = false;

	if ( classifaiTextToSpeechData && classifaiTextToSpeechData.supportedPostTypes.includes( currentPostType ) ) {
		isFeatureEnabled = true;
	}

	const isSaving = useRef( false );

	useEffect( () => {
		if ( isPostSaving && ! isSaving.current ) {
			isSaving.current = true;
		}

		if ( ! isPostSaving && isSaving.current ) {
			const synthesizeSpeechUrl = `${ wpApiSettings.root }classifai/v1/synthesize-speech/${ currentPostId }`;

			const synthesizeSpeech = async () => {
				setIsGeneratingAudio( true );
				const response = await fetch( synthesizeSpeechUrl );

				if ( 200 !== response.status ) {
					console.error( response.json() );
				}

				setAudioFileUrlState( await response.json() );
				setIsGeneratingAudio( false );
			};

			synthesizeSpeech();

			isSaving.current = false;
		}
	}, [ isPostSaving, isSaving.current ] );

	return (
		<>
			<ToggleControl
				label={ __( 'Generate audio for this post.' ) }
				help={ isFeatureEnabled
					? __( 'ClassifAI will generate audio for the post when it is published or updated.' )
					: __( 'Text to Speech generation is disabled for this post type.' ) }
				checked={ isFeatureEnabled && synthesizeSpeech }
				onChange={ ( value ) => {
					editPost( { classifai_synthesize_speech: value ? 'yes' : 'no' } )
				} }
				disabled={ ! isFeatureEnabled }
			/>

			{
				synthesizeSpeech && audioFileUrlState && (
					isGeneratingAudio ? (
						<>
							<Spinner />
							<span>{ __( 'Generating speech audio for the post...' ) }</span>
						</>
					) : (
						<BaseControl>
							<ClipboardButton
								text={ audioFileUrl }
								onCopy={ () => setHasCopied( true ) }
								onFinishCopy={ () => setHasCopied( false ) }
								variant="secondary"
								isSmall={ true }
							>
								{ hasCopied ? __( 'Copied!', 'classifai' ) : __( 'Copy post audio URL', 'classifai' ) }
							</ClipboardButton>
						</BaseControl>
					)
				)
			}
		</>
	);
;};

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
				<ClassifAITSpeechSynthesisToggle />
			</>
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'classifai-plugin', { render: ClassifAIPlugin } );

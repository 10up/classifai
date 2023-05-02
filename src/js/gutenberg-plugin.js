/* eslint-disable no-unused-vars */
import { ReactComponent as icon } from '../../assets/img/block-icon.svg';
import { handleClick } from './helpers';
import { useSelect, useDispatch } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { Button, Icon, ToggleControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

const { classifaiEmbeddingData, classifaiPostData } = window;

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

	if ( ! isNLULanguageProcessingEnabled && ! isEmbeddingProcessingEnabled ) {
		return null;
	}

	// Ensure we are on a supported post type, checking settings from all features.
	const isNLUPostTypeSupported =
		classifaiPostData &&
		classifaiPostData.supportedPostTypes &&
		classifaiPostData.supportedPostTypes.includes( postType );

	const isEmbeddingPostTypeSupported =
		classifaiEmbeddingData &&
		classifaiEmbeddingData.supportedPostTypes &&
		classifaiEmbeddingData.supportedPostTypes.includes( postType );

	if ( ! isNLUPostTypeSupported && ! isEmbeddingPostTypeSupported ) {
		return null;
	}

	// Ensure we are on a supported post status, checking settings from all features.
	const isNLUPostStatusSupported =
		classifaiPostData &&
		classifaiPostData.supportedPostStatues &&
		classifaiPostData.supportedPostStatues.includes( postStatus );

	const isEmbeddingPostStatusSupported =
		classifaiEmbeddingData &&
		classifaiEmbeddingData.supportedPostStatues &&
		classifaiEmbeddingData.supportedPostStatues.includes( postStatus );

	if ( ! isNLUPostStatusSupported && ! isEmbeddingPostStatusSupported ) {
		return null;
	}

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

	return (
		<PluginDocumentSettingPanel
			title={ __( 'ClassifAI', 'classifai' ) }
			icon={ ClassifAIIcon }
			className="classifai-panel"
		>
			<>
				{ ( userHasNLUPermissions || userHasEmbeddingPermissions ) && (
					<>
						<ClassifAIToggle />
						{ classifaiPostData.NLUEnabled && (
							<ClassifAIGenerateTagsButton />
						) }
					</>
				) }
			</>
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'classifai-plugin', { render: ClassifAIPlugin } );

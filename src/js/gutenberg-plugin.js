/* eslint-disable no-unused-vars */
import { ReactComponent as icon } from '../../assets/img/block-icon.svg';
import { handleClick } from './helpers';

const { Icon } = wp.components;
const { useSelect, useDispatch } = wp.data;
const { PluginDocumentSettingPanel } = wp.editPost;
const { ToggleControl, Button } = wp.components;
const { __ } = wp.i18n;
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
	const { select } = wp.data;
	const postId = select( 'core/editor' ).getCurrentPostId();
	const postType = select( 'core/editor' ).getCurrentPostType();

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
			const isDirty = await wp.data
				.select( 'core/editor' )
				.isEditedPostDirty();
			await wp.data
				.dispatch( 'core' )
				.editEntityRecord( 'postType', postType, postId, taxTerms );
			// If no edited values in post trigger save.
			if ( ! isDirty ) {
				await wp.data
					.dispatch( 'core' )
					.saveEditedEntityRecord( 'postType', postType, postId );
			}
		}
	}
};

/**
 *  Generate Tags Button
 */
const ClassifAIGenerateTagsButton = () => {
	const postId = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostId()
	);

	const processContent = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute(
			'classifai_process_content'
		)
	);
	const enabled = 'no' === processContent ? 'no' : 'yes';

	if ( 'yes' === enabled ) {
		return null;
	}

	return (
		<>
			<Button
				variant={ 'secondary' }
				data-id={ postId }
				showTooltip={ true }
				label={ __( 'Process content to generate tags.', 'classifai' ) }
				onClick={ ( e ) =>
					handleClick( {
						button: e.target,
						endpoint: '/classifai/v1/generate-tags/',
						callback: buttonClickCallBack,
						buttonText: __( 'Generate Tags', 'classifai' ),
					} )
				}
			>
				{ __( 'Generate Tags', 'classifai' ) }
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

	// Ensure the user has proper permissions
	if (
		classifaiPostData.noPermissions &&
		1 === parseInt( classifaiPostData.noPermissions )
	) {
		return null;
	}

	// Ensure that language processing is enabled.
	if ( ! classifaiPostData.NLUEnabled ) {
		return null;
	}

	// Ensure we are on a supported post type
	if (
		classifaiPostData.supportedPostTypes &&
		! classifaiPostData.supportedPostTypes.includes( postType )
	) {
		return null;
	}

	// Ensure we are on a supported post status
	if (
		classifaiPostData.supportedPostStatues &&
		! classifaiPostData.supportedPostStatues.includes( postStatus )
	) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			title={ __( 'ClassifAI', 'classifai' ) }
			icon={ ClassifAIIcon }
			className="classifai-panel"
		>
			<>
				<ClassifAIToggle />
				<ClassifAIGenerateTagsButton />
			</>
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'classifai-plugin', { render: ClassifAIPlugin } );

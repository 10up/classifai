/* eslint-disable no-unused-vars */
import PluginIcon from '../../assets/img/editor-icon.svg';

const { Icon } = wp.components;
const { useSelect, useDispatch } = wp.data;
const { PluginDocumentSettingPanel } = wp.editPost;
const { ToggleControl } = wp.components;
const { __ } = wp.i18n;
const { registerPlugin } = wp.plugins;
const { classifaiPostData } = window;

/**
 * Create the ClassifAI icon
 */
const ClassifAIIcon = () => (
	<Icon
		className="components-panel__icon"
		icon={ <PluginIcon /> }
		size={ 20 }
	/>
);

/**
 * ClassifAIToggle Component.
 *
 */
const ClassifAIToggle = () => {
	// Use the datastore to retrieve the all the meta for this post.
	const processContent = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'classifai_process_content' ) );

	// Use the datastore to tell the post to update the meta.
	const { editPost } = useDispatch( 'core/editor' );
	const enabled = ( 'no' === processContent ) ? 'no' : 'yes';

	return (
		<ToggleControl
			label={ __( 'Process content on save', 'classifai' ) }
			help={
				'yes' === enabled
					? __( 'Classifai language processing on save is enabled', 'classifai' )
					: __( 'Classifai language processing on save is disabled', 'classifai' )
			}
			checked={ 'yes' === enabled }
			onChange={ ( value ) => {
				editPost( { 'classifai_process_content': ( value ? 'yes' : 'no' ) } );
			}}
		/>
	);
};

/**
 * Add the ClassifAI panel to Gutenberg
 */
const ClassifAIPlugin = () => {
	// Ensure the user has proper permissions
	if ( classifaiPostData.noPermissions && 1 === parseInt( classifaiPostData.noPermissions ) ) {
		return null;
	}

	// Ensure that language processing is enabled.
	if ( ! classifaiPostData.NLUEnabled  ) {
		return null;
	}

	const postType = useSelect( select => select( 'core/editor' ).getCurrentPostType() );
	const postStatus = useSelect( select => select( 'core/editor' ).getCurrentPostAttribute( 'status' ) );

	// Ensure we are on a supported post type
	if ( classifaiPostData.supportedPostTypes && ! classifaiPostData.supportedPostTypes.includes( postType ) ) {
		return null;
	}

	// Ensure we are on a supported post status
	if ( classifaiPostData.supportedPostStatues && ! classifaiPostData.supportedPostStatues.includes( postStatus ) ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			title={ __( 'ClassifAI', 'classifai' ) }
			icon={ ClassifAIIcon }
			className="classifai-panel"
		>
			<ClassifAIToggle />
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'classifai-plugin', { render: ClassifAIPlugin } );

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { ClassificationToggle } from './classification-toggle';
import { ClassificationButton } from './classification-button';

const { classifaiPostData, ClassifaiEditorSettingPanel } = window;

const ClassificationPlugin = () => {
	const postType = useSelect( ( select ) =>
		select( editorStore ).getCurrentPostType()
	);
	const postStatus = useSelect( ( select ) =>
		select( editorStore ).getCurrentPostAttribute( 'status' )
	);

	// Ensure we are on a supported post type, checking settings from all features.
	const isNLUPostTypeSupported =
		classifaiPostData &&
		classifaiPostData.supportedPostTypes &&
		classifaiPostData.supportedPostTypes.includes( postType );

	// Ensure we are on a supported post status, checking settings from all features.
	const isNLUPostStatusSupported =
		classifaiPostData &&
		classifaiPostData.supportedPostStatues &&
		classifaiPostData.supportedPostStatues.includes( postStatus );

	// Ensure the user has permissions to use the feature.
	const userHasNLUPermissions =
		classifaiPostData &&
		! (
			classifaiPostData.noPermissions &&
			1 === parseInt( classifaiPostData.noPermissions )
		);

	const nluPermissionCheck =
		userHasNLUPermissions &&
		isNLUPostTypeSupported &&
		isNLUPostStatusSupported;

	return (
		<>
			{ nluPermissionCheck && (
				<ClassifaiEditorSettingPanel>
					<ClassificationToggle />
					<ClassificationButton />
				</ClassifaiEditorSettingPanel>
			) }
		</>
	);
};

registerPlugin( 'classifai-plugin-classification', {
	render: ClassificationPlugin,
} );

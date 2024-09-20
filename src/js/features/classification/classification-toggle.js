/**
 * Internal dependencies.
 */
import { ToggleControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * ClassificationToggle Component.
 *
 * Used to toggle the classification process on or off.
 */
export const ClassificationToggle = () => {
	// Use the datastore to retrieve all the meta for this post.
	const processContent = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute(
			'classifai_process_content'
		)
	);

	// Use the datastore to tell the post to update the meta.
	const { editPost } = useDispatch( 'core/editor' );
	const enabled = 'yes' === processContent ? 'yes' : 'no';

	return (
		<ToggleControl
			label={ __( 'Automatically tag content on update', 'classifai' ) }
			checked={ 'yes' === enabled }
			onChange={ ( value ) => {
				editPost( { classifai_process_content: value ? 'yes' : 'no' } );
			} }
		/>
	);
};

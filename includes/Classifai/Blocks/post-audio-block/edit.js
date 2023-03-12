import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Renders the Post to Audio block as a placeholder.
 *
 * @returns JSX
 */
export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'classifai-listen-to-post'
	} );

	function previewAudioAlert() {
		alert( __( 'This is only a placeholder.', 'classifai' ) );
	}

	return (
		<div { ...blockProps }>
			<div className='classifai-listen-to-post-wrapper'>
				<div class="class-post-audio-controls" onClick={ previewAudioAlert }>
					<span class="dashicons dashicons-controls-play"></span>
				</div>
				<div className='classifai-post-audio-heading'>{ __( 'Listen to this post', 'classifai' ) }</div>
			</div>
		</div>
	);
}

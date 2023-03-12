import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export default function Save() {
	const blockProps = useBlockProps.save();

	return (
		<div { ...blockProps }>
			<div className='classifai-listen-to-post-wrapper'>
				<div class="class-post-audio-controls">
					<span class="dashicons dashicons-controls-play"></span>
					<span class="dashicons dashicons-controls-pause" style={ { display: 'none' } }></span>
				</div>
				<div className='classifai-post-audio-heading'>{ __( 'Listen to this post', 'classifai' ) }</div>
			</div>
		</div>
	);
}
import { Button } from '@wordpress/components';
import { _nx } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

const allowedCoreBlocks = [
	'core/image',
	'core/gallery',
	'core/media-text',
	'core/cover',
];

/**
 * Adds a `Generate image` button to the media-related blocks.
 * @see {@link https://github.com/10up/classifai/issues/724}
 *
 * @param {React.ReactNode} Component The Wrapped Media upload component.
 * @return {React.ReactNode} The transformed Media upload component.
 */
function addImageGenerationLink( Component ) {
	return function ( props ) {
		const { render, ...rest } = props;
		let blockProps;

		try {
			blockProps = useBlockProps();
		} catch ( e ) {
			return <Component { ...props } />;
		}

		const { 'data-type': blockName } = blockProps;

		if ( ! allowedCoreBlocks.includes( blockName ) ) {
			return <Component { ...props } />;
		}

		let isSingle = 1;

		if ( blockName && 'core/gallery' === blockName ) {
			isSingle = Infinity;
		}

		return (
			<>
				<Component
					{ ...rest }
					mode="generate"
					render={ ( { open } ) => (
						<Button variant="secondary" onClick={ open }>
							{ _nx(
								'Generate image',
								'Generate images',
								isSingle,
								'Image or gallery upload',
								'classifai'
							) }
						</Button>
					) }
				/>
				<Component { ...props } />
			</>
		);
	};
}

wp.hooks.addFilter(
	'editor.MediaUpload',
	'classifai/image-generation-link',
	addImageGenerationLink
);

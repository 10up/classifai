/**
 * WordPress dependencies
 */
import { useBlockProps, BlockControls } from '@wordpress/block-editor';
import { ToolbarGroup } from '@wordpress/components';
import { list, grid } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

const BlockEdit = ( props ) => {
	const { attributes, setAttributes } = props;
	const { layout } = attributes;
	const blockProps = useBlockProps();

	const layoutControls = [
		{
			icon: list,
			title: __( 'List view', 'classifai' ),
			onClick: () => setAttributes( { layout: 'list' } ),
			isActive: layout === 'list',
		},
		{
			icon: grid,
			title: __( 'Paragraph view', 'classifai' ),
			onClick: () => setAttributes( { layout: 'paragraph' } ),
			isActive: layout === 'paragraph',
		},
	];

	return (
		<>
			<BlockControls>
				<ToolbarGroup controls={ layoutControls } />
			</BlockControls>
			<article { ...blockProps }>
				<div className="wp-block-classifai-key-takeways__content">
					CONTENT GOES HERE
				</div>
			</article>
		</>
	);
};

export default BlockEdit;

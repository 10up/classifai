/**
 * WordPress dependencies
 */
import { useBlockProps, BlockControls } from '@wordpress/block-editor';
import { ToolbarGroup } from '@wordpress/components';
import { postList, paragraph } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

const BlockEdit = ( props ) => {
	const { attributes, setAttributes } = props;
	const { render } = attributes;
	const blockProps = useBlockProps();

	const renderControls = [
		{
			icon: postList,
			title: __( 'List view', 'classifai' ),
			onClick: () => setAttributes( { render: 'list' } ),
			isActive: render === 'list',
		},
		{
			icon: paragraph,
			title: __( 'Paragraph view', 'classifai' ),
			onClick: () => setAttributes( { render: 'paragraph' } ),
			isActive: render === 'paragraph',
		},
	];

	return (
		<>
			<BlockControls>
				<ToolbarGroup controls={ renderControls } />
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

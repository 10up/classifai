/**
 * WordPress dependencies
 */
import { useBlockProps, BlockControls } from '@wordpress/block-editor';
import { select } from '@wordpress/data';
import { ToolbarGroup } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { postList, paragraph } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const BlockEdit = ( props ) => {
	const [ isLoading, setIsLoading ] = useState( false );
	const { attributes, setAttributes } = props;
	const { render, takeaways } = attributes;
	const blockProps = useBlockProps();

	useEffect( () => {
		if ( ! isLoading && takeaways.length === 0 ) {
			const postId = select( 'core/editor' ).getCurrentPostId();
			const postContent =
				select( 'core/editor' ).getEditedPostAttribute( 'content' );
			const postTitle =
				select( 'core/editor' ).getEditedPostAttribute( 'title' );

			setIsLoading( true );

			apiFetch( {
				path: '/classifai/v1/key-takeaways/',
				method: 'POST',
				data: {
					id: postId,
					content: postContent,
					title: postTitle,
					render,
				},
			} ).then(
				async ( res ) => {
					// Ensure takeaways is always an array.
					if ( ! Array.isArray( res ) ) {
						res = [ res ];
					}

					setAttributes( { takeaways: res } );
					setIsLoading( false );
				},
				( err ) => {
					setAttributes( {
						takeaways: [ `Error:  ${ err?.message }` ],
					} );
					setIsLoading( false );
				}
			);
		}
	}, [] );

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
			<div { ...blockProps }>
				<div className="wp-block-classifai-key-takeways__content">
					{ render === 'list' && (
						<ul>
							{ takeaways.map( ( takeaway, index ) => (
								<li key={ index }>{ takeaway }</li>
							) ) }
						</ul>
					) }
					{ render === 'paragraph' && (
						<>
							{ takeaways.map( ( takeaway, index ) => (
								<p key={ index }>{ takeaway }</p>
							) ) }
						</>
					) }
				</div>
			</div>
		</>
	);
};

export default BlockEdit;

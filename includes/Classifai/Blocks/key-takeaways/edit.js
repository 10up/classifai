/**
 * WordPress dependencies
 */
import {
	useBlockProps,
	BlockControls,
	InspectorControls,
	RichText,
} from '@wordpress/block-editor';
import { select } from '@wordpress/data';
import {
	Placeholder,
	ToolbarGroup,
	Spinner,
	PanelBody,
	Button,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { postList, paragraph } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { ReactComponent as icon } from '../../../../assets/img/block-icon.svg';

const BlockEdit = ( props ) => {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ run, setRun ] = useState( false );
	const { attributes, setAttributes } = props;
	const { render, takeaways, title } = attributes;
	const blockProps = useBlockProps();

	useEffect( () => {
		if ( ( ! isLoading && takeaways.length === 0 ) || run ) {
			const postId = select( 'core/editor' ).getCurrentPostId();
			const postContent =
				select( 'core/editor' ).getEditedPostAttribute( 'content' );
			const postTitle =
				select( 'core/editor' ).getEditedPostAttribute( 'title' );

			setRun( false );
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
	}, [ run ] );

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
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'classifai' ) }>
					<Button
						label={ __( 'Re-generate key takeaways', 'classifai' ) }
						text={ __( 'Refresh results', 'classifai' ) }
						variant={ 'secondary' }
						onClick={ () => setRun( true ) }
						isBusy={ isLoading }
					/>
				</PanelBody>
			</InspectorControls>

			{ isLoading && (
				<Placeholder
					icon={ icon }
					label={ __( 'Generating Key Takeaways', 'classifai' ) }
				>
					<Spinner
						style={ {
							height: 'calc(4px * 10)',
							width: 'calc(4px * 10)',
						} }
					/>
				</Placeholder>
			) }

			{ ! isLoading && (
				<div { ...blockProps }>
					<RichText
						tagName="h2"
						className="wp-block-heading wp-block-classifai-key-takeaways__title"
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
						placeholder="Key Takeaways"
					/>
					<div
						className="wp-block-classifai-key-takeways__content"
						style={ { fontStyle: 'italic' } }
					>
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
			) }
		</>
	);
};

export default BlockEdit;

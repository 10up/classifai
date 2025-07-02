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
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { ReactComponent as icon } from '../../../../assets/img/block-icon.svg';

const BlockEdit = ( props ) => {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ run, setRun ] = useState( false );
	const [ errors, setErrors ] = useState( [] );
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

			// If no content or the only content in the post is this block, don't make an API call.
			if (
				! postContent ||
				'<!-- wp:classifai/key-takeaways /-->' === postContent
			) {
				setErrors( [
					__(
						'No content found. Please add content then click the "Generate results" button.',
						'classifai'
					),
				] );
				setRun( false );
				return;
			}

			setRun( false );
			setIsLoading( true );
			setErrors( [] );

			apiFetch( {
				path: '/classifai/v1/key-takeaways/',
				method: 'POST',
				data: {
					id: postId,
					content: postContent,
					title: postTitle,
					render,
					run: run ? 'manual' : 'auto',
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
					setErrors( [ err?.message ] );
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

	const editTakeaways = ( index, value ) => {
		const newTakeaways = [ ...takeaways ];

		if ( ! value ) {
			newTakeaways.splice( index, 1 );
		} else {
			newTakeaways[ index ] = value;
		}

		setAttributes( {
			takeaways: newTakeaways,
		} );
	};

	const buttonText =
		takeaways.length > 0
			? __( 'Refresh results', 'classifai' )
			: __( 'Generate results', 'classifai' );

	return (
		<>
			<BlockControls>
				<ToolbarGroup controls={ renderControls } />
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'classifai' ) }>
					<Button
						label={ __( 'Generate key takeaways', 'classifai' ) }
						text={ buttonText }
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

			{ ! isLoading && errors.length > 0 && (
				<div { ...blockProps }>
					<Placeholder
						icon={ icon }
						label={ __( 'Key Takeaways', 'classifai' ) }
						isColumnLayout
					>
						{ ! errors[ 0 ].toLowerCase().includes( 'disabled' ) ? (
							<>
								<p
									style={ {
										color: '#cc1818',
										fontWeight: 'bold',
										marginBottom: 0,
									} }
								>
									{ __( 'Errors', 'classifai' ) }
								</p>
								<ul
									style={ {
										lineHeight: '1.5',
										marginTop: 0,
									} }
								>
									{ errors.map( ( error, index ) => (
										<li key={ index }>
											{ decodeEntities( error ) }
										</li>
									) ) }
								</ul>
							</>
						) : (
							errors.map( ( error, index ) => (
								<p key={ index }>{ decodeEntities( error ) }</p>
							) )
						) }
						<Button
							label={ __(
								'Generate key takeaways',
								'classifai'
							) }
							text={ buttonText }
							variant={ 'secondary' }
							onClick={ () => setRun( true ) }
							isBusy={ isLoading }
							style={ { width: '125px' } }
						/>
					</Placeholder>
				</div>
			) }

			{ ! isLoading && takeaways.length > 0 && errors.length === 0 && (
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
						className="wp-block-classifai-key-takeaways__content"
						style={ { fontStyle: 'italic' } }
					>
						{ render === 'list' && (
							<ul>
								{ takeaways.map( ( takeaway, index ) => (
									<RichText
										tagName="li"
										value={ takeaway }
										key={ index }
										onChange={ ( value ) =>
											editTakeaways( index, value )
										}
									/>
								) ) }
							</ul>
						) }
						{ render === 'paragraph' && (
							<>
								{ takeaways.map( ( takeaway, index ) => (
									<RichText
										tagName="p"
										value={ takeaway }
										key={ index }
										onChange={ ( value ) =>
											editTakeaways( index, value )
										}
									/>
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

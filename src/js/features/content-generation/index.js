/**
 * External Dependencies.
 */
import { dispatch, select } from '@wordpress/data';
import { PluginPostStatusInfo, PostTypeSupportCheck } from '@wordpress/editor';
import {
	Button,
	TextareaControl,
	Modal,
	Card,
	CardBody,
	CardFooter,
	Flex,
	FlexItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { useState, RawHTML } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { autop } from '@wordpress/autop';
import { rawHandler } from '@wordpress/blocks';

/**
 * Internal Dependencies.
 */
import { DisableFeatureButton } from '../../components';

const RenderError = ( { error } ) => {
	if ( ! error ) {
		return null;
	}

	return <div className="error">{ error }</div>;
};

const ContentGenerationPlugin = () => {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ isOpen, setOpen ] = useState( false );
	const [ error, setError ] = useState( false );
	const [ summary, setSummary ] = useState( '' );
	const [ conversation, setConversation ] = useState( [] );

	const openModal = () => setOpen( true );
	const closeModal = () => {
		setConversation( [] );
		setSummary( '' );
		setError( false );
		setOpen( false );
	};

	const startOver = () => {
		setConversation( [] );
		setSummary( '' );
		setError( false );
	};

	const postId = select( 'core/editor' ).getCurrentPostId();

	const getContent = async () => {
		const title = select( 'core/editor' ).getEditedPostAttribute( 'title' );

		setIsLoading( true );
		apiFetch( {
			path: '/classifai/v1/create-content',
			method: 'POST',
			data: { id: postId, summary, title },
		} ).then(
			async ( res ) => {
				setConversation( [
					...conversation,
					{
						prompt: summary,
						completion: res,
					},
				] );
				setError( false );
				setIsLoading( false );
			},
			( err ) => {
				setError( err?.message );
				setConversation( [] );
				setIsLoading( false );
			}
		);
	};

	const RenderData = ( { data: dataToRender } ) => {
		if ( dataToRender.length < 1 ) {
			return null;
		}

		// TODO: add an iterate button, allowing you to request changes to the generated content while keeping context.

		return (
			<>
				{ dataToRender.map( ( item, i ) => {
					if ( ! item.completion ) {
						return null;
					}

					return (
						<RenderCard
							key={ i }
							item={ item }
							i={ i }
							footer={ i === dataToRender.length - 1 }
						/>
					);
				} ) }
			</>
		);
	};

	const RenderCard = ( { item, i, footer } ) => {
		return (
			<Card key={ i }>
				<RenderCardBody item={ item } />
				{ footer && <RenderCardFooter item={ item } /> }
			</Card>
		);
	};

	const RenderCardBody = ( { item } ) => {
		return (
			<CardBody>
				{ <h2>{ item.prompt }</h2> }
				<RawHTML>{ autop( item.completion ) }</RawHTML>
			</CardBody>
		);
	};

	const RenderCardFooter = ( { item } ) => {
		return (
			<CardFooter justify="flex-end" isBorderless={ true }>
				<Button variant="tertiary" onClick={ startOver }>
					{ __( 'Start over', 'classifai' ) }
				</Button>
				<Button variant="secondary" onClick={ closeModal }>
					{ __( 'Cancel', 'classifai' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ () => {
						dispatch( 'core/editor' )
							.editPost( {
								content: '',
							} )
							.then( () => {
								dispatch( 'core/block-editor' ).insertBlocks(
									rawHandler( {
										HTML: autop( item.completion ),
									} )
								);
								closeModal();
							} );
					} }
				>
					{ __( 'Insert content', 'classifai' ) }
				</Button>
			</CardFooter>
		);
	};

	return (
		<PluginPostStatusInfo className="classifai-post-status">
			{ isOpen && (
				<Modal
					title={ __( 'Generate content', 'classifai' ) }
					onRequestClose={ closeModal }
					isFullScreen={ false }
					size="large"
					className="content-modal"
				>
					<TextareaControl
						rows="5"
						label={ __( 'Article Summary', 'classifai' ) }
						onChange={ ( value ) => {
							setSummary( value );
						} }
						value={ summary }
						disabled={ conversation.length >= 1 }
					/>
					{ conversation.length < 1 && (
						<Flex justify="flex-end">
							<FlexItem>
								<Button
									variant="secondary"
									onClick={ closeModal }
									disabled={ isLoading }
								>
									{ __( 'Cancel', 'classifai' ) }
								</Button>
							</FlexItem>
							<FlexItem>
								<Button
									variant="primary"
									onClick={ getContent }
									isBusy={ isLoading }
								>
									{ __( 'Submit', 'classifai' ) }
								</Button>
							</FlexItem>
						</Flex>
					) }
					{ ! isLoading && conversation.length >= 1 && (
						<RenderData data={ conversation } />
					) }
					{ ! isLoading && error && <RenderError error={ error } /> }
					{ ! isLoading && (
						<DisableFeatureButton feature="feature_content_generation" />
					) }
				</Modal>
			) }
			<PostTypeSupportCheck supportKeys="editor">
				<Button
					className="content"
					variant="secondary"
					onClick={ () => openModal() }
					isBusy={ isLoading }
				>
					{ __( 'Generate content', 'classifai' ) }
				</Button>
			</PostTypeSupportCheck>
		</PluginPostStatusInfo>
	);
};

registerPlugin( 'classifai-plugin-content-generation', {
	render: ContentGenerationPlugin,
} );

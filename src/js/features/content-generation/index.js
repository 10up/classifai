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

const RenderData = ( {
	data: dataToRender,
	closeModal,
	startOver,
	iterating,
	iterate,
	setSummary,
	summary,
	isLoading,
	getContent,
} ) => {
	if ( dataToRender.length < 1 ) {
		return null;
	}

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
						iterating={ iterating }
						iterate={ iterate }
						startOver={ startOver }
						closeModal={ closeModal }
					/>
				);
			} ) }

			{ iterating && (
				<>
					<TextareaControl
						rows="5"
						label={ __( 'Requested changes', 'classifai' ) }
						onChange={ ( value ) => {
							setSummary( value );
						} }
						value={ summary }
						disabled={ isLoading }
					/>
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
								disabled={ isLoading || ! summary }
							>
								{ __( 'Submit', 'classifai' ) }
							</Button>
						</FlexItem>
					</Flex>
				</>
			) }
		</>
	);
};

const RenderCard = ( {
	item,
	i,
	footer,
	iterating,
	iterate,
	startOver,
	closeModal,
} ) => {
	return (
		<Card key={ i } style={ { marginBottom: '1rem' } }>
			<RenderCardBody item={ item } />
			{ footer && ! iterating && (
				<RenderCardFooter
					item={ item }
					iterate={ iterate }
					startOver={ startOver }
					closeModal={ closeModal }
				/>
			) }
		</Card>
	);
};

const RenderCardBody = ( { item } ) => {
	return (
		<CardBody>
			<Flex justify="flex-end" direction="column">
				<FlexItem style={ { alignSelf: 'flex-end' } }>
					<h2>{ __( 'User', 'classifai' ) }</h2>
					<p>{ item.prompt }</p>
				</FlexItem>
				<FlexItem style={ { alignSelf: 'flex-start' } }>
					<h2>{ __( 'AI', 'classifai' ) }</h2>
					<RawHTML>{ autop( item.completion ) }</RawHTML>
				</FlexItem>
			</Flex>
		</CardBody>
	);
};

const RenderCardFooter = ( { item, iterate, startOver, closeModal } ) => {
	return (
		<CardFooter justify="flex-end" isBorderless={ true }>
			<Button variant="tertiary" onClick={ iterate }>
				{ __( 'Request changes', 'classifai' ) }
			</Button>
			<Button variant="tertiary" isDestructive onClick={ startOver }>
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

const ContentGenerationPlugin = () => {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ isOpen, setOpen ] = useState( false );
	const [ error, setError ] = useState( false );
	const [ summary, setSummary ] = useState( '' );
	const [ conversation, setConversation ] = useState( [] );
	const [ iterating, setIterating ] = useState( false );

	const iterate = () => {
		setIterating( true );
		setSummary( '' );
	};

	const openModal = () => setOpen( true );
	const closeModal = () => {
		setConversation( [] );
		setSummary( '' );
		setError( false );
		setIterating( false );
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
			data: { id: postId, summary, title, conversation },
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
				setIterating( false );
				setIsLoading( false );
			},
			( err ) => {
				setError( err?.message );
				setIsLoading( false );
			}
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
					{ conversation.length < 1 && (
						<TextareaControl
							rows="5"
							label={ __( 'Article summary', 'classifai' ) }
							onChange={ ( value ) => {
								setSummary( value );
							} }
							value={ summary }
							disabled={ isLoading }
						/>
					) }

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
									disabled={ isLoading || ! summary }
								>
									{ __( 'Submit', 'classifai' ) }
								</Button>
							</FlexItem>
						</Flex>
					) }

					{ conversation.length >= 1 && (
						<RenderData
							data={ conversation }
							closeModal={ closeModal }
							startOver={ startOver }
							iterating={ iterating }
							iterate={ iterate }
							setSummary={ setSummary }
							summary={ summary }
							isLoading={ isLoading }
							getContent={ getContent }
						/>
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

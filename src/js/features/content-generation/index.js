/**
 * External Dependencies.
 */
import { select } from '@wordpress/data';
import { PluginPostStatusInfo, PostTypeSupportCheck } from '@wordpress/editor';
import {
	Button,
	TextareaControl,
	Modal,
	Flex,
	FlexItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal Dependencies.
 */
import { DisableFeatureButton } from '../../components';
import { RenderData, RenderError } from './components';

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

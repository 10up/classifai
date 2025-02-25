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
	const [ contents, setContents ] = useState( '' );
	const openModal = () => setOpen( true );
	const closeModal = () => {
		setContents( '' );
		setSummary( '' );
		setError( false );
		setOpen( false );
	};

	const startOver = () => {
		setContents( '' );
		setSummary( '' );
		setError( false );
	};

	const postId = select( 'core/editor' ).getCurrentPostId();

	const buttonClick = async () => {
		const title = select( 'core/editor' ).getEditedPostAttribute( 'title' );

		setIsLoading( true );
		apiFetch( {
			path: '/classifai/v1/create-content',
			method: 'POST',
			data: { id: postId, summary, title },
		} ).then(
			async ( res ) => {
				setContents( res );
				setError( false );
				setIsLoading( false );
			},
			( err ) => {
				setError( err?.message );
				setContents( '' );
				setIsLoading( false );
			}
		);
	};

	const RenderData = ( { data: dataToRender } ) => {
		if ( ! dataToRender ) {
			return null;
		}

		dataToRender = autop( dataToRender );

		return (
			<>
				<Card>
					<CardBody>
						<RawHTML>{ dataToRender }</RawHTML>
					</CardBody>
					<CardFooter justify="flex-end" isBorderless={ true }>
						<Button variant="tertiary" onClick={ startOver }>
							{ __( 'Start over', 'classifai' ) }
						</Button>
						<Button variant="tertiary" onClick={ closeModal }>
							{ __( 'Iterate', 'classifai' ) }
						</Button>
						<Button variant="secondary" onClick={ closeModal }>
							{ __( 'Cancel', 'classifai' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ () => {
								dispatch( 'core/editor' ).editPost( {
									content: '',
								} );
								dispatch( 'core/block-editor' ).insertBlocks(
									rawHandler( {
										HTML: dataToRender,
									} )
								);
								closeModal();
							} }
						>
							{ __( 'Insert content', 'classifai' ) }
						</Button>
					</CardFooter>
				</Card>
			</>
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
						label={ __( 'Summary', 'classifai' ) }
						onChange={ ( value ) => setSummary( value ) }
						value={ summary }
					/>
					{ ! contents && (
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
									onClick={ buttonClick }
									isBusy={ isLoading }
								>
									{ __( 'Submit', 'classifai' ) }
								</Button>
							</FlexItem>
						</Flex>
					) }
					{ ! isLoading && contents && (
						<RenderData data={ contents } />
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

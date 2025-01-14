/**
 * External Dependencies.
 */
import { dispatch, select } from '@wordpress/data';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { PostTypeSupportCheck } from '@wordpress/editor';
import {
	Button,
	Modal,
	Spinner,
	TextareaControl,
	BaseControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal Dependencies.
 */
import { DisableFeatureButton } from '../../components';
import { browserAITextGeneration } from '../../helpers';

const { classifaiChatGPTData } = window;

const RenderError = ( { error } ) => {
	if ( ! error ) {
		return null;
	}

	return <div className="error">{ error }</div>;
};

const TitleGenerationPlugin = () => {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ isOpen, setOpen ] = useState( false );
	const [ error, setError ] = useState( false );
	const [ data, setData ] = useState( [] );

	if ( ! classifaiChatGPTData || ! classifaiChatGPTData.enabledFeatures ) {
		return null;
	}

	// Ensure the user has proper permissions
	if (
		classifaiChatGPTData.noPermissions &&
		1 === parseInt( classifaiChatGPTData.noPermissions )
	) {
		return null;
	}

	const postId = select( 'core/editor' ).getCurrentPostId();
	const postType = select( 'core/editor' ).getCurrentPostType();
	const postContent =
		select( 'core/editor' ).getEditedPostAttribute( 'content' );
	const openModal = () => setOpen( true );
	const closeModal = () =>
		setOpen( false ) && setData( [] ) && setError( false );

	const buttonClick = async ( path ) => {
		setIsLoading( true );
		openModal();
		apiFetch( {
			path,
			method: 'POST',
			data: { id: postId, content: postContent },
		} ).then(
			async ( res ) => {
				// Support calling a function from the response for browser AI.
				if ( typeof res === 'object' && res.hasOwnProperty( 'func' ) ) {
					res = await browserAITextGeneration(
						res.func,
						res?.prompt,
						res?.content
					);
					res = [ res.trim() ];
				}

				setData( res );
				setError( false );
				setIsLoading( false );
			},
			( err ) => {
				setError( err?.message );
				setData( [] );
				setIsLoading( false );
			}
		);
	};

	const RenderData = ( { data: dataToRender } ) => {
		if ( ! dataToRender ) {
			return null;
		}

		return (
			<>
				{ dataToRender.map( ( item, i ) => {
					return (
						<div className="classifai-title" key={ i }>
							<BaseControl>
								<TextareaControl
									rows="5"
									width="100%"
									value={ item }
									onChange={ ( e ) => {
										dataToRender[ i ] = e.target.value;
										setData( dataToRender );
									} }
								/>
								<Button
									variant="secondary"
									onClick={ async () => {
										const isDirty =
											select(
												'core/editor'
											).isEditedPostDirty();
										dispatch( 'core/editor' ).editPost( {
											title: data[ i ],
										} );
										closeModal();
										if ( ! isDirty ) {
											await dispatch(
												'core'
											).saveEditedEntityRecord(
												'postType',
												postType,
												postId
											);
										}
									} }
								>
									{ __( 'Select', 'classifai' ) }
								</Button>
							</BaseControl>
							<br />
						</div>
					);
				} ) }
			</>
		);
	};

	return (
		<PluginPostStatusInfo className="classifai-post-status">
			{ isOpen && (
				<Modal
					title={ __( 'Select a title', 'classifai' ) }
					onRequestClose={ closeModal }
					isFullScreen={ false }
					className="title-modal"
				>
					{ isLoading && <Spinner /> }
					{ ! isLoading && data && <RenderData data={ data } /> }
					{ ! isLoading && error && <RenderError error={ error } /> }
					{ ! isLoading && (
						<DisableFeatureButton feature="feature_title_generation" />
					) }
				</Modal>
			) }
			{ classifaiChatGPTData.enabledFeatures.map( ( feature ) => {
				const path = feature?.path;
				return (
					<PostTypeSupportCheck
						key={ feature?.feature }
						supportKeys={ feature?.feature }
					>
						<Button
							className={ feature?.feature }
							variant="secondary"
							onClick={ () => buttonClick( path ) }
						>
							{ feature?.buttonText }
						</Button>
					</PostTypeSupportCheck>
				);
			} ) }
		</PluginPostStatusInfo>
	);
};

registerPlugin( 'classifai-plugin-title-generation', {
	render: TitleGenerationPlugin,
} );

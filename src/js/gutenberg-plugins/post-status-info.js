import { dispatch, select } from '@wordpress/data';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { PostTypeSupportCheck } from '@wordpress/editor';
import { Button, Modal, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const { classifaiChatGPTData } = window;

const RenderError = ( { error } ) => {
	if ( ! error ) {
		return null;
	}

	return <div className="error">{ error }</div>;
};

const PostStatusInfo = () => {
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
			( res ) => {
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
							<textarea
								rows="5"
								onChange={ ( e ) => {
									dataToRender[ i ] = e.target.value;
									setData( dataToRender );
								} }
							>
								{ item }
							</textarea>
							<Button
								variant="secondary"
								onClick={ () => {
									dispatch( 'core/editor' ).editPost( {
										title: data[ i ],
									} );
									closeModal();
								} }
							>
								{ __( 'Select', 'classifai' ) }
							</Button>
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

registerPlugin( 'classifai-status-info', { render: PostStatusInfo } );

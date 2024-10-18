/**
 * WordPress dependencies
 */
import { Card, CardHeader, CardBody, Notice } from '@wordpress/components';
import { useState, useEffect, useContext } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { normalizeScore } from './utils';
import { PreviewerProviderContext } from './context';
import { STORE_NAME } from '../../../data/store';

/**
 * React Component for displaying Azure OpenAI Embeddings classification results.
 *
 * This component is responsible for rendering the classification results obtained from the Azure OpenAI Embeddings service.
 * It displays detailed classification data including categories and tags for a specific post.
 *
 * @param {Object} props        The component props.
 * @param {number} props.postId The ID of the post for which to display the classification results.
 *
 * @return {React.ReactElement} The AzureOpenAIEmbeddingsResults component.
 */
export function AzureOpenAIEmbeddingsResults( { postId } ) {
	const {
		isPreviewUnderProcess,
		setPreviewUnderProcess,
		setIsPreviewerOpen,
	} = useContext( PreviewerProviderContext );

	const [ responseData, setResponseData ] = useState( [] );
	const [ errorMessage, setErrorMessage ] = useState( '' );
	const settings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);

	useEffect( () => {
		// Reset previous results.
		if ( isPreviewUnderProcess ) {
			setResponseData( [] );
		}
	}, [ isPreviewUnderProcess ] );

	useEffect( () => {
		if ( ! postId ) {
			return;
		}

		setPreviewUnderProcess( true );
		setIsPreviewerOpen( true );
		setErrorMessage( '' );

		const formData = new FormData();

		formData.append( 'post_id', postId );
		formData.append(
			'action',
			'get_post_classifier_embeddings_preview_data'
		);

		formData.append( 'nonce', classifAISettings.nonce );

		( async () => {
			const response = await fetch( ajaxurl, {
				method: 'POST',
				body: formData,
			} );

			if ( ! response.ok ) {
				return;
			}

			const responseJSON = await response.json();

			if ( responseJSON.success ) {
				setResponseData( responseJSON.data );
			} else {
				setErrorMessage( responseJSON.data );
			}

			setPreviewUnderProcess( false );
		} )();
	}, [ postId ] );

	const card = Object.keys( responseData ).map( ( taxSlug ) => {
		const tags = responseData[ taxSlug ].data.map( ( tag, _index ) => {
			const threshold = settings[ `${ taxSlug }_threshold` ];
			const score = normalizeScore( tag.score );

			const scoreClass =
				score >= threshold
					? 'classifai__classification-previewer-result-tag--exceeds-threshold'
					: '';

			return (
				<div
					className={ `classifai__classification-previewer-result-tag ${ scoreClass }` }
					key={ _index }
				>
					<span className="classifai__classification-previewer-result-tag-score">
						{ score }%
					</span>
					<span className="classifai__classification-previewer-result-tag-label">
						{ tag.label }
					</span>
				</div>
			);
		} );

		return (
			<Card
				className="classifai__classification-previewer-result-card"
				key={ taxSlug }
			>
				<CardHeader>
					<h2 className="classifai__classification-previewer-result-card-heading">
						{ responseData[ taxSlug ].label }
					</h2>
				</CardHeader>
				<CardBody>
					{ tags.length
						? tags
						: sprintf(
								/* translators: %s: taxonomy label */
								__(
									`No classification data found for %s.`,
									'classifai'
								),
								responseData[ taxSlug ].label
						  ) }
				</CardBody>
			</Card>
		);
	} );

	if ( errorMessage ) {
		return (
			<Notice
				status="error"
				isDismissible={ false }
				className="classifai__classification-previewer-result-notice"
			>
				{ errorMessage }
			</Notice>
		);
	}

	return card.length ? (
		<>
			<Notice
				status="success"
				isDismissible={ false }
				className="classifai__classification-previewer-result-notice"
			>
				{ __(
					'Results for each taxonomy are sorted in descending order, starting with the term that has the highest score, indicating the best match based on the embedding data.',
					'classifai'
				) }
			</Notice>
			{ card }
		</>
	) : null;
}

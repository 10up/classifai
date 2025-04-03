/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { Fill, Notice } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../data/store';

/**
 * Component for Notice about the deprecation of the Personalizer provider.
 *
 * This component displays a notice to inform users about the deprecation of the Personalizer provider.
 *
 * @return {React.ReactElement} The PersonalizerDeprecationNotice component.
 */
const PersonalizerDeprecationNotice = () => (
	<Notice
		status="warning"
		isDismissible={ false }
		className="personalizer-deprecation-notice"
	>
		<p>
			<a
				href="https://learn.microsoft.com/en-us/azure/ai-services/personalizer/"
				target="_blank"
				rel="noreferrer"
			>
				{ __( 'As of September 2023', 'classifai' ) }
			</a>
			{ ', ' }
			{ __(
				'new Personalizer resources can no longer be created in Azure. This means you will not be able to use that as a Provider for the Recommended Content Feature unless you had previously created a Personalizer resource. The Azure AI Personalizer Provider is deprecated and will be removed in a future release.',
				'classifai'
			) }
		</p>
	</Notice>
);

/**
 * Component for the Recommended Content settings.
 *
 * This component displays a notice to inform users about the deprecation of the Personalizer provider.
 *
 * @return {React.ReactElement} The RecommendedContentSettings component.
 */
export const RecommendedContentSettings = () => {
	const [ embedInProgress, setEmbedInProgress ] = useState( false );
	const isEmbeddingInProgress = useRef( false );
	const isSaving = useSelect( ( select ) =>
		select( STORE_NAME ).getIsSaving()
	);
	const { createSuccessNotice } = useDispatch( noticesStore );

	// Check if embeddings are in progress
	useEffect( () => {
		if ( ! isSaving ) {
			const getEmbeddingsInProgress = async () => {
				try {
					const res = await apiFetch( {
						path: '/classifai/v1/embeddings_in_progress/recommended_content',
					} );

					if ( res?.classifAIEmbedInProgress ) {
						setEmbedInProgress( true );
						isEmbeddingInProgress.current = true;
					} else {
						setEmbedInProgress( false );
						clearInterval( intervalId );

						if ( isEmbeddingInProgress.current ) {
							createSuccessNotice(
								__(
									'Generation of embeddings is completed.',
									'classifai'
								),
								{
									id: 'success-feature_recommended_content',
								}
							);
						}

						isEmbeddingInProgress.current = false;
					}
				} catch ( error ) {}
			};

			const intervalId = setInterval( getEmbeddingsInProgress, 10000 );
			getEmbeddingsInProgress();

			return () => clearInterval( intervalId );
		}
	}, [ isSaving, createSuccessNotice ] );

	return (
		<Fill name="ClassifAIBeforeFeatureSettingsPanel">
			<PersonalizerDeprecationNotice />
			{ embedInProgress && (
				<Notice status="info" isDismissible={ false }>
					{ __(
						'Generation of embeddings is in progress.',
						'classifai'
					) }
				</Notice>
			) }
		</Fill>
	);
};

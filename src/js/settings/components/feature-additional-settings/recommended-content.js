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
 * Component for the Recommended Content settings.
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
				} catch {}
			};

			const intervalId = setInterval( getEmbeddingsInProgress, 10000 );
			getEmbeddingsInProgress();

			return () => clearInterval( intervalId );
		}
	}, [ isSaving, createSuccessNotice ] );

	return (
		<Fill name="ClassifAIBeforeFeatureSettingsPanel">
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

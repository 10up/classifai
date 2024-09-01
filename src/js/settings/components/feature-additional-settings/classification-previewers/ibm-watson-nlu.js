import {
	Card,
	CardHeader,
	CardBody,
	Notice,
	__experimentalHeading as Heading
} from '@wordpress/components';
import { useState, useEffect, useContext } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { normalizeScore } from './utils';
import { PreviewerProviderContext } from './context';
import { __ } from '@wordpress/i18n';

import { STORE_NAME } from '../../../data/store';

export function IBMWatsonNLUResults( { postId } ) {
	const {
		isPreviewUnderProcess,
		setPreviewUnderProcess,
		setIsPreviewerOpen,
	} = useContext( PreviewerProviderContext );

	const [ responseData, setResponseData ] = useState( null );
	const [ errorMessage, setErrorMessage ] = useState( '' );
	const settings = useSelect( ( select ) => select( STORE_NAME ).getFeatureSettings() );

	const taxMap = {
		categories: 'category',
		concepts: 'concept',
		entities: 'entity',
		keywords: 'keyword',
	};

	function formatLabel( label ) {
		return label
			.split( '/' )
			.filter( ( i ) => '' !== i )
			.join( ', ' );
	}

	useEffect( () => {
		// Reset previous results.
		if ( isPreviewUnderProcess ) {
			setResponseData( null );
		}
	}, [ isPreviewUnderProcess ] );

	useEffect( () => {
		if ( ! postId ) {
			return;
		}

		setPreviewUnderProcess( true );
		setIsPreviewerOpen( true );

		const formData = new FormData();

		formData.append( 'action', 'get_post_classifier_preview_data' );
		formData.append( 'post_id', postId );
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
		} )()
	}, [] );

	if ( ! responseData ) {
		return null;
	}

	const renderData = {
		categories: responseData.categories,
		concepts: responseData.concepts,
		entities: responseData.entities,
		keywords: responseData.keywords,
	};

	const card = Object.keys( renderData ).map( ( taxSlug ) => {
		const tags = renderData[ taxSlug ].map( ( tag, _index ) => {
			const threshold = settings[ `${ taxMap[ taxSlug ] }_threshold` ];
			const score = normalizeScore( tag.score || tag.relevance );

			const scoreClass = score >= threshold ? 'classifai__classification-previewer-result-tag--exceeds-threshold' : '';

			return (
				<div className={ `classifai__classification-previewer-result-tag ${ scoreClass }` } key={ _index }>
					<span className='classifai__classification-previewer-result-tag-score'>{ score }%</span>
					<span className='classifai__classification-previewer-result-tag-label'>{ formatLabel( tag.label || tag.text ) }</span>
				</div>
			);
		} );

		return (
			<Card className='classifai__classification-previewer-result-card' key={ taxSlug }>
				<CardHeader>
					<Heading className='classifai__classification-previewer-result-card-heading'>
						{ taxSlug }
					</Heading>
				</CardHeader>
				<CardBody>
					{ tags.length ? tags : __( `No classification data found for ${ taxSlug }.`, 'classifai' ) }
				</CardBody>
			</Card>
		)
	} );

	return card.length ? (
		<>
			<Notice status='success' isDismissible={ false } className='classifai__classification-previewer-result-notice'>
				{ __( 'Results for each category are sorted in descending order, starting with the term that has the highest score, indicating the best match based on the embedding data.', 'classifai' ) }
			</Notice>
			{ card }
		</>
	) : null
}

/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { useSelect, dispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import PrePubClassifyPost from './pre-publish-classify-post';
import TaxonomyControls from './taxonomy-controls';
import { DisableFeatureButton } from '../../components';
import { handleClick } from '../../../js/helpers';

const PrePublishClassificationContent = () => {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ resultReceived, setResultReceived ] = useState( false );
	const [ featureTaxonomies, setFeatureTaxonomies ] = useState( [] );
	const [ taxQuery, setTaxQuery ] = useState( {} );
	const [ isSaved, setIsSaved ] = useState( false );

	let [ taxTermsAI, setTaxTermsAI ] = useState( {} );
	const { postType, postId, postTypeLabel } = useSelect( ( select ) => {
		const { getCurrentPostType, getCurrentPostId, getPostTypeLabel } =
			select( 'core/editor' );
		const currentPostType = getCurrentPostType();
		const currentPostId = getCurrentPostId();

		return {
			postType: currentPostType,
			postId: currentPostId,
			postTypeLabel: getPostTypeLabel() || __( 'Post', 'classifai' ),
		};
	} );

	const buttonText = __( 'Suggest terms & tags', 'classifai' );

	const buttonClickCallBack = async ( resp ) => {
		if ( resp && resp.terms ) {
			if ( resp?.feature_taxonomies ) {
				setFeatureTaxonomies( resp.feature_taxonomies );
			}

			const taxonomies = resp.terms;
			const taxTerms = {};
			const taxTermsExisting = {};

			// get current terms of the post
			const currentTerms = wp.data
				.select( 'core' )
				.getEntityRecord( 'postType', postType, postId );

			Object.keys( taxonomies ).forEach( ( taxonomy ) => {
				let tax = taxonomy;
				if ( 'post_tag' === taxonomy ) {
					tax = 'tags';
				}
				if ( 'category' === taxonomy ) {
					tax = 'categories';
				}

				const currentTermsOfTaxonomy = currentTerms[ tax ];
				if ( currentTermsOfTaxonomy ) {
					taxTermsExisting[ tax ] = currentTermsOfTaxonomy;
				}

				const newTerms = Object.values( resp.terms[ taxonomy ] );
				if ( newTerms && Object.keys( newTerms ).length ) {
					// Loop through each term and add in taxTermsAI if it does not exist in the post.
					taxTermsAI = taxTermsAI || {};
					Object( newTerms ).forEach( ( termId ) => {
						if ( taxTermsExisting[ tax ] ) {
							const matchedTerm = taxTermsExisting[ tax ].find(
								( termID ) => termID === termId
							);
							if ( ! matchedTerm ) {
								taxTermsAI[ tax ] = taxTermsAI[ tax ] || [];
								// push only if not exist already
								if ( ! taxTermsAI[ tax ].includes( termId ) ) {
									taxTermsAI[ tax ].push( termId );
								}
							}
						}
					} );

					// update the taxTerms
					taxTerms[ tax ] = newTerms;
				}
			} );

			// Merge taxterms with taxTermsExisting and remove duplicates
			Object.keys( taxTermsExisting ).forEach( ( taxonomy ) => {
				if ( taxTerms[ taxonomy ] ) {
					// Merge taxTermsExisting into taxTerms
					taxTerms[ taxonomy ] = taxTerms[ taxonomy ].concat(
						taxTermsExisting[ taxonomy ]
					);
				} else {
					// Initialize taxTerms with taxTermsExisting if not already set
					taxTerms[ taxonomy ] = taxTermsExisting[ taxonomy ];
				}

				// Remove duplicate items from taxTerms
				taxTerms[ taxonomy ] = [ ...new Set( taxTerms[ taxonomy ] ) ];
			} );

			setTaxQuery( taxTerms );
			setTaxTermsAI( taxTermsAI );
		}
		setIsLoading( false );
		setResultReceived( true );
	};

	/**
	 * Save the terms (Modal).
	 *
	 * @param {Object} taxTerms Taxonomy terms.
	 */
	const saveTerms = async ( taxTerms ) => {
		// Remove index values from the nested object
		// Convert the object into an array of key-value pairs
		const taxTermsArray = Object.entries( taxTerms );

		// Remove index values from the nested objects and convert back to an object
		const newtaxTerms = Object.fromEntries(
			taxTermsArray.map( ( [ key, value ] ) => {
				if ( typeof value === 'object' ) {
					return [ key, Object.values( value ) ];
				}
				return [ key, value ];
			} )
		);

		await dispatch( 'core' ).editEntityRecord(
			'postType',
			postType,
			postId,
			newtaxTerms
		);

		// If no edited values in post trigger save.
		const isDirty = await wp.data
			.select( 'core/editor' )
			.isEditedPostDirty();
		if ( ! isDirty ) {
			await dispatch( 'core' ).saveEditedEntityRecord(
				'postType',
				postType,
				postId
			);
		}

		// Display success notice.
		dispatch( 'core/notices' ).createSuccessNotice(
			sprintf(
				/** translators: %s is post type label. */
				__( '%s classified successfully.', 'classifai' ),
				postTypeLabel
			),
			{ type: 'snackbar' }
		);

		// Mark as saved
		setIsSaved( true );
	};

	const handleClassifyClick = ( e ) => {
		setIsLoading( true );
		handleClick( {
			button: e?.target,
			endpoint: '/classifai/v1/classify/',
			callback: buttonClickCallBack,
			callbackArgs: {},
			buttonText,
			linkTerms: false,
		} );
	};

	let updatedTaxQuery = Object.entries( taxQuery || {} ).reduce(
		( accumulator, [ taxonomySlug, terms ] ) => {
			accumulator[ taxonomySlug ] = terms;
			return accumulator;
		},
		{}
	);

	if ( updatedTaxQuery.taxQuery ) {
		updatedTaxQuery = updatedTaxQuery.taxQuery;
	}

	const modalData = (
		<>
			<TaxonomyControls
				onChange={ ( newTaxQuery ) => {
					setTaxQuery( newTaxQuery );
					setIsSaved( false ); // Reset saved state when user makes changes.
				} }
				query={ {
					contentPostType: postType,
					featureTaxonomies,
					taxQuery: updatedTaxQuery,
					taxTermsAI: taxTermsAI || {},
					isLoading,
				} }
			/>
			<div className="classifai-modal__footer">
				<div className="classifai-modal__notes">
					{ sprintf(
						/* translators: %s is post type label */
						__(
							'Note that the lists above include any pre-existing terms from this %s.',
							'classifai'
						),
						postTypeLabel
					) }
					<br />
					{ __(
						'AI recommendations saved to this post will not include the "[AI]" text.',
						'classifai'
					) }
				</div>
				<Button
					variant="secondary"
					onClick={ () => saveTerms( updatedTaxQuery ) }
					disabled={ isSaved }
				>
					{ isSaved
						? __( 'Saved!', 'classifai' )
						: __( 'Save', 'classifai' ) }
				</Button>
			</div>
			<DisableFeatureButton feature="content_classification" />
		</>
	);

	return (
		<PrePubClassifyPost popupOpened={ false }>
			{ ! resultReceived && (
				<>
					<p>
						{ __(
							'Get AI-powered suggestions for categories and tags.',
							'classifai'
						) }
					</p>
					<Button
						variant="secondary"
						data-id={ postId }
						onClick={ handleClassifyClick }
						isBusy={ isLoading }
						disabled={ isLoading }
					>
						{ buttonText }
					</Button>
					<span
						className="spinner classify"
						style={ { float: 'none', display: 'none' } }
					></span>
					<span
						className="error"
						style={ {
							display: 'none',
							color: '#bc0b0b',
							padding: '5px',
						} }
					></span>
				</>
			) }
			{ resultReceived && modalData }
		</PrePubClassifyPost>
	);
};

registerPlugin( 'classifai-plugin-classification-pre-publish', {
	render: PrePublishClassificationContent,
} );

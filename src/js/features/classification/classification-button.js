/**
 * External dependencies.
 */
import { dispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { Button, Modal } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import TaxonomyControls from './taxonomy-controls';
import PrePubClassifyPost from './pre-publish-classify-post';
import { DisableFeatureButton } from '../../components';
import { handleClick } from '../../../js/helpers';

/**
 * Classify button.
 *
 * Used to manually classify the content.
 */
export const ClassificationButton = () => {
	const processContent = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute(
			'classifai_process_content'
		)
	);

	const postId = wp.data.select( 'core/editor' ).getCurrentPostId();
	const postType = wp.data.select( 'core/editor' ).getCurrentPostType();
	const postTypeLabel =
		wp.data.select( 'core/editor' ).getPostTypeLabel() ||
		__( 'Post', 'classifai' );

	const [ isLoading, setLoading ] = useState( false );
	const [ resultReceived, setResultReceived ] = useState( false );
	const [ isOpen, setOpen ] = useState( false );
	const [ popupOpened, setPopupOpened ] = useState( false );
	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );

	const [ taxQuery, setTaxQuery ] = useState( [] );
	const [ featureTaxonomies, setFeatureTaxonomies ] = useState( [] );
	let [ taxTermsAI, setTaxTermsAI ] = useState( [] );

	/**
	 * Callback function to handle API response.
	 *
	 * @param {Object} resp         Response from the API.
	 * @param {Object} callbackArgs Callback arguments.
	 */
	const buttonClickCallBack = async ( resp, callbackArgs ) => {
		if ( resp && resp.terms ) {
			// set feature taxonomies
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
		if ( callbackArgs?.openPopup ) {
			openModal();
			setPopupOpened( true );
		}
		setLoading( false );
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
		closeModal();
	};

	// Display classify post button only when process content on update is disabled.
	const enabled = 'no' === processContent ? 'no' : 'yes';
	if ( 'yes' === enabled ) {
		return null;
	}

	const buttonText = __( 'Suggest terms & tags', 'classifai' );

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
					variant={ 'secondary' }
					onClick={ () => saveTerms( updatedTaxQuery ) }
				>
					{ __( 'Save', 'classifai' ) }
				</Button>
			</div>
			<DisableFeatureButton feature="content_classification" />
		</>
	);

	return (
		<div id="classify-post-component">
			{ isOpen && (
				<Modal
					title={ __( 'Confirm Classification', 'classifai' ) }
					onRequestClose={ closeModal }
					isFullScreen={ false }
					className="classify-modal"
				>
					{ modalData }
				</Modal>
			) }
			<Button
				variant={ 'secondary' }
				data-id={ postId }
				onClick={ ( e ) => {
					handleClick( {
						button: e.target,
						endpoint: '/classifai/v1/classify/',
						callback: buttonClickCallBack,
						callbackArgs: {
							openPopup: true,
						},
						buttonText,
						linkTerms: false,
					} );
				} }
			>
				{ buttonText }
			</Button>
			<span
				className="spinner"
				style={ { display: 'none', float: 'none' } }
			></span>
			<span
				className="error"
				style={ {
					display: 'none',
					color: '#bc0b0b',
					padding: '5px',
				} }
			></span>
			<PrePubClassifyPost popupOpened={ popupOpened }>
				{ ! resultReceived && (
					<>
						<Button
							variant={ 'secondary' }
							data-id={ postId }
							onClick={ ( e ) => {
								handleClick( {
									button: e.target,
									endpoint: '/classifai/v1/classify/',
									callback: buttonClickCallBack,
									buttonText,
									linkTerms: false,
								} );
							} }
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
		</div>
	);
};

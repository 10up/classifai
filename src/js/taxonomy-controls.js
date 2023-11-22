/**
 * TaxonomyControls Component file.
 * This file inspired by Gutenberg TaxonomyControls component.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/packages/block-library/src/query/edit/inspector-controls/taxonomy-controls.js
 */
import { FormTokenField } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import {
	getEntitiesInfo,
	useTaxonomies,
} from '../../includes/Classifai/Blocks/recommended-content-block/utils';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

const termsPerPage = 100;

// Helper function to get the term id based on user input in terms `FormTokenField`.
// eslint-disable-next-line consistent-return
const getTermIdByTermValue = ( termsMappedByName, termValue ) => {
	// First we check for exact match by `term.id` or case sensitive `term.name` match.
	const termId = termValue?.id || termsMappedByName[ termValue ]?.id;

	if ( termId ) {
		return termId;
	}

	/**
	 * Here we make an extra check for entered terms in a non case sensitive way,
	 * to match user expectations, due to `FormTokenField` behaviour that shows
	 * suggestions which are case insensitive.
	 *
	 * Although WP tries to discourage users to add terms with the same name (case insensitive),
	 * it's still possible if you manually change the name, as long as the terms have different slugs.
	 * In this edge case we always apply the first match from the terms list.
	 */
	const termValueLower = termValue.toLocaleLowerCase();
	// eslint-disable-next-line no-restricted-syntax
	for ( const term in termsMappedByName ) {
		if ( term.toLocaleLowerCase() === termValueLower ) {
			return termsMappedByName[ term ].id;
		}
	}
};

const TaxonomyControls = ( { onChange, query } ) => {
	const taxonomies = useTaxonomies( query.contentPostType );
	const featureTaxonomies = query.featureTaxonomies || [];
	const taxTermsAI = query.taxTermsAI || [];
	const [ newTermsInfo, setNewTermsInfo ] = useState( {} );

	const appendAIPrefix = ( terms, slug ) => {
		if (
			undefined !== terms &&
			undefined !== terms.mapById &&
			taxTermsAI[ slug ]
		) {
			Object.keys( terms.mapById ).forEach( ( term ) => {
				if ( taxTermsAI[ slug ].includes( terms.mapById[ term ].id ) ) {
					// do not add prefix if already added
					if ( terms.mapById[ term ].name.indexOf( '[AI]' ) === -1 ) {
						terms.mapById[ term ].name =
							'[AI] ' + terms.mapById[ term ].name;
					}
				}
			} );
		}

		return terms;
	};

	let taxonomiesInfo = useSelect( ( select ) => {
		const { getEntityRecords } = select( coreStore );
		const termsQuery = { per_page: termsPerPage };
		const _taxonomiesInfo = taxonomies?.map( ( { slug, name } ) => {
			const _terms = getEntityRecords( 'taxonomy', slug, termsQuery );
			let terms = getEntitiesInfo( _terms );

			// Append "[AI]" prefix
			if ( 'post_tag' === slug ) {
				slug = 'tags';
			}
			if ( 'category' === slug ) {
				slug = 'categories';
			}
			terms = appendAIPrefix( terms, slug );

			const termData = {
				slug,
				name,
				terms,
			};

			return termData;
		} );
		return _taxonomiesInfo;
	} );

	// Update the object with newly created terms.
	if ( Object.keys( newTermsInfo ).length > 0 ) {
		taxonomiesInfo = newTermsInfo;
	}

	const onTermsChange = ( taxonomySlug ) => async ( newTermValues ) => {
		let newTermsCreated = 0; // Track the number of new terms created
		const taxonomyInfo = taxonomiesInfo.find(
			( { slug } ) => slug === taxonomySlug
		);

		if ( ! taxonomyInfo ) {
			return;
		}
		const termData = await Promise.all(
			newTermValues.map( async ( termValue ) => {
				const termId = getTermIdByTermValue(
					taxonomyInfo.terms.mapByName,
					termValue
				);

				if ( termId ) {
					return {
						[ termValue.value ]: termId,
					};
				}
				const term = {
					name: termValue,
					taxonomy: taxonomySlug,
				};

				const request = {
					path: `/wp/v2/${ taxonomySlug }`,
					data: term,
					method: 'POST',
				};

				const response = await wp
					.apiRequest( request )
					.catch( ( error ) => {
						// eslint-disable-next-line no-console
						console.log( 'Error', error );
						return null;
					} );

				if ( response && response.id ) {
					newTermsCreated++; // Increment the count of new terms created
					return {
						[ termValue ]: response.id,
					}; // Create an object with the term name as the key and the ID as the value
				}
				return null; // Handle creation failure
			} )
		);

		const termDataObject = termData.reduce( ( accumulator, item ) => {
			if ( item ) {
				return {
					...accumulator,
					...item,
				}; // Merge objects to create a single object with term names as keys and IDs as values
			}
			return accumulator;
		}, {} );

		if ( newTermsCreated > 0 ) {
			// Fetch rest API
			const request = {
				path: `/wp/v2/${ taxonomySlug }`,
				data: {
					per_page: termsPerPage,
				},
			};
			const response = await wp
				.apiRequest( request )
				.catch( ( error ) => {
					// eslint-disable-next-line no-console
					console.log( 'Error', error );
					return null;
				} );

			if ( response ) {
				// Update taxonomiesInfo
				const updatedTaxonomiesInfo = taxonomiesInfo.map(
					( taxoInfo ) => {
						if ( taxoInfo.slug === taxonomySlug ) {
							const terms = getEntitiesInfo( response );

							// Append "[AI]" prefix
							appendAIPrefix( terms, taxonomySlug );

							return {
								...taxoInfo,
								terms,
							};
						}
						return taxoInfo;
					}
				);

				setNewTermsInfo( updatedTaxonomiesInfo );
			}
		}

		const newTaxQuery = {
			...query.taxQuery,
			[ taxonomySlug ]: termDataObject,
		};

		onChange( {
			taxQuery: newTaxQuery,
		} );
	};

	// Returns only the existing term ids in proper format to be
	// used in `FormTokenField`. This prevents the component from
	// crashing in the editor, when non existing term ids were provided.
	const getExistingTaxQueryValue = ( taxonomySlug ) => {
		const taxonomyInfo = taxonomiesInfo.find(
			( { slug } ) => slug === taxonomySlug
		);

		if ( ! taxonomyInfo ) {
			return [];
		}

		let termIds = query.taxQuery[ taxonomySlug ] || [];
		termIds = Object.values( termIds );

		return termIds.reduce( ( accumulator, termId ) => {
			const term = taxonomyInfo.terms.mapById[ termId ];
			if ( term ) {
				// Decode HTML entities.
				const textarea = document.createElement( 'textarea' );
				textarea.innerHTML = term.name;
				accumulator.push( {
					id: termId,
					value: textarea.value,
				} );
			}
			return accumulator;
		}, [] );
	};

	return (
		// eslint-disable-next-line react/jsx-no-useless-fragment
		<>
			{ !! taxonomiesInfo?.length &&
				taxonomiesInfo.map( ( { slug, name, terms } ) => {
					if ( ! terms?.names?.length || query?.isLoading ) {
						return null;
					}

					// if none of the terms?.names has "[AI]" prefix, skip the iteration
					let hasAI = false;
					if ( query.taxTermsAI ) {
						// Return if this is not a feature taxonomy
						if ( ! featureTaxonomies.includes( slug ) ) {
							return null;
						}

						Object.keys( terms.mapById ).forEach( ( term ) => {
							if (
								terms.mapById[ term ].name.indexOf( '[AI]' ) !==
								-1
							) {
								hasAI = true;
							}
						} );
					}

					return (
						<>
							<FormTokenField
								key={ slug }
								label={ name }
								value={ getExistingTaxQueryValue( slug ) }
								suggestions={ terms.names }
								onChange={ onTermsChange( slug ) }
							/>
							{ ! hasAI && (
								<>
									<p
										style={ { color: '#cc1818' } }
										key={ slug }
									>
										{ sprintf(
											/* translators: %s: taxonomy name */
											__(
												'ClassifAI has no new recommendations for %s',
												'classifai'
											),
											name
										) }
									</p>
								</>
							) }
							<hr />
						</>
					);
				} ) }
		</>
	);
};

export default TaxonomyControls;

/**
 * TaxonomyControls Component file.
 * This file inspired by Gutenberg TaxonomyControls component.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/packages/block-library/src/query/edit/inspector-controls/taxonomy-controls.js
 */
import { __ } from '@wordpress/i18n';
import { FormTokenField, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { getEntitiesInfo, useTaxonomies } from '../utils';

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

const TaxonomyControls = ( { onChange, attributes: query, usePostTerms } ) => {
	// Get available taxonomies for the selected post type
	const taxonomies = useTaxonomies( query.contentPostType );
	const syncTaxonomies = taxonomies?.map( ( t ) => t.slug );

	// Get those taxonomy name, slug and terms
	const taxonomiesInfo =
		useSelect(
			( select ) => {
				const { getEntityRecords } = select( coreStore );
				const _taxonomiesInfo = taxonomies?.map( ( { slug, name } ) => {
					const _terms = getEntityRecords( 'taxonomy', slug, {
						per_page: termsPerPage,
					} );
					return {
						slug,
						name,
						terms: getEntitiesInfo( _terms ),
					};
				} );
				return _taxonomiesInfo;
			},
			[ taxonomies ]
		) || [];

	const onTermsChange = ( taxonomySlug ) => ( newTermValues ) => {
		const taxonomyInfo = taxonomiesInfo.find(
			( { slug } ) => slug === taxonomySlug
		);

		if ( ! taxonomyInfo ) {
			return;
		}

		const termIds = Array.from(
			newTermValues.reduce( ( accumulator, termValue ) => {
				const termId = getTermIdByTermValue(
					taxonomyInfo.terms.mapByName,
					termValue
				);

				if ( termId ) {
					accumulator.add( termId );
				}

				return accumulator;
			}, new Set() )
		);
		const newTaxQuery = {
			...query.taxQuery,
			[ taxonomySlug ]: termIds,
		};
		onChange( { taxQuery: newTaxQuery } );
	};

	// Returns only the existing term ids in proper format to be
	// used in `FormTokenField`. This prevents the component from
	// crashing in the editor, when non existing term ids were provided.
	const getExistingTaxQueryValue = ( taxonomySlug ) => {
		// Get the taxonomy info by the slug
		const taxonomyInfo = taxonomiesInfo.find(
			( { slug } ) => slug === taxonomySlug
		);

		if ( ! taxonomyInfo ) {
			return [];
		}

		return ( query.taxQuery?.[ taxonomySlug ] || [] ).reduce(
			( accumulator, termId ) => {
				const term = taxonomyInfo.terms.mapById[ termId ];
				if ( term ) {
					accumulator.push( {
						id: termId,
						value: term.name,
					} );
				}
				return accumulator;
			},
			[]
		);
	};

	const UseTermToggle = () => {
		return (
			<ToggleControl
				label={ __( 'Use assigned terms', 'classifai' ) }
				checked={ usePostTerms }
				onChange={ ( useTerm ) =>
					onChange( { usePostTerms: useTerm } )
				}
			/>
		);
	};

	return (
		// eslint-disable-next-line react/jsx-no-useless-fragment
		<>
			{ !! taxonomiesInfo?.length &&
				taxonomiesInfo.filter(
					( { slug } ) => syncTaxonomies.indexOf( slug ) > -1
				).length && <UseTermToggle /> }

			{ !! taxonomiesInfo?.length &&
				taxonomiesInfo.map( ( { slug, name, terms } ) => {
					if (
						! terms?.names?.length ||
						( usePostTerms && syncTaxonomies.indexOf( slug ) > -1 )
					) {
						return null;
					}

					return (
						<FormTokenField
							key={ slug }
							label={ name }
							value={ getExistingTaxQueryValue( slug ) }
							suggestions={ terms.names }
							onChange={ onTermsChange( slug ) }
						/>
					);
				} ) }
		</>
	);
};

export default TaxonomyControls;

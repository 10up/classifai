/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import {
	CheckboxControl,
	SelectControl,
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { getFeature } from '../../utils/utils';

/**
 * Component for render settings fields when IBM Watson NLU is selected as the provider.
 *
 * This component is used within the ClassificationSettings component.
 *
 * @return {React.ReactElement} NLUFeatureSettings component.
 */
export const NLUFeatureSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const classificationFeature = getFeature( 'feature_classification' );
	const taxonomies = classificationFeature.taxonomiesByPostTypes || {};
	const { nluTaxonomies = {} } = window.classifAISettings;

	const nluFeatures = {
		category: {
			label: __( 'Category', 'classifai' ),
			defaultThreshold: 70,
			helperText: __(
				'<p>IBM Watson analyzes your content and assigns a broad topic hierarchy that best describes the overall subject.</p>'+
				'<p>Example:<code>/technology and computing/software</code></p>'+
				'<p>Categories are useful for general classification and site-wide content grouping.</p>'+
				'<p><a href="https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#categories" target="_blank">Learn more</a></p>',
				'classifai'
			),
		},
		keyword: {
			label: __( 'Keyword', 'classifai' ),
			defaultThreshold: 70,
			helperText: __(
				'<p>Keywords represent important terms in your content that are contextually significant.</p>'+
				'<p>Watson extracts these to help identify core concepts, topics, and SEO-friendly tags.</p>'+
				'<p>Keywords often map well to WordPress tags.</p>'+
				'<p><a href="https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#keywords" target="_blank">Learn more</a></p>',
				'classifai'
			),
		},
		entity: {
			label: __( 'Entity', 'classifai' ),
			defaultThreshold: 70,
			helperText: __(
				'<p>Entities are named people, places, brands, and other proper nouns mentioned in your content.</p>'+
				'<p>Watson identifies and classifies these by type (e.g., Person, Company, Location) and optionally links them to known databases like Wikipedia.</p>'+
				'<p>Entities are helpful for structured data and enhancing rich snippets or metadata.</p>'+
				'<p><a href="https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#entities" target="_blank">Learn more</a></p>',
				'classifai'
			),
		},
		concept: {
			label: __( 'Concept', 'classifai' ),
			defaultThreshold: 70,
			helperText: __(
				'<p>Concepts reflect high-level abstract ideas Watson identifies in your content, even if the term isn\'t explicitly used.</p>'+
				'<p>For example, an article about "the iPhone" might be linked to the concept of “Apple Inc.”</p>'+
				'<p>Concepts are great for semantic tagging and content recommendation systems.</p>'+
				'<p><a href="https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#concepts" target="_blank">Learn more</a></p>',
				'classifai'
			),
		},
	};

	const optionsObjects = {};
	Object.keys( taxonomies ).forEach( ( postType ) => {
		if ( featureSettings.post_types?.[ postType ] === postType ) {
			const postTypeTaxonomies = taxonomies[ postType ] || {};
			Object.keys( postTypeTaxonomies ).forEach( ( taxonomy ) => {
				optionsObjects[ taxonomy ] = postTypeTaxonomies[ taxonomy ];
			} );
		}
	} );

	/*
	 * Add NLU-specific taxonomies to the list if IBM Watson NLU is selected as the provider.
	 *
	 * This ensures that the NLU taxonomies are available in the settings,
	 * as NLU-specific taxonomies are registered only if the Classification feature is enabled and IBM Watson NLU is selected as the provider.
	 */
	if ( 'ibm_watson_nlu' === featureSettings.provider ) {
		Object.keys( nluTaxonomies ).forEach( ( taxonomy ) => {
			optionsObjects[ taxonomy ] = nluTaxonomies[ taxonomy ];
		} );
	}

	const options =
		Object.keys( optionsObjects || {} ).map( ( taxonomy ) => ( {
			label: optionsObjects[ taxonomy ],
			value: taxonomy,
		} ) ) || [];

	let features = {};
	if ( 'ibm_watson_nlu' === featureSettings.provider ) {
		features = nluFeatures;
	} else {
		options?.forEach( ( taxonomy ) => {
			features[ taxonomy.value ] = {
				label: taxonomy.label,
				defaultThreshold: 75,
			};
		} );
	}

	return (
		<>
			{ Object.keys( features ).map( ( feature ) => {
				const { defaultThreshold, label, helperText } = features[ feature ];
				return (
					<SettingsRow
						key={ feature }
						label={ label }
						className={ 'nlu-features' }
						helperText={ helperText }
					>
						<CheckboxControl
							id={ `${ feature }-enabled` }
							label={ __( 'Enable', 'classifai' ) }
							value={ feature }
							checked={ !! featureSettings[ feature ] }
							onChange={ ( value ) => {
								setFeatureSettings( {
									[ feature ]: value ? 1 : 0,
								} );
							} }
							__nextHasNoMarginBottom
						/>
						<InputControl
							id={ `${ feature }-threshold` }
							label={ __( 'Threshold (%)', 'classifai' ) }
							type="number"
							value={
								featureSettings[ `${ feature }_threshold` ] ||
								defaultThreshold
							}
							onChange={ ( value ) => {
								setFeatureSettings( {
									[ `${ feature }_threshold` ]: value,
								} );
							} }
						/>
						{ 'ibm_watson_nlu' === featureSettings.provider && (
							<SelectControl
								id={ `${ feature }-taxonomy` }
								label={ sprintf(
									// translators: %s: feature label
									__( '%s Taxonomy', 'classifai' ),
									label
								) }
								value={
									featureSettings[
										`${ feature }_taxonomy`
									] || feature
								}
								options={ ( options || [] )?.map(
									( taxonomy ) => ( {
										label: taxonomy.label,
										value: taxonomy.value,
									} )
								) }
								onChange={ ( value ) => {
									setFeatureSettings( {
										[ `${ feature }_taxonomy` ]: value,
									} );
								} }
								__nextHasNoMarginBottom
							/>
						) }
					</SettingsRow>
				);
			} ) }
		</>
	);
};

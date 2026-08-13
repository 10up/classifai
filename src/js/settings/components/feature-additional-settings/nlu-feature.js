/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import {
	CheckboxControl,
	SelectControl,
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalInputControlSuffixWrapper as InputControlSuffixWrapper, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { getFeature, TooltipPopover } from '../../utils/utils';
import { thresholdInfo, nluHelperText } from '../../utils/helper-text';

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
			helperText: nluHelperText.category,
		},
		keyword: {
			label: __( 'Keyword', 'classifai' ),
			defaultThreshold: 70,
			helperText: nluHelperText.keyword,
		},
		entity: {
			label: __( 'Entity', 'classifai' ),
			defaultThreshold: 70,
			helperText: nluHelperText.entity,
		},
		concept: {
			label: __( 'Concept', 'classifai' ),
			defaultThreshold: 70,
			helperText: nluHelperText.concept,
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
				const { defaultThreshold, label, helperText } =
					features[ feature ];
				return (
					<SettingsRow
						key={ feature }
						label={ label }
						className="nlu-features"
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
							label={ __(
								'Confidence Threshold (%)',
								'classifai'
							) }
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
							__unstableInputWidth="8em"
							suffix={
								<InputControlSuffixWrapper variant="control">
									<TooltipPopover
										tooltipContent={ thresholdInfo.helper }
									/>
								</InputControlSuffixWrapper>
							}
							min="0"
							max="100"
							step="0.01"
							__next40pxDefaultSize
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
								__next40pxDefaultSize
							/>
						) }
					</SettingsRow>
				);
			} ) }
		</>
	);
};

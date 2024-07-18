import { useSelect, useDispatch } from '@wordpress/data';
import {
	CheckboxControl,
	SelectControl,
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { useTaxonomies } from '../../utils/utils';

export const NLUFeatureSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const { taxonomies = [] } = useTaxonomies();

	const nluFeatures = {
		category: {
			label: __( 'Category', 'classifai' ),
			defaultThreshold: 70,
		},
		keyword: {
			label: __( 'Keyword', 'classifai' ),
			defaultThreshold: 70,
		},
		entity: {
			label: __( 'Entity', 'classifai' ),
			defaultThreshold: 70,
		},
		concept: {
			label: __( 'Concept', 'classifai' ),
			defaultThreshold: 70,
		},
	};

	const options =
		taxonomies
			?.filter( ( taxonomy ) => {
				const intersection = ( taxonomy.types || [] ).filter(
					( type ) => featureSettings.post_types?.[ type ] === type
				);
				return intersection.length > 0;
			} )
			?.map( ( taxonomy ) => ( {
				label: taxonomy.name,
				value: taxonomy.slug,
			} ) ) || [];

	let features = {};
	if ( 'ibm_watson_nlu' === featureSettings.provider ) {
		features = nluFeatures;
		if ( options ) {
			options.push(
				{
					label: __( 'Watson Category', 'classifai' ),
					value: 'watson-category',
				},
				{
					label: __( 'Watson Keyword', 'classifai' ),
					value: 'watson-keyword',
				},
				{
					label: __( 'Watson Entity', 'classifai' ),
					value: 'watson-entity',
				},
				{
					label: __( 'Watson Concept', 'classifai' ),
					value: 'watson-concept',
				}
			);
		}
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
				const { defaultThreshold, label } = features[ feature ];
				return (
					<SettingsRow
						key={ feature }
						label={ label }
						className={ 'nlu-features' }
					>
						<CheckboxControl
							label={ __( 'Enable', 'classifai' ) }
							value={ feature }
							checked={ featureSettings[ feature ] }
							onChange={ ( value ) => {
								setFeatureSettings( {
									[ feature ]: value ? 1 : 0,
								} );
							} }
						/>
						<InputControl
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
							/>
						) }
					</SettingsRow>
				);
			} ) }
		</>
	);
};

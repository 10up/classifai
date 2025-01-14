/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import {
	CheckboxControl,
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { useFeatureContext } from '../feature-settings/context';
import { getFeature } from '../../utils/utils';

/**
 * Component for Term Cleanup feature settings.
 *
 * This component is used within the FeatureSettings component
 * to allow users to configure the Term Cleanup feature.
 *
 * @return {React.ReactElement} TermCleanupSettings component.
 */
export const TermCleanupSettings = () => {
	const { featureName } = useFeatureContext();
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const { taxonomies = {} } = getFeature( featureName );

	const options = Object.keys( taxonomies ).map( ( slug ) => {
		return {
			value: slug,
			label: taxonomies[ slug ],
		};
	} );
	const features = {};

	options?.forEach( ( taxonomy ) => {
		features[ taxonomy.value ] = {
			label: taxonomy.label,
			defaultThreshold: 75,
		};
	} );

	const Description = () => {
		if ( window.classifAISettings?.isEPinstalled ) {
			return __(
				'Use Elasticsearch for finding similar terms; this will speed up the process for finding similar terms.',
				'classifai'
			);
		}

		return (
			<>
				{ __( 'Install and activate the ', 'classifai' ) }
				<a
					title={ __( 'Download ElasticPress', 'classifai' ) }
					href="https://wordpress.org/plugins/elasticpress/"
					target="_blank"
					rel="noreferrer"
				>
					{ __( 'ElasticPress', 'classifai' ) }
				</a>
				{ __(
					' plugin to use Elasticsearch for finding similar terms.',
					'classifai'
				) }
			</>
		);
	};

	return (
		<>
			<SettingsRow
				label={ __( 'Use ElasticPress', 'classifai' ) }
				description={ <Description /> }
				className="settings-term-cleanup-use-ep"
			>
				<CheckboxControl
					id="use_ep"
					key="use_ep"
					checked={ featureSettings?.use_ep === '1' }
					disabled={ ! window.classifAISettings?.isEPinstalled }
					label={ __( 'Use ElasticPress', 'classifai' ) }
					onChange={ ( value ) => {
						setFeatureSettings( {
							use_ep: value ? '1' : '0',
						} );
					} }
					__nextHasNoMarginBottom
				/>
			</SettingsRow>
			<>
				{ Object.keys( features ).map( ( feature ) => {
					const { defaultThreshold, label } = features[ feature ];
					return (
						<SettingsRow
							key={ feature }
							label={ label }
							className="settings-term-cleanup-taxonomies"
						>
							<CheckboxControl
								id={ `${ feature }-enabled` }
								label={ __( 'Enable', 'classifai' ) }
								value={ feature }
								checked={
									!! featureSettings?.taxonomies?.[ feature ]
								}
								onChange={ ( value ) => {
									setFeatureSettings( {
										taxonomies: {
											...featureSettings.taxonomies,
											[ feature ]: value ? 1 : 0,
										},
									} );
								} }
								__nextHasNoMarginBottom
							/>
							<InputControl
								id={ `${ feature }-threshold` }
								label={ __( 'Threshold (%)', 'classifai' ) }
								type="number"
								value={
									featureSettings?.taxonomies?.[
										`${ feature }_threshold`
									] || defaultThreshold
								}
								onChange={ ( value ) => {
									setFeatureSettings( {
										taxonomies: {
											...featureSettings.taxonomies,
											[ `${ feature }_threshold` ]: value,
										},
									} );
								} }
							/>
						</SettingsRow>
					);
				} ) }
			</>
		</>
	);
};

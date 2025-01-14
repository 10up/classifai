/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import {
	CheckboxControl,
	SelectControl,
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';

/**
 * Component for Smart 404 feature settings.
 *
 * This component is used within the FeatureSettings component to allow users to configure the Smart 404 feature.
 *
 * @return {React.ReactElement} Smart404Settings component.
 */
export const Smart404Settings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const featureName = 'feature_smart_404';

	return (
		<>
			<SettingsRow
				label={ __( 'Number of posts to show', 'classifai' ) }
				description={ __(
					'Determines the maximum number of posts that will show on a 404 page. This can be overridden in the display functions.',
					'classifai'
				) }
			>
				<InputControl
					id={ `${ featureName }_num` }
					type="number"
					value={ featureSettings.num || 5 }
					onChange={ ( value ) => {
						setFeatureSettings( {
							num: value,
						} );
					} }
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Number of posts to search', 'classifai' ) }
				description={ __(
					'Determines the maximum number of posts Elasticsearch will use for the vector search. A higher number can give more accurate results but will be slower. This can be overridden in the display functions.',
					'classifai'
				) }
			>
				<InputControl
					id={ `${ featureName }_num_search` }
					type="number"
					value={ featureSettings.num_search || 5000 }
					onChange={ ( value ) => {
						setFeatureSettings( {
							num_search: value,
						} );
					} }
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Threshold', 'classifai' ) }
				description={ __(
					'Set the minimum threshold we want for our results. Any result that falls below this number will be automatically removed.',
					'classifai'
				) }
			>
				<InputControl
					id={ `${ featureName }_threshold` }
					type="number"
					value={ featureSettings.threshold || 2.35 }
					min={ 0 }
					step={ 0.01 }
					onChange={ ( value ) => {
						setFeatureSettings( {
							threshold: value,
						} );
					} }
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Use rescore query', 'classifai' ) }
				description={ __(
					'Will run a normal Elasticsearch query and then rescore those results using a vector query. Can give better results but often results in worse performance. This can be overridden in the display functions.',
					'classifai'
				) }
			>
				<CheckboxControl
					id={ `${ featureName }_rescore` }
					checked={ featureSettings.rescore === '1' }
					onChange={ ( value ) => {
						setFeatureSettings( {
							rescore: value ? '1' : '0',
						} );
					} }
					__nextHasNoMarginBottom
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Use fallback results', 'classifai' ) }
				description={ __(
					'If no results are found in Elasticsearch, will fallback to displaying most recent results from WordPress. This can be overridden in the display functions.',
					'classifai'
				) }
			>
				<CheckboxControl
					id={ `${ featureName }_fallback` }
					checked={ featureSettings.fallback === '1' }
					onChange={ ( value ) => {
						setFeatureSettings( {
							fallback: value ? '1' : '0',
						} );
					} }
					__nextHasNoMarginBottom
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Score function', 'classifai' ) }
				description={ __(
					'Choose which vector scoring function you want to use. You may need to adjust the threshold if you change this. This can be overridden in the display functions.',
					'classifai'
				) }
			>
				<SelectControl
					id={ `${ featureName }_score_function` }
					value={ featureSettings.score_function || 'cosine' }
					onChange={ ( value ) => {
						setFeatureSettings( {
							score_function: value,
						} );
					} }
					options={ [
						{ label: __( 'Cosine', 'classifai' ), value: 'cosine' },
						{
							label: __( 'Dot Product', 'classifai' ),
							value: 'dot_product',
						},
						{
							label: __( 'L1 Norm', 'classifai' ),
							value: 'l1_norm',
						},
						{
							label: __( 'L2 Norm', 'classifai' ),
							value: 'l2_norm',
						},
					] }
					__nextHasNoMarginBottom
				/>
			</SettingsRow>
		</>
	);
};

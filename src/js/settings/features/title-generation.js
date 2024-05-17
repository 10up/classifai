import { addFilter } from '@wordpress/hooks';
import { Fill, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

function titleGenerationSettings( FeatureSettings ) {
	return ( props ) => {
		const { featureName, featureSettings, setSettings } = props;
		if ( featureName !== 'feature_title_generation' ) {
			return <FeatureSettings { ...props } />;
		}

		const provider = featureSettings?.provider || '';
		const numberOfSuggestions =
			featureSettings?.[ provider ]?.number_of_suggestions || '1';
		return (
			<>
				<FeatureSettings { ...props } />
				<Fill name="ProviderSettings">
					<TextControl
						label={ __( 'Number of suggestions', 'classifai' ) }
						value={ numberOfSuggestions }
						onChange={ ( value ) =>
							setSettings( {
								number_of_suggestions: value,
							} )
						}
					/>
				</Fill>
			</>
		);
	};
}

addFilter(
	'classifai.FeatureSettings',
	'classifai-feature-settings/title-generation',
	titleGenerationSettings
);

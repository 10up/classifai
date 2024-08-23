import { ToggleControl, Flex, FlexItem, BaseControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { FeatureContext } from '../feature-settings/context';
import { EnableToggleControl } from '../feature-settings/enable-feature';
import { SaveSettingsButton } from '../../components/feature-settings/save-settings-button';
import { useFeatureSettings } from '../../data/hooks';

export const EnableFeatures = ( { step, setStep } ) => {
	const { features, services } = classifAISettings;
	const { isSaving } = useFeatureSettings();

	useEffect( () => {
		if ( 'enable_features' === step && false === isSaving ) {
			setStep( 'configure_features' );
		}
	}, [ isSaving ] );

	const featureToggles = Object.keys( services ).map( ( service, serviceIndex ) => (
		<React.Fragment key={ service }>
			<div className='classifai-feature-box'>
				<h3 className='classifai-feature-box-title'>
					{ services[ service ] }
				</h3>
				<div className='classifai-features'>
					{
						Object.keys( features[ service ] ).map( featureSlug => (
							<BaseControl key={ featureSlug }>
								<FeatureContext.Provider value={ { featureName: featureSlug } }>
									<EnableToggleControl>
										{
											( { feature, status, setFeatureSettings } ) => {
												return (
													<Flex>
														<FlexItem>
															<span>{ feature.label }</span>
														</FlexItem>
														<FlexItem>
															<ToggleControl
																checked={ status === '1' }
																onChange={ ( value ) => 
																	setFeatureSettings( {
																		status: value ? '1' : '0', // TODO: Use boolean, currently using string for compatibility.
																	} )
																}
															/>
														</FlexItem>
													</Flex>
												)
											}
										}
									</EnableToggleControl>
								</FeatureContext.Provider>
							</BaseControl>
						) )
					}
				</div>
			</div>
			{ Object.keys( services ).length !== serviceIndex + 1 && <hr /> }
		</React.Fragment>
	) );

	return (
		<>
			<h2 className='classifai-setup-title'>{ __( 'Set up ClassifAI to meet your needs', 'classifai' ) }</h2>
			<div className='classifai-onboarding__configure classifai-onboarding__configure--enable-features'>
				<div>
					<img src={ `${ ClassifAI.plugin_url }assets/img/onboarding-1.png` } />
				</div>
				<div>
					{ featureToggles }
					<SaveSettingsButton disableErrorReporting={ true } />
				</div>
			</div>
		</>
	)
};

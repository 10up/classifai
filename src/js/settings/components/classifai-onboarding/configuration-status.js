import { Icon, BaseControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { STORE_NAME } from '../../data/store';
import { isFeatureActive, getFeature, getEnabledFeaturesSlugs } from '../../utils/utils';

export const ConfigurationStatus = ( { setStep } ) => {
	const { features: __settings, services } = classifAISettings;
	const settingsState = useSelect( select => select( STORE_NAME ).getSettings() );
	const enabledFeatureSlugs = getEnabledFeaturesSlugs();
	const settings = Object.keys( __settings ).reduce( ( a, c ) => {
		const res = Object.keys( __settings[ c ] ).reduce( ( __a, __c ) => {
			return {
				...__a,
				[ __c ]: settingsState[ __c ]
			}
		}, {} );

		return {
			...a,
			[ c ]: res
		}
	}, {} );

	return (
		<>
			<h2 className='classifai-setup-title'>{ __( 'Welcome to ClassifAI', 'classifai' ) }</h2>
			<div className='classifai-onboarding__configure classifai-onboarding__configure--status'>
				<div>
					<img src={ `${ ClassifAI.plugin_url }assets/img/onboarding-4.png` } />
				</div>
				<div>
					{
						Object.keys( services ).map( ( service ) => {
							const enabledFeatures = Object
								.keys( settings[ service ] )
								.map( featureSlug => {
									const feature = getFeature( featureSlug );
									const label = feature.label;

									if ( ! enabledFeatureSlugs.includes( featureSlug ) ) {
										return null;
									}

									return (
										<BaseControl key={ featureSlug }>
											{ isFeatureActive( settings[ service ][ featureSlug ] )
												? <Icon icon="yes-alt" /> 
												: <Icon icon="dismiss" />
											}
											{ label }
										</BaseControl>
									)
								} )
								.filter( Boolean );

							if ( ! enabledFeatures.length ) {
								return [];
							}

							return (
								<React.Fragment key={ service }>
									<div className='classifai-feature-box'>
										<h3 className='classifai-feature-box-title'>
											{ services[ service ] }
										</h3>
										<div className='classifai-features'>
											{ enabledFeatures }
										</div>
									</div>
								</React.Fragment>
							)
						} )
					}
				</div>
			</div>
		</>
	);
};

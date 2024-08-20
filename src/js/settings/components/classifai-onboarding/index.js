import { useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { ToggleControl, Flex, FlexItem } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

import { STORE_NAME } from '../../data/store';
import { FeatureContext } from '../feature-settings/context';
import { EnableToggleControl } from '../feature-settings/enable-feature';

export const ClassifAIOnboarding = () => {
	const { setSettings, setIsLoaded } = useDispatch( STORE_NAME );

	// Load the settings.
	useEffect( () => {
		( async () => {
			const classifAISettings = await apiFetch( {
				path: '/classifai/v1/settings',
			} ); // TODO: handle error

			setSettings( classifAISettings );
			setIsLoaded( true );
		} )();
	}, [ setSettings, setIsLoaded ] );

	const { features, services } = classifAISettings;

	return Object.keys( services ).map( service => (
		<>
			<div className='classifai-feature-box-title'>
				{ services[ service ] }
			</div>
			<div className='classifai-features'>
				<ul>
					{
						Object.keys( features[ service ] ).map( featureSlug => (
							<li className='classifai-enable-feature' key={ featureSlug }>
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
																	// setFeatureSettings(
																	// 	{
																	// 		status: value ? '1' : '0', // TODO: Use boolean, currently using string for compatibility.
																	// 	},
																	// 	featureSlug
																	// )
																}
															/>
														</FlexItem>
													</Flex>
												)
											}
										}
									</EnableToggleControl>
								</FeatureContext.Provider>
							</li>
						) )
					}
				</ul>
			</div>
		</>
	) );

};

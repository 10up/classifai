import { Icon } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { STORE_NAME } from '../../data/store';
import { isFeatureActive, getFeature } from '../../utils/utils';

export const FinishStep = () => {
	const { features: __settings, services } = window.classifAISettings;
	const settingsState = useSelect( ( select ) =>
		select( STORE_NAME ).getSettings()
	);
	const enabledFeatureSlugs = Object.keys( settingsState ).filter(
		( feature ) => settingsState[ feature ].status === '1'
	);
	const settings = Object.keys( __settings ).reduce( ( a, c ) => {
		const res = Object.keys( __settings[ c ] ).reduce( ( __a, __c ) => {
			return {
				...__a,
				[ __c ]: settingsState[ __c ],
			};
		}, {} );

		return {
			...a,
			[ c ]: res,
		};
	}, {} );

	return (
		<>
			<h1 className="classifai-setup-heading">
				{ __( 'Welcome to ClassifAI', 'classifai' ) }
			</h1>
			<div className="classifai-setup__content__row">
				<div className="classifai-setup__content__row__column">
					<div className="classifai-setup-image">
						<img
							src={ `${ window.ClassifAI.plugin_url }assets/img/onboarding-4.png` }
							alt={ __( '', 'classifai' ) }
						/>
					</div>
				</div>
				<div className="classifai-setup__content__row__column">
					<div className="classifai-step4-content">
						<h2 className="classifai-setup-title">
							{ __(
								'ClassifAI configured successfully!',
								'classifai'
							) }
						</h2>
						{ Object.keys( services ).map( ( service ) => {
							const enabledFeatures = Object.keys(
								settings[ service ]
							)
								.map( ( featureSlug ) => {
									const feature = getFeature( featureSlug );
									const label = feature.label;

									if (
										! enabledFeatureSlugs.includes(
											featureSlug
										)
									) {
										return null;
									}

									return (
										<li
											key={ featureSlug }
											className="classifai-enable-feature"
										>
											{ isFeatureActive(
												settings[ service ][
													featureSlug
												]
											) ? (
												<Icon icon="yes-alt" />
											) : (
												<Icon icon="dismiss" />
											) }{ ' ' }
											{ label }
										</li>
									);
								} )
								.filter( Boolean );

							if ( ! enabledFeatures.length ) {
								return [];
							}

							return (
								<div
									className="classifai-feature-box"
									key={ service }
								>
									<h4 className="classifai-feature-box-title">
										{ services[ service ] }
									</h4>
									<div className="classifai-features">
										<ul> { enabledFeatures } </ul>
									</div>
								</div>
							);
						} ) }
					</div>
				</div>
			</div>
		</>
	);
};

import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Fill, SlotFillProvider, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { FeatureSettings } from '..';
import { FeatureContext } from '../feature-settings/context';
import { getFeature } from '../../utils/utils';
import { STORE_NAME } from '../../data/store';
import { useSetupPage } from './hooks';
import { useNavigate } from 'react-router-dom';

export const ConfigureFeatures = () => {
	const enabledFeatures = useSelect( ( select ) => {
		const settings = select( STORE_NAME ).getSettings();
		return Object.keys( settings ).filter(
			( feature ) => settings[ feature ].status === '1'
		);
	} );
	const { nextStepPath } = useSetupPage();
	const navigate = useNavigate();
	const [ currentFeature, setCurrentFeature ] = useState(
		enabledFeatures[ 0 ]
	);
	let featureIndex = enabledFeatures.findIndex(
		( ef ) => ef === currentFeature
	);

	const onSaveSuccess = () => {
		if ( featureIndex + 1 !== enabledFeatures.length ) {
			setCurrentFeature( enabledFeatures[ ++featureIndex ] );
		} else {
			// Navigate to the next step.
			navigate( nextStepPath );
		}
	};

	return (
		<>
			<h1 className="classifai-setup-heading">
				{ __( 'Set up AI Providers', 'classifai' ) }
			</h1>
			<div className="service-settings-wrapper classifai-onboarding__configure">
				<div className="classifai-tabs" aria-orientation="vertical">
					{ enabledFeatures.map( ( feature ) => (
						<button
							key={ feature }
							onClick={ () => setCurrentFeature( feature ) }
							className={ `classifai-tabs-item ${
								feature === currentFeature && 'active-tab'
							}` }
						>
							{ getFeature( feature ).label }
						</button>
					) ) }
				</div>
				<div className="feature-settings-wrapper">
					<FeatureContext.Provider
						value={ { featureName: currentFeature } }
					>
						<SlotFillProvider>
							<FeatureSettings onSaveSuccess={ onSaveSuccess } />
							<Fill name="BeforeSaveButton">
								<Button onClick={ onSaveSuccess }>
									{ __( 'Skip for now', 'classifai' ) }
								</Button>
							</Fill>
						</SlotFillProvider>
					</FeatureContext.Provider>
				</div>
			</div>
		</>
	);
};

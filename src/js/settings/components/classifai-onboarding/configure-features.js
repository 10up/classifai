import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { Fill, SlotFillProvider, Button } from '@wordpress/components';
import { store as noticesStore } from '@wordpress/notices';
import { __ } from '@wordpress/i18n';

import { FeatureSettings } from '..';
import { FeatureContext } from '../feature-settings/context';
import { useFeatureSettings } from '../../data/hooks';
import { getFeature } from '../../utils/utils';
import { STORE_NAME } from '../../data/store';
import { getEnabledFeaturesSlugs } from '../../utils/utils';

export const ConfigureFeatures = ( { step, setStep } ) => {
	const { isSaving } = useFeatureSettings();
	const enabledFeatures = getEnabledFeaturesSlugs();
	const [ currentFeature, setCurrentFeature ] = useState( enabledFeatures[0] );
	const errors = useSelect( select => select( STORE_NAME ).getSaveErrors() );
	const { setSaveErrors } = useDispatch( STORE_NAME );
	const { removeNotices } = useDispatch( noticesStore );
	let featureIndex = enabledFeatures.findIndex( ef => ef === currentFeature );
	const notices = useSelect( ( select ) =>
		select( noticesStore ).getNotices()
	);

	useEffect( () => {
		setSaveErrors( [] );
	}, [] );

	useEffect( () => {
		if ( false !== isSaving ) {
			return;
		}

		if ( featureIndex + 1 !== enabledFeatures.length && ! errors.length ) {
			setCurrentFeature( enabledFeatures[ ++featureIndex ] );
		} else if ( featureIndex + 1 === enabledFeatures.length ) {
			setStep( 'configuration_status' );
		}
	}, [ isSaving ] );

	return (
		<>
			<h2 className='classifai-setup-title'>{ __( 'Set up AI Providers', 'classifai' ) }</h2>
			<div className='service-settings-wrapper classifai-onboarding__configure'>
				<div className='classifai-tabs' aria-orientation='vertical'>
					{
						enabledFeatures.map( feature => (
							<div
								key={ feature }
								onClick={ () => {
									removeNotices( notices.map( ( { id } ) => id ) );
									setCurrentFeature( feature );
								} }
								className={ `classifai-tabs-item ${ feature === currentFeature && 'active-tab' }` }
							>
								{ getFeature( feature ).label }
							</div>
						) )
					}
				</div>
				<div className='feature-settings-wrapper'>
					<FeatureContext.Provider value={ { featureName: currentFeature } }>
						<SlotFillProvider>
							<FeatureSettings />
							<Fill name="BeforeSaveButton">
								<Button
									variant="link"
									onClick={ () => {
										if ( featureIndex + 1 !== enabledFeatures.length ) {
											removeNotices( notices.map( ( { id } ) => id ) );
											setCurrentFeature( enabledFeatures[ ++featureIndex ] )
										} else {
											setStep( 'configuration_status' );
										}
									} }
								>
									{ __( 'Skip', 'classifai' ) }
								</Button>
							</Fill>
						</SlotFillProvider>
					</FeatureContext.Provider>
				</div>
			</div>
		</>
	);
};

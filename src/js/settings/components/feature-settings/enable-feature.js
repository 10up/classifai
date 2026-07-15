/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { getFeature } from '../../utils/utils';
import { SettingsRow } from '../settings-row';
import { useFeatureSettings } from '../../data/hooks';
import { CredentialReuseModal } from '../credential-reuse';

/**
 * Enable Feature Toggle component.
 *
 * @param {Object} props          Component props.
 * @param {Object} props.children Component children.
 */
export const EnableToggleControl = ( { children } ) => {
	const { featureName, getFeatureSettings, setFeatureSettings } =
		useFeatureSettings();
	const [ showCredentialModal, setShowCredentialModal ] = useState( false );
	const status = getFeatureSettings( 'status' ) || '0';
	const feature = getFeature( featureName );

	/**
	 * Handle toggle change with credential reuse check.
	 *
	 * @param {boolean} value Whether the feature should be enabled.
	 */
	const handleToggleChange = ( value ) => {
		// Enable/disable the feature immediately
		setFeatureSettings( {
			status: value ? '1' : '0',
		} );

		// Check for reusable credentials in the background (only when enabling)
		if ( value && status === '0' ) {
			checkForReusableCredentials();
		}
	};

	/**
	 * Check for reusable credentials in the background.
	 */
	const checkForReusableCredentials = async () => {
		// Check if user has disabled the prompt
		const dontAsk =
			window.localStorage.getItem(
				'classifai_dont_ask_credential_reuse'
			) === 'true';

		if ( dontAsk ) {
			return;
		}

		try {
			const reusableCredentials = await apiFetch( {
				path: `/classifai/v1/credential-reuse/${ featureName }`,
			} );

			const providers = Object.keys( reusableCredentials );

			if ( providers.length > 0 ) {
				// Show credential reuse modal only if there are reusable credentials
				setShowCredentialModal( true );
			}
		} catch {
			// Error handled gracefully
		}
	};

	/**
	 * Handle credentials being reused.
	 *
	 */
	const handleCredentialsReused = () => {
		// Feature is already enabled, credentials have been copied by the API
		// Trigger a settings refresh to show the updated provider in the UI
		window.location.reload();
	};

	/**
	 * Handle modal close.
	 */
	const handleModalClose = () => {
		setShowCredentialModal( false );
		// Feature is already enabled, no need to change status
	};

	if ( children && 'function' === typeof children ) {
		return children( { feature, status, setFeatureSettings } );
	}

	const enableDescription = decodeEntities(
		feature?.enable_description || __( 'Enable feature', 'classifai' )
	);

	return (
		<>
			<SettingsRow
				label={ __( 'Enable Feature', 'classifai' ) }
				description={ enableDescription }
			>
				<ToggleControl
					className="classifai-enable-feature-toggle"
					checked={ status === '1' }
					onChange={ handleToggleChange }
					__nextHasNoMarginBottom
				/>
			</SettingsRow>

			<CredentialReuseModal
				isOpen={ showCredentialModal }
				onClose={ handleModalClose }
				featureName={ featureName }
				featureLabel={ feature?.label }
				onCredentialsReused={ handleCredentialsReused }
			/>
		</>
	);
};

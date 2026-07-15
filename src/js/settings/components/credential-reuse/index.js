/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import {
	Modal,
	Button,
	Notice,
	CheckboxControl,
	Flex,
	FlexItem,
	Icon,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * CredentialReuseModal component.
 *
 * Shows a modal asking the user if they want to reuse existing provider credentials
 * when enabling a new feature.
 *
 * @param {Object}   props                     Component props.
 * @param {boolean}  props.isOpen              Whether the modal is open.
 * @param {Function} props.onClose             Callback when modal is closed.
 * @param {string}   props.featureName         Name of the feature being enabled.
 * @param {string}   props.featureLabel        Display label of the feature being enabled.
 * @param {Function} props.onCredentialsReused Callback when credentials are reused.
 * @return {React.ReactElement|null} The modal component or null if not open.
 */
export const CredentialReuseModal = ( {
	isOpen,
	onClose,
	featureName,
	featureLabel,
	onCredentialsReused,
} ) => {
	const [ reusableCredentials, setReusableCredentials ] = useState( {} );
	const [ selectedProvider, setSelectedProvider ] = useState( '' );
	const [ dontAskAgain, setDontAskAgain ] = useState( false );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ isLoadingCredentials, setIsLoadingCredentials ] = useState( false );

	useEffect( () => {
		if ( isOpen && featureName ) {
			fetchReusableCredentials();
		}
	}, [ isOpen, featureName ] );

	/**
	 * Fetch reusable credentials for the current feature.
	 */
	const fetchReusableCredentials = async () => {
		setIsLoadingCredentials( true );
		try {
			const response = await apiFetch( {
				path: `/classifai/v1/credential-reuse/${ featureName }`,
			} );
			setReusableCredentials( response );

			// Auto-select the first available provider
			const providerIds = Object.keys( response );
			if ( providerIds.length > 0 ) {
				setSelectedProvider( providerIds[ 0 ] );
			}
		} catch {
			// Error handled gracefully
		} finally {
			setIsLoadingCredentials( false );
		}
	};

	/**
	 * Handle reusing credentials.
	 */
	const handleReuseCredentials = async () => {
		if ( ! selectedProvider ) {
			return;
		}

		setIsLoading( true );
		try {
			const sourceFeatureId =
				reusableCredentials[ selectedProvider ].feature_id;

			await apiFetch( {
				path: '/classifai/v1/credential-reuse/copy',
				method: 'POST',
				data: {
					source_feature_id: sourceFeatureId,
					target_feature_id: featureName,
					provider_id: selectedProvider,
				},
			} );

			// Save "don't ask again" preference
			if ( dontAskAgain ) {
				window.localStorage.setItem(
					'classifai_dont_ask_credential_reuse',
					'true'
				);
			}

			onCredentialsReused( selectedProvider );
			onClose();
		} catch {
			// Error handled gracefully
		} finally {
			setIsLoading( false );
		}
	};

	/**
	 * Handle skipping credential reuse.
	 */
	const handleSkip = () => {
		// Save "don't ask again" preference if checked
		if ( dontAskAgain ) {
			window.localStorage.setItem(
				'classifai_dont_ask_credential_reuse',
				'true'
			);
		}
		onClose();
	};

	// Only return null if the modal is not open.
	if ( ! isOpen ) {
		return null;
	}

	const providers = Object.keys( reusableCredentials );

	return (
		<Modal
			title={ __( 'Reuse Existing Credentials', 'classifai' ) }
			onRequestClose={ onClose }
			className="classifai-credential-reuse-modal"
			size="medium"
		>
			{ isLoadingCredentials && (
				<div className="classifai-loading">
					<Icon icon="update" />
					{ __( 'Checking for existing credentials…', 'classifai' ) }
				</div>
			) }
			{ ! isLoadingCredentials && providers.length > 0 && (
				<>
					<Notice status="info" isDismissible={ false }>
						{ sprintf(
							/* translators: %s: Feature label */
							__(
								'We found existing Provider credentials that can be used with %s. Would you like to reuse them?',
								'classifai'
							),
							featureLabel || 'this feature'
						) }
					</Notice>

					<div className="classifai-provider-selection">
						{ providers.map( ( providerId ) => {
							const provider = reusableCredentials[ providerId ];
							return (
								<label // eslint-disable-line jsx-a11y/label-has-associated-control
									aria-label={ sprintf(
										/* translators: %s: Provider name */
										__( 'Select %s Provider', 'classifai' ),
										provider.provider_display_name ||
											providerId
									) }
									key={ providerId }
									className="classifai-provider-option"
								>
									<input
										type="radio"
										name="provider"
										value={ providerId }
										checked={
											selectedProvider === providerId
										}
										onChange={ () =>
											setSelectedProvider( providerId )
										}
									/>
									<div className="classifai-provider-info">
										<strong>
											{ provider.provider_display_name ||
												providerId }
										</strong>
										<span className="classifai-feature-label">
											{ sprintf(
												/* translators: %s: Feature label */
												__(
													'Currently used by %s',
													'classifai'
												),
												provider.feature_label
											) }
										</span>
									</div>
								</label>
							);
						} ) }
					</div>

					<CheckboxControl
						label={ __( "Don't ask me again", 'classifai' ) }
						checked={ dontAskAgain }
						onChange={ setDontAskAgain }
						help={ __(
							'You can always configure credentials manually in the Feature settings.',
							'classifai'
						) }
					/>

					<Flex justify="flex-end" gap={ 3 }>
						<FlexItem>
							<Button variant="tertiary" onClick={ handleSkip }>
								{ __( 'Skip', 'classifai' ) }
							</Button>
						</FlexItem>
						<FlexItem>
							<Button
								variant="primary"
								onClick={ handleReuseCredentials }
								isBusy={ isLoading }
								disabled={ ! selectedProvider || isLoading }
							>
								{ __( 'Reuse Credentials', 'classifai' ) }
							</Button>
						</FlexItem>
					</Flex>
				</>
			) }
			{ ! isLoadingCredentials && providers.length === 0 && (
				<>
					<Notice status="info" isDismissible={ false }>
						{ sprintf(
							/* translators: %s: Feature label */
							__(
								'No compatible existing credentials were found for %s.',
								'classifai'
							),
							featureLabel || 'this feature'
						) }
					</Notice>
					<Flex justify="flex-end">
						<FlexItem>
							<Button variant="primary" onClick={ onClose }>
								{ __( 'Continue', 'classifai' ) }
							</Button>
						</FlexItem>
					</Flex>
				</>
			) }
		</Modal>
	);
};

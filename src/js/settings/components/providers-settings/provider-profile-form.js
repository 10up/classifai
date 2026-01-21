/**
 * WordPress dependencies
 */
import { useState, useEffect, useMemo } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import {
	Button,
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	Flex,
	FlexItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';

/**
 * Translated label for a credential field.
 *
 * @param {string} field Field name.
 * @return {string} Translated label.
 */
function getCredentialFieldLabel( field ) {
	switch ( field ) {
		case 'api_key':
		case 'apikey':
			return __( 'API Key', 'classifai' );
		case 'endpoint_url':
			return __( 'Endpoint URL', 'classifai' );
		case 'deployment':
			return __( 'Deployment name', 'classifai' );
		case 'access_key_id':
			return __( 'Access Key ID', 'classifai' );
		case 'secret_access_key':
			return __( 'Secret Access Key', 'classifai' );
		case 'aws_region':
			return __( 'AWS Region', 'classifai' );
		default:
			return field;
	}
}

/**
 * Build form config object from credential_fields (excl. authenticated).
 *
 * @param {string[]} credentialFields Field names.
 * @param {Object}   savedConfig      Saved config (from Redux).
 * @return {Object} Key-value of field to string.
 */
function getInitialFormValues( credentialFields, savedConfig ) {
	const inputFields = credentialFields.filter(
		( f ) => f !== 'authenticated'
	);
	const out = {};
	for ( const k of inputFields ) {
		const v = savedConfig?.[ k ];
		out[ k ] =
			v !== null && v !== undefined && typeof v === 'string' ? v : '';
	}
	return out;
}

/**
 * Form to edit and save global Provider credentials for one profile.
 *
 * @param {Object}   props           Component props.
 * @param {string}   props.profileId Profile ID (e.g. openai, ollama).
 * @param {Object}   props.profile   Profile { label, credential_fields }.
 * @param {Object}   props.config    Current saved config from Redux.
 * @param {Function} props.onSaved   Callback after successful save; receives new configs.
 */
export function ProviderProfileForm( { profileId, profile, config, onSaved } ) {
	const { setProviderConfigs } = useDispatch( STORE_NAME );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	const credFields = useMemo(
		() => profile?.credential_fields ?? [],
		[ profile?.credential_fields ]
	);
	const inputFields = credFields.filter( ( f ) => f !== 'authenticated' );

	const [ formValues, setFormValues ] = useState( () =>
		getInitialFormValues( credFields, config )
	);
	const [ isSaving, setIsSaving ] = useState( false );

	// Sync form from Redux when config changes (e.g. after save or load).
	useEffect( () => {
		setFormValues( getInitialFormValues( credFields, config ) );
	}, [ profileId, config, credFields ] );

	const updateField = ( field, value ) => {
		setFormValues( ( prev ) => ( { ...prev, [ field ]: value } ) );
	};

	const handleSave = async () => {
		setIsSaving( true );
		try {
			const res = await apiFetch( {
				path: '/classifai/v1/provider-configs',
				method: 'POST',
				data: {
					profile_id: profileId,
					config: formValues,
				},
			} );

			if ( res.success && res.configs ) {
				setProviderConfigs( res.configs );
				onSaved?.( res.configs );

				if ( res.verification_error ) {
					createSuccessNotice(
						__(
							'Credentials saved but verification failed:',
							'classifai'
						) +
							' ' +
							res.verification_error,
						{ type: 'snackbar' }
					);
				} else if ( res.configs[ profileId ]?.authenticated ) {
					createSuccessNotice(
						__( 'Credentials saved and verified.', 'classifai' ),
						{ type: 'snackbar' }
					);
				} else {
					createSuccessNotice(
						__( 'Credentials saved.', 'classifai' ),
						{ type: 'snackbar' }
					);
				}
			}
		} catch ( err ) {
			createErrorNotice(
				err?.message ||
					__(
						'An error occurred while saving. Please try again.',
						'classifai'
					),
				{ type: 'snackbar' }
			);
		} finally {
			setIsSaving( false );
		}
	};

	if ( inputFields.length === 0 ) {
		return null;
	}

	return (
		<div className="classifai-provider-profile-form">
			{ inputFields.map( ( field ) => {
				const meta = {
					label: field,
					type: 'text',
				};
				const controlId = `provider-${ profileId }-${ field }`;

				if ( field.includes( 'api' ) || field.includes( 'secret' ) ) {
					meta.type = 'password';
				}

				// TODO: Ideally this pulls in the actual credential form from the Provider to avoid duplication.
				return (
					<SettingsRow
						key={ field }
						label={ getCredentialFieldLabel( field ) }
					>
						<InputControl
							id={ controlId }
							type={ meta.type }
							value={ formValues[ field ] ?? '' }
							onChange={ ( v ) => updateField( field, v ?? '' ) }
							__next40pxDefaultSize
						/>
					</SettingsRow>
				);
			} ) }
			<div className="classifai-provider-profile-form__actions">
				<Flex justify="end" expanded={ false }>
					<FlexItem>
						<Button
							variant="primary"
							onClick={ handleSave }
							isBusy={ isSaving }
						>
							{ isSaving
								? __( 'Saving…', 'classifai' )
								: __( 'Save', 'classifai' ) }
						</Button>
					</FlexItem>
				</Flex>
			</div>
		</div>
	);
}

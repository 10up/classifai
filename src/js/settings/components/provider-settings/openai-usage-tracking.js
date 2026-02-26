/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Panel,
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../data/store';
import { SettingsRow } from '../settings-row';

/**
 * Component for OpenAI Usage Tracking Provider settings.
 *
 * This component is used within the ProviderSettings component to allow users to configure the OpenAI Usage Tracking Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} OpenAIChatGPTSettings component.
 */
export const OpenAIUsageTrackingSettings = ( { isConfigured = false } ) => {
	const providerName = 'openai_usage_tracking';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const [ saving, setSaving ] = useState( false );
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const { invalidateResolution } = useDispatch( 'core' );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	const isForceRefreshScheduled = useSelect( ( select ) => {
		const site = select( 'core' ).getEntityRecord( 'root', 'site' );
		return site?.classifai_openai_usage_force_refresh || false;
	} );

	const Description = () => (
		<>
			{ __(
				'Required for usage and cost data. Use an organization-level Admin API key, not the project API key.',
				'classifai'
			) }{ ' ' }
			<a
				title={ __( 'OpenAI Usage API docs', 'classifai' ) }
				href="https://developers.openai.com/api/reference/resources/organization/subresources/audit_logs/methods/get_costs"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __( 'OpenAI Usage API docs', 'classifai' ) }
			</a>{ ' ' }
		</>
	);

	const scopeOptions = [
		{
			label: __( 'Current month', 'classifai' ),
			value: 'current_month',
		},
		{
			label: __( 'Year to date', 'classifai' ),
			value: 'year_to_date',
		},
		{
			label: __( 'All time', 'classifai' ),
			value: 'all_time',
		},
	];

	// Refresh site data periodically while a background force refresh is scheduled.
	// Once the force refresh is completed, the site data updates are picked up by existing
	// useSelect hooks, automatically updating the UI.
	useEffect( () => {
		if ( ! isForceRefreshScheduled ) {
			return;
		}

		let isRefreshing = false;

		const intervalId = setInterval( () => {
			if ( isRefreshing ) {
				return;
			}

			isRefreshing = true;

			try {
				invalidateResolution( 'getEntityRecord', [ 'root', 'site' ] );
			} catch ( e ) {
				// Silently handle refresh errors.
			}

			isRefreshing = false;
		}, 5000 );

		return () => clearInterval( intervalId );
	}, [ isForceRefreshScheduled, invalidateResolution ] );

	const forceRefreshData = async () => {
		setSaving( true );
		try {
			await apiFetch( {
				path: '/classifai/v1/openai-usage/force-refresh',
				method: 'POST',
			} );
		} catch ( err ) {
			// eslint-disable-next-line no-console
			console.error( err );
		} finally {
			invalidateResolution( 'getEntityRecord', [ 'root', 'site' ] );
			setSaving( false );
		}
	};

	return (
		<>
			{ ! isConfigured && (
				<SettingsRow
					label={ __( 'Admin API Key', 'classifai' ) }
					description={ <Description /> }
				>
					<TextControl
						id={ `${ providerName }_api_key` }
						type="password"
						value={ providerSettings.api_key || '' }
						onChange={ ( value ) => onChange( { api_key: value } ) }
						className="classifai-api-key"
						placeholder={ __( 'sk-admin-…', 'classifai' ) }
						__next40pxDefaultSize
					/>
				</SettingsRow>
			) }

			<SettingsRow
				label={ __( 'OpenAI Project ID', 'classifai' ) }
				description={ __(
					'Optional. Restrict usage/costs to this project.',
					'classifai'
				) }
			>
				<TextControl
					id={ `${ providerName }_project_id` }
					value={ providerSettings.project_id }
					onChange={ ( value ) => onChange( { project_id: value } ) }
					placeholder="proj_..."
					__next40pxDefaultSize
				/>
			</SettingsRow>

			<SettingsRow
				label={ __( 'Refresh interval (minutes)', 'classifai' ) }
				description={ sprintf(
					/* translators: %d: default minutes */
					__( 'How often to fetch usage (default %d).', 'classifai' ),
					15
				) }
			>
				<TextControl
					id={ `${ providerName }_refresh_interval_minutes` }
					type="number"
					min={ 15 }
					value={ providerSettings.refresh_interval_minutes || 15 }
					onChange={ ( value ) =>
						onChange( { refresh_interval_minutes: value } )
					}
					__next40pxDefaultSize
				/>
			</SettingsRow>

			<SettingsRow
				label={ __( 'Force refresh data', 'classifai' ) }
				description={ __(
					'Click here to force refresh OpenAI usage data',
					'classifai'
				) }
			>
				<Button
					id={ `${ providerName }_force_refresh_data` }
					variant="secondary"
					onClick={ forceRefreshData }
					isBusy={ saving || isForceRefreshScheduled }
					disabled={
						saving || ! isConfigured || isForceRefreshScheduled
					}
				>
					{ __( 'Force refresh data', 'classifai' ) }
				</Button>
			</SettingsRow>

			<Panel
				className="settings-panel"
				header={ __( 'Soft threshold (alert)', 'classifai' ) }
			>
				<PanelBody>
					<ToggleControl
						label={ __(
							'Enable soft threshold alert',
							'classifai'
						) }
						checked={ providerSettings.soft_threshold_enabled }
						onChange={ ( value ) =>
							onChange( { soft_threshold_enabled: value } )
						}
						__next40pxDefaultSize
					/>
					{ providerSettings.soft_threshold_enabled && (
						<>
							<SettingsRow
								label={ __( 'Amount (USD)', 'classifai' ) }
								description={ __(
									'Alert when usage exceeds this amount.',
									'classifai'
								) }
							>
								<TextControl
									type="number"
									min={ 1.0 }
									step={ 1.0 }
									value={ String(
										providerSettings.soft_threshold_amount
									) }
									onChange={ ( value ) =>
										onChange( {
											soft_threshold_amount: value,
										} )
									}
								/>
							</SettingsRow>
							<SettingsRow label={ __( 'Scope', 'classifai' ) }>
								<SelectControl
									value={
										providerSettings.soft_threshold_scope
									}
									options={ scopeOptions }
									onChange={ ( value ) =>
										onChange( {
											soft_threshold_scope: value,
										} )
									}
								/>
							</SettingsRow>
							<SettingsRow
								label={ __( 'Email addresses', 'classifai' ) }
								description={ __(
									'Comma separated emails.',
									'classifai'
								) }
							>
								<TextControl
									value={
										providerSettings.soft_threshold_emails
									}
									onChange={ ( value ) =>
										onChange( {
											soft_threshold_emails: value,
										} )
									}
								/>
							</SettingsRow>
						</>
					) }
				</PanelBody>
			</Panel>

			<Panel
				className="settings-panel"
				header={ __(
					'Hard threshold (disable Features)',
					'classifai'
				) }
			>
				<PanelBody>
					<ToggleControl
						label={ __( 'Enable hard threshold', 'classifai' ) }
						help={ __(
							'Disable all OpenAI Features when hard limit is reached',
							'classifai'
						) }
						checked={ providerSettings.hard_threshold_enabled }
						onChange={ ( value ) =>
							onChange( { hard_threshold_enabled: value } )
						}
					/>

					{ providerSettings.hard_threshold_enabled && (
						<>
							<SettingsRow
								label={ __( 'Amount (USD)', 'classifai' ) }
								description={ __(
									'When exceeded, optionally disable all OpenAI Features.',
									'classifai'
								) }
							>
								<TextControl
									type="number"
									min={ 1.0 }
									step={ 1.0 }
									value={ String(
										providerSettings.hard_threshold_amount
									) }
									onChange={ ( value ) =>
										onChange( {
											hard_threshold_amount: value,
										} )
									}
								/>
							</SettingsRow>
							<SettingsRow label={ __( 'Scope', 'classifai' ) }>
								<SelectControl
									value={
										providerSettings.hard_threshold_scope
									}
									options={ scopeOptions }
									onChange={ ( value ) =>
										onChange( {
											hard_threshold_scope: value,
										} )
									}
								/>
							</SettingsRow>
							<SettingsRow
								label={ __( 'Email addresses', 'classifai' ) }
								description={ __(
									'Comma separated emails.',
									'classifai'
								) }
							>
								<TextControl
									value={
										providerSettings.hard_threshold_emails
									}
									onChange={ ( value ) =>
										onChange( {
											hard_threshold_emails: value,
										} )
									}
								/>
							</SettingsRow>
						</>
					) }
				</PanelBody>
			</Panel>
		</>
	);
};

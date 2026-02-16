/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import {
	Panel,
	PanelBody,
	ToggleControl,
	TextControl,
	SelectControl,
	Button,
	Notice,
	Spinner,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';

const ADMIN_API_DOCS_URL =
	'https://platform.openai.com/docs/api-reference/usage';

export const OpenAIPricingSettings = () => {
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ data, setData ] = useState( {
		pricing: null,
		hard_limit_reached: false,
		hard_limit_override: false,
	} );
	const [ form, setForm ] = useState( {} );

	const load = useCallback( async () => {
		setLoading( true );
		try {
			const [ settingsRes ] = await Promise.all( [
				apiFetch( { path: '/classifai/v1/openai-pricing-settings' } ),
			] );
			setData( settingsRes );
			const p = settingsRes.pricing || {};
			setForm( {
				enabled: !! p.enabled,
				admin_api_key: p.admin_api_key || '',
				project_id: p.project_id || '',
				refresh_interval_minutes: p.refresh_interval_minutes ?? 15,
				soft_threshold_enabled: !! p.soft_threshold_enabled,
				soft_threshold_amount: p.soft_threshold_amount ?? '',
				soft_threshold_scope: p.soft_threshold_scope || 'current_month',
				soft_threshold_emails: p.soft_threshold_emails || '',
				hard_threshold_amount: p.hard_threshold_amount ?? '',
				hard_threshold_scope: p.hard_threshold_scope || 'current_month',
				hard_threshold_emails: p.hard_threshold_emails || '',
				hard_threshold_enabled: !! p.hard_threshold_enabled,
			} );
		} catch ( err ) {
			createErrorNotice(
				err.message ||
					__( 'Failed to load pricing settings.', 'classifai' )
			);
		} finally {
			setLoading( false );
		}
	}, [ createErrorNotice ] );

	useEffect( () => {
		load();
	}, [ load ] );

	const updateForm = ( key, value ) => {
		setForm( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	const saveSettings = async () => {
		setSaving( true );
		try {
			await apiFetch( {
				path: '/classifai/v1/openai-pricing-settings',
				method: 'POST',
				data: {
					pricing: {
						enabled: form.enabled,
						admin_api_key: form.admin_api_key,
						project_id: form.project_id,
						refresh_interval_minutes:
							Number( form.refresh_interval_minutes ) || 15,
						soft_threshold_enabled: form.soft_threshold_enabled,
						soft_threshold_amount:
							Number( form.soft_threshold_amount ) || 0,
						soft_threshold_scope: form.soft_threshold_scope,
						soft_threshold_emails: form.soft_threshold_emails,
						hard_threshold_amount:
							Number( form.hard_threshold_amount ) || 0,
						hard_threshold_scope: form.hard_threshold_scope,
						hard_threshold_emails: form.hard_threshold_emails,
						hard_threshold_enabled: form.hard_threshold_enabled,
					},
				},
			} );
			createSuccessNotice( __( 'Pricing settings saved.', 'classifai' ), {
				type: 'snackbar',
			} );
			load();
		} catch ( err ) {
			createErrorNotice(
				err.message ||
					__( 'Failed to save pricing settings.', 'classifai' )
			);
		} finally {
			setSaving( false );
		}
	};

	const forceRefreshData = async () => {
		setSaving( true );
		try {
			await apiFetch( {
				path: '/classifai/v1/openai-pricing-settings',
				method: 'POST',
				data: {
					force_refresh: true,
				},
			} );
			createSuccessNotice( __( 'Data refreshed.', 'classifai' ), {
				type: 'snackbar',
			} );
			load();
		} catch ( err ) {
			createErrorNotice(
				err.message || __( 'Failed to refresh data.', 'classifai' )
			);
		} finally {
			setSaving( false );
		}
	};

	if ( loading ) {
		return (
			<div className="classifai-openai-pricing-settings">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="classifai-openai-pricing-settings">
			<Panel>
				<PanelBody title={ __( 'OpenAI usage & costs', 'classifai' ) }>
					<p className="classifai-pricing-description">
						{ __(
							'Monitor OpenAI usage and set soft/hard spending alerts. Requires an OpenAI Admin API key (organization-level).',
							'classifai'
						) }
					</p>

					<ToggleControl
						label={ __(
							'Enable OpenAI pricing usage',
							'classifai'
						) }
						checked={ form.enabled }
						onChange={ ( value ) => updateForm( 'enabled', value ) }
					/>

					{ form.enabled && (
						<>
							<SettingsRow
								label={ __(
									'OpenAI Admin API key',
									'classifai'
								) }
								description={
									<>
										{ __(
											'Required for usage and cost data. Use an organization-level Admin API key, not the project API key.',
											'classifai'
										) }{ ' ' }
										<a
											href={ ADMIN_API_DOCS_URL }
											target="_blank"
											rel="noopener noreferrer"
										>
											{ __(
												'OpenAI Usage API docs',
												'classifai'
											) }
										</a>
									</>
								}
							>
								<TextControl
									type="password"
									value={ form.admin_api_key }
									onChange={ ( value ) =>
										updateForm( 'admin_api_key', value )
									}
									placeholder={ __(
										'sk-… (Admin API key)',
										'classifai'
									) }
								/>
							</SettingsRow>

							<SettingsRow
								label={ __( 'OpenAI Project ID', 'classifai' ) }
								description={ __(
									'Optional. Restrict usage/costs to this project.',
									'classifai'
								) }
							>
								<TextControl
									value={ form.project_id }
									onChange={ ( value ) =>
										updateForm( 'project_id', value )
									}
									placeholder="proj_..."
								/>
							</SettingsRow>

							<SettingsRow
								label={ __(
									'Refresh interval (minutes)',
									'classifai'
								) }
								description={ sprintf(
									/* translators: %d: default minutes */
									__(
										'How often to fetch usage (default %d). Filter: classifai_openai_usage_refresh_interval_minutes',
										'classifai'
									),
									15
								) }
							>
								<TextControl
									type="number"
									min={ 1 }
									value={ String(
										form.refresh_interval_minutes
									) }
									onChange={ ( value ) =>
										updateForm(
											'refresh_interval_minutes',
											value
										)
									}
								/>
							</SettingsRow>
						</>
					) }
				</PanelBody>
			</Panel>

			{ form.enabled && (
				<>
					<Panel>
						<PanelBody
							title={ __(
								'Soft threshold (alert)',
								'classifai'
							) }
						>
							<ToggleControl
								label={ __(
									'Enable soft threshold alert',
									'classifai'
								) }
								checked={ form.soft_threshold_enabled }
								onChange={ ( value ) =>
									updateForm(
										'soft_threshold_enabled',
										value
									)
								}
							/>
							{ form.soft_threshold_enabled && (
								<>
									<SettingsRow
										label={ __( 'Amount', 'classifai' ) }
										description={ __(
											'Alert when usage exceeds this amount.',
											'classifai'
										) }
									>
										<TextControl
											type="number"
											min={ 0 }
											step={ 0.01 }
											value={ String(
												form.soft_threshold_amount
											) }
											onChange={ ( value ) =>
												updateForm(
													'soft_threshold_amount',
													value
												)
											}
										/>
									</SettingsRow>
									<SettingsRow
										label={ __( 'Scope', 'classifai' ) }
									>
										<SelectControl
											value={ form.soft_threshold_scope }
											options={ [
												{
													label: __(
														'Current month',
														'classifai'
													),
													value: 'current_month',
												},
												{
													label: __(
														'Year to date',
														'classifai'
													),
													value: 'year_to_date',
												},
												{
													label: __(
														'All time',
														'classifai'
													),
													value: 'all_time',
												},
											] }
											onChange={ ( value ) =>
												updateForm(
													'soft_threshold_scope',
													value
												)
											}
										/>
									</SettingsRow>
									<SettingsRow
										label={ __(
											'Email addresses',
											'classifai'
										) }
										description={ __(
											'Comma or newline separated.',
											'classifai'
										) }
									>
										<TextControl
											value={ form.soft_threshold_emails }
											onChange={ ( value ) =>
												updateForm(
													'soft_threshold_emails',
													value
												)
											}
											style={ {
												width: '100%',
												minHeight: 60,
											} }
										/>
									</SettingsRow>
								</>
							) }
						</PanelBody>
					</Panel>

					<Panel>
						<PanelBody
							title={ __(
								'Hard threshold (disable features)',
								'classifai'
							) }
						>
							<ToggleControl
								label={ __(
									'Enable hard threshold',
									'classifai'
								) }
								help={ __(
									'Disable all OpenAI features when hard limit is reached',
									'classifai'
								) }
								checked={ form.hard_threshold_enabled }
								onChange={ ( value ) =>
									updateForm(
										'hard_threshold_enabled',
										value
									)
								}
							/>
							{ data.hard_limit_reached && (
								<Notice status="error" isDismissible={ false }>
									{ __(
										'OpenAI features are currently disabled due to hard limit reached. Increase the hard limit amount OR disable hard threshold to re-enable.',
										'classifai'
									) }
								</Notice>
							) }
							{ form.hard_threshold_enabled && (
								<>
									<SettingsRow
										label={ __( 'Amount', 'classifai' ) }
										description={ __(
											'When exceeded, optionally disable all OpenAI features.',
											'classifai'
										) }
									>
										<TextControl
											type="number"
											min={ 0 }
											step={ 0.01 }
											value={ String(
												form.hard_threshold_amount
											) }
											onChange={ ( value ) =>
												updateForm(
													'hard_threshold_amount',
													value
												)
											}
										/>
									</SettingsRow>
									<SettingsRow
										label={ __( 'Scope', 'classifai' ) }
									>
										<SelectControl
											value={ form.hard_threshold_scope }
											options={ [
												{
													label: __(
														'Current month',
														'classifai'
													),
													value: 'current_month',
												},
												{
													label: __(
														'Year to date',
														'classifai'
													),
													value: 'year_to_date',
												},
												{
													label: __(
														'All time',
														'classifai'
													),
													value: 'all_time',
												},
											] }
											onChange={ ( value ) =>
												updateForm(
													'hard_threshold_scope',
													value
												)
											}
										/>
									</SettingsRow>
									<SettingsRow
										label={ __(
											'Email addresses',
											'classifai'
										) }
									>
										<TextControl
											value={ form.hard_threshold_emails }
											onChange={ ( value ) =>
												updateForm(
													'hard_threshold_emails',
													value
												)
											}
											style={ {
												width: '100%',
												minHeight: 60,
											} }
										/>
									</SettingsRow>
								</>
							) }
						</PanelBody>
					</Panel>
				</>
			) }

			<Panel>
				<PanelBody>
					<Button
						variant="primary"
						onClick={ saveSettings }
						isBusy={ saving }
						disabled={ saving }
					>
						{ __( 'Save pricing settings', 'classifai' ) }
					</Button>
					<Button
						variant="secondary"
						onClick={ forceRefreshData }
						isBusy={ saving }
						disabled={ saving || ! form.enabled }
						style={ { marginLeft: 10 } }
					>
						{ __( 'Force refresh data', 'classifai' ) }
					</Button>
				</PanelBody>
			</Panel>
		</div>
	);
};

/**
 * WordPress dependencies
 */
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../settings-row';

/**
 * Component for Amazon Polly Provider settings.
 *
 * This is the base component for Amazon Polly settings.
 *
 * @param {Object}   props                  Component props.
 * @param {Object}   props.providerSettings The provider settings.
 * @param {Function} props.onChange         Function to call when the provider settings change.
 *
 * @return {React.ReactElement} AmazonPollyBaseSettings component.
 */
export const AmazonPollyBaseSettings = ( { providerSettings, onChange } ) => {
	return (
		<>
			<SettingsRow label={ __( 'Access key', 'classifai' ) }>
				<InputControl
					id="aws_polly_access_key_id"
					type="text"
					value={ providerSettings.access_key_id || '' }
					onChange={ ( value ) =>
						onChange( { access_key_id: value } )
					}
					__next40pxDefaultSize
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Secret access key', 'classifai' ) }
				description={ __(
					'Enter the AWS secret access key.',
					'classifai'
				) }
			>
				<InputControl
					id="aws_polly_secret_access_key"
					type="password"
					value={ providerSettings.secret_access_key || '' }
					onChange={ ( value ) =>
						onChange( { secret_access_key: value } )
					}
					__next40pxDefaultSize
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Region', 'classifai' ) }
				description={
					<>
						{ __( 'Enter the AWS Region. eg:', 'classifai' ) }{ ' ' }
						<code>{ __( 'us-east-1', 'classifai' ) }</code>.
					</>
				}
			>
				<InputControl
					id="aws_polly_aws_region"
					type="text"
					value={ providerSettings.aws_region || '' }
					onChange={ ( value ) => onChange( { aws_region: value } ) }
					__next40pxDefaultSize
				/>
			</SettingsRow>
		</>
	);
};

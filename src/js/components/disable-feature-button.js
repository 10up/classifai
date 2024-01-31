/* global ClassifAI */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 *  ClassifAI Disable Feature Link
 *
 * @param {Object} props         The block props.
 * @param {string} props.feature The feature to disable.
 */
export const DisableFeatureButton = ( { feature } ) => {
	// Check if user has permission to disable feature.
	if (
		! feature ||
		! ClassifAI?.opt_out_enabled_features?.includes( feature )
	) {
		return null;
	}

	return (
		<Button
			href={ ClassifAI?.profile_url }
			variant="link"
			className="classifai-disable-feature-link"
			target="_blank"
			rel="noopener noreferrer"
			label={ __(
				'Opt out of using this ClassifAI feature',
				'classifai'
			) }
			text={ __( 'Disable this ClassifAI feature', 'classifai' ) }
		/>
	);
};

/**
 * Excerpt generator component for the excerpt panel.
 */

/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { update } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useExcerptGeneration } from './useExcerptGeneration';
import { DisableFeatureButton } from '../../../components';

/**
 * ExcerptGeneration component.
 *
 * Provides a button to generate an excerpt.
 *
 * @return {React.JSX.Element | null} The excerpt generation component.
 */
export default function ExcerptGeneration() {
	const { isGenerating, hasExcerpt, handleGenerate } = useExcerptGeneration();

	let buttonLabel = __( 'Generate excerpt', 'classifai' );

	if ( isGenerating ) {
		buttonLabel = __( 'Generating…', 'classifai' );
	} else if ( hasExcerpt ) {
		buttonLabel = __( 'Regenerate excerpt', 'classifai' );
	}

	return (
		<>
			<Button
				icon={ update }
				variant="secondary"
				onClick={ handleGenerate }
				disabled={ isGenerating }
				accessibleWhenDisabled
				isBusy={ isGenerating }
			>
				{ buttonLabel }
			</Button>
			<DisableFeatureButton feature="feature_excerpt_generation" />
		</>
	);
}

/**
 * External Dependencies.
 */
import { registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';

/**
 * Internal Dependencies.
 */
import { ReactComponent as icon } from '../../../../assets/img/block-icon.svg';

domReady( () => {
	const{ default_template = 'title-date' } = classifaiRecommendedContentSettings;
	const registeredVariations = wp.blocks.getBlockVariations( 'core/query' );
	const variation =  registeredVariations.find( variation => variation.name === default_template );

	registerBlockVariation( 'core/query', {
		name: 'classifai/recommended-content',
		title: __( 'Recommended Content', 'classifai' ),
		description: __(
			'Render recommended content based on embeddings.',
			'classifai'
		),
		icon,
		attributes: {
			namespace: 'classifai/recommended-content',
			align: 'wide',
			className: 'classifai-recommended-content',
			query: {
				postType: 'post',
				perPage: 3,
				useAI: true,
			},
		},
		allowedControls: [ 'order', 'postCount' ],
		innerBlocks: variation.innerBlocks,
		isActive: [ 'namespace' ],
		scope: [ 'inserter' ],
	} );
} );


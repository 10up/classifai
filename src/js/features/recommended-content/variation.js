/**
 * External Dependencies.
 */
import { registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal Dependencies.
 */
import { ReactComponent as icon } from '../../../../assets/img/block-icon.svg';

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
	allowedControls: [],
	innerBlocks: [
		[
			'core/post-template',
			{
				layout: { type: 'grid', columnCount: 3 },
			},
			[
				[ 'core/post-featured-image' ],
				[ 'core/post-title', { level: 3, isLink: true } ],
			],
		],
	],
	isActive: [ 'namespace' ],
	scope: [ 'inserter' ],
} );

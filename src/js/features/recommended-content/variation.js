/**
 * WordPress dependencies
 */
import { registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';

/**
 * Internal dependencies
 */
import { ReactComponent as icon } from '../../../../assets/img/block-icon.svg';

domReady( () => {
	const { default_template: defaultTemplate = 'title-date' } =
		classifaiRecommendedContentSettings;
	const registeredVariations = wp.blocks.getBlockVariations( 'core/query' );
	const variation = registeredVariations.find(
		( __variation ) => __variation.name === defaultTemplate
	);

	variation.innerBlocks = variation.innerBlocks.filter(
		( innerBlock ) => 'core/post-template' === innerBlock[ 0 ]
	);
	variation.innerBlocks.forEach( ( innerBlock, index ) => {
		if ( 'core/post-template' === innerBlock[ 0 ] ) {
			variation.innerBlocks[ index ][ 1 ] = Object.assign(
				variation.innerBlocks[ index ][ 1 ],
				{
					layout: {
						type: 'grid',
						columnCount: 3,
					},
				}
			);

			if ( Array.isArray( innerBlock[ 2 ] ) ) {
				innerBlock[ 2 ].forEach( ( __innerBlock, __index ) => {
					if ( 'core/post-title' === __innerBlock[ 0 ] ) {
						variation.innerBlocks[ index ][ 2 ][ __index ][ 1 ] =
							variation.innerBlocks[ index ][ 2 ][
								__index
							][ 1 ] || {};
						variation.innerBlocks[ index ][ 2 ][ __index ][ 1 ] =
							Object.assign(
								variation.innerBlocks[ index ][ 2 ][
									__index
								][ 1 ],
								{
									level: 3,
									isLink: true,
								}
							);
					}
				} );
			}
		}
	} );

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

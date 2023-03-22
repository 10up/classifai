/* eslint object-shorthand: 0 */

import Prompt from './views/prompt';

const currentMediaFrame = wp.media.view.MediaFrame.Select;

/**
 * Extend the core MediaFrame.Select view.
 *
 * We do this in order to add our new tab and
 * the content for that tab.
 */
wp.media.view.MediaFrame.Select = currentMediaFrame.extend( {
	/**
	 * Bind region mode event callbacks.
	 *
	 * @see media.controller.Region.render
	 */
	bindHandlers: function () {
		currentMediaFrame.prototype.bindHandlers.apply( this, arguments );

		this.on( 'content:render:generate', this.generateContent, this );
	},

	/**
	 * Render callback for the router region in the `browse` mode.
	 *
	 * @param {wp.media.view.Router} routerView
	 */
	browseRouter: function ( routerView ) {
		currentMediaFrame.prototype.browseRouter.apply( this, arguments );

		routerView.set( {
			generate: {
				text: classifaiDalleData.tabText,
				priority: 60,
			},
		} );
	},

	/**
	 * Render callback for the content region in the `generate` mode.
	 */
	generateContent: function () {
		this.content.set( new Prompt().render() );
	},
} );

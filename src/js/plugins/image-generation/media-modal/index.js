/* eslint object-shorthand: 0 */

import Prompt from './views/prompt';
import '../../../../scss/media-modal.scss';

const currentMediaSelectFrame = wp.media.view.MediaFrame.Select;
const currentPostFrame = wp.media.view.MediaFrame.Post;

/**
 * Extend the core MediaFrame.Select view.
 *
 * We do this in order to add our new tab and
 * the content for that tab in the block editor.
 */
wp.media.view.MediaFrame.Select = currentMediaSelectFrame.extend( {
	/**
	 * Bind region mode event callbacks.
	 *
	 * @see media.controller.Region.render
	 */
	bindHandlers: function () {
		currentMediaSelectFrame.prototype.bindHandlers.apply( this, arguments );

		this.on( 'content:render:generate', this.generateContent, this );
	},

	/**
	 * Render callback for the router region in the `browse` mode.
	 *
	 * @param {wp.media.view.Router} routerView
	 */
	browseRouter: function ( routerView ) {
		currentMediaSelectFrame.prototype.browseRouter.apply( this, arguments );

		routerView.set( {
			generate: {
				text: classifaiDalleData.tabText,
				priority: 30,
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

/**
 * Extend the core MediaFrame.Post view.
 *
 * We do this in order to add our new tab and
 * the content for that tab within the media
 * modal used in the Classic Editor.
 */
wp.media.view.MediaFrame.Post = currentPostFrame.extend( {
	/**
	 * Bind region mode event callbacks.
	 *
	 * @see media.controller.Region.render
	 */
	bindHandlers: function () {
		currentPostFrame.prototype.bindHandlers.apply( this, arguments );

		this.on( 'content:render:generate', this.generateContent, this );
	},

	/**
	 * Render callback for the router region in the `browse` mode.
	 *
	 * @param {wp.media.view.Router} routerView
	 */
	browseRouter: function ( routerView ) {
		currentPostFrame.prototype.browseRouter.apply( this, arguments );

		routerView.set( {
			generate: {
				text: classifaiDalleData.tabText,
				priority: 30,
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

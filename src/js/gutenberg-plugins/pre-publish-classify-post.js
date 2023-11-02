/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { withSelect } from '@wordpress/data';
import { PluginPrePublishPanel } from '@wordpress/edit-post';
import { Component } from '@wordpress/element';

/**
 * Internal dependencies
 */

const PrePubPanel = ( { children } ) => {
	const panelBodyTitle = [
		__( 'Suggestion:' ),
		<span className="editor-post-publish-panel__link" key="label">
			{ __( 'Classify Post', 'classifai' ) }
		</span>,
	];

	return (
		<PluginPrePublishPanel
			title={ panelBodyTitle }
			icon="aside"
			initialOpen={ true }
		>
			{ children }
		</PluginPrePublishPanel>
	);
};

class PrePubClassifyPost extends Component {
	constructor( props ) {
		super( props );
	}

	componentDidUpdate( prevProps ) {
		// Update our state when the publish panel opens.
		if (
			this.props.isPublishPanelOpen &&
			! this.props.popupOpened &&
			prevProps.isPublishPanelOpen !== this.props.isPublishPanelOpen
		) {
			this.props.callback();
		}
	}

	render() {
		// retun null if popupOpened is true
		if ( this.props.popupOpened ) {
			return null;
		}

		return <PrePubPanel>{ this.props.children }</PrePubPanel>;
	}
}

export default withSelect( ( select ) => {
	return {
		isPublishPanelOpen: select( 'core/edit-post' ).isPublishSidebarOpened(),
	};
} )( PrePubClassifyPost );

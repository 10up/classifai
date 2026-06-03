/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { withSelect } from '@wordpress/data';
import { PluginPrePublishPanel } from '@wordpress/editor';
import { Component } from '@wordpress/element';

const PrePubPanel = ( { children } ) => {
	const panelBodyTitle = [
		__( 'Suggestion:', 'classifai' ),
		<span className="editor-post-publish-panel__link" key="label">
			{ __( 'Classify Post', 'classifai' ) }
		</span>,
	];

	return (
		<PluginPrePublishPanel
			title={ panelBodyTitle }
			icon="aside"
			initialOpen
		>
			{ children }
		</PluginPrePublishPanel>
	);
};

class PrePubClassifyPost extends Component {
	/**
	 * Render the component.
	 */
	render() {
		// return null if popupOpened is true
		if ( this.props.popupOpened ) {
			return null;
		}

		return <PrePubPanel>{ this.props.children }</PrePubPanel>;
	}
}

export default withSelect( ( select ) => {
	return {
		isPublishPanelOpen: select( 'core/editor' ).isPublishSidebarOpened(),
	};
} )( PrePubClassifyPost );

/**
 * WordPress dependencies
 */
const { __ } = wp.i18n;
const { withSelect } = wp.data;
const { PluginPrePublishPanel } = wp.editPost;
const { Component } = wp.element;

/**
 * Internal dependencies
 */

const ExcerptPanel = ( { children } ) => {
	const panelBodyTitle = [
		__( 'Suggestion:' ),
		<span className="editor-post-publish-panel__link" key="label">
			{ __( 'Generate excerpt', 'classifai' ) }
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

class MaybeExcerptPanel extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			hadExcerptWhenOpeningThePanel: '' !== props.excerpt,
		};
	}

	componentDidUpdate( prevProps ) {
		// Update our state when the publish panel opens.
		if (
			this.props.isPublishPanelOpen &&
			prevProps.isPublishPanelOpen !== this.props.isPublishPanelOpen
		) {
			this.setState( {
				hadExcerptWhenOpeningThePanel: '' !== this.props.excerpt,
			} );
		}
	}

	/*
	 * We only want to show the excerpt panel if the post didn't have
	 * an excerpt when the user hit the Publish button.
	 */
	render() {
		if ( ! this.state.hadExcerptWhenOpeningThePanel ) {
			return (
				<ExcerptPanel>
					{ this.props.children }
				</ExcerptPanel>
			);
		}

		return null;
	}
}

export default withSelect( ( select ) => {
	return {
		excerpt:
			select( 'core/editor' ).getEditedPostAttribute( 'excerpt' ),
		isPublishPanelOpen:
			select( 'core/edit-post' ).isPublishSidebarOpened(),
	};
} )( MaybeExcerptPanel );

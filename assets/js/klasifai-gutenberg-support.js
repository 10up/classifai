(function() {

	var RefreshManager   = function() {
		this.startedSaving = false;
		this.reloading     = false;
		this.select        = wp.data.select;
		this.subscribe     = wp.data.subscribe;
	};

	RefreshManager.prototype = {

		enable: function() {
			var self = this;

			this.subscribe( function() {
				self.didStateChange();
			} );
		},

		refresh: function() {
			if ( ! this.reloading ) {
				this.reloading = true;
				location.reload();
			}
		},

		didStateChange: function() {
			if ( this.isSavingPost() ) {
				this.startedSaving = true;
			} else if ( this.hasSavedPost() ) {
				this.refresh();
			}
		},

		getEditor: function() {
			return this.select( 'core/editor' );
		},

		isSavingPost: function() {
			return this.getEditor().isSavingPost();
		},

		hasSavedPost: function() {
			var editor = this.getEditor();
			var result = this.startedSaving
				&& editor.didPostSaveRequestSucceed()
				&& editor.isCurrentPostPublished();

			return result;
		}

	}

	document.addEventListener( 'DOMContentLoaded', function() {
		var manager = new RefreshManager();
		manager.enable();
	} );

})();

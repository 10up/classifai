describe( '[Language Processing] Text to Speech (Microsoft Azure) Tests', () => {
	before( () => {
		cy.login();
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=azure_text_to_speech'
		);
		cy.get( '#azure_text_to_speech_post_types_post' ).check( 'post' );
		cy.get( '#url' ).clear();
		cy.get( '#url' ).type( 'https://service.com' );
		cy.get( '#api_key' ).type( 'password' );
		cy.get( '#submit' ).click();

		cy.get( '#voice' ).select( 'en-AU-AnnetteNeural|Female' );
		cy.get( '#submit' ).click();
	} );

	it( 'Generates audio from text', () => {
		cy.createPost( {
			title: 'Text to Speech test',
			content:
				"This feature uses Microsoft's Text to Speech capabilities.",
		} );

		cy.get( 'button[aria-label="Close panel"]' ).click();
		cy.get( 'button[data-label="Post"]' ).click();
		cy.get( '.classifai-panel' ).click();
		cy.get( '#classifai-audio-controls__preview-btn' ).should( 'exist' );
	} );

	it( 'Audio controls are visible if supported by post type', () => {
		cy.visit( '/text-to-speech-test/' );
		cy.get( '.class-post-audio-controls' ).should( 'be.visible' );
	} );

	it( 'a11y - aria-labels', () => {
		cy.visit( '/text-to-speech-test/' );
		cy.get( '.dashicons-controls-play' ).should( 'be.visible' );
		cy.get( '.class-post-audio-controls' ).should(
			'have.attr',
			'aria-label',
			'Play audio'
		);

		cy.get( '.class-post-audio-controls' ).click();

		cy.get( '.dashicons-controls-play' ).should( 'not.be.visible' );
		cy.get( '.class-post-audio-controls' ).should(
			'have.attr',
			'aria-label',
			'Pause audio'
		);

		cy.get( '.class-post-audio-controls' ).click();
		cy.get( '.dashicons-controls-play' ).should( 'be.visible' );
		cy.get( '.class-post-audio-controls' ).should(
			'have.attr',
			'aria-label',
			'Play audio'
		);
	} );

	it( 'a11y - keyboard accessibility', () => {
		cy.visit( '/text-to-speech-test/' );
		cy.get( '.class-post-audio-controls' )
			.tab( { shift: true } )
			.tab()
			.type( '{enter}' );
		cy.get( '.dashicons-controls-pause' ).should( 'be.visible' );
		cy.get( '.class-post-audio-controls' ).should(
			'have.attr',
			'aria-label',
			'Pause audio'
		);

		cy.get( '.class-post-audio-controls' ).type( '{enter}' );
		cy.get( '.dashicons-controls-play' ).should( 'be.visible' );
		cy.get( '.class-post-audio-controls' ).should(
			'have.attr',
			'aria-label',
			'Play audio'
		);
	} );

	it( 'Can see the enable button in a post (Classic Editor)', () => {
		cy.visit( '/wp-admin/plugins.php' );
		cy.get( '#activate-classic-editor' ).click();

		cy.classicCreatePost( {
			title: 'Text to Speech test classic',
			content:
				"This feature uses Microsoft's Text to Speech capabilities.",
			postType: 'post',
		} );

		cy.get( '#classifai-text-to-speech-meta-box' ).should( 'exist' );
		cy.get( '#classifai_synthesize_speech' ).check();
		cy.get( '#classifai-audio-preview' ).should( 'exist' );

		cy.visit( '/text-to-speech-test/' );
		cy.get( '.class-post-audio-controls' ).should( 'be.visible' );

		cy.visit( '/wp-admin/plugins.php' );
		cy.get( '#deactivate-classic-editor' ).click();
	} );

	it( 'Disable support for post type Post', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=azure_text_to_speech'
		);
		cy.get( '#azure_text_to_speech_post_types_post' ).uncheck( 'post' );
		cy.get( '#submit' ).click();

		cy.visit( '/text-to-speech-test/' );
		cy.get( '.class-post-audio-controls' ).should( 'not.exist' );
	} );
} );

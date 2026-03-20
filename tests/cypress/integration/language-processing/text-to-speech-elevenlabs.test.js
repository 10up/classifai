describe( '[Language Processing] Text to Speech (ElevenLabs) Tests', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings(
			'language_processing/feature_text_to_speech_generation'
		);
		cy.get( '#classifai-logo' ).should( 'exist' );
		cy.get( '.settings-allowed-post-types input#post' ).check();
		cy.selectProvider( 'elevenlabs_text_to_speech' );
		cy.get( '#elevenlabs_api_key' ).clear().type( 'password' );
		cy.enableFeature();
		cy.saveFeatureSettings();

		cy.get( '#elevenlabs_text_to_speech_model' ).select(
			'eleven_multilingual_v2'
		);
		cy.get( '#elevenlabs_text_to_speech_voice' ).select(
			'21m00Tcm4TlvDq8ikWAM'
		);
		cy.saveFeatureSettings();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Generates audio from text', () => {
		cy.createPost( {
			title: 'Text to Speech test',
			content:
				"This feature uses ElevenLabs's Text to Speech capabilities.",
		} );

		cy.get( 'button[aria-label="Close panel"]' ).click();
		cy.openDocumentSettingsSidebar();
		cy.get( '.classifai-panel' ).click();
		cy.get( '.classifai-panel' ).should(
			'contain',
			'Audio generation is in progress…'
		);
		cy.exec(
			'npx wp-env run tests-cli wp action-scheduler run --hooks=classifai_schedule_text_to_speech_job',
			{
				timeout: 20000, // 20 seconds
				failOnNonZeroExit: true,
			}
		);
		cy.reload();
		cy.get( '.classifai-panel' ).should(
			'not.contain',
			'Audio generation is in progress…'
		);
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
		cy.enableClassicEditor();

		cy.classicCreatePost( {
			title: 'Text to Speech test classic',
			content:
				"This feature uses ElevenLabs's Text to Speech capabilities.",
			postType: 'post',
		} );

		cy.get( '#classifai-text-to-speech-meta-box' ).should(
			'contain',
			'Audio generation is in progress…'
		);
		cy.exec(
			'npx wp-env run tests-cli wp action-scheduler run --hooks=classifai_schedule_text_to_speech_job',
			{
				timeout: 20000, // 20 seconds
				failOnNonZeroExit: true,
			}
		);
		cy.reload();
		cy.get( '#classifai-text-to-speech-meta-box' ).should(
			'not.contain',
			'Audio generation is in progress…'
		);

		cy.get( '#classifai-text-to-speech-meta-box' ).should( 'exist' );
		cy.get( '#classifai_synthesize_speech' ).check();
		cy.get( '#classifai-audio-preview' ).should( 'exist' );

		cy.visit( '/text-to-speech-test/' );
		cy.get( '.class-post-audio-controls' ).should( 'be.visible' );

		cy.disableClassicEditor();
	} );
} );

describe( 'Common Feature Fields', () => {
	beforeEach( () => {
		cy.login();
	} );

	const services = {
		language_processing: {
			feature_classification: 'Classification',
			feature_title_generation: 'Title Generation',
			feature_excerpt_generation: 'Excerpt Generation',
			feature_content_resizing: 'Content Resizing',
			feature_text_to_speech_generation: 'Text to Speech',
			feature_audio_transcripts_generation:
				'Audio Transcripts Generation',
		},
		image_processing: {
			feature_image_generation: 'Image Generation',
			feature_descriptive_text_generator: 'Descriptive Text Generator',
			feature_image_tags_generator: 'Image Tags Generator',
			feature_image_cropping: 'Image Cropping',
			feature_image_to_text_generator: 'Image Text Extraction',
			feature_pdf_to_text_generation: 'PDF Text Extraction',
		},
		content_recommendation: {
			feature_recommended_content: 'Recommended Content',
		},
		usage_tracking: {
			api_usage_tracking: 'API Usage Tracking',
		},
	};

	const allowedRoles = [ 'administrator', 'editor', 'author', 'contributor' ];

	Object.keys( services ).forEach( ( service ) => {
		const features = services[ service ];

		Object.keys( features ).forEach( ( feature ) => {
			it( `"${ features[ feature ] }" feature common fields`, () => {
				cy.visitFeatureSettings( `${ service }/${ feature }` );

				cy.get( '.classifai-enable-feature-toggle input' ).should(
					'exist'
				);
				cy.openUserPermissionsPanel();
				cy.get(
					'.classifai-settings__user-based-opt-out input[type="checkbox"]'
				).should( 'exist' );
				cy.get( 'body' ).then( ( $body ) => {
					if (
						$body.find( '.classifai-settings-edit-provider' )
							.length > 0
					) {
						cy.get( '.classifai-settings-edit-provider' ).click();
					}
				} );
				cy.get( '.classifai-provider-select select' ).should( 'exist' );

				for ( const role of allowedRoles ) {
					if (
						'feature_image_generation' === feature &&
						'contributor' === role
					) {
						continue;
					}

					const roleField = cy.get(
						`.settings-allowed-roles input#${ role }`
					);
					roleField.should( 'be.visible' );

					if (
						'api_usage_tracking' === feature &&
						'administrator' !== role
					) {
						roleField.should( 'not.be.checked' );
					} else {
						roleField.should( 'be.checked' );
					}
				}

				cy.get( '.classifai-settings__users' ).should( 'be.visible' );
			} );
		} );
	} );
} );

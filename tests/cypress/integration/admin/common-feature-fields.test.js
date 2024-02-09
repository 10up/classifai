describe('Common Feature Fields', () => {
	beforeEach( () => {
		cy.login();
	} );

	const features = {
		feature_classification: 'Classification',
		feature_title_generation: 'Title Generation',
		feature_excerpt_generation: 'Excerpt Generation',
		feature_content_resizing: 'Content Resizing',
		feature_text_to_speech_generation: 'Text to Speech',
		feature_audio_transcripts_generation: 'Audio Transcripts Generation',
		feature_image_generation: 'Image Generation',
		feature_descriptive_text_generator: 'Descriptive Text Generator',
		feature_image_tags_generator: 'Image Tags Generator',
		feature_image_cropping: 'Image Cropping',
		feature_image_to_text_generator: 'Image Text Extraction',
		feature_pdf_to_text_generation: 'PDF Text Extraction',
	};

	const allowedRoles = [
		'administrator',
		'editor',
		'author',
		'contributor',
		'subscriber',
	];

	Object.keys( features ).forEach( ( feature ) => {
		it( `"${ features[ feature ] }" feature common fields`, () => {
			cy.visit(
				`/wp-admin/tools.php?page=classifai&tab=language_processing&feature=${ feature }`
			);

			cy.get( '#status' ).should(
				'have.attr',
				'name',
				`classifai_${ feature }[status]`
			);
			cy.get( '#user_based_opt_out' ).should(
				'have.attr',
				'name',
				`classifai_${ feature }[user_based_opt_out]`
			);
			cy.get( '#provider' ).should(
				'have.attr',
				'name',
				`classifai_${ feature }[provider]`
			);

			for ( const role of allowedRoles ) {
				if (
					'feature_image_generation' === feature &&
					( 'contributor' === role || 'subscriber' === role )
				) {
					continue;
				}

				const roleField = cy.get(
					`#classifai_${ feature }_roles_${ role }`
				);
				roleField.should( 'be.visible' );
				roleField.should( 'have.value', role );
				roleField.should(
					'have.attr',
					'name',
					`classifai_${ feature }[roles][${ role }]`
				);
			}

			cy.get( '.allowed_users_row' ).should( 'be.visible' );

		} );
	} );
} );

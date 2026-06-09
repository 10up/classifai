import { test, expect } from '../../fixtures/test';

const services: Record< string, Record< string, string > > = {
	language_processing: {
		feature_classification: 'Classification',
		feature_title_generation: 'Title Generation',
		feature_excerpt_generation: 'Excerpt Generation',
		feature_content_resizing: 'Content Resizing',
		feature_text_to_speech_generation: 'Text to Speech',
		feature_audio_transcripts_generation: 'Audio Transcripts Generation',
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
};

const allowedRoles = [ 'administrator', 'editor', 'author', 'contributor' ];

test.describe( 'Common Feature Fields', () => {
	for ( const [ service, features ] of Object.entries( services ) ) {
		for ( const [ feature, label ] of Object.entries( features ) ) {
			test( `"${ label }" feature common fields`, async ( {
				classifaiUtils,
				page,
			} ) => {
				await classifaiUtils.visitFeatureSettings(
					`${ service }/${ feature }`
				);

				await expect(
					page.locator(
						'.classifai-enable-feature-toggle input'
					)
				).toBeAttached();

				await classifaiUtils.openUserPermissionsPanel();
				await expect(
					page.locator(
						'.classifai-settings__user-based-opt-out input[type="checkbox"]'
					)
				).toBeAttached();

				const editProvider = page.locator(
					'.classifai-settings-edit-provider'
				);
				if ( await editProvider.count() ) {
					await editProvider.first().click();
				}

				await expect(
					page.locator( '.classifai-provider-select select' )
				).toBeAttached();

				for ( const role of allowedRoles ) {
					if (
						feature === 'feature_image_generation' &&
						role === 'contributor'
					) {
						continue;
					}

					const roleField = page.locator(
						`.settings-allowed-roles input#${ role }`
					);
					await expect( roleField ).toBeVisible();
					await expect( roleField ).toHaveValue( '1' );
				}

				await expect(
					page.locator( '.classifai-settings__users' )
				).toBeVisible();
			} );
		}
	}
} );

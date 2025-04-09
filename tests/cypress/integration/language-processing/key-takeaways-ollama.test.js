describe( '[Language processing] Key Takeaways Tests', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings( 'language_processing/feature_key_takeaways' );
		cy.get( '#classifai-logo' ).should( 'exist' );
		cy.enableFeature();
		cy.saveFeatureSettings();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save Feature settings', () => {
		cy.visitFeatureSettings( 'language_processing/feature_key_takeaways' );
		cy.get( '#classifai-logo' ).should( 'exist' );
		cy.selectProvider( 'ollama' );

		cy.enableFeature();
		cy.allowFeatureToAdmin();
		cy.saveFeatureSettings();

		cy.get( '#true_model' ).select( 'deepseek-llm:latest' );
		cy.saveFeatureSettings();
	} );

	it( 'Can add the Key Takeaways block in a post', () => {
		// Create test post and add our block.
		cy.createPost( {
			title: 'Test Key Takeaways post',
			content: 'Test GPT content',
			beforeSave: () => {
				cy.insertBlock( 'classifai/key-takeaways' );
			},
		} ).then( () => {
			cy.getBlockEditor()
				.find(
					'.wp-block-classifai-key-takeaways .wp-block-classifai-key-takeways__content'
				)
				.should( 'contain.text', 'Ollama request failed' );
		} );
	} );
} );

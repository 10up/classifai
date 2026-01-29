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
		cy.selectProvider( 'azure_openai' );
		cy.get( 'input#azure_openai_endpoint_url' )
			.clear()
			.type( 'https://e2e-test-azure-openai.test/' );
		cy.get( 'input#azure_openai_api_key' ).clear().type( 'password' );
		cy.get( 'input#azure_openai_deployment' ).clear().type( 'test' );

		cy.enableFeature();
		cy.allowFeatureToAdmin();
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
					'.wp-block-classifai-key-takeaways .wp-block-classifai-key-takeaways__content ul li'
				)
				.should(
					'contain.text',
					'Spring symbolizes renewal and beauty, inspiring creativity and reflection.'
				)
				.should( 'have.length', 4 );
		} );
	} );
} );

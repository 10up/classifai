describe( '[Recommendation Service] Recommended Content Tests', () => {
	before( () => {
		cy.login();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save OpenAI Embedding settings', () => {
		cy.visitFeatureSettings( 'personalizer/feature_recommended_content' );
		cy.enableFeature();
		cy.selectProvider( 'openai_embeddings' );
		cy.get( '#openai_api_key' ).clear().type( 'password' );
		cy.get( '#embedding-threshold' ).clear().type( '50' );

		cy.allowFeatureToAdmin();
		cy.saveFeatureSettings();
	} );

	it( 'Can add the Recommended Content block in a post', () => {
		// Create test post and add our block.
		cy.createPost( {
			title: 'Test Recommended Content post',
			content: 'Test content',
			beforeSave: () => {
				cy.insertBlockCustom(
					'core/query/classifai/recommended-content',
					'Recommended Content'
				);
			},
		} ).then( () => {
			cy.getBlockEditor()
				.find( '.classifai-recommended-content' )
				.should( 'exist' );
		} );
	} );
} );

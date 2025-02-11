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
					'.wp-block-classifai-key-takeaways .wp-block-classifai-key-takeways__content'
				)
				.should( 'contain.text', 'Request failed' );
		} );
	} );

	it( 'Can disable feature', () => {
		// Disable feature.
		cy.visitFeatureSettings( 'language_processing/feature_key_takeaways' );
		cy.disableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is not available.
		cy.createPost( {
			title: 'Test Key Takeaways post disabled',
			content: 'Test GPT content',
			beforeSave: () => {
				cy.insertBlock( 'classifai/key-takeaways' );
			},
		} ).then( () => {
			cy.getBlockEditor()
				.find( '.wp-block-classifai-key-takeaways' )
				.should( 'not.exist' );
		} );
	} );

	it( 'Can disable feature by role', () => {
		cy.visitFeatureSettings( 'language_processing/feature_key_takeaways' );
		cy.enableFeature();
		cy.saveFeatureSettings();

		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_key_takeaways', [
			'administrator',
		] );

		// Verify that the feature is not available.
		cy.createPost( {
			title: 'Test Key Takeaways post disabled user',
			content: 'Test GPT content',
			beforeSave: () => {
				cy.insertBlock( 'classifai/key-takeaways' );
			},
		} ).then( () => {
			cy.getBlockEditor()
				.find( '.wp-block-classifai-key-takeaways' )
				.should( 'not.exist' );
		} );
	} );

	it( 'Can disable feature by user', () => {
		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_key_takeaways', [
			'administrator',
		] );

		cy.enableFeatureForUsers( 'feature_key_takeaways', [] );

		// Verify that the feature is not available.
		cy.createPost( {
			title: 'Test Key Takeaways post disabled user',
			content: 'Test GPT content',
			beforeSave: () => {
				cy.insertBlock( 'classifai/key-takeaways' );
			},
		} ).then( () => {
			cy.getBlockEditor()
				.find( '.wp-block-classifai-key-takeaways' )
				.should( 'not.exist' );
		} );
	} );

	it( 'User can opt-out of feature', () => {
		// Enable user based opt-out.
		cy.enableFeatureOptOut( 'feature_key_takeaways', 'azure_openai' );

		// opt-out
		cy.optOutFeature( 'feature_key_takeaways' );

		// Verify that the feature is not available.
		cy.createPost( {
			title: 'Test Key Takeaways post disabled',
			content: 'Test GPT content',
			beforeSave: () => {
				cy.insertBlock( 'classifai/key-takeaways' );
			},
		} ).then( () => {
			cy.getBlockEditor()
				.find( '.wp-block-classifai-key-takeaways' )
				.should( 'not.exist' );
		} );
	} );
} );

<?php

namespace Klasifai\Admin;

/**
 * Adds the Admin Page for configure Klasifai plugin options. This page
 * depends on Fieldmanager. If absent the Settings page will not be
 * shown.
 */
class SettingsSupport {

	/**
	 * Option that stores the klasifai settings
	 */
	public $group = 'klasifai_settings';

	/**
	 * Activates the Klasifai settings page
	 */
	public function register() {
		\fm_register_submenu_page(
			$this->group,
			'options-general.php',
			'Klasifai Settings',
			'Klasifai'
		);

		add_action(
			'fm_submenu_klasifai_settings', [ $this, 'render' ]
		);
	}

	/**
	 * Only allow registration if fieldmanager plugin is activate
	 */
	public function can_register() {
		return function_exists( '\fm_register_submenu_page' );
	}

	/* helpers */

	/**
	 * Renders the Fieldmanager settings
	 */
	function render() {
		$fm = new \Fieldmanager_Group( [
				'name'     => $this->group,
				'children' => [

					'credentials'       => new \Fieldmanager_Group( [
						'label'         => 'IBM Watson API Credentials',
						'children'      => [
							'watson_username'  => new \Fieldmanager_Textfield( [
								'label' => 'Username',
							] ),
							'watson_password' => new \Fieldmanager_Textfield( [
								'label' => 'Password',
							] ),
						]
					] ),

					'post_types' => new \Fieldmanager_Group( [
						'label' => 'Post Types to classify',
						'children' => $this->get_post_types_group(),
					] ),

					'features' => new \Fieldmanager_Group( [
						'label' => 'IBM Watson Features to enable',
						'children' => $this->get_features_group(),
					] )
				],
		] );

		$fm->activate_submenu_page();
	}

	/**
	 * Renders the list of post types that should be classified.
	 */
	function get_post_types_group() {
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		$fields     = [];

		foreach ( $post_types as $post_type ) {
			$field = new \Fieldmanager_Checkbox( [
				'name'          => $post_type->name,
				'label'         => $post_type->label,
			] );

			$fields[ $post_type->name ] = $field;
		}

		return $fields;
	}

	/**
	 * Returns the Fieldmanager settings to manage the IBM Watson NLU
	 * API Features.
	 */
	function get_features_group() {
		$fields['category'] = new \Fieldmanager_Checkbox( [
			'label' => 'Categories',
		] );

		$fields['category_threshold'] = new \Fieldmanager_Textfield( [
			'name'       => 'category_threshold',
			'label'      => 'Category Threshold (%)',
			'input_type' => 'number',
			'default_value'    => 70,
			'display_if' => [
				'src' => 'category',
				'value' => true,
			],
			'attributes' => [
				'min' => 0,
				'max' => 100,
			]
		] );

		$fields['keyword'] = new \Fieldmanager_Checkbox( [
			'label' => 'Keywords',
			'display_if' => [
				'src' => 'keyword',
				'value' => true,
			]
		] );

		$fields['keyword_threshold'] = new \Fieldmanager_Textfield( [
			'name'       => 'keyword_threshold',
			'label'      => 'Keyword Threshold (%)',
			'input_type' => 'number',
			'default_value'    => 70,
			'display_if' => [
				'src' => 'keyword',
				'value' => true,
			],
			'attributes' => [
				'min' => 0,
				'max' => 100,
			]
		] );

		$fields['concept'] = new \Fieldmanager_Checkbox( [
			'label' => 'Concepts',
			'display_if' => [
				'src' => 'concept',
				'value' => true,
			]
		] );

		$fields['concept_threshold'] = new \Fieldmanager_Textfield( [
			'name'       => 'concept_threshold',
			'label'      => 'Concept Threshold (%)',
			'input_type' => 'number',
			'default_value'    => 70,
			'display_if' => [
				'src' => 'concept',
				'value' => true,
			],
			'attributes' => [
				'min' => 0,
				'max' => 100,
			]
		] );


		$fields['entity'] = new \Fieldmanager_Checkbox( [
			'label' => 'Entities',
			'display_if' => [
				'src' => 'entity',
				'value' => true,
			]
		] );

		$fields['entity_threshold'] = new \Fieldmanager_Textfield( [
			'name'       => 'entity_threshold',
			'label'      => 'Entity Threshold (%)',
			'input_type' => 'number',
			'default_value'    => 70,
			'display_if' => [
				'src' => 'entity',
				'value' => true,
			],
			'attributes' => [
				'min' => 0,
				'max' => 100,
			]
		] );


		return $fields;
	}

}

/* global ClassifAI, classifaiTags, classifaiSelectedTags */

import Choices from 'choices.js';
import '../scss/tags.scss';

( () => {
	const filterTypeSelector = document.querySelector(
		'select[name="classifai_computer_vision[filter_tags_type]'
	);

	if ( ! filterTypeSelector ) {
		return;
	}

	const selectElement = document.querySelector( '.classifai-tags-select' );
	const inputElement = document.querySelector(
		'.classifai-disabled-tags input'
	);

	// Allowed Tags
	const selectChoices = new Choices( selectElement, {
		searchEnabled: true,
		itemSelectText: '',
		duplicateItemsAllowed: false,
		choices: classifaiTags,
		shouldSort: false,
		removeItemButton: true,
		placeholder: true,
		placeholderValue: ClassifAI.search_tags,
	} );

	if ( classifaiSelectedTags ) {
		selectChoices.setValue( classifaiSelectedTags );
	}

	// Disabled Tags
	const inputChoices = new Choices( inputElement, {
		searchEnabled: true,
		itemSelectText: '',
		duplicateItemsAllowed: false,
		shouldSort: false,
		removeItemButton: true,
		placeholder: true,
		placeholderValue: ClassifAI.add_tags,
	} );

	// Custom event fired when filter type changes.
	filterTypeSelector.addEventListener( 'filteredTagsTypeChanged', ( e ) => {
		const { value } = e.target.options[ e.target.selectedIndex ];

		// Remove all previously selected items.
		selectChoices.removeActiveItems();
		inputChoices.removeActiveItems();

		if ( 'allowed' === value ) {
			selectChoices.setValue( classifaiTags );
		}
	} );
} )();

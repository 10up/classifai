const { subscribe, select, dispatch } = wp.data;

let saveHappened = false;
let showingNotice = false;

subscribe(() => {
	if (saveHappened === false) {
		saveHappened = wp.data.select('core/editor').isSavingPost() === true;
	}

	if (
		saveHappened &&
		wp.data.select('core/editor').isSavingPost() === false &&
		showingNotice === false
	) {
		const meta = select('core/editor').getCurrentPostAttribute('meta');
		if (meta && meta._classifai_error) {
			showingNotice = true;
			const error = JSON.parse(meta._classifai_error);
			dispatch('core/notices').createErrorNotice(
				`Failed to classify content with the IBM Watson NLU API. Error: ${error.code} - ${error.message}`,
			);
			saveHappened = false;
			showingNotice = false;
		}
	}
});

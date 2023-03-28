/* global ClassifAI */
import '../scss/admin.scss';

(() => {
	const $toggler = document.getElementById('classifai-waston-cred-toggle');
	const $userField = document.getElementById(
		'classifai-settings-watson_username'
	);

	if ($toggler === null || $userField === null) {
		return;
	}

	let $userFieldWrapper = null;
	let $passwordFieldTitle = null;
	if ($userField.closest('tr')) {
		$userFieldWrapper = $userField.closest('tr');
	} else if ($userField.closest('.classifai-setup-form-field')) {
		$userFieldWrapper = $userField.closest('.classifai-setup-form-field');
	}

	if (
		document
			.getElementById('classifai-settings-watson_password')
			.closest('tr')
	) {
		[$passwordFieldTitle] = document
			.getElementById('classifai-settings-watson_password')
			.closest('tr')
			.getElementsByTagName('label');
	} else if (
		document
			.getElementById('classifai-settings-watson_password')
			.closest('.classifai-setup-form-field')
	) {
		[$passwordFieldTitle] = document
			.getElementById('classifai-settings-watson_password')
			.closest('.classifai-setup-form-field')
			.getElementsByTagName('label');
	}

	$toggler.addEventListener('click', (e) => {
		e.preventDefault();
		$userFieldWrapper.classList.toggle('hidden');

		if ($userFieldWrapper.classList.contains('hidden')) {
			$toggler.innerText = ClassifAI.use_password;
			$passwordFieldTitle.innerText = ClassifAI.api_key;
			$userField.value = 'apikey';
			return;
		}

		$toggler.innerText = ClassifAI.use_key;
		$passwordFieldTitle.innerText = ClassifAI.api_password;
	});
})();

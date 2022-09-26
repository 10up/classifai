/* global ClassifAI */

( () => {
	const $toggler = document.getElementById( 'classifai-waston-cred-toggle' );
	const $userField = document.getElementById(
		'classifai-settings-watson_username'
	);

	if ( $toggler === null || $userField === null ) return;

	const $userFieldWrapper = $userField.closest( 'tr' );
	const [ $passwordFieldTitle ] = document
		.getElementById( 'classifai-settings-watson_password' )
		.closest( 'tr' )
		.getElementsByTagName( 'label' );

	$toggler.addEventListener( 'click', ( e ) => {
		e.preventDefault();
		$userFieldWrapper.classList.toggle( 'hidden' );

		if ( $userFieldWrapper.classList.contains( 'hidden' ) ) {
			$toggler.innerText = ClassifAI.use_password;
			$passwordFieldTitle.innerText = ClassifAI.api_key;
			$userField.value = 'apikey';
			return;
		}

		$toggler.innerText = ClassifAI.use_key;
		$passwordFieldTitle.innerText = ClassifAI.api_password;
	} );
} )();

// Display "Classify Post" button only when "Process content on update" is unchecked (Classic Editor).
document.addEventListener( 'DOMContentLoaded', function () {
	const classifaiNLUCheckbox = document.getElementById(
		'_classifai_process_content'
	);
	if ( classifaiNLUCheckbox ) {
		classifaiNLUCheckbox.addEventListener( 'change', function () {
			const classifyButton = document.querySelector(
				'.classifai-clasify-post-wrapper'
			);
			if ( this.checked === true ) {
				classifyButton.style.display = 'none';
			} else {
				classifyButton.style.display = 'block';
			}
		} );
		classifaiNLUCheckbox.dispatchEvent( new Event( 'change' ) );
	}
} );

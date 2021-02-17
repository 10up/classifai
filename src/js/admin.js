/* global ClassifAI */

// Internal dependencies.
import { handleClick } from './helpers';

( () => {
	const $toggler = document.getElementById( 'classifai-waston-cred-toggle' );
	const $userField = document.getElementById( 'classifai-settings-watson_username' );
	const generateTags = document.getElementById( 'classifai-generate-tags' );

	// Handle tags generation for existing content.
	if ( generateTags ) {
		generateTags.addEventListener( 'click', e => {
			e.preventDefault();
			handleClick(
				{
					button: e.target,
					endpoint: '/classifai/v1/generate-tags/',
					callback: resp => {
						if ( true === resp.success ) {
							location.reload();
						}
					}
				}
			);
		} );
	}

	if ( null === $toggler || null === $userField ) return;

	const $userFieldWrapper = $userField.closest( 'tr' );
	const [$passwordFieldTitle] = document.getElementById( 'classifai-settings-watson_password' ).closest( 'tr' ).getElementsByTagName( 'label' );

	if ( null === $toggler ) return;

	$toggler.addEventListener( 'click', e => {
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

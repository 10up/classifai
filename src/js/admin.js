/* global ClassifAI */
import '../scss/admin.scss';
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import 'tippy.js/themes/light.css';

document.addEventListener( 'DOMContentLoaded', function () {
	const template = document.getElementById( 'help-menu-template' );
	if ( ! template ) {
		return;
	}
	const container = document.createElement( 'div' );
	container.appendChild( document.importNode( template.content, true ) );

	tippy( '.classifai-help', {
		allowHTML: true,
		content: container.innerHTML,
		trigger: 'click', // mouseenter, click, focus; manual.
		placement: 'bottom-end',
		arrow: true,
		animation: 'scale',
		duration: [ 250, 200 ],
		theme: 'light',
		interactive: true,
	} );
} );

( () => {
	const $toggler = document.getElementById( 'classifai-waston-cred-toggle' );
	const $userField = document.getElementById(
		'classifai-settings-watson_username'
	);

	if ( $toggler === null || $userField === null ) {
		return;
	}

	let $userFieldWrapper = null;
	let $passwordFieldTitle = null;
	if ( $userField.closest( 'tr' ) ) {
		$userFieldWrapper = $userField.closest( 'tr' );
	} else if ( $userField.closest( '.classifai-setup-form-field' ) ) {
		$userFieldWrapper = $userField.closest( '.classifai-setup-form-field' );
	}

	if (
		document
			.getElementById( 'classifai-settings-watson_password' )
			.closest( 'tr' )
	) {
		[ $passwordFieldTitle ] = document
			.getElementById( 'classifai-settings-watson_password' )
			.closest( 'tr' )
			.getElementsByTagName( 'label' );
	} else if (
		document
			.getElementById( 'classifai-settings-watson_password' )
			.closest( '.classifai-setup-form-field' )
	) {
		[ $passwordFieldTitle ] = document
			.getElementById( 'classifai-settings-watson_password' )
			.closest( '.classifai-setup-form-field' )
			.getElementsByTagName( 'label' );
	}

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

document.addEventListener( 'DOMContentLoaded', function () {
	function toogleAllowedRolesRow() {
		const enabledRoles = document.querySelectorAll(
			'tr.allowed_roles_row'
		);
		if ( this.checked ) {
			enabledRoles.forEach( function ( e ) {
				e.classList.remove( 'hidden' );
			} );
		} else {
			enabledRoles.forEach( function ( e ) {
				e.classList.add( 'hidden' );
			} );
		}
	}

	const roleBasedAccess = document.getElementById(
		'enable_role_based_access'
	);
	const nluRoleBasedAccess = document.getElementById(
		'classifai-settings-enable_role_based_access'
	);

	if ( roleBasedAccess ) {
		roleBasedAccess.addEventListener( 'change', toogleAllowedRolesRow );
		roleBasedAccess.dispatchEvent( new Event( 'change' ) );
	}

	if ( nluRoleBasedAccess ) {
		nluRoleBasedAccess.addEventListener( 'change', toogleAllowedRolesRow );
		nluRoleBasedAccess.dispatchEvent( new Event( 'change' ) );
	}
} );

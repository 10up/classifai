/* global ClassifAI */
import '../scss/admin.scss';
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import 'tippy.js/themes/light.css';

import { createRoot } from '@wordpress/element';
import { UserSelector } from './components';

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
	function toogleAllowedRolesRow( e ) {
		const checkbox = e.target;
		const parentTr = checkbox.closest( 'tr.classifai-role-based-access' );
		const allowedRoles = parentTr.nextElementSibling.classList.contains(
			'allowed_roles_row'
		)
			? parentTr.nextElementSibling
			: null;
		if ( checkbox.checked ) {
			allowedRoles.classList.remove( 'hidden' );
		} else {
			allowedRoles.classList.add( 'hidden' );
		}
	}

	function toogleAllowedUsersRow( e ) {
		const checkbox = e.target;
		const parentTr = checkbox.closest( 'tr.classifai-user-based-access' );
		const allowedUsers = parentTr.nextElementSibling.classList.contains(
			'allowed_users_row'
		)
			? parentTr.nextElementSibling
			: null;
		if ( checkbox.checked ) {
			allowedUsers.classList.remove( 'hidden' );
		} else {
			allowedUsers.classList.add( 'hidden' );
		}
	}

	const roleBasedAccessCheckBoxes = document.querySelectorAll(
		'tr.classifai-role-based-access input[type="checkbox"]'
	);
	const userBasedAccessCheckBoxes = document.querySelectorAll(
		'tr.classifai-user-based-access input[type="checkbox"]'
	);

	if ( roleBasedAccessCheckBoxes ) {
		roleBasedAccessCheckBoxes.forEach( function ( e ) {
			e.addEventListener( 'change', toogleAllowedRolesRow );
			e.dispatchEvent( new Event( 'change' ) );
		} );
	}

	if ( userBasedAccessCheckBoxes ) {
		userBasedAccessCheckBoxes.forEach( function ( e ) {
			e.addEventListener( 'change', toogleAllowedUsersRow );
			e.dispatchEvent( new Event( 'change' ) );
		} );
	}
} );

// User Selector.
( () => {
	const userSearches = document.querySelectorAll(
		'.classifai-user-selector'
	);
	if ( ! userSearches ) {
		return;
	}

	userSearches.forEach( ( userSearch ) => {
		const id = userSearch.getAttribute( 'data-id' );
		const userElement = document.getElementById( id );
		const values = userElement.value?.split( ',' ) || [];
		const onChange = ( newValues ) => {
			userElement.value = newValues.join( ',' );
		};

		const root = createRoot( userSearch );
		root.render( <UserSelector value={ values } onChange={ onChange } /> );
	} );
} )();

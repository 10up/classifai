/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo, useState, useEffect } from '@wordpress/element';
import { store as coreStore } from '@wordpress/core-data';
import { FormTokenField } from '@wordpress/components';
import { useDebounce } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';

/**
 * User selector component.
 *
 * @param {Object} props          The block props.
 * @param {string} props.value    The selected user ids.
 * @param {string} props.onChange The change handler.
 */
export const UserSelector = ( { value, onChange } ) => {
	const [ usersByName, setUsersByName ] = useState( {} );
	const [ values, setValues ] = useState( [] );
	const [ search, setSearch ] = useState( '' );
	const debouncedSearch = useDebounce( setSearch, 500 );

	// Utility functions.
	const getNameString = ( user ) => `${ user.name } (${ user.slug })`;
	const getNameKey = ( name ) => {
		return name.replace( / /g, '' ).toLowerCase();
	};

	// Load existing selected users.
	const { users, hasResolvedUsers } = useSelect(
		( select ) => {
			const { getUsers, hasFinishedResolution } = select( coreStore );
			const query = {
				context: 'view',
				include: value,
				per_page: -1,
				__fields: 'id,name,slug',
			};
			return {
				users: getUsers( query ),
				hasResolvedUsers: hasFinishedResolution( 'getUsers', [
					query,
				] ),
			};
		},
		[ value ]
	);

	// Set selected user values.
	useEffect( () => {
		if ( hasResolvedUsers ) {
			const newValues = ( users ?? [] ).map( getNameString );
			setValues( newValues );
		}
	}, [ users, hasResolvedUsers ] );

	// Load search results.
	const searchResults = useSelect(
		( select ) => {
			const { getUsers } = select( coreStore );
			return getUsers( {
				context: 'view',
				search: encodeURIComponent( search ),
				per_page: 10,
				__fields: 'id,name,slug',
			} );
		},
		[ search ]
	);

	// Set search result values.
	const suggestions = useMemo(
		() => ( searchResults ?? [] ).map( getNameString ),
		[ searchResults ]
	);

	// Set users by name object to map user names to ids.
	useEffect( () => {
		if ( hasResolvedUsers && users ) {
			( users ?? [] ).forEach( ( user ) => {
				usersByName[ getNameKey( getNameString( user ) ) ] = user.id;
			} );
			setUsersByName( usersByName );
		}
	}, [ users, hasResolvedUsers, usersByName ] );

	useEffect( () => {
		( searchResults ?? [] ).forEach( ( user ) => {
			usersByName[ getNameKey( getNameString( user ) ) ] = user.id;
		} );
		setUsersByName( usersByName );
	}, [ searchResults, usersByName ] );

	/**
	 * Handle change.
	 *
	 * @param {string[]} userNames User names.
	 */
	function handleChange( userNames ) {
		const userIds = [];
		const uniqueUsers = userNames.reduce( ( acc, name ) => {
			if (
				! acc.some(
					( user ) => user.toLowerCase() === name.toLowerCase()
				)
			) {
				acc.push( name );
				if ( usersByName && usersByName[ getNameKey( name ) ] ) {
					userIds.push( usersByName[ getNameKey( name ) ] );
				}
			}
			return acc;
		}, [] );
		onChange( userIds );
		setValues( uniqueUsers );
	}

	return (
		<FormTokenField
			className="classifai-user-selector-field"
			value={ values }
			suggestions={ suggestions }
			onChange={ handleChange }
			onInputChange={ debouncedSearch }
			label={ null }
			placeholder={ __( 'Search for users', 'classifai' ) }
			__experimentalShowHowTo={ false }
			messages={ {
				added: __( 'User added.', 'classifai' ),
				removed: __( 'User removed.', 'classifai' ),
				remove: __( 'Remove user', 'classifai' ),
				__experimentalInvalid: __( 'Invalid user', 'classifai' ),
			} }
		/>
	);
};

import { useState } from 'react';
import { FormControl } from './form-control';
import { cloneDeep,
	toPairs,
	isPlainObject,
	isArray,
	set
} from 'lodash';

import '/node_modules/@wordpress/components/build-style/style.css';

export const AdminApp = ( { data } ) => {
	const [ adminData, setAdminData ] = useState( data );
	const [ featureKey ] = Object.keys( data );
	const featureData = adminData[ featureKey ];
	const __data = cloneDeep( adminData );

	function findKeyPath( obj, targetKey, currentPath = [] ) {
		for ( const [ key, value ] of toPairs( obj ) ) {
			const path = [ ...currentPath, key ];

			if ( key === targetKey ) {
				return path.join( '.' );
			}
		
			if ( isPlainObject( value ) || isArray( value ) ) {
				const result = findKeyPath( value, targetKey, path );

				if ( result ) {
					return result;
				}
			}
		}

		return null;
	}

	function __setAdminData( val, path ) {
		const { settingKey, isRepeater, repeaterIndex, repeaterSettingKey, is_unique } = path;

		let pathString = '';

		pathString = findKeyPath( __data[ featureKey ], settingKey );

		if ( isRepeater ) {
			pathString = `${ pathString }.value[${ repeaterIndex }].${ repeaterSettingKey }`;
		} else {
			pathString = `${ pathString }.value`;
		}

		set( __data[ featureKey ], pathString, val );
		setAdminData( __data );
	}

	const settingContainerStyle = {
		marginBottom: '2rem',
	};

	const ele = Object.keys( adminData[ featureKey ] ).map( ( settingKey, index ) => {
		return (
			<div style={ settingContainerStyle }>
				<FormControl
					{ ...featureData[ settingKey ] }
					onChange={ __setAdminData }
					featureKey={ featureKey }
					settingKey={ settingKey }
					key={ index }
				/>
			</div>
		)
	} );

	const style = {
		margin: '2rem 0',
		maxWidth: '400px',
	}

	return <div style={ style }>
		{ ele }
	</div>;
};

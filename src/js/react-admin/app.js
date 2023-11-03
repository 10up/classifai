import { useState } from '@wordpress/element';
import { FormControl } from './form-control';

export const AdminApp = ( { data } ) => {
	const [ adminData, setAdminData ] = useState( data );
	const [ featureKey ] = Object.keys( data );
	const featureData = adminData[ featureKey ];

	function __setAdminData( settingKey, val ) {
		const __data = { ...data };

		__data[ featureKey ][ settingKey ].value = val;

		setAdminData( __data );
	}

	const ele = Object.keys( adminData[ featureKey ] ).map( ( settingKey ) => {
		return (
			<FormControl
				{ ...featureData[ settingKey ] }
				onChange={ __setAdminData }
				featureKey={ featureKey }
				settingKey={ settingKey }
			/>
		)
	} );

	return ele;
};
import {
	CheckboxControl,
	FormTokenField,
	SelectControl,
} from '@wordpress/components';

export const FormControl = ( props ) => {
	const {
		type,
		label,
		description,
		value,
		onChange,
		featureKey,
		settingKey,
	} = props;

	const name = `${ featureKey }[${ settingKey }]`;

	switch ( type ) {
		case 'checkbox':
			return <CheckboxControl
				name={ name }
				label={ label }
				checked={ 'on' === value }
				onChange={ ( val ) => onChange( settingKey, val ? 'on' : 'off ' ) }
				value={ value }
			/>;

		case 'multiselect':
			const options = props?.options ?? [];
			const suggestions = Object.values( options );

			function getValueKeys( value = [] ) {
				return value.map( ( val ) => {
					return Object.keys( options ).find( ( key ) => options[ key ] === val );
				} );
			}

			const selectedKeys = getValueKeys( value );
			const hiddenFields = selectedKeys.map( key => <input type="hidden" name={ `${ name }[]` } value={ key } /> );

			return (
				<>
					<FormTokenField
						name={ name }
						label={ label }
						value={ value }
						onChange={ ( val ) => onChange( settingKey, val ) }
						suggestions={ suggestions }
						description={ description }
						__experimentalExpandOnFocus={ true }
					/>
					{ hiddenFields }
				</>
			);

		case 'select':
			return (
				<SelectControl
					name={ name }
					label={ label }
					value={ value }
					onChange={ ( val ) => onChange( settingKey, val ) }
					options={ props?.options ?? [] }
					description={ description }
				/>
			)

	}

	return null;
};
import {
	BaseControl,
	CheckboxControl,
	FormTokenField,
	SelectControl,
	__experimentalNumberControl as NumberControl,
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

	const fields = [];

	switch ( type ) {
		case 'checkbox':
			fields.push( <CheckboxControl
				name={ name }
				label={ label }
				checked={ 'on' === value }
				onChange={ ( val ) => onChange( settingKey, val ? 'on' : 'off ' ) }
				value={ value }
			/> );
			break;

		case 'multiselect':
			const options = props?.options ?? [];
			const suggestions = Object.values( options );

			function getValueByLabel( label ) {
				return Object.keys( options ).find( ( key ) => options[ key ] === label );
			}

			const hiddenFields = value.map( ( val ) => {
				return (
					<input
						type="hidden"
						name={ `${ name }[]` }
						value={ val }
					/>
				);
			} );

			fields.push(
				<>
					<BaseControl help={ description }>
						<FormTokenField
							name={ name }
							label={ label }
							value={ value.map( ( val ) => options[ val ] ) }
							onChange={ ( val ) => onChange( settingKey, val.map( getValueByLabel ) ) }
							suggestions={ suggestions }
							description={ description }
							__experimentalShowHowTo={ false }
							__experimentalExpandOnFocus={ true }
						/>
					</BaseControl>
					{ hiddenFields }
				</>
			);

			break;

		case 'select':
			fields.push(
				<SelectControl
					name={ name }
					label={ label }
					value={ value }
					onChange={ ( val ) => onChange( settingKey, val ) }
					options={ props?.options ?? [] }
					help={ description }
				/>
			);
			break;

		case 'number':
			const { min = 0, max = 100, step = 1 } = props;

			fields.push(
				<NumberControl
					name={ name }
					label={ label }
					value={ value }
					onChange={ ( val ) => onChange( settingKey, val ) }
					help={ description }
					min={ min }
					max={ max }
					step={ step }
				/>
			);
			break;
	}

	if ( 'provider' === settingKey && props?.provider_settings ) {
		fields.push( ...Object.keys( props.provider_settings[ value ] ).map( ( settingKey ) => {
			return (
				<FormControl
					{ ...props.provider_settings[ value ][ settingKey ] }
					onChange={ onChange }
					featureKey={ value }
					settingKey={ settingKey }
				/>
			)
		} ) );
	}

	return fields;
};
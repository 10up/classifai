import {
	BaseControl,
	CheckboxControl,
	FormTokenField,
	SelectControl,
	TextControl,
	TextareaControl,
	Button,
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';

import { __ } from '@wordpress/i18n';

import { cloneDeep } from 'lodash';
import { useEffect } from '@wordpress/element';

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

	const name = props?.name || `${ featureKey }[${ settingKey }]`;
	const isRepeater = props?.isRepeater ?? false;
	const repeaterIndex = props?.repeaterIndex ?? null;
	const repeaterSettingKey = props?.repeaterSettingKey ?? null;
	const is_unique = props?.is_unique ?? null;

	const fields = [];

	switch ( type ) {
		case 'checkbox':
			fields.push( <CheckboxControl
				name={ name }
				label={ label }
				checked={ 'on' === value }
				onChange={ ( val ) => onChange( val ? 'on' : 'off ', { settingKey, isRepeater, repeaterIndex, repeaterSettingKey } ) }
				value={ value }
			/> );
			break;

		case 'radio_trad':
			fields.push( <input
				type="radio"
				name={ name }
				checked={ 'on' === value }
				onChange={ ( val ) => onChange( val ? 'on' : 'off ', { settingKey, isRepeater, repeaterIndex, repeaterSettingKey } ) }
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
							onChange={ ( val ) => onChange( val.map( getValueByLabel ), { settingKey, isRepeater, repeaterIndex, repeaterSettingKey } ) }
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
					onChange={ ( val ) => onChange( val, { settingKey, isRepeater, repeaterIndex, repeaterSettingKey } ) }
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
					onChange={ ( val ) => onChange( val, { settingKey, isRepeater, repeaterIndex, repeaterSettingKey } ) }
					help={ description }
					min={ min }
					max={ max }
					step={ step }
				/>
			);
			break;


		case 'text':
			fields.push( <TextControl
				name={ name }
				label={ label }
				value={ value }
				help={ description }
				onChange={ ( val ) => onChange( val, { settingKey, isRepeater, repeaterIndex, repeaterSettingKey } ) }
			/> );
			break;


		case 'textarea':
			const placeholder = props?.placeholder ?? '';

			fields.push( <TextareaControl
				name={ name }
				label={ label }
				placeholder={ placeholder }
				value={ value }
				help={ description }
				onChange={ ( val ) => onChange( val, { settingKey, isRepeater, repeaterIndex, repeaterSettingKey } ) }
			/> );
			break;


		case 'repeater':
			const { repeater_unit } = props;

			console.log(props)

			fields.push(
				<Repeater
					repeaterUnit={ repeater_unit }
					featureKey={ featureKey }
					settingKey={ settingKey }
					value={ value }
					onChange={ onChange }
					addNewLabel={ props?.add_new_label ?? __( 'Add new', 'classifai' ) }
				/>
			);

			break;
	}

	if ( 'provider' === settingKey && props?.provider_settings ) {
		fields.push( ...Object.keys( props.provider_settings[ value ] ).map( ( settingKey, index ) => {
			return (
				<FormControl
					{ ...props.provider_settings[ value ][ settingKey ] }
					onChange={ onChange }
					featureKey={ featureKey }
					settingKey={ settingKey }
					key={ index }
				/>
			)
		} ) );
	}

	return fields;
};

// featureKey[ settingKey ][ 0 ][ title ]
// featureKey[ settingKey ][ 0 ][ prompt ]
// 
// featureKey[ settingKey ][ 1 ][ prompt ]
// featureKey[ settingKey ][ 1 ][ prompt ]
// ---------------------------------------
// classifai_feature_excerpt_generation[ generate_excerpt_prompt ][ 0 ][ title ]
// classifai_feature_excerpt_generation[ generate_excerpt_prompt ][ 0 ][ prompt ]

function Repeater( props ) {
	const { value, repeaterUnit, onChange, featureKey, settingKey, addNewLabel } = props;

	const addNew = (
		<Button
			variant='secondary'
			onClick={ () => {
				const defaultValue = {};
				Object.keys( repeaterUnit ).forEach( ( __settingKey ) => {
					defaultValue[ __settingKey ] = repeaterUnit[ __settingKey ].value;
				} );
		
				onChange( [ ...value, defaultValue ], { settingKey } );
			} }
		>
			{ addNewLabel }
		</Button>
	);

	function normalizeRepeaterData( __repeaterData = [] ) {
		return __repeaterData.map( ( dbField ) => {
			const __repeaterUnit = cloneDeep( repeaterUnit );
			Object.keys( dbField ).forEach( ( key ) => {
				__repeaterUnit[ key ].value = dbField[ key ];
			} );
			return __repeaterUnit;
		} );
	}

	const repeaterData = normalizeRepeaterData( value );

	const style = {
		backgroundColor: 'beige',
		marginTop: '1rem',
		marginBottom: '1rem',
		padding: '1rem'
	}

	useEffect( () => {
		if ( value.length ) {
			return;
		}

		const defaultValue = {};

		Object.keys( repeaterUnit ).forEach( ( __settingKey ) => {
			defaultValue[ __settingKey ] = repeaterUnit[ __settingKey ].value;
		} );

		onChange( [ defaultValue ], { settingKey, index: 0 } );
	}, value );

	return (
		<>
			{
				repeaterData.map( ( repeaterUnit, repeaterIndex ) => {
					return (
						<div style={ style }>
							{
								Object.keys( repeaterUnit ).map( ( repeaterSettingKey, index ) => {
									const is_unique = repeaterUnit[ repeaterSettingKey ]?.is_unique;
									return (
										<FormControl
											{ ...repeaterUnit[ repeaterSettingKey ] }
											name={ `${featureKey}[${settingKey}][${repeaterIndex}][${repeaterSettingKey}]` }
											featureKey={ featureKey }
											settingKey={ settingKey }
											onChange={ onChange }
											key={ index }
											isRepeater={ true }
											repeaterIndex={ repeaterIndex }
											repeaterSettingKey={ repeaterSettingKey }
											is_unique={ is_unique }
										/>
									)
								} )
							}
						</div>
					);
				} )
			}
			{ addNew }
		</>
	);
}

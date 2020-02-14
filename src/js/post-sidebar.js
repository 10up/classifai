import React from 'react';
import propTypes from 'prop-types';

const { registerPlugin } = wp.plugins;
const { PluginDocumentSettingPanel } = wp.editPost;
const { FormToggle, SelectControl } = wp.components;
const { __ } = wp.i18n;
//const { withState } = wp.compose;
const { withSelect, withDispatch } = wp.data;

/**
 *
 * @param subtitle
 * @param handleSubtitleChange
 * @returns {*}
 * @constructor
 */
let OverrideLanguageSelect = ( { language, handleLanguageChange } ) => (
	<fieldset>
		<SelectControl
			label={__( 'Set overriding language', 'classsifai' ) }
			value={language}
			options={[
				{ label: 'English', value: 'en' }
			]}
			onChange={language => handleLanguageChange( language )}
		/>
	</fieldset>
);

OverrideLanguageSelect = withSelect(
	( select ) => {
		return {
			language: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ 'classifai_override_language' ]
		};
	}
)( OverrideLanguageSelect );

OverrideLanguageSelect = withDispatch(
	( dispatch ) => {
		return {
			handleLanguageChange: ( value ) => {
				dispatch( 'core/editor' ).editPost( { meta: { customName: value } } );
			}
		};
	}
)( OverrideLanguageSelect );

OverrideLanguageSelect.propTypes = {
	handleLanguageChange: propTypes.any,
	language: propTypes.any
};

/**
 *
 * @param height
 * @param handleHeightChange
 * @returns {*}
 * @constructor
 */
let OverrideLanguageOptInControl = ( { value, handleOptInChange } ) => (

	<fieldset>
		<p>
			<FormToggle
				checked={value}
				onChange={handleOptInChange}
			/>&nbsp;&nbsp;
			{__( 'Override Content Classification Language?', 'classifai' )}
		</p>
		<p>
			<span>
			</span>
		</p>

	</fieldset>
);

OverrideLanguageOptInControl = withSelect(
	( select ) => {
		return {
			optIn: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ 'classifai_override_language_opt_in' ]
		};
	}
)( OverrideLanguageOptInControl );

OverrideLanguageOptInControl = withDispatch(
	( dispatch ) => {
		return {
			handleOptInChange: ( value ) => {
				dispatch( 'core/editor' ).editPost( { meta: { classifai_override_language_opt_in: parseInt( value ) } } );
			}
		};
	}
)( OverrideLanguageOptInControl );

OverrideLanguageOptInControl.propTypes = {
	handleOptInChange: propTypes.any,
	value: propTypes.any
};

/**
 *
 * @returns {*}
 * @constructor
 */
const ClassifaiSettings = () => (
	<PluginDocumentSettingPanel
		name="classifai"
		title={__( 'Content Classification', 'classifai' )}
		className="classifai"
	>
		<p>{__( 'Classifai will consider Portuguese as the language for this content', 'classifai' )}</p>
		<OverrideLanguageOptInControl/>
		<OverrideLanguageSelect />
	</PluginDocumentSettingPanel>
);

registerPlugin( 'classifai_content_classification_panel', {
	render: ClassifaiSettings
} );

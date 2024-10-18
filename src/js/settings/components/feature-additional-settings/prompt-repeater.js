/**
 * WordPress dependencies
 */
import {
	Button,
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	TextareaControl,
	__experimentalConfirmDialog as ConfirmDialog, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Component for the Prompt.
 *
 * This component allows users to add or remove prompts for various features such as Title Generation, Excerpt Generation and Descriptive Text Generation.
 *
 * @param {Object} props Component props.
 *
 * @return {React.ReactElement} PromptRepeater component.
 */
export const PromptRepeater = ( props ) => {
	const [ showConfirmDialog, setShowConfirmDialog ] = useState( false );
	const [ activeIndex, setActiveIndex ] = useState( null );
	const { prompts = [], setPromts } = props;

	const placeholder =
		prompts?.filter( ( prompt ) => prompt.original )[ 0 ]?.prompt || '';

	// Add a new prompt.
	const addPrompt = () => {
		setPromts( [
			...prompts,
			{ default: 0, original: 0, prompt: '', title: '' },
		] );
	};

	// Remove a prompt.
	const removePrompt = ( index ) => {
		const prompt = prompts.splice( index, 1 );
		// Make the first prompt default if default prompt is removed.
		if ( prompt[ 0 ]?.default ) {
			prompts[ 0 ].default = 1;
		}
		setPromts( [ ...prompts ] );
	};

	// Update prompt.
	const onChange = ( index, changes ) => {
		// Remove default from all other prompts
		if ( changes.default ) {
			prompts.forEach( ( prompt, i ) => {
				if ( i !== index ) {
					prompt.default = 0;
				}
			} );
		}

		prompts[ index ] = {
			...prompts[ index ],
			...changes,
		};
		setPromts( [ ...prompts ] );
	};

	// Confirm dialog to remove prompt.
	const handleConfirm = () => {
		setShowConfirmDialog( false );
		removePrompt( activeIndex );
	};

	return (
		<div className="classifai-prompts">
			{ prompts.map( ( prompt, index ) => (
				<div
					className="classifai-field-type-prompt-setting"
					id={ `classifai-prompt-setting-${ index }` }
					key={ index }
				>
					{ !! prompt.original && (
						<>
							<p className="classifai-original-prompt">
								<strong>
									{ __(
										'ClassifAI default prompt: ',
										'classifai'
									) }
								</strong>
								{ prompt.prompt }
							</p>
						</>
					) }
					{ ! prompt.original && (
						<>
							<InputControl
								type="text"
								value={ prompt.title }
								label={ __( 'Title', 'classifai' ) }
								placeholder={ __(
									'Prompt title',
									'classifai'
								) }
								onChange={ ( value ) => {
									onChange( index, {
										title: value,
									} );
								} }
								help={ __(
									'Short description of prompt to use for identification.',
									'classifai'
								) }
								className="classifai-prompt-title"
							/>
							<TextareaControl
								value={ prompt.prompt }
								label={ __( 'Prompt', 'classifai' ) }
								placeholder={ placeholder }
								onChange={ ( value ) => {
									onChange( index, {
										prompt: value,
									} );
								} }
								className="classifai-prompt-text"
							/>
						</>
					) }
					<div className="actions-rows">
						<Button
							className="action__set_default"
							variant={ 'link' }
							disabled={ !! prompt.default ? true : false }
							onClick={ () => {
								onChange( index, {
									default: 1,
								} );
							} }
						>
							{ !! prompt.default
								? __( 'Default prompt', 'classifai' )
								: __( 'Set as default prompt', 'classifai' ) }
						</Button>
						{ ! prompt.original && (
							<>
								<span className="separator">{ '|' }</span>
								<Button
									className="action__remove_prompt"
									variant={ 'link' }
									onClick={ () => {
										setActiveIndex( index );
										setShowConfirmDialog( true );
									} }
								>
									{ __( 'Trash', 'classifai' ) }
								</Button>
							</>
						) }
					</div>
				</div>
			) ) }
			<ConfirmDialog
				isOpen={ showConfirmDialog }
				onConfirm={ handleConfirm }
				onCancel={ () => setShowConfirmDialog( false ) }
				confirmButtonText={ __( 'Remove' ) }
				size="medium"
			>
				{ __(
					'Are you sure you want to remove the prompt?',
					'classifai'
				) }
			</ConfirmDialog>
			<Button
				className="action__add_prompt"
				onClick={ addPrompt }
				variant={ 'secondary' }
			>
				{ __( 'Add new prompt', 'classifai' ) }
			</Button>
		</div>
	);
};

/* global lodash */
import { __, sprintf } from '@wordpress/i18n';
const { get } = lodash;

/**
 * Handle Click for given button.
 *
 * @param {Object}           root              Option for handle click.
 * @param {Element}          root.button       The button being clicked
 * @param {string}           root.endpoint     Which endpoint to query
 * @param {Function|boolean} root.callback     Optional callback to run after the request completes.
 * @param {Array|object}     root.callbackArgs Optional arguments to pass to the callback.
 * @param {string}           root.buttonText   Optional text to display on the button while the request is running.
 * @param {boolean}          root.linkTerms    Optional boolean to link terms.
 */
export const handleClick = ( {
	button,
	endpoint,
	callback = false,
	callbackArgs = [],
	buttonText = __( 'Rescan', 'classifai' ),
	linkTerms = true,
} ) => {
	const postID = button.getAttribute( 'data-id' );
	const [ spinner ] = button.parentNode.getElementsByClassName( 'spinner' );
	const [ errorContainer ] =
		button.parentNode.getElementsByClassName( 'error' );
	const path = `${ endpoint }${ postID }`;

	button.setAttribute( 'disabled', 'disabled' );
	spinner.style.display = 'inline-block';
	spinner.classList.add( 'is-active' );
	errorContainer.style.display = 'none';

	// Include the linkTerms attribute in the data object
	const request = {
		path,
		data: {
			linkTerms,
		},
	};

	wp.apiRequest( request ).then(
		( response ) => {
			button.removeAttribute( 'disabled' );
			spinner.style.display = 'none';
			spinner.classList.remove( 'is-active' );
			button.textContent = buttonText;
			// eslint-disable-next-line no-unused-expressions
			callback && callback( response, callbackArgs );
		},
		( error ) => {
			const errorObj = get( error, 'responseJSON', {
				code: 'unknown_error',
				message: __( 'An unknown error occurred.', 'classifai' ),
			} );
			spinner.style.display = 'none';
			spinner.classList.remove( 'is-active' );
			button.removeAttribute( 'disabled' );
			button.textContent = buttonText;
			errorContainer.style.display = 'inline-block';
			errorContainer.textContent = `Error: ${ errorObj.message }`;
		}
	);
};

/**
 * Make a request to a browser AI to generate text.
 *
 * @param {string} provider Provider to use.
 * @param {string} prompt   Prompt to send to the API.
 * @param {string} content  Content to add in addition to the prompt.
 */
export const browserAITextGeneration = async (
	provider = '',
	prompt = '',
	content = ''
) => {
	switch ( provider ) {
		case 'chrome_ai':
			return chromeAITextGeneration( prompt, content );
		default:
			return '';
	}
};

/**
 * Make a request to the Chrome AI API to generate text.
 *
 * @param {string} prompt  Prompt to send to the API.
 * @param {string} content Content to add in addition to the prompt.
 */
export const chromeAITextGeneration = async ( prompt = '', content = '' ) => {
	let result = '';
	const errorMessage = __(
		'Your browser does not support Chrome AI or the language model is not available. Please see setup instructions at https://10up.github.io/classifai/tutorial-chrome-built-in-ai.html',
		'classifai'
	);

	if ( ! window.ai ) {
		// eslint-disable-next-line no-alert
		window.alert( errorMessage );
		return result;
	}

	const supportsTextGeneration =
		await window.ai.languageModel?.capabilities();

	if (
		supportsTextGeneration &&
		supportsTextGeneration.available === 'readily'
	) {
		try {
			const session = await window.ai.languageModel.create( {
				initialPrompts: [
					{
						role: 'system',
						content: prompt,
					},
				],
			} );

			result = await session.prompt( `"""${ content }"""` );
		} catch ( e ) {
			// eslint-disable-next-line no-alert
			window.alert(
				sprintf(
					/* translators: %s: error message */
					__(
						'Error occured during AI text generation: %1$s. Please ensure you have followed the setup instructions at https://10up.github.io/classifai/tutorial-chrome-built-in-ai.html',
						'classifai'
					),
					e?.message
				)
			);
		}
	} else {
		// eslint-disable-next-line no-alert
		window.alert( errorMessage );
	}

	return result;
};

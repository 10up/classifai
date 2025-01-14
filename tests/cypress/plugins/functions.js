import * as nluData from '../../test-plugin/nlu.json';
import * as chatgptData from '../../test-plugin/chatgpt.json';
import * as chatgptCustomExcerptData from '../../test-plugin/chatgpt-custom-excerpt-prompt.json';
import * as chatgptCustomTitleData from '../../test-plugin/chatgpt-custom-title-prompt.json';
import * as dalleData from '../../test-plugin/dalle.json';
import * as whisperData from '../../test-plugin/whisper.json';
import * as imageData from '../../test-plugin/image_analyze.json';
import * as pdfData from '../../test-plugin/pdf.json';
import * as geminiData from '../../test-plugin/geminiapi.json';

/**
 * Get Taxonomy data from test NLU json file.
 *
 * @param {string} taxonomy  Taxonomy.
 * @param {number} threshold Threshold to select terms.
 * @return {string[]} NLU Data.
 */
export const getNLUData = ( taxonomy = 'categories', threshold = 0.7 ) => {
	const taxonomies = [];
	if ( taxonomy === 'categories' ) {
		nluData.categories
			.filter( ( el ) => el.score >= threshold )
			.forEach( ( cat ) =>
				taxonomies.push(
					...cat.label.split( '/' ).filter( ( n ) => n )
				)
			);
	} else {
		return nluData[ taxonomy ]
			.filter( ( el ) => el.relevance >= threshold )
			.map( ( el ) => el.text );
	}
	return taxonomies;
};

/**
 * Get text data from test ChatGPT json file.
 *
 * @param {string} type Type of data to return.
 * @return {string[]} ChatGPT Data.
 */
export const getChatGPTData = ( type = 'default' ) => {
	const text = [];

	if ( type === 'excerpt' ) {
		chatgptCustomExcerptData.choices.forEach( ( el ) => {
			text.push( el.message.content );
		} );
	} else if ( type === 'title' ) {
		chatgptCustomTitleData.choices.forEach( ( el ) => {
			text.push( el.message.content );
		} );
	} else {
		chatgptData.choices.forEach( ( el ) => {
			text.push( el.message.content );
		} );
	}

	return text.join( ' ' );
};

/**
 * Get text data from test GeminiAPI json file.
 *
 * @return {string[]} GeminiAPI Data.
 */
export const getGeminiAPIData = () => {
	const text = [];
	geminiData.candidates.forEach( ( el ) => {
		text.push( el.content.parts[ 0 ].text );
	} );

	return text.join( ' ' );
};

/**
 * Get data from test DALL·E json file.
 *
 * @return {string[]} DALL·E Data.
 */
export const getDalleData = () => {
	return dalleData.data;
};

/**
 * Get data from test Whisper json file.
 *
 * @return {string[]} Whisper data.
 */
export const getWhisperData = () => {
	return whisperData.text;
};

/**
 * Get Image OCR data
 *
 * @return {string} data Image OCR data
 */
export const getOCRData = () => {
	const words = [];
	imageData.readResult.blocks.forEach( ( el ) => {
		el.lines.forEach( ( el2 ) => {
			el2.words.forEach( ( el3 ) => {
				words.push( el3.text );
			} );
		} );
	} );
	return words.join( ' ' );
};

/**
 * Get image analysis data
 *
 * @return {Object} data image data
 */
export const getImageData = () => {
	const data = {
		altText: imageData.captionResult.text,
		tags: imageData.tagsResult.values
			.filter( ( el ) => el.confidence > 0.7 )
			.map( ( el ) => el.name ),
	};
	return data;
};

/**
 * Get PDF read data
 *
 * @return {string} data pdf data
 */
export const getPDFData = () =>
	pdfData.analyzeResult.readResults[ 0 ].lines[ 0 ].text;

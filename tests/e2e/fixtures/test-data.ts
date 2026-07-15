/**
 * Helpers that read the canned JSON responses shipped by the test plugin and
 * return the values the UI is expected to display. These are framework
 * agnostic — they're a direct port of `tests/cypress/plugins/functions.js`.
 */
import nluData from '../../test-plugin/nlu.json';
import chatgptData from '../../test-plugin/chatgpt.json';
import chatgptCustomExcerptData from '../../test-plugin/chatgpt-custom-excerpt-prompt.json';
import chatgptCustomTitleData from '../../test-plugin/chatgpt-custom-title-prompt.json';
import dalleData from '../../test-plugin/dalle.json';
import whisperData from '../../test-plugin/whisper.json';
import imageData from '../../test-plugin/image_analyze.json';
import pdfData from '../../test-plugin/pdf.json';
import geminiData from '../../test-plugin/geminiapi.json';

type NluTaxonomy = 'categories' | 'keywords' | 'concepts' | 'entities';

export const getNLUData = (
	taxonomy: NluTaxonomy | 'tags' = 'categories',
	threshold = 0.7
): string[] => {
	const taxonomies: string[] = [];
	if ( taxonomy === 'categories' ) {
		( nluData as any ).categories
			.filter( ( el: any ) => el.score >= threshold )
			.forEach( ( cat: any ) =>
				taxonomies.push(
					...cat.label.split( '/' ).filter( ( n: string ) => n )
				)
			);
		return taxonomies;
	}
	return ( nluData as any )[ taxonomy ]
		.filter( ( el: any ) => el.relevance >= threshold )
		.map( ( el: any ) => el.text );
};

export const getChatGPTData = (
	type: 'default' | 'excerpt' | 'title' = 'default'
): string => {
	const text: string[] = [];

	if ( type === 'excerpt' ) {
		chatgptCustomExcerptData.choices.forEach( ( el: any ) => {
			text.push( el.message.content );
		} );
	} else if ( type === 'title' ) {
		chatgptCustomTitleData.choices.forEach( ( el: any ) => {
			text.push( el.message.content );
		} );
	} else {
		chatgptData.choices.forEach( ( el: any ) => {
			text.push( el.message.content );
		} );
	}

	return text.join( ' ' );
};

export const getGeminiAPIData = (): string => {
	const text: string[] = [];
	geminiData.candidates.forEach( ( el: any ) => {
		text.push( el.content.parts[ 0 ].text );
	} );
	return text.join( ' ' );
};

export const getDalleData = () => dalleData.data;

export const getWhisperData = (): string => whisperData.text;

export const getOCRData = (): string => {
	const words: string[] = [];
	imageData.readResult.blocks.forEach( ( el: any ) => {
		el.lines.forEach( ( el2: any ) => {
			el2.words.forEach( ( el3: any ) => {
				words.push( el3.text );
			} );
		} );
	} );
	return words.join( ' ' );
};

export const getImageData = (): { altText: string; tags: string[] } => {
	return {
		altText: imageData.captionResult.text,
		tags: imageData.tagsResult.values
			.filter( ( el: any ) => el.confidence > 0.7 )
			.map( ( el: any ) => el.name ),
	};
};

export const getPDFData = (): string =>
	pdfData.analyzeResult.readResults[ 0 ].lines[ 0 ].text;

import * as nluData from '../../test-plugin/nlu.json';
import * as ocrData from '../../test-plugin/ocr.json';
import * as imageData from '../../test-plugin/image_analyze.json';
import * as pdfData from '../../test-plugin/pdf.json';

/**
 * Get Taxonomy data from test NLU json file.
 *
 * @param {string} taxonomy
 * @param {number} threshold
 * @returns string[]
 */
export const getNLUData = (taxonomy = 'categories', threshold = 0.7) => {
	const taxonomies = [];
	if (taxonomy === 'categories') {
		nluData.categories
			.filter((el) => el.score >= threshold)
			.forEach((cat) => taxonomies.push(...cat.label.split('/').filter((n) => n)));
	} else {
		return nluData[taxonomy].filter((el) => el.relevance >= threshold).map((el) => el.text);
	}
	return taxonomies;
};

/**
 * Get Image OCR data
 */
export const getOCRData = () => {
	const words = [];
	ocrData.regions.forEach((el) => {
		el.lines.forEach((el2) => {
			el2.words.forEach((el3) => {
				words.push(el3.text);
			});
		});
	});
	return words.join(' ');
};

/**
 * Get image analysis data
 *
 * @returns Object data image data
 */
export const getImageData = () => {
	const data = {
		altText: imageData.description.captions.filter((el) => el.confidence > 0.75)[0].text,
		tags: imageData.tags.filter((el) => el.confidence > 0.7).map((el) => el.name),
	};
	return data;
};

/**
 * Get PDF read data
 *
 * @returns string data pdf data
 */
export const getPDFData = () => pdfData.analyzeResult.readResults[0].lines[0].text;

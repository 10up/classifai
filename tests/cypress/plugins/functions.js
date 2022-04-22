import * as nluData from '../../test-plugin/nlu.json';

/**
 * Get Taxonomy data from test NLU json file.
 *
 * @param {string} taxonomy
 * @param {number} threshold
 * @returns string[]
 */
export const getNLUData = ( taxonomy = 'categories', threshold = 0.70 ) => {
	const taxonomies = [];
	if ( 'categories' === taxonomy ) {
		nluData.categories
			.filter( el => ( el.score >= threshold ) )
			.forEach( cat => taxonomies.push( ...cat.label.split( '/' ).filter( n => n ) ) );
	} else {
		return nluData[taxonomy]
			.filter( el => el.relevance >= threshold )
			.map( el => el.text );
	}
	return taxonomies;
};

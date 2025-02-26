/**
 * External Dependencies.
 */
import { Card } from '@wordpress/components';

/**
 * Internal Dependencies.
 */
import { RenderCardBody, RenderCardFooter } from './';

/**
 * Render the card.
 *
 * @param {Object}   props            The component props.
 * @param {Object}   props.item       The item to render.
 * @param {number}   props.i          The index of the item.
 * @param {boolean}  props.footer     Whether to render the footer or not.
 * @param {boolean}  props.iterating  Whether we are iterating or not.
 * @param {Function} props.iterate    The iterate function.
 * @param {Function} props.startOver  The start over function.
 * @param {Function} props.closeModal The close modal function.
 * @return {React.ReactNode} The rendered component.
 */
export const RenderCard = ( {
	item,
	i,
	footer,
	iterating,
	iterate,
	startOver,
	closeModal,
} ) => {
	return (
		<Card key={ i } style={ { marginBottom: '1rem' } }>
			<RenderCardBody item={ item } />
			{ footer && ! iterating && (
				<RenderCardFooter
					item={ item }
					iterate={ iterate }
					startOver={ startOver }
					closeModal={ closeModal }
				/>
			) }
		</Card>
	);
};

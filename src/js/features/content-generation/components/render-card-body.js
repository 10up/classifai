/**
 * External Dependencies.
 */
import { __ } from '@wordpress/i18n';
import { CardBody, Flex, FlexItem } from '@wordpress/components';
import { RawHTML } from '@wordpress/element';
import { autop } from '@wordpress/autop';

/**
 * Render the card body.
 *
 * @param {Object} props      The component props.
 * @param {Object} props.item The item to render.
 * @return {React.ReactNode} The rendered component.
 */
export const RenderCardBody = ( { item } ) => {
	return (
		<CardBody>
			<Flex justify="flex-end" direction="column">
				<FlexItem style={ { alignSelf: 'flex-end' } }>
					<h2>{ __( 'User', 'classifai' ) }</h2>
					<p>{ item.prompt }</p>
				</FlexItem>
				<FlexItem style={ { alignSelf: 'flex-start' } }>
					<h2>{ __( 'AI', 'classifai' ) }</h2>
					<RawHTML>{ autop( item.completion ) }</RawHTML>
				</FlexItem>
			</Flex>
		</CardBody>
	);
};

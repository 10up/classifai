/**
 * External Dependencies.
 */
import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import { CardFooter, Button } from '@wordpress/components';
import { autop } from '@wordpress/autop';
import { rawHandler } from '@wordpress/blocks';

/**
 * Render our card footer.
 *
 * @param {Object}   props            The component props.
 * @param {Object}   props.item       The item we want to render.
 * @param {Function} props.iterate    The iterate function.
 * @param {Function} props.startOver  The start over function.
 * @param {Function} props.closeModal The close modal function.
 * @return {React.ReactNode} The rendered component.
 */
export const RenderCardFooter = ( {
	item,
	iterate,
	startOver,
	closeModal,
} ) => {
	return (
		<CardFooter justify="flex-end" isBorderless={ true }>
			<Button variant="tertiary" onClick={ iterate }>
				{ __( 'Request changes', 'classifai' ) }
			</Button>
			<Button variant="tertiary" isDestructive onClick={ startOver }>
				{ __( 'Start over', 'classifai' ) }
			</Button>
			<Button variant="secondary" onClick={ closeModal }>
				{ __( 'Cancel', 'classifai' ) }
			</Button>
			<Button
				variant="primary"
				onClick={ () => {
					dispatch( 'core/editor' )
						.editPost( {
							content: '',
						} )
						.then( () => {
							dispatch( 'core/block-editor' ).insertBlocks(
								rawHandler( {
									HTML: autop( item.completion ),
								} )
							);
							closeModal();
						} );
				} }
			>
				{ __( 'Insert content', 'classifai' ) }
			</Button>
		</CardFooter>
	);
};

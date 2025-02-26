/**
 * External Dependencies.
 */
import { __ } from '@wordpress/i18n';
import { Button, TextareaControl, Flex, FlexItem } from '@wordpress/components';

/**
 * Internal Dependencies.
 */
import { RenderCard } from './render-card';

/**
 * Render our data.
 *
 * @param {Object}   props            The component props.
 * @param {Array}    props.data       The data we want to render.
 * @param {Function} props.closeModal The close modal function.
 * @param {Function} props.startOver  The start over function.
 * @param {boolean}  props.iterating  Whether we are iterating or not.
 * @param {Function} props.iterate    The iterate function.
 * @param {Function} props.setSummary The set summary function.
 * @param {string}   props.summary    The summary.
 * @param {boolean}  props.isLoading  The loading state.
 * @param {Function} props.getContent The get content function.
 * @return {React.ReactNode} The rendered component.
 */
export const RenderData = ( {
	data: dataToRender,
	closeModal,
	startOver,
	iterating,
	iterate,
	setSummary,
	summary,
	isLoading,
	getContent,
} ) => {
	if ( dataToRender.length < 1 ) {
		return null;
	}

	return (
		<>
			{ dataToRender.map( ( item, i ) => {
				if ( ! item.completion ) {
					return null;
				}

				return (
					<RenderCard
						key={ i }
						item={ item }
						i={ i }
						footer={ i === dataToRender.length - 1 }
						iterating={ iterating }
						iterate={ iterate }
						startOver={ startOver }
						closeModal={ closeModal }
					/>
				);
			} ) }

			{ iterating && (
				<>
					<TextareaControl
						rows="5"
						label={ __( 'Requested changes', 'classifai' ) }
						onChange={ ( value ) => {
							setSummary( value );
						} }
						value={ summary }
						disabled={ isLoading }
					/>
					<Flex justify="flex-end">
						<FlexItem>
							<Button
								variant="secondary"
								onClick={ closeModal }
								disabled={ isLoading }
							>
								{ __( 'Cancel', 'classifai' ) }
							</Button>
						</FlexItem>
						<FlexItem>
							<Button
								variant="primary"
								onClick={ getContent }
								isBusy={ isLoading }
								disabled={ isLoading || ! summary }
							>
								{ __( 'Submit', 'classifai' ) }
							</Button>
						</FlexItem>
					</Flex>
				</>
			) }
		</>
	);
};

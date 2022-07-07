/* eslint-disable no-unused-vars */
/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl, Placeholder, SelectControl } from '@wordpress/components';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import TaxonomyControls from './inspector-controls/taxonomy-controls';
import { usePostTypes } from './utils';

// Importing the block's editor styles via JS will enable hot reloading for css
import './editor.css';

/**
 * Edit component.
 * See https://wordpress.org/gutenberg/handbook/designers-developers/developers/block-api/block-edit-save/#edit
 *
 * @param {object}   props                                   The block props.
 * @param {object}   props.attributes                        Block attributes.
 * @param {string}   props.attributes.contentPostType        Post type for display recommended content.
 * @param {object}   props.attributes.taxQuery               Tax Query for filter recommended content.
 * @param {boolean}  props.attributes.displayAuthor          Whether to display post author.
 * @param {boolean}  props.attributes.displayFeaturedImage   Whether to display featured image.
 * @param {boolean}  props.attributes.displayPostDate        Whether to display post date.
 * @param {boolean}  props.attributes.displayPostExcerpt     Whether to display post excerpt.
 * @param {boolean}  props.attributes.addLinkToFeaturedImage Whether to add post permalink to featured image.
 * @param {string}   props.className                         Class name for the block.
 * @param {Function} props.setAttributes                     Sets the value for block attributes.
 * @returns {Function} Render the edit screen
 */
const RecommendedContentBlockEdit = (props) => {
	const { attributes, setAttributes } = props;
	const {
		contentPostType,
		taxQuery,
		displayAuthor,
		displayFeaturedImage,
		displayPostDate,
		displayPostExcerpt,
		addLinkToFeaturedImage,
	} = attributes;

	const blockProps = useBlockProps();
	const { postTypesTaxonomiesMap, postTypesSelectOptions } = usePostTypes();
	const onPostTypeChange = (newValue) => {
		const updateQuery = { contentPostType: newValue };
		// We need to dynamically update the `taxQuery` property,
		// by removing any not supported taxonomies from the query.
		const supportedTaxonomies = postTypesTaxonomiesMap[newValue];
		const updatedTaxQuery = Object.entries(taxQuery || {}).reduce(
			(accumulator, [taxonomySlug, terms]) => {
				if (supportedTaxonomies.includes(taxonomySlug)) {
					accumulator[taxonomySlug] = terms;
				}
				return accumulator;
			},
			{},
		);
		// eslint-disable-next-line no-extra-boolean-cast
		updateQuery.taxQuery = !!Object.keys(updatedTaxQuery).length ? updatedTaxQuery : undefined;
		setAttributes(updateQuery);
	};

	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody title={__('Recommended Content Filters', 'classifai')}>
					{postTypesSelectOptions && (
						<SelectControl
							label={__('Post type', 'classifai')}
							value={contentPostType}
							options={postTypesSelectOptions}
							onChange={onPostTypeChange}
						/>
					)}
					{postTypesSelectOptions && (
						<TaxonomyControls onChange={setAttributes} query={attributes} />
					)}
				</PanelBody>
				<PanelBody title={__('Post content settings', 'classifai')}>
					<ToggleControl
						label={__('Post excerpt', 'classifai')}
						checked={displayPostExcerpt}
						onChange={(value) => setAttributes({ displayPostExcerpt: value })}
					/>
				</PanelBody>

				<PanelBody title={__('Post meta settings', 'classifai')}>
					<ToggleControl
						label={__('Display author name', 'classifai')}
						checked={displayAuthor}
						onChange={(value) => setAttributes({ displayAuthor: value })}
					/>
					<ToggleControl
						label={__('Display post date', 'classifai')}
						checked={displayPostDate}
						onChange={(value) => setAttributes({ displayPostDate: value })}
					/>
				</PanelBody>

				<PanelBody title={__('Featured image settings', 'classifai')}>
					<ToggleControl
						label={__('Display featured image', 'classifai')}
						checked={displayFeaturedImage}
						onChange={(value) => setAttributes({ displayFeaturedImage: value })}
					/>
					{displayFeaturedImage && (
						<ToggleControl
							label={__('Add link to featured image', 'classifai')}
							checked={addLinkToFeaturedImage}
							onChange={(value) =>
								setAttributes({
									addLinkToFeaturedImage: value,
								})
							}
						/>
					)}
				</PanelBody>
			</InspectorControls>

			{!contentPostType && (
				<Placeholder label={__('ClassifAI Recommended Content', 'classifai')}>
					<p>
						{__(
							'Please select Post type for this Recommended Content block on the sidebar settings.',
							'classifai',
						)}
					</p>
				</Placeholder>
			)}
			{contentPostType && (
				<ServerSideRender
					block="classifai/recommended-content-block"
					attributes={{
						contentPostType,
						taxQuery,
						displayAuthor,
						displayFeaturedImage,
						displayPostDate,
						displayPostExcerpt,
						addLinkToFeaturedImage,
					}}
				/>
			)}
		</div>
	);
};
export default RecommendedContentBlockEdit;

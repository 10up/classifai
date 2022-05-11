/* eslint-disable no-unused-vars */
/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { PanelBody, ToggleControl, Placeholder, SelectControl } from '@wordpress/components';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

// Importing the block's editor styles via JS will enable hot reloading for css
import './editor.css';

/**
 * Edit component.
 * See https://wordpress.org/gutenberg/handbook/designers-developers/developers/block-api/block-edit-save/#edit
 *
 * @param {object}   props                                 The block props.
 * @param {object}   props.attributes                      Block attributes.
 * @param {string}   props.attributes.contentPostType      Post type for display recommended content.
 * @param {boolean}  props.attributes.displayAuthor        Whether to display post author.
 * @param {boolean}  props.attributes.displayFeaturedImage Whether to display featured image.
 * @param {boolean}  props.attributes.displayPostDate      Whether to display post date.
 * @param {boolean}  props.attributes.displayPostExcept    Whether to display post excerpt.
 * @param {boolean}  props.attributes.addLinkToFeaturedImage Whether to add post permalink to featured image.
 * @param {string}   props.className                       Class name for the block.
 * @param {Function} props.setAttributes                   Sets the value for block attributes.
 * @returns {Function} Render the edit screen
 */
const RecommendedContentBlockEdit = (props) => {
	const { attributes, setAttributes } = props;
	const {
		title,
		contentPostType,
		displayAuthor,
		displayFeaturedImage,
		displayPostDate,
		displayPostExcept,
		addLinkToFeaturedImage,
	} = attributes;

	const blockProps = useBlockProps();

	const availablePostTypes = useSelect((select) => {
		const { getPostTypes } = select('core');

		const excludedPostTypes = ['attachment'];
		const filteredPostTypes = getPostTypes({ per_page: -1 })
			?.filter(({ viewable, slug }) => viewable && !excludedPostTypes.includes(slug))
			?.map((ele) => ({ value: ele.slug, label: ele.name }));

		return filteredPostTypes;
	}, []);

	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody title={__('Recommended Content settings', 'classifai')}>
					{availablePostTypes && (
						<SelectControl
							label={__('Post type', 'classifai')}
							value={contentPostType}
							options={availablePostTypes}
							onChange={(value) => setAttributes({ contentPostType: value })}
						/>
					)}
				</PanelBody>
				<PanelBody title={__('Post content settings', 'classifai')}>
					<ToggleControl
						label={__('Post excerpt', 'classifai')}
						checked={displayPostExcept}
						onChange={(value) => setAttributes({ displayPostExcept: value })}
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
						displayAuthor,
						displayFeaturedImage,
						displayPostDate,
						displayPostExcept,
						addLinkToFeaturedImage,
					}}
				/>
			)}
		</div>
	);
};
export default RecommendedContentBlockEdit;

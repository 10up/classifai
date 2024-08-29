import { useSelect, useDispatch } from '@wordpress/data';
import { RadioControl, CheckboxControl, Button, SearchControl, TextHighlight } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { usePostTypes, usePostStatuses } from '../../utils/utils';
import { NLUFeatureSettings } from './nlu-feature';

const ClassificationMethodSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );

	const classifaicationMethodOptions = [
		{
			label: __(
				'Recommend terms even if they do not exist on the site',
				'classifai'
			),
			value: 'recommended_terms',
		},
		{
			label: __(
				'Only recommend terms that already exist on the site',
				'classifai'
			),
			value: 'existing_terms',
		},
	];

	if (
		[ 'openai_embeddings', 'azure_openai_embeddings' ].includes(
			featureSettings.provider
		)
	) {
		delete classifaicationMethodOptions[ 0 ];
		if ( featureSettings.classification_method === 'recommended_terms' ) {
			setFeatureSettings( {
				classification_method: 'existing_terms',
			} );
		}
	}

	return (
		<SettingsRow label={ __( 'Classification method', 'classifai' ) }>
			<RadioControl
				onChange={ ( value ) => {
					setFeatureSettings( {
						classification_method: value,
					} );
				} }
				options={ classifaicationMethodOptions }
				selected={ featureSettings.classification_method }
			/>
		</SettingsRow>
	);
};

export const ClassificationSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const { postTypesSelectOptions } = usePostTypes();
	const { postStatusOptions } = usePostStatuses();

	return (
		<>
			<SettingsRow>
				<Button variant='secondary'>
					{ __( 'Open Previewer', 'classifai' ) }
				</Button>
				<Previewer />
			</SettingsRow>
			<SettingsRow label={ __( 'Classification mode', 'classifai' ) }>
				<RadioControl
					onChange={ ( value ) => {
						setFeatureSettings( {
							classification_mode: value,
						} );
					} }
					options={ [
						{
							label: __( 'Manual review', 'classifai' ),
							value: 'manual_review',
						},
						{
							label: __(
								'Automatic classification',
								'classifai'
							),
							value: 'automatic_classification',
						},
					] }
					selected={ featureSettings.classification_mode }
				/>
			</SettingsRow>
			<ClassificationMethodSettings />
			<NLUFeatureSettings />
			<SettingsRow
				label={ __( 'Post statuses', 'classifai' ) }
				description={ __(
					'Choose which post statuses are allowed to use this feature.',
					'classifai'
				) }
			>
				{ postStatusOptions.map( ( option ) => {
					const { value: key, label } = option;
					return (
						<CheckboxControl
							key={ key }
							checked={
								featureSettings.post_statuses?.[ key ] === key
							}
							label={ label }
							onChange={ ( value ) => {
								setFeatureSettings( {
									post_statuses: {
										...featureSettings.post_statuses,
										[ key ]: value ? key : '0',
									},
								} );
							} }
						/>
					);
				} ) }
			</SettingsRow>
			<SettingsRow
				label={ __( 'Allowed post types', 'classifai' ) }
				description={ __(
					'Choose which post types are allowed to use this feature.',
					'classifai'
				) }
			>
				{ postTypesSelectOptions.map( ( option ) => {
					const { value: key, label } = option;
					return (
						<CheckboxControl
							key={ key }
							checked={
								featureSettings.post_types?.[ key ] === key
							}
							label={ label }
							onChange={ ( value ) => {
								setFeatureSettings( {
									post_types: {
										...featureSettings.post_types,
										[ key ]: value ? key : '0',
									},
								} );
							} }
						/>
					);
				} ) }
			</SettingsRow>
		</>
	);
};

function Previewer() {
	return (
		<div className='classifai__classification-previewer'>
			<PostSelector />
		</div>
	);
}

function PostSelector() {
	const [ searchText, setSearchText ] = useState( '' );
	const [ searchResults, setSearchResults ] = useState( [] );
	const [ selectedPostId, setSelectedPostId ] = useState( 0 );
	const [ shouldSearch, setShoudlSearch ] = useState( true );
	const debouncedSearch = useDebounce( setSearchText, 1000 );

	function selectPost( post ) {
		setShoudlSearch( false );
		setSelectedPostId( post.id );
		setSearchText( post.title );
		setSearchResults( [] );
	}

	useEffect( () => {
		if ( ! searchText ) {
			setSearchResults( [] );
			return;
		}

		if ( ! shouldSearch ) {
			return;
		}

		( async () => {
			const response = await wp.apiRequest({
				path: '/wp/v2/posts',
				data: {
					search: searchText
				}
			} );

			if ( Array.isArray( response ) ) {
				setSearchResults(
					response.map( post => ( { id: post.id, title: post.title.rendered } ) )
				);
			}
		} )()
	}, [ searchText, shouldSearch ] );

	const searchResultsHtml = searchResults.length ? searchResults.map( ( post ) => (
		<div
			key={ post.id }
			onClick={ () => selectPost( post ) }
			className='classifai__classification-previewer-search-item'
		>
			<TextHighlight text={ post.title } highlight={ searchText } />
		</div>
	) ) : [];

	return (
		<div className='classifai__classification-previewer-search-control'>
			<div className='classifai__classification-previewer-search-and-results'>
				<SearchControl
					__nextHasNoMarginBottom
					size="compact"
					value={ searchText }
					placeholder={ __( 'Search for a post:', 'classifai' ) }
					onChange={ ( text ) => {
						setShoudlSearch( true );
						debouncedSearch( text );
					} }
					onClose={ () => {
						setSearchText( '' );
						setSearchResults( [] );
						setShoudlSearch( true );
					} }
				/>
				{
					searchResults.length ? (
						<div className='classifai__classification-previewer-search-results'>
							{ searchResultsHtml }
						</div>
					) : null
				}
			</div>
			<Button size='compact' variant='primary'>{ __( 'Preview', 'classifai' ) }</Button>
		</div>
	);
}

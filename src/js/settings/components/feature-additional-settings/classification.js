import { useSelect, useDispatch } from '@wordpress/data';
import {
	RadioControl,
	CheckboxControl,
	Button,
	SearchControl,
	TextHighlight,
	Card,
	CardHeader,
	CardBody,
	__experimentalHeading as Heading
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';
import apiFetch from '@wordpress/api-fetch';
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
	const [ selectedPostId, setSelectedPostId ] = useState( 0 );

	return (
		<div className='classifai__classification-previewer'>
			<PostSelector setSelectedPostId={ setSelectedPostId } />
			<PreviewerResults selectedPostId={ selectedPostId } />
		</div>
	);
}

function PostSelector( { setSelectedPostId } ) {
	const [ searchText, setSearchText ] = useState( '' );
	const [ searchResults, setSearchResults ] = useState( [] );
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
						setSelectedPostId( 0 );
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

function PreviewerResults( { selectedPostId } ) {
	const activeProvider = useSelect( ( select ) => select( STORE_NAME ).getFeatureSettings().provider );

	if ( ! selectedPostId ) {
		return null;
	}

	if ( ! activeProvider ) {
		return null;
	}

	return (
		<div className='classifai__classification-previewer-search-result-container'>
			{ 'azure_openai_embeddings' === activeProvider && <AzureOpenAIEmbeddingsResults postId={ selectedPostId } /> }
		</div>
	);
}

function AzureOpenAIEmbeddingsResults( { postId } ) {
	const [ responseData, setResponseData ] = useState( [] );

	useEffect( () => {
		if ( ! postId ) {
			return;
		}

		const formData = new FormData();

		formData.append( 'post_id', postId );
		formData.append(
			'action',
			'get_post_classifier_embeddings_preview_data'
		);
		// formData.append( 'nonce', previewerNonce );

		( async () => {
			const response = await fetch( ajaxurl, {
				method: 'POST',
				body: formData,
			} );

			if ( ! response.ok ) {
				return;
			}

			const responseJSON = await response.json();

			if ( responseJSON.success ) {
				const flattenedResponse = responseJSON.data.reduce((acc, obj) => {
					const [ key, value ] = Object.entries( obj )[0];
					acc[ key ] = value;
					return acc;
				}, {} );

				setResponseData( flattenedResponse );
			}
		} )()
	}, [ postId ] );

	const card = Object.keys( responseData ).map( ( taxLabel, index ) => {
		const tags = responseData[ taxLabel ].map( ( tag, _index ) => (
			<div className='classifai__classification-previewer-result-tag'>
				<span className='classifai__classification-previewer-result-tag-score'>{ formatScore( tag.score ) }</span>
				<span className='classifai__classification-previewer-result-tag-label'>{ tag.label }</span>
			</div>
		) );

		return (
			<Card className='classifai__classification-previewer-result-card'>
				<CardHeader>
					<Heading className='classifai__classification-previewer-result-card-heading'>
						{ taxLabel }
					</Heading>
				</CardHeader>
				<CardBody>
					{ tags.length ? tags : __( `No classification data found for ${ taxLabel }`, 'classifai' ) }
				</CardBody>
			</Card>
		)
	} );

	return card.length ? card : null
}

function formatScore( score ) {
	return ( score * 100 ).toFixed( 2 );
}
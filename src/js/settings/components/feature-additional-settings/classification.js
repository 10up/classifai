import { useSelect, useDispatch } from '@wordpress/data';
import {
	RadioControl,
	CheckboxControl,
	BaseControl,
	SearchControl,
	TextHighlight,
	Card,
	CardHeader,
	CardBody,
	Spinner,
	Notice,
	Button,
	__experimentalHeading as Heading
} from '@wordpress/components';
import { useState, useEffect, createContext, useContext } from '@wordpress/element';
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

const PreviewerProviderContext = createContext( {
	isPreviewerOpen: false
} );

function PreviewerProvider( { children, value } ) {
	return (
		<PreviewerProviderContext.Provider value={ value }>
			{ children }
		</PreviewerProviderContext.Provider>
	)
}

export const ClassificationSettings = () => {
	const [ isPreviewerOpen, setIsPreviewerOpen ] = useState( false );
	const [ selectedPostId, setSelectedPostId ] = useState( 0 );
	const [ isPreviewUnderProcess, setPreviewUnderProcess ] = useState( null );

	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const { postTypesSelectOptions } = usePostTypes();
	const { postStatusOptions } = usePostStatuses();

	const previewerContextData = { 
		isPreviewerOpen,
		setIsPreviewerOpen,
		selectedPostId,
		setSelectedPostId,
		isPreviewUnderProcess,
		setPreviewUnderProcess,
	};

	return (
		<>
			<SettingsRow>
				<PreviewerProvider value={ previewerContextData }>
					<BaseControl help={ __( 'Used to preview the results for a particular post.', 'classifai' ) }>
						<PostSelector showLabel={ false } />
					</BaseControl>
					<Previewer />
				</PreviewerProvider>
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
	const {
		isPreviewerOpen,
		setIsPreviewerOpen,
		selectedPostId,
	} = useContext( PreviewerProviderContext );

	return (
		<div className={ `classifai__classification-previewer ${ isPreviewerOpen ? 'classifai__classification-previewer--open' : '' }` }>
			<PostSelector placeholder={ __( 'Search a different post to preview...', 'classifai' ) } />
			<PreviewInProcess />
			<PreviewerResults />
			<Button
				className='classifai__classification-previewer-close-button'
				onClick={ () => setIsPreviewerOpen( ! isPreviewerOpen ) }
				variant='link'
			>
				{ __( 'Close previewer', 'classifai' ) }
			</Button>
		</div>
	);
}

function PreviewInProcess() {
	const { isPreviewUnderProcess } = useContext( PreviewerProviderContext );

	if ( ! isPreviewUnderProcess ) {
		return null;
	}

	return (
		<div className='classifai__classification-previewer-processing'>
			<Spinner
				style={ {
					width: '48px',
					height: '48px',
				} }
			/>
		</div>
	)
}

function PostSelector( { placeholder = '', showLabel = true } ) {
	const { setSelectedPostId } = useContext( PreviewerProviderContext );
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
					hideLabelFromVision={ showLabel }
					value={ searchText }
					label={ __( 'Previewer:' ) }
					placeholder={ placeholder || __( 'Search a post by title...', 'classifai' ) }
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
		</div>
	);
}

function PreviewerResults() {
	const { selectedPostId } = useContext( PreviewerProviderContext );
	const activeProvider = useSelect( ( select ) => select( STORE_NAME ).getFeatureSettings().provider );

	if ( ! selectedPostId ) {
		return null;
	}

	if ( ! activeProvider ) {
		return null;
	}

	return (
		<div className='classifai__classification-previewer-search-result-container'>
			{ 'azure_openai_embeddings' === activeProvider || 'openai_embeddings' === activeProvider && <AzureOpenAIEmbeddingsResults postId={ selectedPostId } /> }
			{ 'ibm_watson_nlu' === activeProvider && <IBMWatsonNLUResults postId={ selectedPostId } /> }
		</div>
	);
}

function AzureOpenAIEmbeddingsResults( { postId } ) {
	const {
		isPreviewUnderProcess,
		setPreviewUnderProcess,
		setIsPreviewerOpen,
	} = useContext( PreviewerProviderContext );

	const [ responseData, setResponseData ] = useState( [] );
	const [ errorMessage, setErrorMessage ] = useState( '' );
	const settings = useSelect( ( select ) => select( STORE_NAME ).getFeatureSettings() );

	useEffect( () => {
		// Reset previous results.
		if ( isPreviewUnderProcess ) {
			setResponseData( [] );
		}
	}, [ isPreviewUnderProcess ] );

	useEffect( () => {
		if ( ! postId ) {
			return;
		}

		setPreviewUnderProcess( true );
		setIsPreviewerOpen( true );
		setErrorMessage( '' );

		const formData = new FormData();

		formData.append( 'post_id', postId );
		formData.append(
			'action',
			'get_post_classifier_embeddings_preview_data'
		);

		formData.append( 'nonce', classifAISettings.nonce );

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
				setResponseData( responseJSON.data );
			} else {
				setErrorMessage( responseJSON.data );
			}

			setPreviewUnderProcess( false );
		} )()
	}, [ postId ] );

	const card = Object.keys( responseData ).map( ( taxSlug ) => {
		const tags = responseData[ taxSlug ].data.map( ( tag, _index ) => {
			const threshold = settings[ `${ taxSlug}_threshold` ];
			const score = normalizeScore( tag.score );

			const scoreClass = score >= threshold ? 'classifai__classification-previewer-result-tag--exceeds-threshold' : '';

			return (
				<div className={ `classifai__classification-previewer-result-tag ${ scoreClass }` } key={ _index }>
					<span className='classifai__classification-previewer-result-tag-score'>{ score }%</span>
					<span className='classifai__classification-previewer-result-tag-label'>{ tag.label }</span>
				</div>
			)
		} );

		return (
			<Card className='classifai__classification-previewer-result-card' key={ taxSlug }>
				<CardHeader>
					<Heading className='classifai__classification-previewer-result-card-heading'>
						{ responseData[ taxSlug ].label }
					</Heading>
				</CardHeader>
				<CardBody>
					{ tags.length ? tags : __( `No classification data found for ${ taxLabel }.`, 'classifai' ) }
				</CardBody>
			</Card>
		)
	} );

	if ( errorMessage ) {
		return (
			<Notice status='error' isDismissible={ false } className='classifai__classification-previewer-result-notice'>
				{ errorMessage }
			</Notice>
		);
	}

	return card.length ? (
		<>
			<Notice status='success' isDismissible={ false } className='classifai__classification-previewer-result-notice'>
				{ __( 'Results for each taxonomy are sorted in descending order, starting with the term that has the highest score, indicating the best match based on the embedding data.', 'classifai' ) }
			</Notice>
			{ card }
		</>
	) : null
}

function IBMWatsonNLUResults( { postId } ) {
	const {
		isPreviewUnderProcess,
		setPreviewUnderProcess,
		setIsPreviewerOpen,
	} = useContext( PreviewerProviderContext );

	const [ responseData, setResponseData ] = useState( null );
	const [ errorMessage, setErrorMessage ] = useState( '' );
	const settings = useSelect( ( select ) => select( STORE_NAME ).getFeatureSettings() );

	const taxMap = {
		categories: 'category',
		concepts: 'concept',
		entities: 'entity',
		keywords: 'keyword',
	};

	function formatLabel( label ) {
		return label
			.split( '/' )
			.filter( ( i ) => '' !== i )
			.join( ', ' );
	}

	useEffect( () => {
		// Reset previous results.
		if ( isPreviewUnderProcess ) {
			setResponseData( null );
		}
	}, [ isPreviewUnderProcess ] );

	useEffect( () => {
		if ( ! postId ) {
			return;
		}

		setPreviewUnderProcess( true );
		setIsPreviewerOpen( true );

		const formData = new FormData();

		formData.append( 'action', 'get_post_classifier_preview_data' );
		formData.append( 'post_id', postId );
		formData.append( 'nonce', classifAISettings.nonce );

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
				setResponseData( responseJSON.data );
			} else {
				setErrorMessage( responseJSON.data );
			}

			setPreviewUnderProcess( false );
		} )()
	}, [] );

	if ( ! responseData ) {
		return null;
	}

	const renderData = {
		categories: responseData.categories,
		concepts: responseData.concepts,
		entities: responseData.entities,
		keywords: responseData.keywords,
	};

	const card = Object.keys( renderData ).map( ( taxSlug ) => {
		const tags = renderData[ taxSlug ].map( ( tag, _index ) => {
			const threshold = settings[ `${ taxMap[ taxSlug ] }_threshold` ];
			const score = normalizeScore( tag.score || tag.relevance );

			const scoreClass = score >= threshold ? 'classifai__classification-previewer-result-tag--exceeds-threshold' : '';

			return (
				<div className={ `classifai__classification-previewer-result-tag ${ scoreClass }` } key={ _index }>
					<span className='classifai__classification-previewer-result-tag-score'>{ score }%</span>
					<span className='classifai__classification-previewer-result-tag-label'>{ formatLabel( tag.label || tag.text ) }</span>
				</div>
			);
		} );

		return (
			<Card className='classifai__classification-previewer-result-card' key={ taxSlug }>
				<CardHeader>
					<Heading className='classifai__classification-previewer-result-card-heading'>
						{ taxSlug }
					</Heading>
				</CardHeader>
				<CardBody>
					{ tags.length ? tags : __( `No classification data found for ${ taxSlug }.`, 'classifai' ) }
				</CardBody>
			</Card>
		)
	} );

	return card.length ? (
		<>
			<Notice status='success' isDismissible={ false } className='classifai__classification-previewer-result-notice'>
				{ __( 'Results for each category are sorted in descending order, starting with the term that has the highest score, indicating the best match based on the embedding data.', 'classifai' ) }
			</Notice>
			{ card }
		</>
	) : null
}

function normalizeScore( score ) {
	return Number( ( score * 100 ).toFixed( 2 ) );
}

/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import {
	RadioControl,
	CheckboxControl,
	BaseControl,
	SearchControl,
	TextHighlight,
	Spinner,
	Button,
	Fill,
	Notice,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect, useContext, useRef } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { isFeatureActive } from '../../utils/utils';
import { NLUFeatureSettings } from './nlu-feature';
import {
	AzureOpenAIEmbeddingsResults,
	IBMWatsonNLUResults,
} from './classification-previewers';
import { PreviewerProviderContext } from './classification-previewers/context';

/**
 * Component for the Classification Method settings.
 *
 * This component is used within the ClassificationSettings component to allow users to configure the Classification Method.
 *
 * @return {React.ReactElement} The ClassificationMethodSettings component.
 */
const ClassificationMethodSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );

	const classificationMethodOptions = [];
	if ( featureSettings.provider === 'ibm_watson_nlu' ) {
		classificationMethodOptions.push( {
			label: __(
				'Recommend terms even if they do not exist on the site',
				'classifai'
			),
			value: 'recommended_terms',
		} );
	}

	classificationMethodOptions.push( {
		label: __(
			'Only recommend terms that already exist on the site',
			'classifai'
		),
		value: 'existing_terms',
	} );

	useEffect( () => {
		if (
			[ 'openai_embeddings', 'azure_openai_embeddings' ].includes(
				featureSettings.provider
			)
		) {
			if (
				featureSettings.classification_method === 'recommended_terms'
			) {
				setFeatureSettings( {
					classification_method: 'existing_terms',
				} );
			}
		}
	}, [
		featureSettings.provider,
		featureSettings.classification_method,
		setFeatureSettings,
	] );

	return (
		<SettingsRow label={ __( 'Classification method', 'classifai' ) }>
			<RadioControl
				className="classification-method-radio-control"
				onChange={ ( value ) => {
					setFeatureSettings( {
						classification_method: value,
					} );
				} }
				options={ classificationMethodOptions }
				selected={ featureSettings.classification_method }
			/>
		</SettingsRow>
	);
};

/**
 * Provider component for the Classification Previewer.
 *
 * This component is used to provide the context for the Classification Previewer.
 *
 * @param {Object} props          The component props.
 * @param {Object} props.children The child components.
 * @param {Object} props.value    The context value.
 *
 * @return {React.ReactElement} The PreviewerProvider component.
 */
function PreviewerProvider( { children, value } ) {
	return (
		<PreviewerProviderContext.Provider value={ value }>
			{ children }
		</PreviewerProviderContext.Provider>
	);
}

/**
 * React Component for configuring the Classification feature settings.
 *
 * This component is used within the ClassifAI settings to allow users to configure the Classification feature.
 *
 * @return {React.ReactElement} The ClassificationSettings component.
 */
export const ClassificationSettings = () => {
	const [ embedInProgress, setEmbedInProgress ] = useState( false );
	const isEmbeddingInProgress = useRef( false );
	const [ isPreviewerOpen, setIsPreviewerOpen ] = useState( false );
	const [ selectedPostId, setSelectedPostId ] = useState( 0 );
	const [ isPreviewUnderProcess, setPreviewUnderProcess ] = useState( null );

	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const isSaving = useSelect( ( select ) =>
		select( STORE_NAME ).getIsSaving()
	);
	const isConfigured = isFeatureActive( featureSettings );
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const { postTypes, postStatuses } = window.classifAISettings;
	const { createSuccessNotice } = useDispatch( noticesStore );

	const previewerContextData = {
		isPreviewerOpen,
		setIsPreviewerOpen,
		selectedPostId,
		setSelectedPostId,
		isPreviewUnderProcess,
		setPreviewUnderProcess,
	};

	// Check if embeddings are in progress
	useEffect( () => {
		if ( ! isSaving ) {
			const getEmbeddingsInProgress = async () => {
				try {
					const res = await apiFetch( {
						path: '/classifai/v1/embeddings_in_progress',
					} );
					if ( res?.classifAIEmbedInProgress ) {
						setEmbedInProgress( true );
						isEmbeddingInProgress.current = true;
					} else {
						setEmbedInProgress( false );
						clearInterval( intervalId );
						if ( isEmbeddingInProgress.current ) {
							createSuccessNotice(
								__(
									'Generation of embeddings is completed.',
									'classifai'
								),
								{
									id: 'success-feature_classification',
								}
							);
						}
						isEmbeddingInProgress.current = false;
					}
				} catch ( error ) {}
			};

			const intervalId = setInterval( getEmbeddingsInProgress, 10000 );
			getEmbeddingsInProgress();

			return () => clearInterval( intervalId );
		}
	}, [ isSaving, createSuccessNotice ] );

	return (
		<>
			{ embedInProgress && (
				<Fill name="ClassifAIBeforeFeatureSettingsPanel">
					<Notice status="info" isDismissible={ false }>
						{ __(
							'Generation of embeddings is in progress.',
							'classifai'
						) }
					</Notice>
				</Fill>
			) }
			{ !! isConfigured && (
				<SettingsRow>
					<PreviewerProvider value={ previewerContextData }>
						<BaseControl
							help={ __(
								'Used to preview the results for a particular post.',
								'classifai'
							) }
							__nextHasNoMarginBottom
						>
							<PostSelector showLabel={ false } />
						</BaseControl>
						<Previewer />
					</PreviewerProvider>
				</SettingsRow>
			) }
			<SettingsRow label={ __( 'Classification mode', 'classifai' ) }>
				<RadioControl
					className="classification-mode-radio-control"
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
				className="settings-allowed-post-statuses"
			>
				{ Object.keys( postStatuses || {} ).map( ( key ) => {
					return (
						<CheckboxControl
							id={ `post_status_${ key }` }
							key={ key }
							checked={
								featureSettings.post_statuses?.[ key ] === key
							}
							label={ postStatuses?.[ key ] }
							onChange={ ( value ) => {
								setFeatureSettings( {
									post_statuses: {
										...featureSettings.post_statuses,
										[ key ]: value ? key : '0',
									},
								} );
							} }
							__nextHasNoMarginBottom
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
				className="settings-allowed-post-types"
			>
				{ Object.keys( postTypes || {} ).map( ( key ) => {
					return (
						<CheckboxControl
							id={ key }
							key={ key }
							checked={
								featureSettings.post_types?.[ key ] === key
							}
							label={ postTypes?.[ key ] }
							onChange={ ( value ) => {
								setFeatureSettings( {
									post_types: {
										...featureSettings.post_types,
										[ key ]: value ? key : '0',
									},
								} );
							} }
							__nextHasNoMarginBottom
						/>
					);
				} ) }
			</SettingsRow>
		</>
	);
};

/**
 * Component for the Classification Previewer.
 *
 * @return {React.ReactElement} The Previewer component.
 */
function Previewer() {
	const { isPreviewerOpen, setIsPreviewerOpen } = useContext(
		PreviewerProviderContext
	);

	return (
		<div
			className={ `classifai__classification-previewer ${
				isPreviewerOpen
					? 'classifai__classification-previewer--open'
					: ''
			}` }
		>
			<PostSelector
				placeholder={ __(
					'Search a different post to preview…',
					'classifai'
				) }
			/>
			<PreviewInProcess />
			<PreviewerResults />
			<Button
				className="classifai__classification-previewer-close-button"
				onClick={ () => setIsPreviewerOpen( ! isPreviewerOpen ) }
				variant="link"
			>
				{ __( 'Close previewer', 'classifai' ) }
			</Button>
		</div>
	);
}

/**
 * Component for displaying a spinner when the preview is in process.
 *
 * @return {React.ReactElement} The PreviewInProcess component.
 */
function PreviewInProcess() {
	const { isPreviewUnderProcess } = useContext( PreviewerProviderContext );

	if ( ! isPreviewUnderProcess ) {
		return null;
	}

	return (
		<div className="classifai__classification-previewer-processing">
			<Spinner
				style={ {
					width: '48px',
					height: '48px',
				} }
			/>
		</div>
	);
}

/**
 * Component for selecting a post to preview.
 *
 * @param {Object}  props             The component props.
 * @param {string}  props.placeholder The placeholder text for the search control.
 * @param {boolean} props.showLabel   Whether to show the label for the search control.
 *
 * @return {React.ReactElement} The PostSelector component.
 */
function PostSelector( { placeholder = '', showLabel = true } ) {
	const { setSelectedPostId } = useContext( PreviewerProviderContext );
	const [ searchText, setSearchText ] = useState( '' );
	const [ searchResults, setSearchResults ] = useState( [] );
	const [ shouldSearch, setShouldSearch ] = useState( true );
	const debouncedSearch = useDebounce( setSearchText, 1000 );

	function selectPost( post ) {
		setShouldSearch( false );
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
			const response = await wp.apiRequest( {
				path: '/wp/v2/posts',
				data: {
					search: searchText,
				},
			} );

			if ( Array.isArray( response ) ) {
				setSearchResults(
					response.map( ( post ) => ( {
						id: post.id,
						title: post.title.rendered,
					} ) )
				);
			}
		} )();
	}, [ searchText, shouldSearch ] );

	const searchResultsHtml = searchResults.length
		? searchResults.map( ( post ) => (
				<div
					key={ post.id }
					onClick={ () => selectPost( post ) }
					onKeyDown={ ( event ) => {
						if ( event.key === 'Enter' ) {
							selectPost( post );
						}
					} }
					className="classifai__classification-previewer-search-item"
					tabIndex={ 0 }
					role="button"
				>
					<TextHighlight
						text={ post.title }
						highlight={ searchText }
					/>
				</div>
		  ) )
		: [];

	return (
		<div className="classifai__classification-previewer-search-control">
			<div className="classifai__classification-previewer-search-and-results">
				<SearchControl
					__nextHasNoMarginBottom
					hideLabelFromVision={ showLabel }
					value={ searchText }
					label={ __( 'Previewer:' ) }
					placeholder={
						placeholder ||
						__( 'Search a post by title…', 'classifai' )
					}
					onChange={ ( text ) => {
						setShouldSearch( true );
						debouncedSearch( text );
					} }
					onClose={ () => {
						setSearchText( '' );
						setSelectedPostId( 0 );
						setSearchResults( [] );
						setShouldSearch( true );
					} }
				/>
				{ searchResults.length ? (
					<div className="classifai__classification-previewer-search-results">
						{ searchResultsHtml }
					</div>
				) : null }
			</div>
		</div>
	);
}

/**
 * Component for displaying the results of the previewer.
 *
 * @return {React.ReactElement} The PreviewerResults component.
 */
function PreviewerResults() {
	const { selectedPostId } = useContext( PreviewerProviderContext );
	const activeProvider = useSelect(
		( select ) => select( STORE_NAME ).getFeatureSettings().provider
	);

	if ( ! selectedPostId ) {
		return null;
	}

	if ( ! activeProvider ) {
		return null;
	}

	return (
		<div className="classifai__classification-previewer-search-result-container">
			{ ( 'azure_openai_embeddings' === activeProvider ||
				'openai_embeddings' === activeProvider ) && (
				<AzureOpenAIEmbeddingsResults postId={ selectedPostId } />
			) }
			{ 'ibm_watson_nlu' === activeProvider && (
				<IBMWatsonNLUResults postId={ selectedPostId } />
			) }
		</div>
	);
}

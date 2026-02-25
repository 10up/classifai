import domReady from '@wordpress/dom-ready';
import apiFetch from '@wordpress/api-fetch';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	createRoot,
	createElement,
	useEffect,
	useState,
	useCallback,
} from '@wordpress/element';

const BUTTON_ID = 'openai_usage_tracking_force_refresh_data';

const OpenaiUsageForceRefreshData = () => {
	const [ saving, setSaving ] = useState( false );
	const { invalidateResolution } = useDispatch( 'core' );
	const isForceRefreshScheduled = useSelect( ( select ) => {
		const site = select( 'core' ).getEntityRecord( 'root', 'site' );
		return site?.classifai_openai_usage_force_refresh || false;
	} );

	// Refresh site data periodically while a background force refresh is scheduled.
	// Once the force refresh is completed, the site data updates are picked up by existing
	// useSelect hooks, automatically updating the UI.
	useEffect( () => {
		if ( ! isForceRefreshScheduled ) {
			return;
		}

		let isRefreshing = false;

		const intervalId = setInterval( () => {
			if ( isRefreshing ) {
				return;
			}

			isRefreshing = true;

			try {
				invalidateResolution( 'getEntityRecord', [ 'root', 'site' ] );
			} catch ( e ) {
				// Silently handle refresh errors.
			}

			isRefreshing = false;
		}, 5000 );

		return () => clearInterval( intervalId );
	}, [ isForceRefreshScheduled, invalidateResolution ] );

	const forceRefreshData = useCallback( async () => {
		setSaving( true );
		try {
			await apiFetch( {
				path: '/classifai/v1/openai-usage/force-refresh',
				method: 'POST',
			} );
		} catch ( err ) {
			console.error( err );
		} finally {
			invalidateResolution( 'getEntityRecord', [ 'root', 'site' ] );
			setSaving( false );
		}
	}, [ invalidateResolution ] );

	// Attach click listener to the HTML-rendered button; cleanup on unmount.
	useEffect( () => {
		const button = document.getElementById( BUTTON_ID );
		if ( ! button ) {
			return;
		}
		button.addEventListener( 'click', forceRefreshData );
		return () => button.removeEventListener( 'click', forceRefreshData );
	}, [ forceRefreshData ] );

	// Keep button disabled and busy state in sync with saving / background refresh.
	useEffect( () => {
		const button = document.getElementById( BUTTON_ID );
		if ( ! button ) {
			return;
		}
		const isBusy = saving || isForceRefreshScheduled;
		button.disabled = isBusy;
		if ( isBusy ) {
			button.classList.add( 'is-busy' );
		} else {
			button.classList.remove( 'is-busy' );
		}
	}, [ saving, isForceRefreshScheduled ] );

	return null;
};

domReady( () => {
	const container = document.createElement( 'div' );
	container.id = 'openai-usage-force-refresh-root';
	document.body.appendChild( container );

	const root = createRoot( container );
	root.render( createElement( OpenaiUsageForceRefreshData ) );
} );

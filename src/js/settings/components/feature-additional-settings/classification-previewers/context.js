/**
 * WordPress dependencies
 */
import { createContext } from '@wordpress/element';

/**
 * Context for the PreviewerProvider.
 *
 * @return {React.Context} PreviewerProviderContext context.
 */
export const PreviewerProviderContext = createContext( {
	isPreviewerOpen: false,
} );

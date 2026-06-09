/**
 * Type declarations for WordPress modules to solve TypeScript errors
 *
 * These modules are provided by WordPress core at runtime and are listed
 * in .eslintrc.json under settings.import/core-modules. This file ensures
 * TypeScript recognizes them as valid imports.
 */

// Declare modules that don't have TypeScript definitions
declare module '@wordpress/api-fetch' {
	const apiFetch: any;
	export default apiFetch;
}

declare module '@wordpress/block-editor' {
	export const store: any;
	export const BlockEditorProvider: any;
	export const InspectorControls: any;
	export const useBlockProps: any;
}

declare module '@wordpress/block-library' {
	// Block library exports
	export const registerCoreBlocks: any;
}

declare module '@wordpress/blocks' {
	export const pasteHandler: any;
	export const parse: any;
	export const registerBlockType: any;
}

declare module '@wordpress/commands' {
	export const useCommand: any;
	export const useCommandLoader: any;
}

declare module '@wordpress/components' {
	export const Button: React.ComponentType< any >;
	export const Icon: React.ComponentType< any >;
	export const Panel: React.ComponentType< any >;
	export const PanelBody: React.ComponentType< any >;
	export const TextControl: React.ComponentType< any >;
	export const TextareaControl: React.ComponentType< any >;
	export const SelectControl: React.ComponentType< any >;
	export const ToggleControl: React.ComponentType< any >;
	export const Spinner: React.ComponentType< any >;
	export const Notice: React.ComponentType< any >;
	export const Modal: React.ComponentType< any >;
	export const Popover: React.ComponentType< any >;
	export const Tooltip: React.ComponentType< any >;
	export const Dashicon: React.ComponentType< any >;
	export const SVG: React.ComponentType< any >;
	export const Path: React.ComponentType< any >;
	// Add other commonly used components as needed
	export const __experimentalHStack: React.ComponentType< any >;
	export const __experimentalVStack: React.ComponentType< any >;
	[ key: string ]: any; // Allow any other exports
}

declare module '@wordpress/compose' {
	export const useCopyToClipboard: (
		text: string,
		onSuccess?: () => void
	) => React.Ref< HTMLElement >;
	export const useDebounce: any;
	export const usePrevious: any;
	export const useViewportMatch: any;
	[ key: string ]: any;
}

declare module '@wordpress/core-data' {
	export const store: any;
}

declare module '@wordpress/data' {
	export const select: any;
	export const dispatch: any;
	export const subscribe: any;
	export const useSelect: any;
	export const useDispatch: any;
	export const withSelect: any;
	export const withDispatch: any;
}

declare module '@wordpress/dom-ready' {
	const domReady: ( callback: () => void ) => void;
	export default domReady;
}

declare module '@wordpress/dom' {
	export const remove: any;
	export const insertAfter: any;
	export const replace: any;
}

declare module '@wordpress/editor' {
	export const store: any;
	export const EditorProvider: any;
}

declare module '@wordpress/element' {
	import * as React from 'react';
	export = React;
	export as namespace React;
}

declare module '@wordpress/hooks' {
	export const addAction: any;
	export const addFilter: any;
	export const removeAction: any;
	export const removeFilter: any;
	export const applyFilters: any;
	export const doAction: any;
}

declare module '@wordpress/html-entities' {
	export const decodeEntities: ( text: string ) => string;
}

declare module '@wordpress/i18n' {
	export const __: ( text: string, domain?: string ) => string;
	export const _x: ( text: string, context: string, domain?: string ) => string;
	export const _n: (
		single: string,
		plural: string,
		number: number,
		domain?: string
	) => string;
	export const sprintf: ( format: string, ...args: any[] ) => string;
}

declare module '@wordpress/icons' {
	export const search: any;
	export const update: any;
	export const paragraph: any;
	export const grid: any;
	export const table: any;
	export const formatListBullets: any;
	export const keyboardReturn: any;
	export const backup: any;
	export const check: any;
	export const copySmall: any;
	export const close: any;
	export const plus: any;
	export const trash: any;
	[ key: string ]: any; // Allow any other icon exports
}

declare module '@wordpress/media-utils' {
	export const uploadMedia: any;
	export const getMedia: any;
}

declare module '@wordpress/notices' {
	export const store: any;
}

declare module '@wordpress/plugins' {
	export const registerPlugin: any;
	export const unregisterPlugin: any;
	export const getPlugin: any;
}

declare module '@wordpress/url' {
	export const addQueryArgs: ( url: string, args: Record< string, any > ) => string;
	export const getQueryArgs: ( url: string ) => Record< string, any >;
	export const hasQueryArg: ( url: string, arg: string ) => boolean;
	export const removeQueryArgs: ( url: string, ...args: string[] ) => string;
	export const buildQueryString: ( data: Record< string, any > ) => string;
}

declare module '@wordpress/wordcount' {
	export const count: ( text: string, type?: string ) => number;
}

declare module 'motion/react' {
	export const motion: any;
	export const AnimatePresence: any;
}

/**
 * Type declarations for WordPress modules to solve TypeScript errors
 */

// Declare modules that don't have TypeScript definitions
declare module '@wordpress/blocks' {
  export const pasteHandler: any;
  export const parse: any;
}

declare module '@wordpress/editor' {
  export const store: any;
}

declare module '@wordpress/block-editor' {
  export const store: any;
}

declare module '@wordpress/api-fetch' {
  const apiFetch: any;
  export default apiFetch;
}

declare module '@wordpress/data' {
  export const select: any;
  export const dispatch: any;
  export const subscribe: any;
}

declare module '@wordpress/i18n' {
  export const __: (text: string, domain: string) => string;
}

declare module '@wordpress/html-entities' {
  export const decodeEntities: (text: string) => string;
}

declare module 'motion/react' {
  export const motion: any;
  export const AnimatePresence: any;
}

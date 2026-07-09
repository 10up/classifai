/**
 * Convert an AI-generated JSON "BlockTree" into WordPress block markup.
 *
 * The LLM returns a constrained, flat JSON structure (the BlockTree format
 * from `@10up/block-renderer-core`) rather than hand-authoring fragile
 * `<!-- wp:… -->` markup. We render it to valid markup here using the
 * editor's native `@wordpress/blocks` (`createBlock` + `serialize`), so no
 * Node-only renderer needs to be bundled.
 *
 * Callers must ensure the relevant block types are registered before
 * rendering (the block editor registers core blocks automatically; other
 * contexts such as the dashboard must call `registerCoreBlocks()` first).
 */

/**
 * WordPress dependencies
 */
import { createBlock, serialize } from '@wordpress/blocks';

/**
 * External dependencies
 */
import {
	blockTreeSchema,
	FRAGMENT_BLOCK_TYPE,
} from '@10up/block-renderer-core';

type BlockTree = ReturnType< typeof blockTreeSchema.parse >;

/** A WordPress block instance, as produced by `createBlock`. */
type BlockInstance = ReturnType< typeof createBlock >;

/**
 * Recursively build block instances for a single element key.
 *
 * A `fragment` element is virtual: its children are promoted to the current
 * level rather than wrapped in a real block. Visited keys are tracked to
 * guard against malformed trees that reference each other in a cycle.
 *
 * @param {string}      key  Element key to resolve.
 * @param {BlockTree}   tree The full block tree.
 * @param {Set<string>} seen Keys already visited on this branch.
 * @return {BlockInstance[]} Resolved block instances.
 */
function elementToBlocks(
	key: string,
	tree: BlockTree,
	seen: Set< string >
): BlockInstance[] {
	if ( seen.has( key ) ) {
		return [];
	}
	seen.add( key );

	const element = tree.elements[ key ];
	if ( ! element ) {
		return [];
	}

	const childKeys = element.children ?? [];
	const innerBlocks = childKeys.flatMap( ( childKey ) =>
		elementToBlocks( childKey, tree, seen )
	);

	// A fragment is a virtual wrapper: surface its children directly.
	if ( element.type === FRAGMENT_BLOCK_TYPE ) {
		return innerBlocks;
	}

	return [
		createBlock(
			element.type,
			( element.props ?? {} ) as Record< string, unknown >,
			innerBlocks
		),
	];
}

/**
 * Render a JSON BlockTree string to WordPress block markup.
 *
 * Returns `null` when the input is not a valid BlockTree (invalid JSON, fails
 * schema validation, or produces no blocks) so callers can fall back to
 * treating the response as raw HTML.
 *
 * @param {string} json The raw JSON string returned by the provider.
 * @return {string|null} Serialized block markup, or `null` on failure.
 */
export function renderBlockTreeToMarkup( json: string ): string | null {
	let data: unknown;
	try {
		data = JSON.parse( json );
	} catch {
		return null;
	}

	const result = blockTreeSchema.safeParse( data );
	if ( ! result.success ) {
		return null;
	}

	try {
		const blocks = elementToBlocks(
			result.data.root,
			result.data,
			new Set()
		);
		if ( ! blocks.length ) {
			return null;
		}
		return serialize( blocks );
	} catch {
		return null;
	}
}

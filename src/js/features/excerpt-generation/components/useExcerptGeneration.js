/**
 * Shared hook for excerpt generation logic.
 */

/**
 * WordPress dependencies
 */
import { dispatch, useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { browserAITextGeneration } from '../../../helpers';

const NOTICE_ID = 'classifai_excerpt_generation_error';

/**
 * Generates an excerpt for the given post.
 *
 * @param {number} postId  The ID of the post to generate an excerpt for.
 * @param {string} content The content of the post to generate an excerpt for.
 * @param {string} title   The title of the post to generate an excerpt for.
 * @param {Object} author  The author object of the post to generate an excerpt for.
 * @return {Promise<string>} A promise that resolves to the generated excerpt.
 */
async function generateExcerpt( postId, content, title, author ) {
	// Get author display name.
	let authorName = '';
	if ( author && author.name ) {
		authorName = author.name;
	}

	// Prepare the payload.
	const payload = {
		id: postId,
		content,
		title,
	};

	// Only include author in payload if we have it, otherwise let server fetch it.
	if ( authorName ) {
		payload.author = authorName;
	}

	return apiFetch( {
		path: '/classifai/v1/generate-excerpt/',
		method: 'POST',
		data: payload,
	} ).then(
		async ( res ) => {
			// Support calling a function from the response for browser AI.
			if ( typeof res === 'object' ) {
				if ( res.hasOwnProperty( 'func' ) ) {
					res = await browserAITextGeneration(
						res.func,
						res?.prompt,
						res?.content
					);
				} else {
					res = '';
				}
			}

			return res.trim();
		},
		( err ) => {
			throw new Error( err?.message );
		}
	);
}

/**
 * Hook for excerpt generation functionality.
 *
 * @return {Object} An object with generation state and handler.
 */
export function useExcerptGeneration() {
	const { postId, content, excerpt, title, authorId } = useSelect(
		( select ) => {
			return {
				postId: select( editorStore ).getCurrentPostId(),
				content: select( editorStore ).getEditedPostContent(),
				excerpt:
					select( editorStore ).getEditedPostAttribute( 'excerpt' ),
				title: select( editorStore ).getEditedPostAttribute( 'title' ),
				authorId:
					select( editorStore ).getEditedPostAttribute( 'author' ),
			};
		}
	);
	const author = useSelect( ( select ) =>
		select( 'core' ).getUser( authorId )
	);
	const { editPost } = useDispatch( editorStore );
	const [ isGenerating, setIsGenerating ] = useState( false );

	const handleGenerate = async () => {
		setIsGenerating( true );
		dispatch( noticesStore ).removeNotice( NOTICE_ID );

		try {
			const generatedExcerpt = await generateExcerpt(
				postId,
				content,
				title,
				author
			);

			// Update the editor store first.
			editPost( {
				excerpt: generatedExcerpt,
			} );

			// Find the textarea element and update it.
			const excerptInput = document.querySelector(
				'.editor-post-excerpt .editor-post-excerpt__textarea textarea'
			);

			if ( excerptInput ) {
				const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
					window.HTMLTextAreaElement.prototype,
					'value'
				)?.set;

				if ( nativeInputValueSetter ) {
					nativeInputValueSetter.call(
						excerptInput,
						generatedExcerpt
					);
				} else {
					excerptInput.value = generatedExcerpt;
				}

				excerptInput.focus();

				const changeEvent = new Event( 'change', {
					bubbles: true,
					cancelable: true,
				} );
				excerptInput.dispatchEvent( changeEvent );
			}
		} catch ( error ) {
			const message =
				typeof error === 'string'
					? error
					: error?.message ??
					  __( 'Failed to generate excerpt.', 'classifai' );
			dispatch( noticesStore ).createErrorNotice( message, {
				id: NOTICE_ID,
				isDismissible: true,
			} );
		} finally {
			setIsGenerating( false );
		}
	};

	return {
		isGenerating,
		hasExcerpt: excerpt && excerpt.trim().length > 0,
		handleGenerate,
	};
}

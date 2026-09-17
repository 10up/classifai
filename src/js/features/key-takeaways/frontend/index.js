/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Wires up the on-demand "Key Takeaways" buttons rendered on the front-end.
 *
 * Each button toggles a disclosure panel. The first time it is activated and
 * no takeaways exist yet, it requests generation from the REST endpoint, injects
 * the returned markup, then reveals the panel. Subsequent activations simply
 * toggle the (already populated) panel.
 */
document
	.querySelectorAll( '.classifai-key-takeaways-toggle' )
	.forEach( ( toggleEl ) => {
		const panelEl = document.getElementById(
			toggleEl.getAttribute( 'aria-controls' )
		);

		if ( ! panelEl ) {
			return;
		}

		let isGenerating = false;

		/**
		 * Shows or hides the takeaways panel and syncs ARIA state.
		 *
		 * @param {boolean} expanded Whether the panel should be expanded.
		 */
		function setExpanded( expanded ) {
			toggleEl.setAttribute(
				'aria-expanded',
				expanded ? 'true' : 'false'
			);
			panelEl.hidden = ! expanded;
		}

		/**
		 * Generates takeaways on demand, then reveals them.
		 */
		async function generateAndShow() {
			if ( isGenerating ) {
				return;
			}

			isGenerating = true;
			toggleEl.classList.remove( 'has-error' );
			toggleEl.classList.add( 'is-generating' );

			const labelEl = toggleEl.querySelector(
				'.classifai-key-takeaways-toggle__label'
			);
			const originalLabel = labelEl ? labelEl.textContent : '';

			if ( labelEl && toggleEl.dataset.generatingLabel ) {
				labelEl.textContent = toggleEl.dataset.generatingLabel;
			}

			try {
				// Send the REST (`wp_rest`) nonce so logged-in users' requests
				// authenticate; without it WordPress rejects the cookie with a
				// 403 before our permission callback runs.
				const response = await fetch( toggleEl.dataset.restUrl, {
					method: 'POST',
					headers: { 'X-WP-Nonce': toggleEl.dataset.nonce },
				} );
				const data = await response.json();

				if ( data && data.success && data.html ) {
					panelEl.innerHTML = data.html;
					toggleEl.dataset.hasTakeaways = '1';

					if ( labelEl ) {
						labelEl.textContent = originalLabel;
					}

					setExpanded( true );
				} else {
					throw new Error(
						data && data.message
							? data.message
							: 'generation_failed'
					);
				}
			} catch {
				toggleEl.classList.add( 'has-error' );

				if ( labelEl ) {
					labelEl.textContent =
						toggleEl.dataset.errorLabel || originalLabel;
				}
			} finally {
				isGenerating = false;
				toggleEl.classList.remove( 'is-generating' );
			}
		}

		/**
		 * Handles activation of the toggle.
		 */
		function handleActivate() {
			if ( isGenerating ) {
				return;
			}

			// Generate on demand the first time, otherwise just toggle the panel.
			if (
				'1' !== toggleEl.dataset.hasTakeaways &&
				toggleEl.dataset.restUrl
			) {
				generateAndShow();
			} else {
				setExpanded(
					'true' !== toggleEl.getAttribute( 'aria-expanded' )
				);
			}
		}

		toggleEl.addEventListener( 'click', handleActivate );
	} );

/* globals classifaiGenerateExcerpt */
const classifaiGenerateExcerptButtonID = 'classifai-generate-excerpt';
const classifaiGenerateExcerptButtonElement = document.getElementById(
	classifaiGenerateExcerptButtonID
);
const classifaiGenerateExcerptTextareaID = 'excerpt';
const classifaiGenerateExcerptTextareaElement = document.getElementById(
	classifaiGenerateExcerptTextareaID
);
let classifarGenerateExcerptDebug = false;
let classifaiGenerateExcerptText;

// Note: classifarGenerateExcerptDebug is set by the SCRIPT_DEBUG constant
// Errors are only logged to console when SCRIPT_DEBUG is defined with value: TRUE
if ( classifaiGenerateExcerpt && classifaiGenerateExcerpt.scriptDebug ) {
	classifarGenerateExcerptDebug = classifaiGenerateExcerpt.scriptDebug;
}

/* Generate excerpt when the button is clicked */
if ( null !== classifaiGenerateExcerptButtonElement ) {
	classifaiGenerateExcerptButtonElement.onclick = function () {
		if ( classifarGenerateExcerptDebug ) {
			// eslint-disable-next-line no-console
			console.log(
				'Classifai Generate Excerpt Debug: Button clicked, excerpt is being generated..'
			);
		}

		classifaiExcerptGenerate();
	};
}

/* Generate excerpt from API endpoint */
function classifaiExcerptGenerate() {
	// Confirm the endpoint URL is available; excerpt generation cannot function without this.
	if (
		! classifaiGenerateExcerpt ||
		false === classifaiGenerateExcerpt.endpointUrl
	) {
		if ( classifarGenerateExcerptDebug ) {
			// eslint-disable-next-line no-console
			console.log(
				'Classifai Generate Excerpt Debug: Endpoint URL not set!'
			);
		}

		return;
	}

	classifaiGenerateExcerptButtonElement.disabled = true;

	fetch( classifaiGenerateExcerpt.endpointUrl, {
		headers: {
			'X-WP-Nonce': classifaiGenerateExcerpt.nonce,
		},
	} )
		.then( ( response ) => response.json() )
		.then( ( result ) => {
			classifaiGenerateExcerptText = result;

			if ( classifarGenerateExcerptDebug ) {
				// eslint-disable-next-line no-console
				console.log(
					'Classifai Generate Excerpt Debug: Generated Text',
					classifaiGenerateExcerptText
				);
			}

			if ( classifaiGenerateExcerptText ) {
				classifaiGenerateExcerptTextareaElement.textContent =
					classifaiGenerateExcerptText;
				classifaiGenerateExcerptButtonElement.value =
					classifaiGenerateExcerpt.regenerateExcerptText;
			}
		} )
		.finally( () => {
			classifaiGenerateExcerptButtonElement.disabled = false;
		} );
}

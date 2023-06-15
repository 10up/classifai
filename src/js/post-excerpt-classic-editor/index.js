
/**
 * This is the main webpack entry point for compiling STAT Comments plugin JS.
 */

console.log('Classifai Generate Excerpt Debug: Script Loaded');

/* Variables */
const classifaiGenerateExcerptButtonID = 'classifai-generate-excerpt';
const classifaiGenerateExcerptButtonElement = document.getElementById(classifaiGenerateExcerptButtonID);
const classifaiGenerateExcerptTextareaID = 'excerpt';
const classifaiGenerateExcerptTextareaElement = document.getElementById(classifaiGenerateExcerptTextareaID);
let classifarGenerateExcerptDebug = false;
let classifaiGenerateExcerptText;

if (classifai_generate_excerpt && classifai_generate_excerpt.script_debug) {
	classifarGenerateExcerptDebug = classifai_generate_excerpt.script_debug;
}

/* Generate excerpt when the button is clicked */
if (null !== classifaiGenerateExcerptButtonElement) {

	classifaiGenerateExcerptButtonElement.onclick = function () {
		classifarGenerateExcerptDebug && console.log('Classifai Generate Excerpt Debug: Button clicked, excerpt is being generated..');
		classifaiExcerptGenerate();
	};
}

/* Generate excerpt from API endpoint */
function classifaiExcerptGenerate() {

	// Confirm the endpoint URL is available; excerpt generation cannot function without this.
	if (!classifai_generate_excerpt || false === classifai_generate_excerpt.endpoint_url) {
		classifarGenerateExcerptDebug && console.log('Classifai Generate Excerpt Debug: Endpoint URL not set!');
		return;
	}

	classifaiGenerateExcerptButtonElement.disabled = true;

	fetch(classifai_generate_excerpt.endpoint_url, {
		headers: {
			'X-WP-Nonce': classifai_generate_excerpt.nonce
		}
	})
		.then((response) => response.json())
		.then((result) => {
			classifaiGenerateExcerptText = result;

			classifarGenerateExcerptDebug && console.log('Classifai Generate Excerpt Debug: Generated Text', classifaiGenerateExcerptText)

			if (classifaiGenerateExcerptText) {
				classifaiGenerateExcerptTextareaElement.textContent = classifaiGenerateExcerptText
				classifaiGenerateExcerptButtonElement.value = classifai_generate_excerpt.regenerate_excerpt_text;
			}
		})
		.catch((error) => {
		})
		.finally(() => {
			classifaiGenerateExcerptButtonElement.disabled = false
		});

}

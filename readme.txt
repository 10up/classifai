=== ClassifAI ===
Contributors:      10up, jeffpaul, dkotter
Tags:              AI, Artificial Intelligence, ML, Machine Learning, Microsoft Azure, IBM Watson, OpenAI, ChatGPT, Content Tagging, Classification, Smart Cropping, Alt Text
Requires at least: 6.8
Tested up to:      6.9
Requires PHP:      7.4
Stable tag:        3.8.0
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Supercharge WordPress Content Workflows and Engagement with Artificial Intelligence.

== Description ==

Tap into leading cloud-based services like [OpenAI](https://openai.com/), [Microsoft Azure AI](https://azure.microsoft.com/en-us/overview/ai-platform/), [Google Gemini](https://ai.google.dev/) and [IBM Watson](https://www.ibm.com/watson) to augment your WordPress-powered websites.  Publish content faster while improving SEO performance and increasing audience engagement.  ClassifAI integrates Artificial Intelligence and Machine Learning technologies to lighten your workload and eliminate tedious tasks, giving you more time to create original content that matters.

*You can learn more about ClassifAI's features at [ClassifAIPlugin.com](https://classifaiplugin.com/) and documentation at the [ClassifAI documentation site](https://10up.github.io/classifai/).*

== Features ==

* Generate a summary of post content and store it as an excerpt using [OpenAI's ChatGPT API](https://platform.openai.com/docs/guides/chat), [Microsoft Azure's OpenAI service](https://azure.microsoft.com/en-us/products/ai-services/openai-service), [Google's Gemini API](https://ai.google.dev/docs/gemini_api_overview), [xAI's Grok](https://x.ai/) or locally using [Ollama](https://ollama.com/)
* Generate key takeaways from post content and render at the top of a post using [OpenAI's ChatGPT API](https://platform.openai.com/docs/guides/chat), [Microsoft Azure's OpenAI service](https://azure.microsoft.com/en-us/products/ai-services/openai-service) or locally using [Ollama](https://ollama.com/)
* Generate titles from post content using [OpenAI's ChatGPT API](https://platform.openai.com/docs/guides/chat), [Microsoft Azure's OpenAI service](https://azure.microsoft.com/en-us/products/ai-services/openai-service), [Google's Gemini API](https://ai.google.dev/docs/gemini_api_overview), [xAI's Grok](https://x.ai/) or locally using [Ollama](https://ollama.com/)
* Expand or condense text content using [OpenAI's ChatGPT API](https://platform.openai.com/docs/guides/chat), [Microsoft Azure's OpenAI service](https://azure.microsoft.com/en-us/products/ai-services/openai-service), [Google's Gemini API](https://ai.google.dev/docs/gemini_api_overview), [xAI's Grok](https://x.ai/) or locally using [Ollama](https://ollama.com/)
* Draft a full length article using [OpenAI's ChatGPT API](https://platform.openai.com/docs/guides/chat), [Microsoft Azure's OpenAI service](https://azure.microsoft.com/en-us/products/ai-services/openai-service) or locally using [Ollama](https://ollama.com/)
* Generate new images on demand to use in-content or as a featured image using [OpenAI's Image Generation API](https://platform.openai.com/docs/guides/images-vision), [Google AI's Imagen API](https://ai.google.dev/gemini-api/docs/image-generation#imagen), [Together AI's API](https://docs.together.ai/docs/images-overview) or locally using [Stable Diffusion](https://github.com/AUTOMATIC1111/stable-diffusion-webui/)
* Generate transcripts of audio files using [OpenAI's Audio Transcription API](https://platform.openai.com/docs/guides/speech-to-text) or [ElevenLabs Speech to Text API](https://elevenlabs.io/docs/capabilities/speech-to-text)
* Convert text content into audio and output a "read-to-me" feature on the front-end to play this audio using [Microsoft Azure's Text to Speech API](https://learn.microsoft.com/en-us/azure/cognitive-services/speech-service/text-to-speech), [Amazon Polly](https://aws.amazon.com/polly/) or [OpenAI's Text to Speech API](https://platform.openai.com/docs/guides/text-to-speech)
* Classify post content using [IBM Watson's Natural Language Understanding API](https://www.ibm.com/watson/services/natural-language-understanding/), [OpenAI's Embedding API](https://platform.openai.com/docs/guides/embeddings), [Microsoft Azure's OpenAI service](https://azure.microsoft.com/en-us/products/ai-services/openai-service) or locally using [Ollama](https://ollama.com/)
* Create a smart 404 page that has a recommended results section that suggests relevant content to the user based on the page URL they were trying to access using either [OpenAI's Embedding API](https://platform.openai.com/docs/guides/embeddings) or [Microsoft Azure's OpenAI service](https://azure.microsoft.com/en-us/products/ai-services/openai-service) in combination with [ElasticPress](https://github.com/10up/ElasticPress)
* Find similar terms to merge together using either [OpenAI's Embedding API](https://platform.openai.com/docs/guides/embeddings) or [Microsoft Azure's OpenAI service](https://azure.microsoft.com/en-us/products/ai-services/openai-service) in combination with [ElasticPress](https://github.com/10up/ElasticPress). Note this only compares top-level terms and if you merge a term that has children, these become top-level terms as per default WordPress behavior
* Suggest related content based on the currently viewed post using [OpenAI's Embedding API](https://platform.openai.com/docs/guides/embeddings)
* Generate image alt text using [Microsoft Azure's AI Vision API](https://azure.microsoft.com/en-us/services/cognitive-services/computer-vision/), [OpenAI's ChatGPT API](https://platform.openai.com/docs/guides/chat), [xAI's Grok](https://x.ai/) or locally using [Ollama](https://ollama.com/)
* Generate image tags and extract text from images using [Microsoft Azure's AI Vision API](https://azure.microsoft.com/en-us/services/cognitive-services/computer-vision/), [OpenAI's ChatGPT API](https://platform.openai.com/docs/guides/chat) or locally using [Ollama](https://ollama.com/)
* Smartly crop images using [Microsoft Azure's AI Vision API](https://azure.microsoft.com/en-us/services/cognitive-services/computer-vision/)
* Scan PDF files for embedded text and save for use in post meta using [Microsoft Azure's AI Vision API](https://azure.microsoft.com/en-us/services/cognitive-services/computer-vision/)
* Bulk classify content with [WP-CLI](https://wp-cli.org/)
* Modification of your `robots.txt` file to block the most common AI data scraping bots from indexing your site

== Requirements ==

* PHP 7.4+
* [WordPress](http://wordpress.org) 6.8+
* Each individual Feature may have its own requirements, please refer to the [ClassifAI documentation site](https://10up.github.io/classifai/get-started/#requirements) for detailed requirements.

== Frequently Asked Questions ==

For a full list of frequently asked questions, please refer to the [ClassifAI documentation site](https://10up.github.io/classifai/get-started/faq).

= Where do I report security bugs found in this plugin? =

Please report security bugs found in the source code of the undefined plugin through the [Patchstack Vulnerability Disclosure  Program](https://patchstack.com/database/vdp/f298c330-8d56-4af5-8a69-736281841ce1).  The Patchstack team will assist you with verification, CVE assignment, and notify the developers of this plugin.

== Upgrade Notice ==

= x.x.x =
**Note that the legacy settings screen (enabled via the `classifai_use_legacy_settings_panel` filter) is now formally deprecated and scheduled for removal in a future release. Please migrate to the new React-based settings experience.**

= 3.3.0 =
**Note that this release bumps the WordPress minimum from 6.5 to 6.6.**

= 3.2.0 =
**Note that this release of ClassifAI rearchitects how the settings pages are built, from a standard PHP approach to using React components. If you've created custom Features or Providers or added your own custom settings, you'll need to update your code to work in this new structure. See our [documentation](https://10up.github.io/classifai/advanced-docs/useful-snippets) for examples.**

**Also note that this release bumps the WordPress minimum from 6.1 to 6.5.**

= 3.0.0 =
**Note that this is a major release of ClassifAI that restructures most of the codebase and will have some breaking changes. If you're extending ClassifAI in any way, please ensure you fully test those integrations prior to running this update on production. For more details on what is changing, see the [migration guide](https://10up.github.io/classifai/advanced-docs/migration-guide-v2-to-v3).**

= 2.5.0 =
**Note that this release bumps the WordPress minimum from 5.8 to 6.1.**

= 2.3.0 =
**Note that this release bumps the WordPress minimum from 5.7 to 5.8.**

= 2.1.0 =
**Note that this release moves the ClassifAI settings to be nested under Tools instead of it's own menu.**

= 1.8.1 =
**Note that this release bumps the WordPress minimum from 5.6 to 5.7 and the PHP minimum from 7.2 to 7.4.**

= 1.8.0 =
**Note that this release bumps the PHP minimum from 7.0 to 7.2.**

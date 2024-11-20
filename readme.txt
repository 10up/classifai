=== ClassifAI ===
Contributors:      10up, jeffpaul, dkotter
Tags:              AI, Artificial Intelligence, ML, Machine Learning, Microsoft Azure, IBM Watson, OpenAI, ChatGPT, DALL·E, Content Tagging, Classification, Smart Cropping, Alt Text
Requires at least: 6.5
Tested up to:      6.7
Requires PHP:      7.4
Stable tag:        3.1.1
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Supercharge WordPress Content Workflows and Engagement with Artificial Intelligence.

== Description ==

Tap into leading cloud-based services like [OpenAI](https://openai.com/), [Microsoft Azure AI](https://azure.microsoft.com/en-us/overview/ai-platform/), [Google Gemini](https://ai.google.dev/) and [IBM Watson](https://www.ibm.com/watson) to augment your WordPress-powered websites.  Publish content faster while improving SEO performance and increasing audience engagement.  ClassifAI integrates Artificial Intelligence and Machine Learning technologies to lighten your workload and eliminate tedious tasks, giving you more time to create original content that matters.

*You can learn more about ClassifAI's features at [ClassifAIPlugin.com](https://classifaiplugin.com/) and documentation at the [ClassifAI documentation site](https://10up.github.io/classifai/).*

**Features**

* Generate a summary of post content and store it as an excerpt using [OpenAI's ChatGPT API](https://platform.openai.com/docs/guides/chat), [Microsoft Azure's OpenAI service](https://azure.microsoft.com/en-us/products/ai-services/openai-service) or [Google's Gemini API](https://ai.google.dev/docs/gemini_api_overview)
* Generate titles from post content using [OpenAI's ChatGPT API](https://platform.openai.com/docs/guides/chat), [Microsoft Azure's OpenAI service](https://azure.microsoft.com/en-us/products/ai-services/openai-service) or [Google's Gemini API](https://ai.google.dev/docs/gemini_api_overview)
* Expand or condense text content using [OpenAI's ChatGPT API](https://platform.openai.com/docs/guides/chat), [Microsoft Azure's OpenAI service](https://azure.microsoft.com/en-us/products/ai-services/openai-service) or [Google's Gemini API](https://ai.google.dev/docs/gemini_api_overview)
* Generate new images on demand to use in-content or as a featured image using [OpenAI's DALL·E 3 API](https://platform.openai.com/docs/guides/images)
* Generate transcripts of audio files using [OpenAI's Whisper API](https://platform.openai.com/docs/guides/speech-to-text)
* Convert text content into audio and output a "read-to-me" feature on the front-end to play this audio using [Microsoft Azure's Text to Speech API](https://learn.microsoft.com/en-us/azure/cognitive-services/speech-service/text-to-speech), [Amazon Polly](https://aws.amazon.com/polly/) or [OpenAI's Text to Speech API](https://platform.openai.com/docs/guides/text-to-speech)
* Classify post content using [IBM Watson's Natural Language Understanding API](https://www.ibm.com/watson/services/natural-language-understanding/), [OpenAI's Embedding API](https://platform.openai.com/docs/guides/embeddings) or [Microsoft Azure's OpenAI service](https://azure.microsoft.com/en-us/products/ai-services/openai-service)
* Create a smart 404 page that has a recommended results section that suggests relevant content to the user based on the page URL they were trying to access using either [OpenAI's Embedding API](https://platform.openai.com/docs/guides/embeddings) or [Microsoft Azure's OpenAI service](https://azure.microsoft.com/en-us/products/ai-services/openai-service) in combination with [ElasticPress](https://github.com/10up/ElasticPress)
* BETA: Recommend content based on overall site traffic via [Microsoft Azure's AI Personalizer API](https://azure.microsoft.com/en-us/services/cognitive-services/personalizer/) _(note that this service has been deprecated by Microsoft and as such, will no longer work. We are looking to replace this with a new provider to maintain the same functionality)_
* Generate image alt text, image tags, and smartly crop images using [Microsoft Azure's AI Vision API](https://azure.microsoft.com/en-us/services/cognitive-services/computer-vision/)
* Scan images and PDF files for embedded text and save for use in post meta using [Microsoft Azure's AI Vision API](https://azure.microsoft.com/en-us/services/cognitive-services/computer-vision/)
* Bulk classify content with [WP-CLI](https://wp-cli.org/)

**Requirements**

* To utilize the NLU Language Processing functionality, you will need an active [IBM Watson](https://cloud.ibm.com/registration) account.
* To utilize the ChatGPT, Embeddings, Text to Speech or Whisper Language Processing functionality or DALL·E Image Processing functionality, you will need an active [OpenAI](https://platform.openai.com/signup) account.
* To utilize the Azure AI Vision Image Processing functionality or Text to Speech Language Processing functionality, you will need an active [Microsoft Azure](https://signup.azure.com/signup) account.
* To utilize the Azure OpenAI Language Processing functionality, you will need an active [Microsoft Azure](https://signup.azure.com/signup) account and you will need to [apply](https://customervoice.microsoft.com/Pages/ResponsePage.aspx?id=v4j5cvGGr0GRqy180BHbR7en2Ais5pxKtso_Pz4b1_xUNTZBNzRKNlVQSFhZMU9aV09EVzYxWFdORCQlQCN0PWcu) for OpenAI access.
* To utilize the Google Gemini Language Processing functionality, you will need an active [Google Gemini](https://ai.google.dev/tutorials/setup) account.
* To utilize the AWS Language Processing functionality, you will need an active [AWS](https://console.aws.amazon.com/) account.
* To utilize the Smart 404 feature, you will need to use [ElasticPress](https://github.com/10up/ElasticPress) 5.0.0+ and [Elasticsearch](https://www.elastic.co/elasticsearch) 7.0+.

== Upgrade Notice ==

= 3.0.0 =
**Note that this is a major release of ClassifAI that restructures most of the codebase and will have some breaking changes. If you're extending ClassifAI in any way, please ensure you fully test those integrations prior to running this update on production. For more details on what is changing, see the [migration guide](https://10up.github.io/classifai/tutorial-migration-guide-v2-to-v3.html).**

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

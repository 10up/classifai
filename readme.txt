=== ClassifAI ===
Contributors:      10up, jeffpaul, dkotter
Tags:              AI, Artifical Intelligence, ML, Machine Learning, Microsoft Azure, IBM Watson, OpenAI, ChatGPT, DALL·E, Content Tagging, Classification, Smart Cropping, Alt Text
Requires at least: 5.7
Tested up to:      6.2
Requires PHP:      7.4
Stable tag:        2.2.2
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Supercharge WordPress Content Workflows and Engagement with Artificial Intelligence.

== Description ==

Tap into leading cloud-based services like [OpenAI](https://openai.com/), [Microsoft Azure AI](https://azure.microsoft.com/en-us/overview/ai-platform/), and [IBM Watson](https://www.ibm.com/watson) to augment your WordPress-powered websites.  Publish content faster while improving SEO performance and increasing audience engagement.  ClassifAI integrates Artificial Intelligence and Machine Learning technologies to lighten your workload and eliminate tedious tasks, giving you more time to create original content that matters.

*You can learn more about ClassifAI's features at [ClassifAIPlugin.com](https://classifaiplugin.com/) and documentation at the [ClassifAI documentation site](https://10up.github.io/classifai/).*

**Features**

* Generate a summary of post content and store it as an excerpt using [OpenAI's ChatGPT API](https://platform.openai.com/docs/guides/chat)
* Generate titles from post content using [OpenAI's ChatGPT API](https://platform.openai.com/docs/guides/chat)
* Generate new images on demand to use in-content or as a featured image using [OpenAI's DALL·E API](https://platform.openai.com/docs/guides/images)
* Generate transcripts of audio files using [OpenAI's Whisper API](https://platform.openai.com/docs/guides/speech-to-text)
* Convert text content into audio and output a "read-to-me" feature on the front-end to play this audio using [Microsoft Azure's Text to Speech API](https://learn.microsoft.com/en-us/azure/cognitive-services/speech-service/text-to-speech)
* Classify post content using [IBM Watson's Natural Language Understanding API](https://www.ibm.com/watson/services/natural-language-understanding/) and [OpenAI's Embedding API](https://platform.openai.com/docs/guides/embeddings)
* BETA: Recommend content based on overall site traffic via [Microsoft Azure's Personalizer API](https://azure.microsoft.com/en-us/services/cognitive-services/personalizer/) _(note that we're gathering feedback on this feature and may significantly iterate depending on community input)_
* Generate image alt text, image tags, and smartly crop images using [Microsoft Azure's Computer Vision API](https://azure.microsoft.com/en-us/services/cognitive-services/computer-vision/)
* Scan images and PDF files for embedded text and save for use in post meta using [Microsoft Azure's Computer Vision API](https://azure.microsoft.com/en-us/services/cognitive-services/computer-vision/)
* Bulk classify content with [WP-CLI](https://wp-cli.org/)

**Requirements**

* To utilize the NLU Language Processing functionality, you will need an active [IBM Watson](https://cloud.ibm.com/registration) account.
* To utilize the ChatGPT, Embeddings, or Whisper Language Processing functionality or DALL·E Image Processing functionality, you will need an active [OpenAI](https://platform.openai.com/signup) account.
* To utilize the Computer Vision Image Processing functionality or Text to Speech Language Processing functionality, you will need an active [Microsoft Azure](https://signup.azure.com/signup) account.

== Upgrade Notice ==

= 2.1.0 =
**Note that this release moves the ClassifAI settings to be nested under Tools instead of it's own menu.**

= 1.8.1 =
**Note that this release bumps the WordPress minimum from 5.6 to 5.7 and the PHP minimum from 7.2 to 7.4.**

= 1.8.0 =
**Note that this release bumps the PHP minimum from 7.0 to 7.2.**

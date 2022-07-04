The following WP-CLI commands are supported by ClassifAI:

### Language Processing Commands

* `wp classifai post <post_ids> [--post_type=<post_type>] [--limit=<limit>] [--link=<link>]`

  Batch post classification using IBM Watson NLU API.

  * `<post_ids>`: A comma delimited list of post IDs to classify. Used if post_type is false or absent.

    default: `true`

  * `[--post_type=<post_type>]`: Batch classify posts belonging to this post type. If false or absent relies on post_ids.

    default: `false`

    options:

    - any post type name
    - `false`, if args contains post_ids

  * `[--limit=<limit>]`: Limit classification to N posts.

    default: `false`

    options:

    - `false`, no limit
    - N, max number of posts to classify

  * `[--link=<link>]`: Whether to link classification results to Taxonomy terms.

    default: `true`

* `wp classifai text <text> [--category=<bool>] [--keyword=<bool>] [--concept=<bool>] [--entity=<bool>] [--input=<input>] [--only-normalize=<bool>]`

  Directly classify text using IBM Watson NLU API.

  * `<text>`: A string of text to classify.

  * `[--category=<bool>]`: Enables NLU category feature.

    default: `true`

  * `[--keyword=<bool>]`: Enables NLU keyword feature.

    default: `true`

  * `[--concept=<bool>]`: Enables NLU concept feature.

    default: `true`

  * `[--entity=<bool>]`: Enables NLU entity feature.

    default: `true`

  * `[--input=<input>]`: Path to input file or URL.

    default: `false`

    options:

    - path to local file
    - path to remote URL
    - `false`, uses `args[0]` instead

  * `[--only-normalize=<bool>]`: Prints the normalized text that will be sent to the NLU API.

    default: `false`

### Image Processing Commands

* `wp classifai image <attachment_ids> [--limit=<int>] [--skip=<skip>] [--force]`

  Directly add description "alt text" and tags to attachment(s) using Azure AI Computer Vision API.

  * `<attachment_ids>`: Comma delimited list of Attachment IDs to classify.

  * `[--limit=<int>]`: Limit number of attachments to classify.

    default: `100`

  * `[--skip=<skip>]`: Skip first N attachments.

    default: `false`

  * `[--force]`: Force classifying attachments regardless of their alt.

    default: `false`

* `wp classifai crop <attachment_ids> [--limit=<limit>] [--skip=<skip>]`

  Batch crop image(s) using Azure AI Computer Vision API.

  * `<attachment_ids>`: Comma delimited list of Attachment IDs to crop.

  * `[--limit=<int>]`: Limit number of attachments to crop.

	default: `100`

  * `[--skip=<skip>]`: Skip first N attachments.

	default: `false`

### ClassifAI Settings Commands

* `wp classifai auth`

  Prints the Basic Auth header based on credentials configured in the plugin.

* `wp classifai reset`

  Restores the plugin configuration to factory defaults. Any API credentials will need to be re-entered after this is ran.

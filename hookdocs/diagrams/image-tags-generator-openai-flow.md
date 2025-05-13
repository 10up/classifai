# ClassifAI Image Tags Generator Data Flow (with OpenAI)

This diagram outlines the sequence of events when a user generates tags for an image using ClassifAI's Image Tags Generator feature, with OpenAI (ChatGPT with Vision) as the configured AI provider. This flow can be initiated manually from the Media Modal or attachment edit screen, or automatically upon image upload.

```mermaid
sequenceDiagram
    actor User
    participant WPAdmin as WordPress Admin UI (Media Modal/Editor)
    participant ClassifAI_JS as ClassifAI Admin JS
    participant WP_REST_API as WordPress REST API\n(/wp-json/classifai/v1/image-tags/{attachment_id})
    participant ImageTagsGen_PHP as ClassifAI ImageTagsGenerator Class\n(includes/Classifai/Features/ImageTagsGenerator.php)
    participant ChatGPT_Provider_PHP as ClassifAI OpenAI ChatGPT Provider Class\n(includes/Classifai/Providers/OpenAI/ChatGPT.php)
    participant WP_DB as WordPress Database
    participant OpenAI_API as OpenAI GPT API (Vision-capable)

    %% Manual Flow initiated by User
    User->>WPAdmin: Clicks "Generate image tags" button for an image
    WPAdmin->>ClassifAI_JS: Triggers tag generation function with attachment ID
    ClassifAI_JS->>WP_REST_API: GET /wp-json/classifai/v1/image-tags/{attachment_id}
    Note right of ClassifAI_JS: Sends Attachment ID.

    WP_REST_API->>ImageTagsGen_PHP: Routes request to rest_endpoint_callback()
    ImageTagsGen_PHP->>ImageTagsGen_PHP: Performs image_tags_generator_permissions_check()
    Note over ImageTagsGen_PHP, WP_DB: Verifies user can edit attachment, feature is enabled, and user has permissions for the target taxonomy.

    ImageTagsGen_PHP->>WP_DB: Retrieves image URL (e.g., using wp_get_attachment_url(attachment_id))
    WP_DB-->>ImageTagsGen_PHP: Returns image URL

    ImageTagsGen_PHP->>WP_DB: SELECT option_value FROM wp_options WHERE option_name = 'classifai_feature_image_tags_generator'
    WP_DB-->>ImageTagsGen_PHP: Returns feature settings (prompt, API key, provider config, tag_taxonomy)
    Note right of ImageTagsGen_PHP: Retrieves configured prompt, OpenAI API key, and target taxonomy.

    ImageTagsGen_PHP->>ChatGPT_Provider_PHP: Calls a method like generate_tags_for_image(image_url, prompt, api_key)
    Note left of ChatGPT_Provider_PHP: Passes image URL and specific prompt from ImageTagsGen_PHP settings.
    ChatGPT_Provider_PHP->>OpenAI_API: POST /v1/chat/completions\nBody: { model: "gpt-4o-mini (default)", messages: [{role:"system", content:"image_tags_prompt"}, {role:"user", content:[{type:"image_url", image_url:{url:"image_url"}}]}] }
    Note right of ChatGPT_Provider_PHP: Sends image URL and system prompt to OpenAI.

    OpenAI_API-->>ChatGPT_Provider_PHP: HTTPS Response\nBody: { choices: [{message:{content:"- Tag1\n- Tag2\n- Tag3"}}] }
    Note over ChatGPT_Provider_PHP: Parses the response to extract a list of tags (e.g., ["Tag1", "Tag2", "Tag3"]).
    ChatGPT_Provider_PHP-->>ImageTagsGen_PHP: Returns array ["Tag1", "Tag2", "Tag3"]

    ImageTagsGen_PHP->>ImageTagsGen_PHP: Calls save(["Tag1", "Tag2", "Tag3"], attachment_id)
    Note over ImageTagsGen_PHP, WP_DB: Saves tags to the configured taxonomy ('tag_taxonomy' setting). Uses wp_add_object_terms() and wp_update_term_count_now().\nInteracts with wp_terms, wp_term_taxonomy, wp_term_relationships.
    ImageTagsGen_PHP-->>WP_REST_API: Returns generated tags or success status
    WP_REST_API-->>ClassifAI_JS: JSON Response: { data: ["Tag1", "Tag2", "Tag3"] }
    ClassifAI_JS->>WPAdmin: Displays/updates tags in the UI.

    Note over User, WPAdmin: User may need to save the media item for changes to fully persist in some views.
```

## Automatic Generation on Upload

Image tags can also be generated automatically when an image is uploaded:
1.  User uploads an image.
2.  WordPress core triggers the `wp_generate_attachment_metadata` hook.
3.  `ImageTagsGenerator::generate_image_tags()` is called.
4.  This method internally calls its `run()` method (which then calls the `ChatGPT_Provider_PHP` similar to the manual flow) to get the tags.
5.  The tags are then saved using the `save()` method to the configured taxonomy.

## Layers Involved

*   **WordPress Application Layer**:
    *   `User`: The end-user interacting with the WordPress Media Library or editor.
    *   `WordPress Admin UI (Media Modal/Editor)`: The interface for managing media.
    *   `ClassifAI Admin JS`: JavaScript handling client-side interaction for tag generation.
    *   `WordPress REST API`: The `/wp-json/` interface, including ClassifAI's custom endpoint.
    *   `ClassifAI ImageTagsGenerator Class (ImageTagsGen_PHP)`: The PHP class (`ImageTagsGenerator.php`) containing the server-side logic for this feature.
    *   `ClassifAI OpenAI ChatGPT Provider Class (ChatGPT_Provider_PHP)`: The PHP class (`ChatGPT.php`) responsible for communicating with the OpenAI API.
*   **Database Layer**:
    *   `WordPress Database (WP_DB)`:
        *   `wp_posts`: Stores attachment details (post_type 'attachment').
        *   `wp_postmeta`: May store other metadata related to attachments.
        *   `wp_options`: Stores ClassifAI plugin settings, including the image tags generation prompt, OpenAI API key, and the target taxonomy (e.g., under `classifai_feature_image_tags_generator` option).
        *   `wp_terms`: Stores the individual tag names.
        *   `wp_term_taxonomy`: Links terms to taxonomies (e.g., 'classifai-image-tags' or a user-defined taxonomy), stores term descriptions and counts.
        *   `wp_term_relationships`: Associates attachments (object_id) with term_taxonomy_ids.
*   **API Layer**:
    *   `WordPress REST API` (Internal): Endpoint `/wp-json/classifai/v1/image-tags/{attachment_id}`.
    *   `OpenAI GPT API` (External): The AI service endpoint (e.g., GPT-4 Vision).
*   **AI Provider**:
    *   `OpenAI GPT API`: The specific AI model service (e.g., a vision-capable GPT model) used for analyzing the image and generating relevant tags based on the provided prompt.

## Data Flow Summary

1.  **User Action (Manual)**: The user initiates image tag generation for an image via the WordPress Admin UI (e.g., Media Modal by clicking "Generate image tags").
2.  **Client-Side Request**: JavaScript makes a GET request to the ClassifAI REST API endpoint `/wp-json/classifai/v1/image-tags/{attachment_id}`, passing the attachment ID.
3.  **Server-Side Processing (ClassifAI - ImageTagsGenerator)**:
    *   The `ImageTagsGenerator.php` class handles the request.
    *   It performs permission checks (user capability, feature enabled, taxonomy permissions).
    *   It retrieves the image URL using `wp_get_attachment_url()`.
    *   It fetches the configured prompt, OpenAI API key, and target taxonomy from `wp_options` (via `classifai_feature_image_tags_generator` setting).
4.  **AI Provider Request (ClassifAI - ChatGPT_Provider_PHP)**:
    *   The `ChatGPT.php` provider class receives the image URL and the specific prompt for generating tags.
    *   It sends the image URL and the prompt to the OpenAI GPT API (a vision-capable model like GPT-4 Vision).
5.  **AI Provider Response**: OpenAI processes the request and returns a list of suggested tags, often in a string format (e.g., hyphenated list).
6.  **Server-Side Response & Save (ClassifAI - ImageTagsGenerator)**:
    *   The provider class parses the OpenAI response into an array of tag strings.
    *   The `ImageTagsGenerator.php` class receives this array.
    *   The `save()` method is called, which uses `wp_add_object_terms()` to assign the tags to the attachment in the configured taxonomy. This updates `wp_terms`, `wp_term_taxonomy`, and `wp_term_relationships`. `wp_update_term_count_now()` is also called.
    *   The ClassifAI REST endpoint sends the generated tags (or a success message) back to the client.
7.  **Client-Side Display**: JavaScript displays the generated tags in the Media Modal or editor UI.
8.  **Automatic Flow**: Alternatively, on image upload, the `wp_generate_attachment_metadata` hook triggers a similar server-side flow: `ImageTagsGenerator::generate_image_tags()` calls `run()`, which engages the OpenAI provider to get tags, and then `save()` stores them.

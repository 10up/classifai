# ClassifAI Descriptive Text Generator Data Flow (with OpenAI)

This diagram outlines the sequence of events when a user generates descriptive text (alt text, caption, or description) for an image using ClassifAI's Descriptive Text Generator feature, with OpenAI as the configured AI provider. This flow typically initiates from the Media Modal or the attachment edit screen.

```mermaid
sequenceDiagram
    actor User
    participant WPAdmin as WordPress Admin UI (Media Modal/Editor)
    participant ClassifAI_JS as ClassifAI Admin JS
    participant WP_REST_API as WordPress REST API <br>(/wp-json/classifai/v1/alt-tags/{attachment_id})
    participant DescTextGen_PHP as ClassifAI DescriptiveTextGenerator Class<br>(includes/Classifai/Features/DescriptiveTextGenerator.php)
    participant ChatGPT_Provider_PHP as ClassifAI OpenAI ChatGPT Provider Class<br>(includes/Classifai/Providers/OpenAI/ChatGPT.php)
    participant WP_DB as WordPress Database
    participant OpenAI_API as OpenAI GPT API (Vision-capable)

    User->>WPAdmin: Clicks "Generate descriptive text" button for an image
    WPAdmin->>ClassifAI_JS: Triggers text generation function with attachment ID
    ClassifAI_JS->>WP_REST_API: GET /wp-json/classifai/v1/alt-tags/{attachment_id}
    Note right of ClassifAI_JS: Sends Attachment ID.

    WP_REST_API->>DescTextGen_PHP: Routes request to rest_endpoint_callback()
    DescTextGen_PHP->>DescTextGen_PHP: Performs descriptive_text_generator_permissions_check()
    Note over DescTextGen_PHP, WP_DB: Verifies user can edit attachment and feature is enabled.

    DescTextGen_PHP->>WP_DB: Retrieves image URL (e.g., using wp_get_attachment_url(attachment_id))
    WP_DB-->>DescTextGen_PHP: Returns image URL

    DescTextGen_PHP->>WP_DB: SELECT option_value FROM wp_options WHERE option_name = 'classifai_feature_descriptive_text_generator'
    WP_DB-->>DescTextGen_PHP: Returns feature settings (prompt, API key, provider config)
    Note right of DescTextGen_PHP: Retrieves configured prompt and OpenAI API key.

    DescTextGen_PHP->>ChatGPT_Provider_PHP: Calls run_openai_image_description(image_url, prompt, api_key)
    Note left of ChatGPT_Provider_PHP: Passes image URL and prompt from DescTextGen_PHP settings.
    ChatGPT_Provider_PHP->>OpenAI_API: POST /v1/chat/completions <br>Body: { model: "gpt-4o-mini (default)", messages: [{role:"system", content:"descriptive_text_prompt"}, {role:"user", content:[{type:"image_url", image_url:{url:"image_url"}}]}] }
    Note right of ChatGPT_Provider_PHP: Sends image URL and system prompt to OpenAI.

    OpenAI_API-->>ChatGPT_Provider_PHP: HTTPS Response <br>Body: { choices: [{message:{content:"Generated Description"}}] }
    ChatGPT_Provider_PHP-->>DescTextGen_PHP: Returns "Generated Description"

    DescTextGen_PHP->>DescTextGen_PHP: Calls save("Generated Description", attachment_id)
    DescTextGen_PHP->>WP_DB: UPDATE wp_postmeta SET meta_value = "Generated Description" WHERE post_id = {attachment_id} AND meta_key = '_wp_attachment_image_alt' (if alt text enabled)
    DescTextGen_PHP->>WP_DB: UPDATE wp_posts SET post_excerpt = "Generated Description" WHERE ID = {attachment_id} (if caption enabled)
    DescTextGen_PHP->>WP_DB: UPDATE wp_posts SET post_content = "Generated Description" WHERE ID = {attachment_id} (if description enabled)
    WP_DB-->>DescTextGen_PHP: Save confirmations

    DescTextGen_PHP-->>WP_REST_API: Returns "Generated Description" or success status
    WP_REST_API-->>ClassifAI_JS: JSON Response: { data: "Generated Description" }
    ClassifAI_JS->>WPAdmin: Displays/updates descriptive text in the UI (e.g., Alt Text field in Media Modal)

    Note over User, WPAdmin: User may need to save the post/media item for changes to persist if not updated directly.
```

## Automatic Generation on Upload

It's worth noting that descriptive text can also be generated automatically when an image is uploaded.
1.  User uploads an image.
2.  WordPress core triggers the `wp_generate_attachment_metadata` hook.
3.  `DescriptiveTextGenerator::generate_image_alt_tags()` is called.
4.  This method internally calls the provider (similar to the manual flow above) to get the description.
5.  The description is then saved to the configured fields (alt, caption, description) in the `wp_postmeta` and `wp_posts` tables.

## Layers Involved

*   **WordPress Application Layer**:
    *   `User`: The end-user interacting with the WordPress Media Library or editor.
    *   `WordPress Admin UI (Media Modal/Editor)`: The interface for managing media.
    *   `ClassifAI Admin JS`: JavaScript handling client-side interaction for text generation.
    *   `WordPress REST API`: The `/wp-json/` interface, including ClassifAI's custom endpoint.
    *   `ClassifAI DescriptiveTextGenerator Class (DescTextGen_PHP)`: The PHP class (`DescriptiveTextGenerator.php`) containing the server-side logic for this feature.
    *   `ClassifAI OpenAI ChatGPT Provider Class (ChatGPT_Provider_PHP)`: The PHP class responsible for communicating with the OpenAI API.
*   **Database Layer**:
    *   `WordPress Database (WP_DB)`:
        *   `wp_posts`: Stores image captions (in `post_excerpt`) and descriptions (in `post_content`).
        *   `wp_postmeta`: Stores image alt text (in `_wp_attachment_image_alt`).
        *   `wp_options`: Stores ClassifAI plugin settings, including the descriptive text generation prompt and OpenAI API key (e.g., under `classifai_feature_descriptive_text_generator` option).
*   **API Layer**:
    *   `WordPress REST API` (Internal): Endpoint `/wp-json/classifai/v1/alt-tags/{attachment_id}`.
    *   `OpenAI GPT API` (External): The AI service endpoint (e.g., GPT-4 Vision).
*   **AI Provider**:
    *   `OpenAI GPT API`: The specific AI model service used for generating image descriptions.

## Data Flow Summary

1.  **User Action**: The user initiates descriptive text generation for an image via the WordPress Admin UI (e.g., Media Modal).
2.  **Client-Side Request**: JavaScript makes a GET request to a ClassifAI REST API endpoint, passing the attachment ID.
3.  **Server-Side Processing (ClassifAI - DescriptiveTextGenerator)**:
    *   The `DescriptiveTextGenerator.php` class handles the request.
    *   It performs permission checks.
    *   It retrieves the image URL.
    *   It fetches the configured prompt (default: "You are an assistant that generates descriptions of images...") and OpenAI API key from `wp_options`.
4.  **AI Provider Request (ClassifAI - ChatGPT_Provider_PHP)**:
    *   The `ChatGPT.php` provider class receives the image URL and prompt.
    *   It sends the image URL and the prompt to the OpenAI GPT API (a vision-capable model).
5.  **AI Provider Response**: OpenAI processes the request and returns a generated description.
6.  **Server-Side Response & Save (ClassifAI - DescriptiveTextGenerator)**:
    *   The generated description is received.
    *   The `save()` method updates the relevant WordPress fields (`_wp_attachment_image_alt` in `wp_postmeta`, `post_excerpt`, or `post_content` in `wp_posts`) based on plugin settings.
    *   The ClassifAI REST endpoint sends the generated description back to the client.
7.  **Client-Side Display**: JavaScript displays the generated text in the appropriate field in the Media Modal or editor UI.
8.  **Automatic Flow**: Alternatively, on image upload, the `wp_generate_attachment_metadata` hook triggers a similar server-side flow to automatically generate and save descriptive text.

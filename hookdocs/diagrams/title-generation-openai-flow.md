# ClassifAI Title Generation Data Flow (with OpenAI)

This diagram outlines the sequence of events when a user generates a title for a post using ClassifAI's Title Generation feature, with OpenAI as the configured AI provider.

```mermaid
sequenceDiagram
    actor User
    participant WPAdmin as WordPress Admin UI (Editor)
    participant ClassifAI_JS as ClassifAI Admin JS
    participant WP_REST_API as WordPress REST API <br>(/wp-json/classifai/v1/generate-title)
    participant TitleGeneration_PHP as ClassifAI TitleGeneration Class<br>(includes/Classifai/Features/TitleGeneration.php)
    participant WP_DB as WordPress Database
    participant OpenAI_API as OpenAI ChatGPT API

    User->>WPAdmin: Clicks "Generate titles" button for a post
    WPAdmin->>ClassifAI_JS: Triggers title generation function
    ClassifAI_JS->>WP_REST_API: GET /wp-json/classifai/v1/generate-title/{post_id}?n={num_titles}
    Note right of ClassifAI_JS: Sends Post ID and desired number of titles.

    WP_REST_API->>TitleGeneration_PHP: Routes request to rest_endpoint_callback()
    TitleGeneration_PHP->>TitleGeneration_PHP: Performs generate_title_permissions_check()
    Note over TitleGeneration_PHP, WP_DB: Verifies user can edit post_id.

    TitleGeneration_PHP->>WP_DB: SELECT post_content FROM wp_posts WHERE ID = {post_id}
    WP_DB-->>TitleGeneration_PHP: Returns post content

    TitleGeneration_PHP->>WP_DB: SELECT option_value FROM wp_options WHERE option_name = 'classifai_feature_title_generation'
    WP_DB-->>TitleGeneration_PHP: Returns feature settings (prompt, API key, provider config)
    Note right of TitleGeneration_PHP: Retrieves configured prompt and OpenAI API key.

    TitleGeneration_PHP->>OpenAI_API: POST /v1/chat/completions <br>Body: { model: "gpt-...", messages: [{role:"system", content:"configured_prompt"},{role:"user", content:"post_content"}], n: num_titles }
    Note right of TitleGeneration_PHP: Sends post content and prompt to OpenAI.

    OpenAI_API-->>TitleGeneration_PHP: HTTPS Response <br>Body: { choices: [{message:{content:"Generated Title 1"}}, ...] }
    TitleGeneration_PHP-->>WP_REST_API: Returns array of generated titles

    WP_REST_API-->>ClassifAI_JS: JSON Response: { titles: ["Generated Title 1", ...] }
    ClassifAI_JS->>WPAdmin: Displays titles in a modal or selection UI

    User->>WPAdmin: Selects one of the generated titles
    WPAdmin->>ClassifAI_JS: User confirms selection
    ClassifAI_JS->>WPAdmin: Updates the post title field in the editor UI (client-side)

    User->>WPAdmin: Clicks "Update" or "Publish" to save the post
    WPAdmin->>WP_REST_API: Standard WordPress POST /wp-json/wp/v2/posts/{post_id} <br> (or classic editor form post)
    Note right of WPAdmin: Request includes the new post_title.

    WP_REST_API->>WP_DB: UPDATE wp_posts SET post_title = "Selected Title" WHERE ID = {post_id}
    WP_DB-->>WP_REST_API: Save confirmation
    WP_REST_API-->>WPAdmin: Save confirmation
```

## Layers Involved

*   **WordPress Application Layer**:
    *   `User`: The end-user interacting with the WordPress editor.
    *   `WordPress Admin UI (Editor)`: The Gutenberg or Classic editor interface.
    *   `ClassifAI Admin JS`: JavaScript handling the client-side interaction for title generation.
    *   `WordPress REST API`: The `/wp-json/` interface, including ClassifAI's custom endpoint.
    *   `ClassifAI TitleGeneration Class`: The PHP class (`TitleGeneration.php`) containing the server-side logic.
*   **Database Layer**:
    *   `WordPress Database`:
        *   `wp_posts`: Stores post content (`post_content`) and titles (`post_title`).
        *   `wp_options`: Stores ClassifAI plugin settings, including the title generation prompt and provider API keys (e.g., under `classifai_feature_title_generation` option).
*   **API Layer**:
    *   `WordPress REST API` (Internal): Endpoint `/wp-json/classifai/v1/generate-title/{post_id}`.
    *   `OpenAI ChatGPT API` (External): The AI service endpoint.
*   **AI Provider**:
    *   `OpenAI ChatGPT API`: The specific AI model service used for generating titles.

## Data Flow Summary

1.  **User Action**: The user initiates title generation from the WordPress editor for a specific post.
2.  **Client-Side Request**: JavaScript makes a GET request to a ClassifAI REST API endpoint, passing the post ID and the number of titles desired.
3.  **Server-Side Processing (ClassifAI)**:
    *   The `TitleGeneration.php` class handles the request.
    *   It performs permission checks.
    *   It fetches the post content from the `wp_posts` table.
    *   It retrieves the configured prompt and OpenAI API key from `wp_options`.
4.  **AI Provider Request**: ClassifAI sends the post content and the prompt to the OpenAI API.
5.  **AI Provider Response**: OpenAI processes the request and returns a set of generated titles.
6.  **Server-Side Response (ClassifAI)**: The ClassifAI REST endpoint sends the generated titles back to the client.
7.  **Client-Side Display**: JavaScript displays the suggested titles to the user in the editor.
8.  **User Selection & Save**:
    *   The user selects a title.
    *   The selected title is updated in the editor's title field.
    *   When the user saves the post, the standard WordPress save mechanism updates the `post_title` in the `wp_posts` table.

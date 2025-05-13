# ClassifAI Text-to-Speech Feature Flow (with OpenAI)

This diagram illustrates the sequence of operations when a user triggers the Text-to-Speech feature in ClassifAI using OpenAI as the provider. It shows the interaction between the WordPress application layers, the database, and the external OpenAI API.

```mermaid
sequenceDiagram
    participant User/Editor
    participant WordPress UI (Editor)
    participant WordPress REST API
    participant WP Core/Hooks
    participant FeatTextToSpeech as Classifai\\Features\\TextToSpeech
    participant DB as WordPress DB (wp_posts, wp_postmeta, wp_options)
    participant ProvOpenAITTS as Classifai\\Providers\\OpenAI\\TextToSpeech
    participant OpenAI_API as OpenAI API (api.openai.com/v1/audio/speech)

    User/Editor->>WordPress UI (Editor): Clicks "Generate Audio" for Post ID X
    WordPress UI (Editor)->>WordPress REST API: GET /classifai/v1/synthesize-speech/X
    WordPress REST API-->>WP Core/Hooks: Route request
    WP Core/Hooks->>FeatTextToSpeech: rest_endpoint_callback(Request for Post ID X)
    FeatTextToSpeech->>DB: Get Post X (title, content)
    DB-->>FeatTextToSpeech: Post X data
    FeatTextToSpeech->>FeatTextToSpeech: normalize_post_content(Post X data)
    FeatTextToSpeech->>ProvOpenAITTS: synthesize(normalized_text)
    ProvOpenAITTS->>DB: Get OpenAI API Key, Model, Voice (from wp_options)
    DB-->>ProvOpenAITTS: API Key, Model, Voice settings
    ProvOpenAITTS->>OpenAI_API: POST /v1/audio/speech <br>Body: { input: "text_to_speak", model: "tts-...", voice: "alloy|..." }
    OpenAI_API-->>ProvOpenAITTS: Audio stream response
    ProvOpenAITTS-->>FeatTextToSpeech: Returns audio stream
    FeatTextToSpeech->>FeatTextToSpeech: save(audio_stream, Post ID X)
    FeatTextToSpeech->>DB: wp_upload_bits (save .mp3)
    DB-->>FeatTextToSpeech: File data
    FeatTextToSpeech->>DB: wp_insert_attachment (create attachment post)
    DB-->>FeatTextToSpeech: Attachment ID Y
    FeatTextToSpeech->>DB: update_post_meta for Post X (_classifai_post_audio_id = Y, _classifai_post_audio_timestamp)
    DB-->>FeatTextToSpeech: Meta update success
    FeatTextToSpeech-->>WordPress REST API: JSON Response { success: true, audio_id: Y }
    WordPress REST API-->>WordPress UI (Editor): JSON Response
    WordPress UI (Editor)-->>User/Editor: Display audio player/success message
```

**Key Database Interactions:**

*   **`wp_posts`**: Stores the original post content and details for the generated audio attachment.
*   **`wp_postmeta`**:
    *   For the original post: Stores metadata like `_classifai_post_audio_id` (ID of the audio attachment), `_classifai_post_audio_timestamp`, `_classifai_display_generated_audio` (boolean to control frontend display), `_classifai_post_audio_hash`, and `_classifai_text_to_speech_error` (if any error occurs).
    *   For the attachment: Standard WordPress attachment metadata.
*   **`wp_options`**: Stores ClassifAI plugin settings, including feature enablement, selected provider (OpenAI), OpenAI API key, and selected voice/model for Text-to-Speech.

**WordPress REST API Endpoint:**

*   **Endpoint:** `GET /classifai/v1/synthesize-speech/{post_id}`
*   **Purpose:** Triggers the speech synthesis process for a given post.
*   **Handler:** `Classifai\Features\TextToSpeech::rest_endpoint_callback()`
*   **Response:** JSON indicating success or failure, including the `audio_id` if successful.

**OpenAI API Endpoint:**

*   **Endpoint:** `POST https://api.openai.com/v1/audio/speech`
*   **Purpose:** Converts text input to audio.
*   **Key Request Data:** `model` (e.g., `tts-1`, `tts-1-hd`), `input` (the text to synthesize), `voice` (e.g., `alloy`, `nova`).
*   **Authentication:** Via API Key in request headers.
*   **Response:** Audio stream (e.g., MP3 format).

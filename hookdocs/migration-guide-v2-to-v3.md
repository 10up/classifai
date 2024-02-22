**ClassifAI 3.0** introduces significant improvements and restructuring aimed at enhancing flexibility and extensibility. The key changes include a transition to a "**Feature first**" settings approach, where settings screens are now organized around specific Features rather than Service Providers. This allows for the integration of multiple AI providers for each feature, offering users greater customization options.

The migration process is streamlined with an **automated migration routine** that seamlessly transitions your existing settings to the new version upon upgrading to version 3.0.

This migration guide provides a detailed overview of the changes, including updates to the REST API endpoints and information regarding the removed hooks. Explore the details below to smoothly navigate and leverage the enhanced capabilities of ClassifAI 3.0.

### Provider Class Changes
In ClassifAI v2, provider classes (such as ChatGPT, ComputerVision, DaLLE, NLU, Whisper, etc.) handled various aspects related to both provider and feature functionalities. This included functionalities such as feature access control, registering feature settings fields, managing provider fields, registering REST API endpoints, connecting to provider services, and exposing in-context features.

With the introduction of the "Feature first" approach in ClassifAI 3.0, the provider class has undergone a significant transformation. It is now divided into two distinct classes: Feature and Provider. Consequently, all feature-related functionalities, including feature access control, registering feature settings fields, registering REST API endpoints, and exposing in-context features, have been moved to specific Feature classes. Provider-related tasks remain within the provider classes.

If you have extended any of the Provider classes in your codebase, it is essential to update your code accordingly to align with the changes introduced in ClassifAI 3.0.

### REST API Changes
To standardize the REST API endpoints, we have renamed some of the REST endpoints in ClassifAI 3.0. There are no changes in the request body or parameters. You can find them below.

| ClassifAI version 2.x | ClassifAI version 3.x |
| --- | --- |
| **GET** `/classifai/v1/generate-tags/{POST_ID}` | **GET** `/classifai/v1/classify/{POST_ID}` |
| **GET** `/classifai/v1/openai/generate-excerpt/{POST_ID}` | **GET** `/classifai/v1/generate-excerpt/{POST_ID}` |
| **POST** `/classifai/v1/openai/generate-excerpt/` | **POST** `/classifai/v1/generate-excerpt/` |
| **GET** `/classifai/v1/openai/generate-transcript/{POST_ID}` | **GET** `/classifai/v1/generate-transcript/{POST_ID}` |
| **GET** `/classifai/v1/openai/generate-title/{POST_ID}` | **GET** `/classifai/v1/generate-title/{POST_ID}` |
| **POST** `/classifai/v1/openai/generate-title/` | **POST** `/classifai/v1/generate-title/` |
| **POST** `/classifai/v1/openai/resize-content/` | **POST** `/classifai/v1/resize-content/` |
| **GET** `/classifai/v1/openai/generate-image/` | **GET** `/classifai/v1/generate-image/` |


### Filter hooks Changes
To standardize the action/filter names, we have removed and updated some of the filter hooks in ClassifAI 3.0. You can find them below, along with their replacements.

| ClassifAI version 2.x | Replacement in ClassifAI version 3.x |
| --- | --- |
| **`classifai_allowed_roles`** | [`classifai_{feature}_roles`](./classifai_%257Bfeature%257D_roles.html) |
| **`classifai_chatgpt_allowed_roles`** | [`classifai_{feature}_roles`](./classifai_%257Bfeature%257D_roles.html) |
| **`classifai_openai_dalle_allowed_image_roles`** | [`classifai_{feature}_roles`](./classifai_%257Bfeature%257D_roles.html) |
| **`classifai_has_access`** | [`classifai_{$feature}_has_access`](./classifai_%257B$feature%257D_has_access.html) |
| **`classifai_is_{$feature}_enabled`** | [`classifai_{$feature}_is_enabled`](./classifai_%257B$feature%257D_is_enabled.html) |
| **`classifai_openai_dalle_enable_image_gen`** | [`classifai_{$feature}_is_feature_enabled`](./classifai_%257B$feature%257D_is_feature_enabled.html) |
| **`classifai_openai_embeddings_post_statuses`** | [`classifai_{feature}_post_statuses`](./classifai_%257Bfeature%257D_post_statuses.html) |
| **`classifai_post_statuses`** | [`classifai_{feature}_post_statuses`](./classifai_%257Bfeature%257D_post_statuses.html) |
| **`classifai_post_types`** | [`classifai_{feature}_post_types`](./classifai_%257Bfeature%257D_post_types.html) |
| **`classifai_{$this->option_name}_enable_{$feature}`** | [`classifai_{$feature}_is_feature_enabled`](./classifai_%257B$feature%257D_is_feature_enabled.html) |
| **`classifai_taxonomy_for_feature`** | [`classifai_feature_classification_taxonomy_for_feature`](./classifai_feature_classification_taxonomy_for_feature.html) |
| **`classifai_openai_embeddings_taxonomies`** | _Removed in ClassifAI 3.0_ |
| **`classifai_post_statuses_for_post_type_or_id`** | _Removed in ClassifAI 3.0_ |
| **`classifai_rest_bases`** | _Removed in ClassifAI 3.0_ |
| **`classifai_should_register_save_post_handler`** | _Removed in ClassifAI 3.0_ |

Aside from these filter hooks, ClassifAI 3.0 introduces many new actions and filters, which you can find [here](./index.html)

If you encounter any issues after migration, please feel free to report them [here](https://github.com/10up/classifai/issues/new/choose)

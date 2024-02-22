**ClassifAI 3.0** introduces significant improvements and restructuring aimed at enhancing flexibility and extensibility. The key changes include a transition to a "**Feature first**" settings approach, where settings screens are now organized around specific Features rather than Service Providers. This allows for the integration of multiple AI providers for each feature, offering users greater customization options.

The migration process is streamlined with an **automated migration routine** that seamlessly transitions your existing settings to the new version upon upgrading to version 3.0.

This migration guide provides a detailed overview of the changes, including updates to the REST API endpoints and information regarding the removed hooks. Explore the details below to smoothly navigate and leverage the enhanced capabilities of ClassifAI 3.0.

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

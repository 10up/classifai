# ClassifAI

[![Build Status](https://travis-ci.com/10up/classifai-for-wordpress.svg?token=Jy6DFK4YVZbgtyNHcjm5&branch=develop)](https://travis-ci.com/10up/classifai-for-wordpress)

Enhance your WordPress content with Artificial Intelligence and Machine Learning services.

## Features

* Classify Post content using [IBM Watson's Natural Language Understanding API] \(https://www.ibm.com/watson/services/natural-language-understanding/)
* Supports Watson's [Categories](https://console.bluemix.net/docs/services/natural-language-understanding/index.html#categories), [Keywords](https://console.bluemix.net/docs/services/natural-language-understanding/index.html#keywords), [Concepts](https://console.bluemix.net/docs/services/natural-language-understanding/index.html#concepts) & [Entities](https://console.bluemix.net/docs/services/natural-language-understanding/index.html#entities)
* Bulk Classify Posts
* Automatically classify content on save

## Installation

#### 1. Download or Clone this repo, install dependencies and build.
- `git clone https://github.com/10up/classifai-for-wordpress.git && cd classifai-for-wordpress`
- `composer install && npm install && npm run build`

#### 2. Activate Plugin

#### 3. Sign up for Watson services
- Start here: https://cloud.ibm.com/registration, set up an account to begin.
- Check for an email from `IBM Cloud` and click the `Confirm Account` link.
- Log into your account (accepting the privacy policy) and create a new *"Natural Language Understanding"* Resource - https://cloud.ibm.com/catalog/services/natural-language-understanding.
- Click `Manage` in the left hand menu, then `Show credentials` on the Manage page to view the credentials for this resource.

#### 4. Configure IBM Watson API Keys under Settings > ClassifAI

**The credentials screen will show either an API key or a username/password combination.**

##### If your credentials contain an API Key, then:
- In the `API URL` field enter the URL
- In the `API User` field, enter `apikey`.
- Enter your API Key in the `API Key` field.

##### If your credentials contain a username and password, then:
- In the `API URL` field enter the URL
- Enter the `username` value into the `API User field`.
- Enter the `password` into the `API key` field.

#### 5. Configure Post Types to classify and IBM Watson Features to enable under Settings > ClassifAI
- Choose which types to classify when saved: posts, pages, and media.
- Chose whether to assign category, keyword, and entity as well as the taxonomies used for each.

#### 6. Save Post or run WP CLI command to batch classify posts

## WP CLI

#### 1. Batch Classify Posts

$ wp klasifai post {post_ids} [--post_type=post_type] [--limit=limit] [--link=link]

[--post_type=post_type]
    Batch classify posts belonging to this post type. If false
    relies on post_ids in args
    ---
    default: false
    options:
      - any other post type name
      - false, if args contains post_ids
    ---

  [--limit=limit]
    Limit classification to N posts.
    ---
    default: false
    options:
      - false, no limit
      - N, max number of posts to classify
    ---

  [--link=link]
    Whether to link classification results to Taxonomy terms
    ---
    default: true
    options:
      - bool, any bool value
    ---

#### 2. Classify Text

wp klasifai text {text} [--category=bool] [--keyword=bool] [--concept=bool] [--entity=bool] [--input=input] [--only-normalize=bool]

Directly classify text using Watson NLU.

Options

  [--category=bool]
    Enables NLU category feature
    ---
    default: true
    options:
      - any boolean value
    ---

  [--keyword=bool]
    Enables NLU keyword feature
    ---
    default: true
    options:
      - any boolean value
    ---

  [--concept=bool]
    Enables NLU concept feature
    ---
    default: true
    options:
      - any boolean value
    ---

  [--entity=bool]
    Enables NLU entity feature
    ---
    default: true
    options:
      - any boolean value
    ---

  [--input=input]
    Path to input file or URL
    ---
    default: false
    options:
      - path to local file
      - path to remote URL
      - false, uses args[0] instead
    ---

  [--only-normalize=<bool>]
    Prints the normalized text that will be sent to the NLU API
    ---
    default: false
    options:
      - any boolean value
    ---

## Contributing

Please read [CODE_OF_CONDUCT.md](https://github.com/10up/classifai-for-wordpress/blob/develop/CODE_OF_CONDUCT.md) for details on our code of conduct and [CONTRIBUTING.md](https://github.com/10up/classifai-for-wordpress/blob/develop/CONTRIBUTING.md) for details on the process for submitting pull requests to us.

## License

ClassifAI utilizes an [MIT license](https://github.com/10up/classifai-for-wordpress/blob/develop/LICENSE).

## Work with us

<a href="http://10up.com/contact/"><img src="https://10updotcom-wpengine.s3.amazonaws.com/uploads/2016/10/10up-Github-Banner.png" width="850"></a>

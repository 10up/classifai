## ClassifAI

Classify WordPress Content using [IBM Watson Natural Language Processing API](https://www.ibm.com/watson/services/natural-language-understanding/).

[![Build Status](https://travis-ci.org/10up/klasifai.svg?branch=master)](https://travis-ci.org/10up/klasifai)

## Features

* Classify Post Content using IBM Watson Natural Language Understanding API (https://www.ibm.com/watson/services/natural-language-understanding/)
* Supports Watson Categories, Keywords, Concepts & Entities
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
- Click `Manage` in the left hand menu, then `Show credentials` on the Manage page to view `API Key` and `URL` information

#### 4. Configure IBM Watson API Keys under Settings > ClassifAI

##### Credentials contain API Key
- In the `API URL` field enter the URL
- In the `API User` field, enter `apikey`.
- Enter your API Key in the `API Key` field.

##### Credentials contain username and password
- In the `API URL` field enter the URL
- Enter the username value into the `API User field`.
- Enter the password into the `API key` field.


#### 5. Configure Post Types to classify and IBM Watson Features to enable under Settings > ClassifAI
- Choose which types to classify when saved:  posts, pages and media.
- Chose whether to assign category, keyword and entity and the taxonomies used for each.

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

1. Clone the repo
2. Create Pull Request against the master branch.
3. Fix failing tests if any.

## License

ClassifAI is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 2 of the License, or (at your
option) any later version.

## Work with us

<a href="http://10up.com/contact/"><img src="https://10updotcom-wpengine.s3.amazonaws.com/uploads/2016/10/10up-Github-Banner.png" width="850"></a>

## Klasifai

Classify WordPress Content using [IBM Watson Natural Language Processing API](https://www.ibm.com/watson/services/natural-language-understanding/).

[![Build Status](https://travis-ci.org/10up/klasifai.svg?branch=master)](https://travis-ci.org/10up/klasifai)

## Dependencies

* [Fieldmanager](http://fieldmanager.org/)

## Features

* Classify Post Content using IBM Watson NLU API
* Supports Watson Categories, Keywords, Concepts & Entities
* Bulk Classify Posts
* Automatically classify content on save

## Installation

#### 1. Download or Clone this repo

#### 2. Activate Plugin ( ensure the Fieldmanager is also installed and active )

#### 3. Configure IBM Watson API Keys under Settings > Klasifai

#### 4. Configure Post Types unde Settings > Klasifai

#### 5. Save Post or run WP CLI command to batch classify posts

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

Klasifai is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 2 of the License, or (at your
option) any later version.

# ![ClassifAI](https://cldup.com/zn3_j2A-DL.png)
> Enhance your WordPress content with Artificial Intelligence and Machine Learning services.

[![Build Status](https://travis-ci.com/10up/classifai.svg?token=Jy6DFK4YVZbgtyNHcjm5&branch=develop)](https://travis-ci.com/10up/classifai) [![Release Version](https://img.shields.io/github/release/10up/classifai.svg)](https://github.com/10up/classifai/releases/latest) ![WordPress tested up to version](https://img.shields.io/badge/WordPress-v5.2%20tested-success.svg)

## Features

* Classify your content using [IBM Watson's Natural Language Understanding API](https://www.ibm.com/watson/services/natural-language-understanding/) and [Microsoft Azure's Computer Vision API](https://azure.microsoft.com/en-us/services/cognitive-services/computer-vision/)
* Supports Watson's [Categories](https://console.bluemix.net/docs/services/natural-language-understanding/index.html#categories), [Keywords](https://console.bluemix.net/docs/services/natural-language-understanding/index.html#keywords), [Concepts](https://console.bluemix.net/docs/services/natural-language-understanding/index.html#concepts) & [Entities](https://console.bluemix.net/docs/services/natural-language-understanding/index.html#entities) and Azure's [Describe Image](https://westus.dev.cognitive.microsoft.com/docs/services/5adf991815e1060e6355ad44/operations/56f91f2e778daf14a499e1fe)
* Automatically classify content and images on save
* Bulk classify content and images with [WP-CLI](https://wp-cli.org/)

## Installation

#### 1. Download or Clone this repo, install dependencies and build.
- `git clone https://github.com/10up/classifai.git && cd classifai`
- `composer install && npm install && npm run build`

#### 2. Activate Plugin

## Setup Content Tagging (via IBM Watson)

#### 1. Sign up for Watson services
- [Register for an IBM Cloud account](https://cloud.ibm.com/registration) or sign into your existing one.
- Check for an email from `IBM Cloud` and click the `Confirm Account` link.
- Log into your account (accepting the privacy policy) and create a new [*Natural Language Understanding*](https://cloud.ibm.com/catalog/services/natural-language-understanding) Resource if you do not already have one. It may take a minute for your account to fully populate with the default resource group to use.
- Click `Manage` in the left hand menu, then `Show credentials` on the Manage page to view the credentials for this resource.

#### 2. Configure IBM Watson API Keys under Settings > ClassifAI

**The credentials screen will show either an API key or a username/password combination.**

##### If your credentials contain an API Key, then:
- In the `API URL` field enter the URL
- In the `API User` field, enter `apikey`.
- Enter your API Key in the `API Key` field.

##### If your credentials contain a username and password, then:
- In the `API URL` field enter the URL
- Enter the `username` value into the `API User field`.
- Enter the `password` into the `API key` field.

#### 3. Configure Post Types to classify and IBM Watson Features to enable under Settings > ClassifAI
- Choose which public post types to classify when saved.
- Chose whether to assign category, keyword, entity, and concept as well as the taxonomies used for each.

#### 4. Save Post or run WP CLI command to batch classify posts

## Setup Image Alt Text (via Microsoft Azure)

Note that [Computer Vision](https://docs.microsoft.com/en-us/azure/cognitive-services/computer-vision/home#image-requirements) can analyze images that meet the following requirements:
- The image must be presented in JPEG, PNG, GIF, or BMP format
- The file size of the image must be less than 4 megabytes (MB)
- The dimensions of the image must be greater than 50 x 50 pixels

#### 1. Sign up for Azure services

#### 2. Configure Microsoft Azure API Keys under Settings > ClassifAI

#### 3. Configure Images to classify and Microsoft Azure Features to enable under Settings > ClassifAI

#### 4. Save Image or run WP CLI command to batch classify images

## WP CLI Usage Instructions

#### 1. Batch Classify Posts

`$ wp classifai post {post_ids} [--post_type=post_type] [--limit=limit] [--link=link]`

##### Options

`--post_type=post_type`

Batch classify posts belonging to this post type. If `false` or absent relies on `post_ids` in args

default: `false`    
options:    
- any post type name    
- `false`, if args contains `post_ids`

`--limit=limit`

Limit classification to N posts.

default: `false`    
options:    
- `false`, no limit    
- `N`, max number of posts to classify

`--link=link`

Whether to link classification results to Taxonomy terms

default: `true`

#### 2. Classify Text

`$ wp classifai text {text} [--category=bool] [--keyword=bool] [--concept=bool] [--entity=bool] [--input=input] [--only-normalize=bool]`

Directly classify text using Watson NLU.

##### Options

`--category=bool`

Enables NLU category feature

default: `true`    

`--keyword=bool`

Enables NLU keyword feature

default: `true`    

`--concept=bool`

Enables NLU concept feature

default: `true`

`--entity=bool`

Enables NLU entity feature

default: `true`

`--input=input`

Path to input file or URL

default: `false`    
options:    
- path to local file    
- path to remote URL    
- `false`, uses args[0] instead

`--only-normalize=<bool>`

Prints the normalized text that will be sent to the NLU API

default: `false`

## Contributing

Please read [CODE_OF_CONDUCT.md](https://github.com/10up/classifai/blob/develop/CODE_OF_CONDUCT.md) for details on our code of conduct and [CONTRIBUTING.md](https://github.com/10up/classifai/blob/develop/CONTRIBUTING.md) for details on the process for submitting pull requests to us.

## License

ClassifAI utilizes an [MIT license](https://github.com/10up/classifai/blob/develop/LICENSE.md).

## Like what you see?

<a href="http://10up.com/contact/"><img src="https://10updotcom-wpengine.s3.amazonaws.com/uploads/2016/10/10up-Github-Banner.png" width="850" alt="Work with us at 10up"></a>

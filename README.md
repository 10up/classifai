# ![ClassifAI](https://classifaiplugin.com/wp-content/themes/classifai-theme/assets/img/logo.svg "ClassifAI")

> Enhance your WordPress content with Artificial Intelligence and Machine Learning services.

[![Support Level](https://img.shields.io/badge/support-active-green.svg)](#support-level) [![Release Version](https://img.shields.io/github/release/10up/classifai.svg)](https://github.com/10up/classifai/releases/latest) ![WordPress tested up to version](https://img.shields.io/badge/WordPress-v6.1%20tested-success.svg) [![GPLv2 License](https://img.shields.io/github/license/10up/classifai.svg)](https://github.com/10up/classifai/blob/develop/LICENSE.md)

[![E2E Testing](https://github.com/10up/classifai/actions/workflows/cypress.yml/badge.svg)](https://github.com/10up/classifai/actions/workflows/cypress.yml) [![PHPUnit Testing](https://github.com/10up/classifai/actions/workflows/test.yml/badge.svg)](https://github.com/10up/classifai/actions/workflows/test.yml) [![Linting](https://github.com/10up/classifai/actions/workflows/lint.yml/badge.svg)](https://github.com/10up/classifai/actions/workflows/lint.yml) [![CodeQL](https://github.com/10up/classifai/actions/workflows/codeql-analysis.yml/badge.svg)](https://github.com/10up/classifai/actions/workflows/codeql-analysis.yml) [![Dependency Review](https://github.com/10up/classifai/actions/workflows/dependency-review.yml/badge.svg)](https://github.com/10up/classifai/actions/workflows/dependency-review.yml)

## Table of Contents
* [Features](#features)
* [Requirements](#requirements)
* [Pricing](#pricing)
* [Installation](#installation)
* [Register ClassifAI account](#register-classifai-account)
* [Set Up NLU Language Processing](#set-up-language-processing-via-ibm-watson)
* [Set Up ChatGPT Language Processing](#set-up-language-processing-via-openai)
* [Set Up Image Processing](#set-up-image-processing-via-microsoft-azure)
* [Set Up Recommended Content](#set-up-recommended-content-via-microsoft-azure-personalizer)
* [WP CLI Commands](#wp-cli-commands)
* [FAQs](#frequently-asked-questions)
* [Support](#support-level)
* [Changelog](#changelog)
* [Contributing](#contributing)

## Features

* Classify your content using [IBM Watson's Natural Language Understanding API](https://www.ibm.com/watson/services/natural-language-understanding/) and [Microsoft Azure's Computer Vision API](https://azure.microsoft.com/en-us/services/cognitive-services/computer-vision/)
* Automatically generate a summary of your content and store that as an excerpt using [OpenAI's ChatGPT](https://platform.openai.com/docs/guides/chat)
* Supports Watson's [Categories](https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#categories), [Keywords](https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#keywords), [Concepts](https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#concepts) & [Entities](https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#entities) and Azure's [Describe Image](https://westus.dev.cognitive.microsoft.com/docs/services/5adf991815e1060e6355ad44/operations/56f91f2e778daf14a499e1fe)
* Automatically classify content and images on save
* Automatically generate alt text and image tags for images
* Automatically scan images and PDF files for embedded text and save for use in WordPress
* [Smartly crop images](https://docs.microsoft.com/en-us/rest/api/computervision/3.1/generate-thumbnail/generate-thumbnail) around a region of interest identified by Computer Vision
* BETA: Recommend content based on overall site traffic via [Azure Personalizer](https://azure.microsoft.com/en-us/services/cognitive-services/personalizer/) (note that we're gathering feedback on this feature and may significantly iterate depending on community input)
* Bulk classify content with [WP-CLI](https://wp-cli.org/)

| Language Processing - Tagging | Recommended Content | Excerpt Generation |
| :-: | :-: | :-: |
| ![Screenshot of ClassifAI post tagging](assets/img/screenshot-1.png "Example of a Block Editor post with Watson Categories, Keywords, Concepts, and Entities.") | ![Screenshot of ClassifAI recommended content](assets/img/screenshot-2.png "Example of a Recommended Content Block with Azure Personalizer.") | ![Screenshot of ClassifAI excerpt generation](assets/img/screenshot-7.png "Example of automatic excerpt generation with OpenAI.") |

| Image Processing - Alt Text | Image Processing - Smart Cropping | Image Processing - Tagging |
| :-: | :-: | :-: |
| ![Screenshot of ClassifAI alt-text](assets/img/screenshot-3.png "Example of an image with Azure Alt Text.") | ![Screenshot of ClassifAI smart coppring](assets/img/screenshot-4.png "Example of an image with Azure Smart Focal Point Cropping.") | ![Screenshot of ClassifAI image tagging](assets/img/screenshot-5.png "Example of an image with Azure Image Tagging.") |

## Requirements

* PHP 7.4+
* [WordPress](http://wordpress.org) 5.7+
* To utilize the NLU Language Processing functionality, you will need an active [IBM Watson](https://cloud.ibm.com/registration) account.
* To utilize the ChatGPT Language Processing functionality, you will need an active [OpenAI](https://platform.openai.com/signup) account.
* To utilize the Image Processing functionality, you will need an active [Microsoft Azure](https://signup.azure.com/signup) account.

## Pricing

Note that there is no cost to using ClassifAI itself. Both IBM Watson and Microsoft Azure have free plans for their AI services, but above those free plans there are paid levels as well.  So if you expect to process a high volume of content, then you'll want to review the pricing plans for these services to understand if you'll incur any costs.  For the most part, both services' free plans are quite generous and should at least allow for testing ClassifAI to better understand its featureset and could at best allow for totally free usage. OpenAI has a limited trial option that can be used for testing but will require a valid paid plan after that.

The service that powers ClassifAI's NLU Language Processing, IBM Watson's Natural Language Understanding ("NLU"), has a ["lite" pricing tier](https://www.ibm.com/cloud/watson-natural-language-understanding/pricing) that offers 30,000 free NLU items per month.

The service that powers ClassifAI's ChatGPT Language Processing, OpenAI's ChatGPT, has a limited free trial and then requires a [pay per usage](https://openai.com/pricing) plan.

The service that powers ClassifAI's Image Processing, Microsoft Azure's Computer Vision, has a ["free" pricing tier](https://azure.microsoft.com/en-us/pricing/details/cognitive-services/computer-vision/) that offers 20 transactions per minute and 5,000 transactions per month.

The service that powers ClassifAI's Recommended Content, Microsoft Azure's Personalizer, has a ["free" pricing tier](https://azure.microsoft.com/en-us/pricing/details/cognitive-services/personalizer/) that offers 50,000 transactions per month.

## Installation

### Manual Installation

#### 1. Download or Clone this repo, install dependencies and build

- `git clone https://github.com/10up/classifai.git && cd classifai`
- `composer install && npm install && npm run build`

#### 2. Activate Plugin

### Installation via Composer

ClassifAI releases can be installed via Composer.

#### 1. Update composer.json

Instruct Composer to install ClassifAI into the plugins directory by adding or modifying the "extra" section of your project's composer.json file to match the following:

```json
"extra": {
    "installer-paths": {
        "plugins/{$name}": [
            "type:wordpress-plugin"
        ]
    }
}
```

Add this repository to composer.json, specifying a release version, as shown below:

```json
"repositories": [
    {
        "type": "package",
        "package": {
            "name": "10up/classifai",
            "version": "1.8.1",
            "type": "wordpress-plugin",
            "dist": {
                "url": "https://github.com/10up/classifai/archive/refs/tags/1.8.1.zip",
                "type": "zip"
            }
        }
    }
]
```

Finally, require the plugin, using the version number you specified in the previous step:

```json
"require": {
    "10up/classifai": "1.8.1"
}
```

After you run `composer update`, ClassifAI will be installed in the plugins directory with no build steps needed.

#### 2. Activate Plugin

## Register ClassifAI account

ClassifAI is a sophisticated solution that we want organizations of all shapes and sizes to count on. To keep adopters apprised of major updates and beta testing opportunities, gather feedback, and prioritize common use cases, we're asking for a little bit of information in exchange for a free key. Your information will be kept confidential.

#### 1. Register for a ClassifAI account

- Register for a free ClassifAI account [here](https://classifaiplugin.com/#cta).
- Check for an email from `ClassifAI Team` which contains the registration key.
- Note that the email will be sent from `opensource@10up.com`, so please whitelist this email address if needed.

#### 2. Configure ClassifAI Registration Key under ClassifAI > ClassifAI

- In the `Registered Email` field, enter the email you used for registration.
- In the `Registration Key` field, enter the registration key from the email in step 1 above.

![Screenshot of registration settings](assets/img/screenshot-6.png "Example of an empty ClassifAI Settings registration screen.")

## Set Up Language Processing (via IBM Watson)

#### 1. Sign up for Watson services

- [Register for an IBM Cloud account](https://cloud.ibm.com/registration) or sign into your existing one.
- Check for an email from `IBM Cloud` and click the `Confirm Account` link.
- Log into your account (accepting the privacy policy) and create a new [*Natural Language Understanding*](https://cloud.ibm.com/catalog/services/natural-language-understanding) Resource if you do not already have one. It may take a minute for your account to fully populate with the default resource group to use.
- Click `Manage` in the left hand menu, then `Show credentials` on the Manage page to view the credentials for this resource.

#### 2. Configure IBM Watson API Keys under ClassifAI > Language Processing > IBM Watson

**The credentials screen will show either an API key or a username/password combination.**

##### If your credentials contain an API Key, then:

- In the `API URL` field enter the URL
- Enter your API Key in the `API Key` field.

##### If your credentials contain a username and password, then:

- In the `API URL` field enter the URL
- Enter the `username` value into the `API Username`.
- Enter the `password` into the `API Key` field.

#### 3. Configure Post Types to classify and IBM Watson Features to enable under ClassifAI > Language Processing > IBM Watson

- Choose which public post types to classify when saved.
- Choose whether to assign category, keyword, entity, and concept as well as the thresholds and taxonomies used for each.

#### 4. Save a Post/Page/CPT or run WP CLI command to batch classify your content

### ⚠️  Note: Deprecated Endpoint URLs: `watsonplatform.net`

IBM Watson endpoint urls with `watsonplatform.net` were deprecated on 26 May 2021. The pattern for the new endpoint URLs is `api.{location}.{offering}.watson.cloud.ibm.com`. For example, Watson's NLU service offering endpoint will be like: `api.{location}.natural-language-understanding.watson.cloud.ibm.com`

For more information, see https://cloud.ibm.com/docs/watson?topic=watson-endpoint-change.

## Set Up Language Processing (via OpenAI)

#### 1. Sign up for OpenAI

* [Sign up for an OpenAI account](https://platform.openai.com/signup) or sign into your existing one.
* If creating a new account, complete the verification process (requires confirming your email and phone number).
* Log into your account and go to the [API key page](https://platform.openai.com/account/api-keys).
* Click `Create new secret key` and copy the key that is shown.

#### 2. Configure OpenAI API Keys under ClassifAI > Language Processing > OpenAI

* Enter your API Key copied from the above step into the `API Key` field.

#### 3. Enable specific Language Processing features

* Choose to add the ability to generate excerpts.
* If excerpt generation is configured, set the other options as needed.
* Save changes and ensure a success message is shown. An error will show if API authentication fails.

#### 4. Edit a content type that has excerpts enabled

* Edit (or create) an item that supports excerpts. Note: only the block editor is supported.
* Ensure this item has content saved.
* Open the Excerpt panel in the sidebar and click on `Generate Excerpt`

## Set Up Image Processing (via Microsoft Azure)

Note that [Computer Vision](https://docs.microsoft.com/en-us/azure/cognitive-services/computer-vision/home#image-requirements) can analyze and crop images that meet the following requirements:
- The image must be presented in JPEG, PNG, GIF, or BMP format
- The file size of the image must be less than 4 megabytes (MB)
- The dimensions of the image must be greater than 50 x 50 pixels
- The file must be externally accessible via URL (i.e. local sites and setups that block direct file access will not work out of the box)

#### 1. Sign up for Azure services

- [Register for a Microsoft Azure account](https://azure.microsoft.com/en-us/free/) or sign into your existing one.
- Log into your account and create a new [*Computer Vision*](https://portal.azure.com/#blade/Microsoft_Azure_Marketplace/GalleryFeaturedMenuItemBlade/selectedMenuItemId/CognitiveServices_MP/dontDiscardJourney/true/launchingContext/%7B%22source%22%3A%22Resources%20Microsoft.CognitiveServices%2Faccounts%22%7D/resetMenuId/) Service if you do not already have one.  It may take a minute for your account to fully populate with the default resource group to use.
- Click `Keys and Endpoint` in the left hand Resource Management menu to view the `Endpoint` URL for this resource.
- Click the copy icon next to `KEY 1` to copy the API Key credential for this resource.

#### 2. Configure Microsoft Azure API and Key under ClassifAI > Image Processing

- In the `Endpoint URL` field, enter your `API endpoint`.
- In the `API Key` field, enter your `KEY 1`.

#### 3. Enable specific Image Processing features

- Choose to `Generate alt text`, `Tag images`, `Enable smart cropping`, and/or `Scan image or PDF for text`.
- For features that have thresholds or taxonomy settings, set those as well.

#### 4. Save Image or PDF file or run WP CLI command to batch classify your content

## Set Up Recommended Content (via Microsoft Azure Personalizer)

Note that [Personalizer](https://azure.microsoft.com/en-us/services/cognitive-services/personalizer/) requires sufficient data volume to enable Personalizer to learn. In general, we recommend a minimum of ~1,000 events per day to ensure Personalizer learns effectively. If Personalizer doesn't receive sufficient data, the service takes longer to determine the best actions.

#### 1. Sign up for Azure services

- [Register for a Microsoft Azure account](https://azure.microsoft.com/en-us/free/) or sign into your existing one.
- Log into your account and create a new [Personalizer resource](https://portal.azure.com/#create/Microsoft.CognitiveServicesPersonalizer).
- Enter your service name, select a subscription, location, pricing tier, and resource group.
- Select **Create** to create the resource.
- After your resource has deployed, select the **Go to Resource** button to go to your Personalizer resource.
- Click `Keys and Endpoint` in the left hand Resource Management menu to view the `Endpoint` URL for this resource.
- Click the copy icon next to `KEY 1` to copy the API Key credential for this resource.

For more information, see https://docs.microsoft.com/en-us/azure/cognitive-services/personalizer/how-to-create-resource

#### 2. Configure Microsoft Azure API and Key under ClassifAI > Recommended Content

- In the `Endpoint URL` field, enter your `Endpoint` URL from Step 1 above.
- In the `API Key` field, enter your `KEY 1` from Step 1 above.

#### 3. Use "Recommended Content" block to display recommended content on your website.

## WP CLI Commands

- Check out the [ClassifAI docs](https://10up.github.io/classifai/).

## Frequently Asked Questions

### What data does ClassifAI gather?

ClassifAI connects your WordPress site directly to your account with specific service provider(s) (e.g. Microsoft Azure AI, IBM Watson, OpenAI), so no data is gathered by 10up.  The data gathered in our [registration form](https://classifaiplugin.com/#cta) is used simply to stay in touch with users so we can provide product updates and news.  More information is available in the [Privacy Policy on ClassifAIplugin.com](https://drive.google.com/open?id=1Hn4XEWmNGqeMzLqnS7Uru2Hl2vJeLc7cI7225ztThgQ).

### What are the Categories, Keywords, Concepts, and Entities within the NLU Language Processing feature?

[Categories](https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#categories) are five levels of hierarchies that IBM Watson can identify from your text.  [Keywords](https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#keywords) are specific terms from your text that IBM Watson is able to identify.  [Concepts](https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#concepts) are high-level concepts that are not necessarily directly referenced in your text.  [Entities](https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#entities) are people, companies, locations, and classifications that are made by IBM Watson from your text.

### How can I view the taxonomies that are generated from the NLU Language Processing?

Whatever options you have selected in the Category, Keyword, Entity, and Concept taxonomy dropdowns in the NLU Language Processing settings can be viewed within Classic Editor metaboxes and the Block Editor side panel.  They can also be viewed in the All Posts and All Pages table list views by utilizing the Screen Options to enable those columns if they're not already appearing in your table list view.

## Support Level

**Active:** 10up is actively working on this, and we expect to continue work for the foreseeable future including keeping tested up to the most recent version of WordPress.  Bug reports, feature requests, questions, and pull requests are welcome.

## Changelog

A complete listing of all notable changes to ClassifAI are documented in [CHANGELOG.md](https://github.com/10up/classifai/blob/develop/CHANGELOG.md).

## Contributing

Please read [CODE_OF_CONDUCT.md](https://github.com/10up/classifai/blob/develop/CODE_OF_CONDUCT.md) for details on our code of conduct, [CONTRIBUTING.md](https://github.com/10up/classifai/blob/develop/CONTRIBUTING.md) for details on the process for submitting pull requests to us, and [CREDITS.md](https://github.com/10up/classifai/blob/develop/CREDITS.md) for a listing of maintainers, contributors, and libraries for ClassifAI.

## Like what you see?

<a href="http://10up.com/contact/"><img src="https://10up.com/uploads/2016/10/10up-Github-Banner.png" width="850" alt="Work with us at 10up"></a>

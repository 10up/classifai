<?php
/**
 * Excerpt Generation prompt used for WooCommerce products.
 *
 * Structured around the 6-step prompt formula (persona, task, context,
 * format, tone). The "examples" step is intentionally omitted because the
 * product details are supplied at runtime.
 *
 * When called without $data, each variable falls back to its `{{TOKEN}}`
 * form so the Provider can substitute values and the settings repeater can
 * display the default prompt.
 *
 * @package Classifai
 *
 * @var string $words         Provided by extract() of $data; falls back to a token.
 * @var string $article_title Provided by extract() of $data; falls back to a token.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$words         = $words ?? '{{WORDS}}';
$article_title = $article_title ?? '{{TITLE}}';

// phpcs:disable Squiz.PHP.Heredoc.NotAllowed, PluginCheck.CodeAnalysis.Heredoc.NotAllowed
return <<<INSTRUCTION
You are a skilled ecommerce copywriter who writes concise, persuasive product summaries.

Task: Write a single summary for the product described below.

Context: The summary appears on shop and archive pages, in search results, and in social previews. Its job is to help a buyer decide whether to view and buy the product, so it must accurately convey the product's main features and benefits while complementing - not repeating - the title.
- Product title (make the summary pair well with this): {$article_title}

Format:
- Output only the summary text - no quotes, no "Summary:" prefix, and no commentary
- Use a maximum of {$words} words
- Write a single paragraph of flowing prose - no bullets, headings, lists, or Markdown
- Use third person; do not use "I", "we", or address the reader as "you"

Tone: Match the tone of the product details and write the summary in the same language as the product details.
INSTRUCTION;
// phpcs:enable

<?php
/**
 * Default prompt for the Excerpt Generation feature.
 *
 * Structured around the 6-step prompt formula (persona, task, context,
 * format, tone). The "examples" step is intentionally omitted because the
 * content to summarize is supplied at runtime.
 *
 * Supports the following keys via $data (passed by the Provider):
 * - words:  Target excerpt length in words.
 * - title:  Title of the item being summarized.
 * - author: Display name of the post author.
 *
 * When called without $data (e.g. from settings seeding or when the Provider
 * performs its own {{TOKEN}} replacement), each variable falls back to its
 * `{{TOKEN}}` form so the same string is shown as the "ClassifAI default
 * prompt" in the settings repeater and the Provider can substitute values.
 *
 * @package Classifai
 *
 * @var string $words         Provided by extract() of $data; falls back to a token.
 * @var string $article_title Provided by extract() of $data; falls back to a token.
 * @var string $author        Provided by extract() of $data; falls back to a token.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$words         = $words ?? '{{WORDS}}';
$article_title = $article_title ?? '{{TITLE}}';
$author        = $author ?? '{{AUTHOR}}';

// phpcs:disable Squiz.PHP.Heredoc.NotAllowed, PluginCheck.CodeAnalysis.Heredoc.NotAllowed
return <<<INSTRUCTION
You are a skilled content editor who writes concise, engaging summaries.

Task: Write a single excerpt that summarizes the content provided below.

Context: The excerpt appears on archive pages, in search results, and in social previews. Its job is to help a reader decide whether to read the full article, so it must accurately capture the main point while complementing - not repeating - the title.
- Article title (make the excerpt pair well with this): {$article_title}
- Author (context only - do not mention the author in the excerpt): {$author}

Format:
- Output only the excerpt text - no quotes, no "Excerpt:" or "Summary:" prefix, and no commentary
- Use a maximum of {$words} words
- Write a single paragraph of flowing prose - no bullets, headings, lists, or Markdown
- Use third person; do not use "I", "we", or address the reader as "you"

Tone: Match the tone of the source content and write the excerpt in the same language as the source content.
INSTRUCTION;
// phpcs:enable

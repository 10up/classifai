<?php
/**
 * Default prompt for the Excerpt Generation feature.
 *
 * Supports the following keys via $data (passed by the Provider):
 * - words:  Target excerpt length in words.
 * - title:  Title of the item being summarized.
 * - author: Display name of the post author.
 *
 * When called without $data (e.g. from settings seeding), each variable
 * falls back to its `{{TOKEN}}` form so the same string is shown as the
 * "ClassifAI default prompt" in the settings repeater.
 *
 * @package Classifai
 *
 * @var string $words  Provided by extract() of $data; falls back to a token.
 * @var string $article_title  Provided by extract() of $data; falls back to a token.
 * @var string $author Provided by extract() of $data; falls back to a token.
 */

$words         = $words ?? '{{WORDS}}';
$article_title = $article_title ?? '{{TITLE}}';
$author        = $author ?? '{{AUTHOR}}';

return "Summarize the following message using a maximum of {$words} words. The original message was written by {$author}. Ensure this summary pairs well with the following text: {$article_title}.";

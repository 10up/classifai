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

// phpcs:disable Squiz.PHP.Heredoc.NotAllowed, PluginCheck.CodeAnalysis.Heredoc.NotAllowed
return <<<INSTRUCTION
You are an editorial assistant responsible for writing the excerpt for a WordPress article.

Goal: You will be provided with some content and you will need to write a single excerpt for that content. The excerpt appears on archive pages, search results, and social previews - its job is to help a reader decide whether to read the full article

Article title (for context - ensure the excerpt pairs well with this): {$article_title}
Article author (for context - do not use in the excerpt): {$author}

Write an excerpt that:
- Accurately summarizes the article's main point - no clickbait, no exaggeration, no curiosity-gap teasers
- Targets approximately {$words} words
- Reads as a single paragraph of flowing prose
- Complements the title rather than restating it
- Is written in the same language as the source content
- Uses third person; avoid "I", "we", or addressing the reader as "you"

Do not:
- Wrap the excerpt in quotes
- Prefix the excerpt with "Excerpt:" or "Summary:"
- Include the author's name or any commentary
- Use any formatting (bullets, headings, lists, Markdown)

Output only the excerpt text.
INSTRUCTION;
// phpcs:enable

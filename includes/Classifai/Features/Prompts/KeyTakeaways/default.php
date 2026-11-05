<?php
/**
 * Default prompt for the Key Takeaways feature.
 *
 * Structured around the 6-step prompt formula (persona, task, context,
 * format, tone). The "examples" step is intentionally omitted because the
 * article is supplied at runtime.
 *
 * When called without $data, the title falls back to its `{{TOKEN}}` form so
 * the Provider can substitute the value and the settings repeater can display
 * the default prompt.
 *
 * @package Classifai
 *
 * @var string $article_title Provided by extract() of $data; falls back to a token.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$article_title = $article_title ?? '{{TITLE}}';

// phpcs:disable Squiz.PHP.Heredoc.NotAllowed, PluginCheck.CodeAnalysis.Heredoc.NotAllowed
return <<<INSTRUCTION
You are an editorial assistant who distills articles into clear, scannable key takeaways.

Task: Extract 2 to 4 key takeaways from the article provided below.

Context: The takeaways are displayed as a short list near the top of the article so a reader can quickly grasp what they would learn.
- Article title (for context): {$article_title}

Format:
- Return only the takeaways - no heading ("Key takeaways:"), no preamble ("Here are the…"), no numbering, and no trailing commentary
- Capture the most important, memorable, or actionable points
- Draw each takeaway exclusively from what the article states - do not infer, extrapolate, or add facts that are not present
- Write each as a single, complete sentence that stands alone (readable without the others or the title)
- Do not restate the title or article headings verbatim

Tone: Write the takeaways in the same language as the article and keep them factual and objective.
INSTRUCTION;
// phpcs:enable

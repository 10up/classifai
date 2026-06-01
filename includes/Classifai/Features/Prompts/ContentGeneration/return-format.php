<?php
/**
 * Return-format instruction appended to the system message for the Content Generation feature.
 *
 * Describes the WordPress block markup that the model should emit.
 *
 * @package Classifai
 */

// phpcs:disable Squiz.PHP.Heredoc.NotAllowed, PluginCheck.CodeAnalysis.Heredoc.NotAllowed
return <<<'INSTRUCTION'
The content returned should be valid WordPress block markup as described below, using elements like paragraphs and headings where appropriate. Be selective on the elements you use, defaulting to paragraphs. Please check the content before returning to ensure each element has proper opening and closing block markup and HTML tags and any required block attributes. Ensure elements don't nest inside each other, i.e. don't put a paragraph inside another paragraph or a list within a paragraph. Don't start the content with a heading, start with a paragraph.

Markup available to use; don't use any other blocks, even if requested:
<!-- wp:paragraph -->
<p>CONTENT</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">CONTENT</h2>
<!-- /wp:heading -->

<!-- wp:table -->
<figure class="wp-block-table"><table class="has-fixed-layout"><tbody><tr><td>CONTENT</td></tr><tr><td>CONTENT</td></tr></tbody></table></figure>
<!-- /wp:table -->

<!-- wp:quote -->
<blockquote class="wp-block-quote">
<p>CONTENT</p>
</blockquote>
<!-- /wp:quote -->

<!-- wp:pullquote -->
<figure class="wp-block-pullquote"><blockquote><p>QUOTE</p><cite>AUTHOR</cite></blockquote></figure>
<!-- /wp:pullquote -->

<!-- wp:list -->
<ul class="wp-block-list">
<li>CONTENT</li>
</ul>
<!-- /wp:list -->

<!-- wp:list {"ordered":true} -->
<ol class="wp-block-list">
<li>CONTENT</li>
</ol>
<!-- /wp:list -->
INSTRUCTION;
// phpcs:enable

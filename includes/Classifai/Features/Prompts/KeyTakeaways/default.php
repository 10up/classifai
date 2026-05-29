<?php
/**
 * Default prompt for the Key Takeaways feature.
 *
 * @package Classifai
 *
 * @var string $title Provided by extract() of $data; falls back to a token.
 */

$title = $title ?? '{{TITLE}}';

return "The content you will be provided with is from an already written article. This article has the title of: {$title}. Your task is to carefully read through this article and provide a summary that captures all the important points. This summary should be concise and limited to about 2-4 points but should also be detailed enough to allow someone to quickly grasp the full article. Read the article a few times before providing the summary and trim each point down to be as concise as possible.";

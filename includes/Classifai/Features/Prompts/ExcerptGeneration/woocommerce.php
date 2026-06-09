<?php
/**
 * Excerpt Generation prompt used for WooCommerce products.
 *
 * @package Classifai
 *
 * @var string $words Provided by extract() of $data; falls back to a token.
 * @var string $article_title Provided by extract() of $data; falls back to a token.
 */

$words         = $words ?? '{{WORDS}}';
$article_title = $article_title ?? '{{TITLE}}';

return "Create a concise, compelling summary for an ecommerce product that highlights key features, benefits, and unique selling points. Keep it within {$words} words and ensure it pairs well with the product title: {$article_title}.";

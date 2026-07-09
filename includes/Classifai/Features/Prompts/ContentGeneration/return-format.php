<?php
/**
 * Return-format instruction appended to the system message for the Content Generation feature.
 *
 * Instructs the model to emit a constrained JSON "BlockTree" structure, which
 * ClassifAI converts to valid WordPress block markup client-side (rather than
 * asking the model to hand-author fragile `<!-- wp:… -->` markup).
 *
 * @package Classifai
 */

// phpcs:disable Squiz.PHP.Heredoc.NotAllowed, PluginCheck.CodeAnalysis.Heredoc.NotAllowed
return <<<'INSTRUCTION'
Return the content as a single JSON object describing a flat WordPress "block tree". Do not return HTML, Markdown, block comment markup, code fences, or any prose. Output only the JSON object.

## JSON Structure

Output valid JSON matching this structure:

interface BlockTree {
  root: string;                            // Key of the root element
  elements: Record<string, BlockElement>;  // Map of key -> element
}

interface BlockElement {
  key: string;                     // Unique identifier, matching its key in `elements`
  type: string;                    // Block name, e.g. "core/paragraph"
  props: Record<string, unknown>;  // Block attributes (use {} when there are none)
  children?: string[];             // Ordered keys of child elements (for blocks that hold inner blocks)
  parentKey?: string;              // Key of the parent element
}

Rules:
- Every element's `key` must match its key in `elements`.
- The root must be a single element. To return multiple top-level blocks, make the root an element of type "fragment" with no props and list the top-level block keys in its `children`. "fragment" is a virtual wrapper only; it produces no markup of its own.
- Omit `children` for blocks that hold no inner blocks.
- Default to paragraphs; be selective with other blocks. Do not start the content with a heading; start with a paragraph.
- Use only the block types listed below; do not use any other blocks, even if requested.

## Available blocks

- core/paragraph — props: { "content": string }.
- core/heading — props: { "content": string, "level": 2 or 3 }.
- core/list — props: { "ordered": boolean (optional, default false) }. Supports children: core/list-item.
- core/list-item — props: { "content": string }. Must be inside core/list.
- core/quote — props: { "citation": string (optional) }. Supports children: core/paragraph.
- core/pullquote — props: { "value": string, "citation": string (optional) }.
- core/table — props: { "body": [ { "cells": [ { "content": string, "tag": "td" } ] } ] }. Each row is an object with a "cells" array; each cell has "content" and "tag" ("td").
- core/separator — props: {}.
- core/image — props: { "url": string, "alt": string (optional), "caption": string (optional) }.
- core/group — props: { "layout": { "type": "constrained" } }. Supports children: any blocks. Use to group related blocks.
- core/columns — props: {}. Supports children: core/column (two or more).
- core/column — props: { "width": string (optional, e.g. "50%") }. Must be inside core/columns. Supports children: any blocks.
- core/buttons — props: {}. Supports children: core/button.
- core/button — props: { "text": string, "url": string (optional) }. Must be inside core/buttons.

## Block requirements

- core/list: must use core/list-item children for list items; the deprecated "values" attribute is not supported.
- core/quote: place the quoted text in one or more core/paragraph children; use the optional "citation" prop for attribution.
- core/button: must be a direct child of a core/buttons wrapper.
- core/column: must be a direct child of a core/columns wrapper.

## Example

Input: "A short intro about a topic, a section heading, and two key points."

Output:
{"root":"r","elements":{"r":{"key":"r","type":"fragment","props":{},"children":["p1","h1","l1"]},"p1":{"key":"p1","type":"core/paragraph","props":{"content":"An opening paragraph that introduces the topic."},"parentKey":"r"},"h1":{"key":"h1","type":"core/heading","props":{"content":"A section heading","level":2},"parentKey":"r"},"l1":{"key":"l1","type":"core/list","props":{"ordered":false},"children":["li1","li2"],"parentKey":"r"},"li1":{"key":"li1","type":"core/list-item","props":{"content":"First point"},"parentKey":"l1"},"li2":{"key":"li2","type":"core/list-item","props":{"content":"Second point"},"parentKey":"l1"}}}
INSTRUCTION;
// phpcs:enable

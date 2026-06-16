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
Return the content as a single JSON object describing a flat "block tree". Do not return HTML, Markdown, block comment markup, code fences, or any prose. Output only the JSON object.

The JSON object has this shape:
{
  "root": "<key of the root element>",
  "elements": {
    "<key>": { "key": "<key>", "type": "<block type>", "props": { ... }, "children": ["<child key>", ...] }
  }
}

Rules:
- Every element has a unique string "key" matching its key in "elements".
- "children" is an ordered array of keys; omit it for blocks that hold no inner blocks.
- The root must be a single element. To return multiple top-level blocks, make the root an element of type "fragment" and list the top-level block keys in its "children". A "fragment" is a virtual wrapper only; give it no props.
- Default to paragraphs. Be selective with other blocks. Do not start the content with a heading; start with a paragraph.
- Use only the block types listed below; do not use any other blocks, even if requested.

Block types and their props:
- "core/paragraph": props { "content": string }. No children.
- "core/heading": props { "content": string, "level": 2 or 3 }. No children.
- "core/list": props { "ordered": boolean (optional, default false) }. children: one or more "core/list-item".
- "core/list-item": props { "content": string }. No children.
- "core/quote": props { "citation": string (optional) }. children: one or more "core/paragraph".
- "core/pullquote": props { "value": string, "citation": string (optional) }. No children.
- "core/table": props { "body": [ { "cells": [ { "content": string, "tag": "td" } ] } ] }. Each row is an object with a "cells" array; each cell has "content" and "tag" ("td" for body cells). No children.
- "core/separator": props {}. No children.
- "core/image": props { "url": string, "alt": string (optional), "caption": string (optional) }. No children.
- "core/group": props { "layout": { "type": "constrained" } }. children: any blocks. Use to group related blocks.
- "core/columns": props {}. children: two or more "core/column".
- "core/column": props { "width": string (optional, e.g. "50%") }. children: any blocks.
- "core/buttons": props {}. children: one or more "core/button".
- "core/button": props { "text": string, "url": string (optional) }. No children.

Example:
{"root":"r","elements":{"r":{"key":"r","type":"fragment","props":{},"children":["p1","h1","l1"]},"p1":{"key":"p1","type":"core/paragraph","props":{"content":"An opening paragraph."}},"h1":{"key":"h1","type":"core/heading","props":{"content":"A section heading","level":2}},"l1":{"key":"l1","type":"core/list","props":{"ordered":false},"children":["li1","li2"]},"li1":{"key":"li1","type":"core/list-item","props":{"content":"First point"}},"li2":{"key":"li2","type":"core/list-item","props":{"content":"Second point"}}}}
INSTRUCTION;
// phpcs:enable

(()=>{"use strict";var e={n:t=>{var c=t&&t.__esModule?()=>t.default:()=>t;return e.d(c,{a:c}),c},d:(t,c)=>{for(var o in c)e.o(c,o)&&!e.o(t,o)&&Object.defineProperty(t,o,{enumerable:!0,get:c[o]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t)};const t=window.React,c=window.wp.data,o=window.wp.blocks,n=window.wp.apiFetch;var i=e.n(n);const r=window.wp.hooks,a=window.wp.compose,l=window.wp.blockEditor,s=window.wp.components,d=window.wp.i18n,p=window.wp.element,{find:m,debounce:u}=lodash,f=(0,t.createElement)("span",{className:"dashicons dashicons-editor-paste-text"}),b=async(e,t,n)=>{if(!n)return;const{getBlockIndex:i}=(0,c.select)("core/block-editor"),r=(0,o.createBlock)("core/group",{anchor:`classifai-ocr-${t}`,className:"is-style-classifai-ocr-text"}),a=(0,o.createBlock)("core/paragraph",{content:n});await(0,c.dispatch)("core/block-editor").insertBlock(r,i(e)+1),(0,c.dispatch)("core/block-editor").insertBlock(a,0,r.clientId)},g=(e,t=[])=>{if(0===t.length){const{getBlocks:e}=(0,c.select)("core/block-editor");t=e()}return!!m(t,(t=>t.attributes.anchor===`classifai-ocr-${e}`))},h=(0,a.createHigherOrderComponent)((e=>c=>{const[o,n]=(0,p.useState)(!1),{attributes:r,clientId:a,isSelected:m,name:u,setAttributes:h}=c;return m&&"core/image"==u?(!r.ocrChecked&&r.id&&(async e=>{const t=await i()({path:`/wp/v2/media/${e}`});return!(!Object.prototype.hasOwnProperty.call(t,"classifai_has_ocr")||!t.classifai_has_ocr)&&!!(Object.prototype.hasOwnProperty.call(t,"description")&&Object.prototype.hasOwnProperty.call(t.description,"rendered")&&t.description.rendered)&&t.description.rendered.replace(/(<([^>]+)>)/gi,"").replace(/(\r\n|\n|\r)/gm,"").trim()})(r.id).then((e=>{e?(h({ocrScannedText:e,ocrChecked:!0}),n(!0)):h({ocrChecked:!0})})),(0,t.createElement)(p.Fragment,null,(0,t.createElement)(e,{...c}),r.ocrScannedText&&(0,t.createElement)(l.BlockControls,null,(0,t.createElement)(s.ToolbarGroup,null,(0,t.createElement)(s.ToolbarButton,{label:(0,d.__)("Insert scanned text into content","classifai"),icon:f,onClick:()=>b(a,r.id,r.ocrScannedText),disabled:g(r.id)}))),o&&(0,t.createElement)(s.Modal,{title:(0,d.__)("ClassifAI detected text in your image","classifai")},(0,t.createElement)("p",null,(0,d.__)("Would you like to insert the scanned text under this image block? This enhances search indexing and accessibility for your readers.","classifai")),(0,t.createElement)(s.Flex,{align:"flex-end",justify:"flex-end"},(0,t.createElement)(s.FlexItem,null,(0,t.createElement)(s.Button,{isPrimary:!0,onClick:()=>{b(a,r.id,r.ocrScannedText),n(!1)}},(0,d.__)("Insert text","classifai"))),(0,t.createElement)(s.FlexItem,null,(0,t.createElement)(s.Button,{isSecondary:!0,onClick:()=>n(!1)},(0,d.__)("Dismiss","classifai"))))))):(0,t.createElement)(e,{...c})}),"imageOcrControl");(0,r.addFilter)("editor.BlockEdit","classifai/image-processing-ocr",h),(0,r.addFilter)("blocks.registerBlockType","classifai/image-processing-ocr",((e,t)=>("core/image"!==t||e.attributes&&(e.attributes.ocrScannedText={type:"string",default:""},e.attributes.ocrChecked={type:"boolean",default:!1}),e))),wp.blocks.registerBlockStyle("core/group",{name:"classifai-ocr-text",label:(0,d.__)("Scanned Text from Image","classifai")});{let e,t=[];(0,c.subscribe)(u((()=>{const o=(0,c.select)("core/block-editor"),r=o.getSelectedBlock(),a=o.getBlocks();if(null===r)return i(),void(e=r);if(r!==e&&!t.includes(r.clientId))if(i(),e=r,"core/image"===r.name){const e=m(a,(e=>e.attributes.anchor===`classifai-ocr-${r.attributes.id}`));void 0!==e&&n([e.clientId,r.clientId])}else{const e=o.getBlock(o.getBlockHierarchyRootClientId(r.clientId));if("core/group"===e.name){let t=/classifai-ocr-([0-9]+)/.exec(e.attributes.anchor);if(null!==t){[,t]=t;const c=m(a,(e=>e.attributes.id==t));void 0!==c&&n([c.clientId,e.clientId])}}}}),100));const o=()=>{const e=document.head||document.getElementsByTagName("head")[0],t=document.createElement("style");return t.setAttribute("id","classifai-ocr-style"),e.appendChild(t),t},n=e=>{var c;const n=null!==(c=document.getElementById("classifai-ocr-style"))&&void 0!==c?c:o(),i=`${e.map((e=>`#block-${e}:before`)).join(", ")} {\n\t\t\tcontent: "";\n\t\t\tposition: absolute;\n\t\t\tdisplay: block;\n\t\t\ttop: 0;\n\t\t\tleft: -15px;\n\t\t\tbottom: 0;\n\t\t\tborder-left: 4px solid #cfe7f3;\n\t\t\tmix-blend-mode: difference;\n\t\t\topacity: 0.25;\n\t\t}`;n.appendChild(document.createTextNode(i)),t=e},i=()=>{if(0===t.length)return;const e=document.getElementById("classifai-ocr-style");e&&(e.innerText=""),t=[]}}})();
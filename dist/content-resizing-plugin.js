(()=>{"use strict";const e=window.wp.element,t=window.wp.plugins,n=window.wp.blockEditor,i=window.wp.editor,s=window.wp.data,c=window.wp.dom,l=window.wp.compose,a=window.wp.components,r=window.wp.wordcount,o=window.wp.i18n,d=(0,e.createElement)("svg",{width:"20",height:"15",viewBox:"0 0 61 46",fill:"none",xmlns:"http://www.w3.org/2000/svg"},(0,e.createElement)("path",{fillRule:"evenodd",clipRule:"evenodd",d:"M3.51922 0C1.57575 0 0 1.5842 0 3.53846V42.4615C0 44.4158 1.57575 46 3.51922 46H57.4808C59.4243 46 61 44.4158 61 42.4615V3.53846C61 1.5842 59.4243 0 57.4808 0H3.51922ZM16.709 8.13836H21.4478L33.58 39.5542H27.524L24.0318 30.5144H13.9699L10.5169 39.5542H4.55669L16.709 8.13836ZM19.0894 16.7007C18.9846 17.041 18.878 17.3735 18.7702 17.698L18.7582 17.7344L15.9976 25.1398H22.1464L19.4013 17.6851L19.0894 16.7007ZM40.3164 8.13836H52.9056V12.1528L49.4929 12.9715V34.6306L52.9056 35.41V39.4338H40.3164V35.41L43.7291 34.6306V12.9715L40.3164 12.1528V8.13836Z"})),u={clientId:"",resizingType:null,isResizing:!1},g=(0,s.createReduxStore)("resize-content-store",{reducer(e=u,t){switch(t.type){case"SET_CLIENT_ID":return{...e,clientId:t.clientId};case"SET_RESIZING_TYPE":return{...e,resizingType:t.resizingType};case"SET_IS_RESIZING":return{...e,isResizing:t.isResizing}}return e},actions:{setClientId:e=>({type:"SET_CLIENT_ID",clientId:e}),setResizingType:e=>({type:"SET_RESIZING_TYPE",resizingType:e}),setIsResizing:e=>({type:"SET_IS_RESIZING",isResizing:e})},selectors:{getClientId:e=>e.clientId,getResizingType:e=>e.resizingType,getIsResizing:e=>e.isResizing}});(0,s.register)(g);const p=({count:t=0,countEntity:n="word"})=>0===t?(0,e.createElement)("div",null,"word"===n?(0,o.__)("No change in word count.","classifai"):(0,o.__)("No change in character count.","classifai")):t<0?(0,e.createElement)("div",{className:"classifai-content-resize__shrink-stat"},"word"===n?(0,e.createElement)(e.Fragment,null,(0,e.createElement)("strong",null,t)," ",(0,o.__)("words","classifai")):(0,e.createElement)(e.Fragment,null,(0,e.createElement)("strong",null,t)," ",(0,o.__)("characters","classifai"))):(0,e.createElement)("div",{className:"classifai-content-resize__grow-stat"},"word"===n?(0,e.createElement)(e.Fragment,null,(0,e.createElement)("strong",null,"+",t)," ",(0,o.__)("words","classifai")):(0,e.createElement)(e.Fragment,null,(0,e.createElement)("strong",null,"+",t)," ",(0,o.__)("characters","classifai")));function m(e){return e=e.replace(/<br>/g,"\n"),(0,c.__unstableStripHTML)(e).trim().replace(/\n\n+/g,"\n\n")}(0,t.registerPlugin)("tenup-openai-expand-reduce-content",{render:()=>{const[t,c]=(0,e.useState)(""),[l,d]=(0,e.useState)(null),[u,_]=(0,e.useState)([]),[E,w]=(0,e.useState)(!1),{isMultiBlocksSelected:h,resizingType:f,isResizing:z}=(0,s.useSelect)((e=>({isMultiBlocksSelected:e(n.store).hasMultiSelection(),resizingType:e(g).getResizingType(),isResizing:e(g).getIsResizing()})));function I(){d(null),_([]),w(!1),(0,s.dispatch)(g).setResizingType(null)}return(0,e.useEffect)((()=>{f&&(async()=>{await async function(){var e;const t=(0,s.select)(n.store).getSelectedBlock(),i=null!==(e=t.attributes.content)&&void 0!==e?e:"";d(t),c(m(i)),(0,s.dispatch)(g).setIsResizing(!0)}()})()}),[f]),(0,e.useEffect)((()=>{z&&l&&(async()=>{const e=await async function(){let e=[];const n=`${wpApiSettings.root}classifai/v1/openai/resize-content`,c=(0,s.select)(i.store).getCurrentPostId(),a=new FormData;a.append("id",c),a.append("content",t),a.append("resize_type",f),(0,s.dispatch)(g).setClientId(l.clientId);const r=await fetch(n,{method:"POST",body:a,headers:new Headers({"X-WP-Nonce":wpApiSettings.nonce})});return 200===r.status?e=await r.json():((0,s.dispatch)(g).setIsResizing(!1),(0,s.dispatch)(g).setClientId(""),I()),(0,s.dispatch)(g).setIsResizing(!1),(0,s.dispatch)(g).setClientId(""),e}();_(e),w(!0)})()}),[z,l]),h||z?null:!z&&u.length&&E&&(0,e.createElement)(a.Modal,{title:(0,o.__)("Select a suggestion","classifai"),isFullScreen:!1,className:"classifai-content-resize__suggestion-modal",onRequestClose:()=>{w(!1),I()}},(0,e.createElement)("div",{className:"classifai-content-resize__result-wrapper"},(0,e.createElement)("table",{className:"classifai-content-resize__result-table"},(0,e.createElement)("thead",null,(0,e.createElement)("tr",null,(0,e.createElement)("th",null,(0,o.__)("Suggestion","classifai")),(0,e.createElement)("th",{className:"classifai-content-resize__stat-header"},(0,o.__)("Stats","classifai")),(0,e.createElement)("th",null,(0,o.__)("Action","classifai")))),(0,e.createElement)("tbody",null,u.map(((i,c)=>{const d=(0,r.count)(t,"words"),u=(0,r.count)(t,"characters_including_spaces"),g=(0,r.count)(i,"words")-d,m=(0,r.count)(i,"characters_including_spaces")-u;return(0,e.createElement)("tr",{key:c},(0,e.createElement)("td",null,i),(0,e.createElement)("td",null,(0,e.createElement)(p,{count:g}),(0,e.createElement)(p,{count:m,countEntity:"character"})),(0,e.createElement)("td",null,(0,e.createElement)(a.Button,{text:(0,o.__)("Select","classifai"),variant:"secondary",onClick:()=>{return e=i,(0,s.dispatch)(n.store).updateBlockAttributes(l.clientId,{content:e}),(0,s.dispatch)(n.store).selectionChange(l.clientId,"content",0,e.length),void I();var e},tabIndex:"0"})))}))))))}});const _=["#8c2525","#ca4444","#303030"];let E=0;function w(e="",t){if(!t)return;if(!(0,s.select)(g).getIsResizing())return void clearTimeout(E);const n=e.split(" "),i=function(e=[],t=10){const n=Array.from({length:e.length},((e,t)=>t)),i=[];for(;i.length<t;){const e=Math.floor(Math.random()*n.length);i.includes(e)||i.push(e)}return i}(n,n.length/4),c=n.map(((e,t)=>{if(i.includes(t)){const t=Math.floor(5*Math.random());return`<span class="classifai-content-resize__blot" style="background-color: ${_[t]}">${e}</span>`}return e}));t.current.innerHTML=c.join(" "),E=setTimeout((()=>{requestAnimationFrame((()=>w(e,t)))}),1e3/1.35)}const h=(0,l.createHigherOrderComponent)((t=>n=>{const{currentClientId:i}=(0,s.useSelect)((e=>({currentClientId:e(g).getClientId()}))),c=(0,e.useRef)();if(i!==n.clientId)return(0,e.createElement)(t,n);if("core/paragraph"!==n.name)return(0,e.createElement)(t,n);const l=m(n.attributes.content);return(0,s.select)(g).getIsResizing()&&requestAnimationFrame((()=>w(l,c))),(0,e.createElement)(e.Fragment,null,(0,e.createElement)("div",{style:{position:"relative"}},(0,e.createElement)("div",{className:"classifai-content-resize__overlay"},(0,e.createElement)("div",{className:"classifai-content-resize__overlay-text"},(0,o.__)("Processing data…","classifai"))),(0,e.createElement)("div",{id:"classifai-content-resize__mock-content",ref:c},l),(0,e.createElement)(t,n)))}),"withInspectorControl");wp.hooks.addFilter("editor.BlockEdit","resize-content/lock-block-editing",h);const f=(0,l.createHigherOrderComponent)((t=>i=>{const{isMultiBlocksSelected:c,resizingType:l}=(0,s.useSelect)((e=>({isMultiBlocksSelected:e(n.store).hasMultiSelection(),currentClientId:e(g).getClientId(),resizingType:e(g).getResizingType()})));return"core/paragraph"!==i.name?(0,e.createElement)(t,i):(0,e.createElement)(e.Fragment,null,l||c?null:(0,e.createElement)(n.BlockControls,{group:"other"},(0,e.createElement)(a.ToolbarDropdownMenu,{icon:d,className:"classifai-resize-content-btn",controls:[{title:(0,o.__)("Expand this text","classifai"),onClick:()=>{(0,s.dispatch)(g).setResizingType("grow")}},{title:(0,o.__)("Condense this text","classifai"),onClick:()=>{(0,s.dispatch)(g).setResizingType("shrink")}}]})),(0,e.createElement)(t,i))}),"withBlockControl");wp.hooks.addFilter("editor.BlockEdit","resize-content/lock-block-editing",f)})();
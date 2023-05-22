(()=>{"use strict";const e=window.wp.element,t=window.React;var s;function i(){return i=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var s=arguments[t];for(var i in s)Object.prototype.hasOwnProperty.call(s,i)&&(e[i]=s[i])}return e},i.apply(this,arguments)}var o=function(e){return t.createElement("svg",i({width:20,height:15,viewBox:"0 0 61 46",fill:"none",xmlns:"http://www.w3.org/2000/svg"},e),s||(s=t.createElement("path",{fillRule:"evenodd",clipRule:"evenodd",d:"M3.52 0C1.575 0 0 1.584 0 3.538v38.924C0 44.416 1.576 46 3.52 46h53.96c1.944 0 3.52-1.584 3.52-3.538V3.538C61 1.584 59.424 0 57.48 0H3.52Zm13.189 8.138h4.739L33.58 39.554h-6.056l-3.492-9.04H13.97l-3.453 9.04h-5.96L16.709 8.138Zm2.38 8.563c-.104.34-.211.672-.319.997l-.012.036-2.76 7.406h6.148l-2.745-7.455-.312-.984Zm21.227-8.563h12.59v4.015l-3.413.819V34.63l3.413.779v4.024h-12.59V35.41l3.413-.78V12.972l-3.413-.818V8.138Z",fill:"#1e1e1e"})))};const a=window.wp.i18n,{get:n}=lodash,r=window.wp.data,c=window.wp.editPost,l=window.wp.components,d=window.wp.plugins,u=window.wp.notices,p={audioId:0,isProcessing:!1},g=(0,r.createReduxStore)("classifai-post-audio",{reducer(){let e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:p,t=arguments.length>1?arguments[1]:void 0;switch(t.type){case"SET_AUDIO_ID":return{...e,audioId:t.id};case"SET_PROCESSING_STATUS":return{...e,isProcessing:t.status}}return e},actions:{setAudioId:e=>({type:"SET_AUDIO_ID",id:e}),setIsProcessing:e=>({type:"SET_PROCESSING_STATUS",status:e})},selectors:{getAudioId:e=>e.audioId,getIsProcessing:e=>e.isProcessing}});(0,r.register)(g);const{classifaiEmbeddingData:f,classifaiPostData:y}=window,_=()=>(0,e.createElement)(l.Icon,{className:"components-panel__icon",icon:o,size:24}),m=()=>{const t=(0,r.useSelect)((e=>e("core/editor").getEditedPostAttribute("classifai_process_content"))),{editPost:s}=(0,r.useDispatch)("core/editor"),i="no"===t?"no":"yes";return(0,e.createElement)(l.ToggleControl,{label:(0,a.__)("Process content on update","classifai"),help:"yes"===i?(0,a.__)("ClassifAI language processing is enabled","classifai"):(0,a.__)("ClassifAI language processing is disabled","classifai"),checked:"yes"===i,onChange:e=>{s({classifai_process_content:e?"yes":"no"})}})},w=async e=>{const{select:t,dispatch:s}=wp.data,i=t("core/editor").getCurrentPostId(),o=t("core/editor").getCurrentPostType(),n=t("core/editor").getPostTypeLabel()||(0,a.__)("Post","classifai");if(e&&e.terms){let r=!1;const c=Object.keys(e.terms),l={};if(c.forEach((s=>{let i=s;"post_tag"===s&&(i="tags"),"category"===s&&(i="categories");const o=[...t("core/editor").getEditedPostAttribute(s)||[],...e.terms[s].map((e=>Number.parseInt(e)))].filter(((e,t,s)=>s.indexOf(e)===t));o&&o.length&&(r=!0,l[i]=o)})),r){const e=await t("core/editor").isEditedPostDirty();await s("core").editEntityRecord("postType",o,i,l),e||await s("core").saveEditedEntityRecord("postType",o,i),s("core/notices").createSuccessNotice((0,a.sprintf)(/** translators: %s is post type label. */
(0,a.__)("%s classified successfully.","classifai"),n),{type:"snackbar"})}}},P=()=>{if("yes"==("no"===(0,r.useSelect)((e=>e("core/editor").getEditedPostAttribute("classifai_process_content")))?"no":"yes"))return null;const t=wp.data.select("core/editor").getCurrentPostId(),s=wp.data.select("core/editor").getPostTypeLabel()||(0,a.__)("Post","classifai"),i=(0,a.sprintf)(/** translators: %s Post type label */
(0,a.__)("Classify %s","classifai"),s);return(0,e.createElement)(e.Fragment,null,(0,e.createElement)(l.Button,{variant:"secondary","data-id":t,onClick:e=>(e=>{let{button:t,endpoint:s,callback:i=!1,buttonText:o=(0,a.__)("Rescan","classifai")}=e;const r=t.getAttribute("data-id"),[c]=t.parentNode.getElementsByClassName("spinner"),[l]=t.parentNode.getElementsByClassName("error"),d=`${s}${r}`;t.setAttribute("disabled","disabled"),c.style.display="inline-block",c.classList.add("is-active"),l.style.display="none",wp.apiRequest({path:d}).then((e=>{t.removeAttribute("disabled"),c.style.display="none",c.classList.remove("is-active"),t.textContent=o,i&&i(e)}),(e=>{const s=n(e,"responseJSON",{code:"unknown_error",message:(0,a.__)("An unknown error occurred.","classifai")});c.style.display="none",c.classList.remove("is-active"),t.removeAttribute("disabled"),t.textContent=o,l.style.display="inline-block",l.textContent=`Error: ${s.message}`}))})({button:e.target,endpoint:"/classifai/v1/generate-tags/",callback:w,buttonText:i})},i),(0,e.createElement)("span",{className:"spinner",style:{display:"none",float:"none"}}),(0,e.createElement)("span",{className:"error",style:{display:"none",color:"#bc0b0b",padding:"5px"}}))};let h="";const b=t=>{const[s,i]=(0,e.useState)(!1),[o,n]=(0,e.useState)((new Date).getTime());let c=!1;const d="yes"===(0,r.useSelect)((e=>e("core/editor").getEditedPostAttribute("classifai_synthesize_speech"))),u=(0,r.useSelect)((e=>e("core/editor").getCurrentPostType())),p=(0,r.useSelect)((e=>e(g).getIsProcessing()));"undefined"!=typeof classifaiTextToSpeechData&&classifaiTextToSpeechData.supportedPostTypes.includes(u)&&(c=!0);const f=(0,r.useSelect)((e=>e("core/editor").getEditedPostAttribute("classifai_post_audio_id"))),y=t.audioId||f,_=(0,r.useSelect)((e=>e("core").getEntityRecords("postType","attachment",{include:[y]}))),m=_&&_.length>0&&_[0].source_url,w=(0,e.useRef)(!1);(0,e.useEffect)((()=>{const e=document.getElementById("classifai-audio-preview");e&&(s?e.play():e.pause())}),[s]),(0,e.useEffect)((()=>{p&&(w.current=!0),w.current&&!p&&n((new Date).getTime())}),[p]);const P=`${m}?ver=${o}`;let h="controls-play";return p?h="format-audio":s&&(h="controls-pause"),(0,e.createElement)(e.Fragment,null,(0,e.createElement)(l.ToggleControl,{label:(0,a.__)("Generate audio for this post.","classifai"),help:c?(0,a.__)("ClassifAI will generate audio for the post when it is published or updated.","classifai"):(0,a.__)("Text to Speech generation is disabled for this post type.","classifai"),checked:c&&d,onChange:e=>{wp.data.dispatch("core/editor").editPost({classifai_synthesize_speech:e?"yes":"no"})},disabled:!c}),m&&(0,e.createElement)("audio",{id:"classifai-audio-preview",src:P,onEnded:()=>i(!1)}),m&&d&&(0,e.createElement)(l.BaseControl,{id:"classifai-audio-controls",help:p?"":(0,a.__)("Preview the generated audio.","classifai")},(0,e.createElement)(l.Button,{id:"classifai-audio-controls__preview-btn",icon:(0,e.createElement)(l.Icon,{icon:h}),variant:"secondary",onClick:()=>i(!s),disabled:p,isBusy:p},p?(0,a.__)("Generating audio..","classifai"):(0,a.__)("Preview","classifai"))))};let E=!1;wp.data.subscribe((function(){const{select:e}=wp.data;if("yes"!==e("core/editor").getEditedPostAttribute("classifai_synthesize_speech"))return;let t=e("core/editor").isSavingPost();const s=e("core/editor").isAutosavingPost(),i=e("core/editor").getCurrentPostId();t=t&&!s,t&&!E&&(E=!0),!t&&E&&((async e=>{const{select:t,dispatch:s}=wp.data,i=`${wpApiSettings.root}classifai/v1/synthesize-speech/${e}`;if(t(g).getIsProcessing())return;s(g).setIsProcessing(!0);const o=await fetch(i,{headers:new Headers({"X-WP-Nonce":wpApiSettings.nonce})});if(200!==o.status)return s(g).setIsProcessing(!1),!1;const a=await o.json();if(a.success)return s(g).setAudioId(a.audio_id),s(g).setIsProcessing(!1),h&&(s(u.store).removeNotice(h),h=""),!0;h=a.code,s("core/notices").createErrorNotice(a.message,{id:h}),s(g).setIsProcessing(!1)})(i),E=!1)})),(0,d.registerPlugin)("classifai-plugin",{render:()=>{const t=(0,r.useSelect)((e=>e("core/editor").getCurrentPostType())),s=(0,r.useSelect)((e=>e("core/editor").getCurrentPostAttribute("status"))),i=(0,r.useSelect)((e=>e("core/editor").getEditedPostAttribute("classifai_post_audio_id"))),o=(0,r.useSelect)((e=>e(g).getAudioId()))||i,n=y&&y.NLUEnabled,l=f&&f.enabled,d=y&&y.supportedPostTypes&&y.supportedPostTypes.includes(t),u=f&&f.supportedPostTypes&&f.supportedPostTypes.includes(t),p=y&&y.supportedPostStatues&&y.supportedPostStatues.includes(s),w=f&&f.supportedPostStatues&&f.supportedPostStatues.includes(s),h=y&&!(y.noPermissions&&1===parseInt(y.noPermissions))&&n&&d&&p,E=f&&!(f.noPermissions&&1===parseInt(f.noPermissions))&&l&&u&&w;return(0,e.createElement)(c.PluginDocumentSettingPanel,{title:(0,a.__)("ClassifAI","classifai"),icon:_,className:"classifai-panel"},(0,e.createElement)(e.Fragment,null,(h||E)&&(0,e.createElement)(e.Fragment,null,(0,e.createElement)(m,null),h&&(0,e.createElement)(P,null))),(0,e.createElement)(b,{audioId:o}))}})})();
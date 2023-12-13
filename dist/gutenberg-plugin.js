(()=>{"use strict";const e=window.React;var t;function s(){return s=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var s=arguments[t];for(var a in s)Object.prototype.hasOwnProperty.call(s,a)&&(e[a]=s[a])}return e},s.apply(this,arguments)}var a=function(a){return e.createElement("svg",s({xmlns:"http://www.w3.org/2000/svg",width:20,height:15,fill:"none",viewBox:"0 0 61 46"},a),t||(t=e.createElement("path",{fill:"#1e1e1e",fillRule:"evenodd",d:"M3.52 0C1.575 0 0 1.584 0 3.538v38.924C0 44.416 1.576 46 3.52 46h53.96c1.944 0 3.52-1.584 3.52-3.538V3.538C61 1.584 59.424 0 57.48 0zm13.189 8.138h4.739L33.58 39.554h-6.056l-3.492-9.04H13.97l-3.453 9.04h-5.96zm2.38 8.563c-.104.34-.211.672-.319.997l-.012.036-2.76 7.406h6.148l-2.745-7.455zm21.227-8.563h12.59v4.015l-3.413.819V34.63l3.413.779v4.024h-12.59V35.41l3.413-.78V12.972l-3.413-.818z",clipRule:"evenodd"})))};const n=window.wp.i18n,{get:i}=lodash,o=({button:e,endpoint:t,callback:s=!1,callbackArgs:a=[],buttonText:o=(0,n.__)("Rescan","classifai"),linkTerms:r=!0})=>{const l=e.getAttribute("data-id"),[c]=e.parentNode.getElementsByClassName("spinner"),[d]=e.parentNode.getElementsByClassName("error"),u=`${t}${l}`;e.setAttribute("disabled","disabled"),c.style.display="inline-block",c.classList.add("is-active"),d.style.display="none";const p={path:u,data:{linkTerms:r}};wp.apiRequest(p).then((t=>{e.removeAttribute("disabled"),c.style.display="none",c.classList.remove("is-active"),e.textContent=o,s&&s(t,a)}),(t=>{const s=i(t,"responseJSON",{code:"unknown_error",message:(0,n.__)("An unknown error occurred.","classifai")});c.style.display="none",c.classList.remove("is-active"),e.removeAttribute("disabled"),e.textContent=o,d.style.display="inline-block",d.textContent=`Error: ${s.message}`}))},r=window.wp.data,l=window.wp.editPost,c=window.wp.components,d=window.wp.plugins,u=window.wp.element,p={audioId:0,isProcessing:!1},m=(0,r.createReduxStore)("classifai-post-audio",{reducer(e=p,t){switch(t.type){case"SET_AUDIO_ID":return{...e,audioId:t.id};case"SET_PROCESSING_STATUS":return{...e,isProcessing:t.status}}return e},actions:{setAudioId:e=>({type:"SET_AUDIO_ID",id:e}),setIsProcessing:e=>({type:"SET_PROCESSING_STATUS",status:e})},selectors:{getAudioId:e=>e.audioId,getIsProcessing:e=>e.isProcessing}});(0,r.register)(m);const f=window.wp.coreData,g=({onChange:t,query:s})=>{const a=(i=s.contentPostType,(0,r.useSelect)((e=>{const{getTaxonomies:t}=e(f.store);return t({type:i,per_page:-1,context:"view"})}),[i]));var i;const o=s.featureTaxonomies||[],l=s.taxTermsAI||[],[d,p]=(0,u.useState)({});let m=(0,r.useSelect)((e=>{const{getEntityRecords:t}=e(f.store),s={per_page:-1},n=a?.map((({slug:e,name:a})=>{let n=(e=>{const t=e?.reduce(((e,t)=>{const{mapById:s,mapByName:a,names:n}=e;return s[t.id]=t,a[t.name]=t,n.push(t.name),e}),{mapById:{},mapByName:{},names:[]});return{entities:e,...t}})(t("taxonomy",e,s));return"post_tag"===e&&(e="tags"),"category"===e&&(e="categories"),n=((e,t)=>(void 0!==e&&void 0!==e.mapById&&l[t]&&Object.keys(e.mapById).forEach((s=>{l[t].includes(e.mapById[s].id)&&-1===e.mapById[s].name.indexOf("[AI]")&&(e.mapById[s].name="[AI] "+e.mapById[s].name)})),e))(n,e),{slug:e,name:a,terms:n}}));return n}));Object.keys(d).length>0&&(m=d);const g=e=>{const t=m.find((({slug:t})=>t===e));if(!t)return[];let a=s.taxQuery[e]||[];return a=Object.values(a),a.reduce(((e,s)=>{const a=t.terms.mapById[s];if(a){const t=document.createElement("textarea");t.innerHTML=a.name,e.push({id:s,value:t.value})}return e}),[])};return(0,e.createElement)(e.Fragment,null,!!m?.length&&m.map((({slug:a,name:i,terms:r})=>{if(!r?.names?.length||s?.isLoading)return null;let l=!1;if(s.taxTermsAI){if(!o.includes(a))return null;Object.keys(r.mapById).forEach((e=>{-1!==r.mapById[e].name.indexOf("[AI]")&&(l=!0)}))}return(0,e.createElement)(e.Fragment,null,(0,e.createElement)(c.FormTokenField,{key:a,label:i,value:g(a),suggestions:r.names,onChange:(d=a,async e=>{const a=m.find((({slug:e})=>e===d));if(!a)return;let n={};const i=(await Promise.all(e.map((async e=>{const t=((e,t)=>{const s=t?.id||e[t]?.id;if(s)return s;const a=t.toLocaleLowerCase();for(const t in e)if(t.toLocaleLowerCase()===a)return e[t].id})(a.terms.mapByName,e);if(t)return{[e.value]:t};const s={path:`/wp/v2/${d}`,data:{name:e,taxonomy:d},method:"POST"},i=await wp.apiRequest(s).catch((e=>(console.log("Error",e),null)));if(i&&i.id){n={id:i.id,name:e,taxonomy:d,count:0,description:""};const t=m.map((e=>{if(e.slug===d){const t={...e.terms,entitites:[...e.terms.entities,n],mapById:{...e.terms.mapById,[n.id]:n},mapByName:{...e.terms.mapByName,[n.name]:n},names:[...e.terms.names,n.name]};return{...e,terms:t}}return e}));return p(t),{[e]:i.id}}return null})))).reduce(((e,t)=>t?{...e,...t}:e),{}),o={...s.taxQuery,[d]:i};t({taxQuery:o})})}),!l&&(0,e.createElement)(e.Fragment,null,(0,e.createElement)("p",{style:{color:"#cc1818"},key:a},(0,n.sprintf)(/* translators: %s: taxonomy name */
(0,n.__)("ClassifAI has no new recommendations for %s","classifai"),i))),(0,e.createElement)("hr",null));var d})))},y=({children:t})=>{const s=[(0,n.__)("Suggestion:"),(0,e.createElement)("span",{className:"editor-post-publish-panel__link",key:"label"},(0,n.__)("Classify Post","classifai"))];return(0,e.createElement)(l.PluginPrePublishPanel,{title:s,icon:"aside",initialOpen:!0},t)};class _ extends u.Component{render(){return this.props.popupOpened?null:(0,e.createElement)(y,null,this.props.children)}}const h=(0,r.withSelect)((e=>({isPublishPanelOpen:e("core/edit-post").isPublishSidebarOpened()})))(_),E=({feature:t})=>t&&ClassifAI?.opt_out_enabled_features?.includes(t)?(0,e.createElement)(c.Button,{href:ClassifAI?.profile_url,variant:"link",className:"classifai-disable-feature-link",target:"_blank",rel:"noopener noreferrer",label:(0,n.__)("Opt out of using this ClassifAI feature","classifai"),text:(0,n.__)("Disable this ClassifAI feature","classifai")}):null,{classifaiEmbeddingData:b,classifaiPostData:w,classifaiTTSEnabled:P}=(window.wp.compose,window.wp.apiFetch,window.wp.url,window),v=()=>(0,e.createElement)(c.Icon,{className:"components-panel__icon",icon:a,size:24}),S=()=>{const t=(0,r.useSelect)((e=>e("core/editor").getEditedPostAttribute("classifai_process_content"))),{editPost:s}=(0,r.useDispatch)("core/editor"),a="yes"===t?"yes":"no";return(0,e.createElement)(c.ToggleControl,{label:(0,n.__)("Automatically tag content on update","classifai"),checked:"yes"===a,onChange:e=>{s({classifai_process_content:e?"yes":"no"})}})},I=()=>{const t=(0,r.useSelect)((e=>e("core/editor").getEditedPostAttribute("classifai_process_content"))),{select:s,dispatch:a}=wp.data,i=s("core/editor").getCurrentPostId(),l=s("core/editor").getCurrentPostType(),d=s("core/editor").getPostTypeLabel()||(0,n.__)("Post","classifai"),[p,m]=(0,u.useState)(!1),[f,y]=(0,u.useState)(!1),[_,b]=(0,u.useState)(!1),[w,P]=(0,u.useState)(!1),v=()=>b(!1),[S,I]=(0,u.useState)([]),[T,C]=(0,u.useState)([]);let[A,x]=(0,u.useState)([]);const k=async(e,t)=>{if(e&&e.terms){e?.feature_taxonomies&&C(e.feature_taxonomies);const t=e.terms,a={},n={},o=s("core").getEntityRecord("postType",l,i);Object.keys(t).forEach((t=>{let s=t;"post_tag"===t&&(s="tags"),"category"===t&&(s="categories");const i=o[s];i&&(n[s]=i);const r=Object.values(e.terms[t]);r&&Object.keys(r).length&&(A=A||{},Object(r).forEach((e=>{n[s]&&(n[s].find((t=>t===e))||(A[s]=A[s]||[],A[s].includes(e)||A[s].push(e)))})),a[s]=r)})),Object.keys(n).forEach((e=>{a[e]?a[e]=a[e].concat(n[e]):a[e]=n[e],a[e]=[...new Set(a[e])]})),I(a),x(A)}t?.openPopup&&(b(!0),P(!0)),m(!1),y(!0)};if("yes"==("no"===t?"no":"yes"))return null;const B=(0,n.__)("Suggest terms & tags","classifai");let O=Object.entries(S||{}).reduce(((e,[t,s])=>(e[t]=s,e)),{});O.taxQuery&&(O=O.taxQuery);const N=(0,e.createElement)(e.Fragment,null,(0,e.createElement)(g,{onChange:e=>{I(e)},query:{contentPostType:l,featureTaxonomies:T,taxQuery:O,taxTermsAI:A||{},isLoading:p}}),(0,e.createElement)("div",{className:"classifai-modal__footer"},(0,e.createElement)("div",{className:"classifai-modal__notes"},(0,n.sprintf)(/* translators: %s is post type label */
(0,n.__)("Note that the lists above include any pre-existing terms from this %s.","classifai"),d),(0,e.createElement)("br",null),(0,n.__)('AI recommendations saved to this post will not include the "[AI]" text.',"classifai")),(0,e.createElement)(c.Button,{variant:"secondary",onClick:()=>(async e=>{const t=Object.entries(e),o=Object.fromEntries(t.map((([e,t])=>"object"==typeof t?[e,Object.values(t)]:[e,t])));await a("core").editEntityRecord("postType",l,i,o),await s("core/editor").isEditedPostDirty()||await a("core").saveEditedEntityRecord("postType",l,i),a("core/notices").createSuccessNotice((0,n.sprintf)(/** translators: %s is post type label. */
(0,n.__)("%s classified successfully.","classifai"),d),{type:"snackbar"}),v()})(O)},(0,n.__)("Save","classifai"))),(0,e.createElement)(E,{feature:"content_classification"}));return(0,e.createElement)("div",{id:"classify-post-component"},_&&(0,e.createElement)(c.Modal,{title:(0,n.__)("Confirm Post Classification","classifai"),onRequestClose:v,isFullScreen:!1,className:"classify-modal"},N),(0,e.createElement)(c.Button,{variant:"secondary","data-id":i,onClick:e=>{o({button:e.target,endpoint:"/classifai/v1/generate-tags/",callback:k,callbackArgs:{openPopup:!0},buttonText:B,linkTerms:!1})}},B),(0,e.createElement)("span",{className:"spinner",style:{display:"none",float:"none"}}),(0,e.createElement)("span",{className:"error",style:{display:"none",color:"#bc0b0b",padding:"5px"}}),(0,e.createElement)(h,{popupOpened:w},!f&&(0,e.createElement)(e.Fragment,null,(0,e.createElement)(c.Button,{variant:"secondary","data-id":i,onClick:e=>{o({button:e.target,endpoint:"/classifai/v1/generate-tags/",callback:k,buttonText:B,linkTerms:!1})}},B),(0,e.createElement)("span",{className:"spinner classify",style:{float:"none",display:"none"}}),(0,e.createElement)("span",{className:"error",style:{display:"none",color:"#bc0b0b",padding:"5px"}})),f&&N))},T=()=>{const[t,s]=(0,u.useState)(!1),[a,i]=(0,u.useState)((new Date).getTime()),o=(0,r.useSelect)((e=>e("core/editor").getEditedPostAttribute("classifai_synthesize_speech"))),l=(0,r.useSelect)((e=>e("core/editor").getEditedPostAttribute("classifai_display_generated_audio"))),d=(0,r.useSelect)((e=>void 0!==e("core/editor").getPostTypeLabel&&e("core/editor").getPostTypeLabel()||(0,n.__)("Post","classifai"))),p=(0,r.useSelect)((e=>e(m).getIsProcessing())),f=(0,r.useSelect)((e=>e("core/editor").getEditedPostAttribute("classifai_post_audio_id"))),g=(0,r.useSelect)((e=>e(m).getAudioId()))||f,y=(0,r.useSelect)((e=>e("core").getEntityRecords("postType","attachment",{include:[g]}))),_=y&&y.length>0&&y[0].source_url,h=(0,u.useRef)(!1),E=(0,u.useRef)(!1),{isSavingPost:b}=(0,r.useSelect)((e=>({isSavingPost:e("core/editor").isSavingPost()}))),{isAutosavingPost:w}=(0,r.useSelect)((e=>({isSavingPost:e("core/editor").isAutosavingPost()})));(0,u.useEffect)((()=>{const e=document.getElementById("classifai-audio-preview");e&&(t?e.play():e.pause())}),[t]),(0,u.useEffect)((()=>{p&&(h.current=!0),h.current&&!p&&i((new Date).getTime())}),[p]),(0,u.useEffect)((()=>{!b||w||E.current||(E.current=!0,o&&wp.data.dispatch(m).setIsProcessing(!0)),b||w||!E.current||(E.current=!1,wp.data.dispatch(m).setIsProcessing(!1))}),[b,w,o]);const P=`${_}?ver=${a}`;let v="controls-play";return p?v="format-audio":t&&(v="controls-pause"),(0,e.createElement)(e.Fragment,null,(0,e.createElement)(c.ToggleControl,{label:(0,n.__)("Enable audio generation","classifai"),help:(0,n.sprintf)(/** translators: %s is post type label. */
(0,n.__)("ClassifAI will generate audio for this %s when it is published or updated.","classifai"),d),checked:o,onChange:e=>{wp.data.dispatch("core/editor").editPost({classifai_synthesize_speech:e})},disabled:p,isBusy:p}),_&&(0,e.createElement)(e.Fragment,null,(0,e.createElement)(c.ToggleControl,{label:(0,n.__)("Display audio controls","classifai"),help:(0,n.__)("Controls the display of the audio player on the front-end.","classifai"),checked:l,onChange:e=>{wp.data.dispatch("core/editor").editPost({classifai_display_generated_audio:e})},disabled:p,isBusy:p}),(0,e.createElement)(c.BaseControl,{id:"classifai-audio-preview-controls",help:p?"":(0,n.__)("Preview the generated audio.","classifai")},(0,e.createElement)(c.Button,{id:"classifai-audio-controls__preview-btn",icon:(0,e.createElement)(c.Icon,{icon:v}),variant:"secondary",onClick:()=>s(!t),disabled:p,isBusy:p},p?(0,n.__)("Generating audio..","classifai"):(0,n.__)("Preview","classifai")))),_&&(0,e.createElement)("audio",{id:"classifai-audio-preview",src:P,onEnded:()=>s(!1)}))};(0,d.registerPlugin)("classifai-plugin",{render:()=>{const t=(0,r.useSelect)((e=>e("core/editor").getCurrentPostType())),s=(0,r.useSelect)((e=>e("core/editor").getCurrentPostAttribute("status"))),a=w&&w.NLUEnabled,i=b&&b.enabled,o=w&&w.supportedPostTypes&&w.supportedPostTypes.includes(t),c=b&&b.supportedPostTypes&&b.supportedPostTypes.includes(t),d=w&&w.supportedPostStatues&&w.supportedPostStatues.includes(s),u=b&&b.supportedPostStatues&&b.supportedPostStatues.includes(s),p=w&&!(w.noPermissions&&1===parseInt(w.noPermissions))&&a&&o&&d,m=b&&!(b.noPermissions&&1===parseInt(b.noPermissions))&&i&&c&&u;return(0,e.createElement)(l.PluginDocumentSettingPanel,{title:(0,n.__)("ClassifAI","classifai"),icon:v,className:"classifai-panel"},(0,e.createElement)(e.Fragment,null,(p||m)&&(0,e.createElement)(e.Fragment,null,(0,e.createElement)(S,null),p&&(0,e.createElement)(I,null)),P&&(0,e.createElement)(T,null)))}})})();
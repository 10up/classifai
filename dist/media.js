(()=>{"use strict";const t=window.wp.i18n,{get:e}=lodash,a=a=>{let{button:n,endpoint:s,callback:i=!1,buttonText:d=(0,t.__)("Rescan","classifai")}=a;const c=n.getAttribute("data-id"),[l]=n.parentNode.getElementsByClassName("spinner"),[o]=n.parentNode.getElementsByClassName("error"),r=`${s}${c}`;n.setAttribute("disabled","disabled"),l.style.display="inline-block",l.classList.add("is-active"),o.style.display="none",wp.apiRequest({path:r}).then((t=>{n.removeAttribute("disabled"),l.style.display="none",l.classList.remove("is-active"),n.textContent=d,i&&i(t)}),(a=>{const s=e(a,"responseJSON",{code:"unknown_error",message:(0,t.__)("An unknown error occurred.","classifai")});l.style.display="none",l.classList.remove("is-active"),n.removeAttribute("disabled"),n.textContent=d,o.style.display="inline-block",o.textContent=`Error: ${s.message}`}))};!function(e){const n=()=>{const e=document.getElementById("classifai-rescan-alt-tags"),n=document.getElementById("classifai-rescan-image-tags"),s=document.getElementById("classifai-rescan-ocr"),i=document.getElementById("classifai-rescan-smart-crop"),d=document.getElementById("classifai-rescan-pdf");e&&e.addEventListener("click",(t=>a({button:t.target,endpoint:"/classifai/v1/alt-tags/",callback:t=>{const{enabledAltTextFields:e}=classifaiMediaVars;if(t){if(e.includes("alt")){var a;const e=null!==(a=document.getElementById("attachment-details-two-column-alt-text"))&&void 0!==a?a:document.getElementById("attachment-details-alt-text");e&&(e.value=t)}if(e.includes("caption")){var n;const e=null!==(n=document.getElementById("attachment-details-two-column-caption"))&&void 0!==n?n:document.getElementById("attachment-details-caption");e&&(e.value=t)}if(e.includes("description")){var s;const e=null!==(s=document.getElementById("attachment-details-two-column-description"))&&void 0!==s?s:document.getElementById("attachment-details-description");e&&(e.value=t)}}}}))),n&&n.addEventListener("click",(t=>a({button:t.target,endpoint:"/classifai/v1/image-tags/"}))),s&&s.addEventListener("click",(t=>a({button:t.target,endpoint:"/classifai/v1/ocr/",callback:t=>{if(t){var e;const a=null!==(e=document.getElementById("attachment-details-two-column-description"))&&void 0!==e?e:document.getElementById("attachment-details-description");a&&(a.value=t)}}}))),i&&i.addEventListener("click",(t=>a({button:t.target,endpoint:"/classifai/v1/smart-crop/"}))),d&&d.addEventListener("click",(e=>{const a=e.target.getAttribute("data-id");wp.apiRequest({path:`/classifai/v1/read-pdf/${a}`}),e.target.setAttribute("disabled","disabled"),e.target.textContent=(0,t.__)("Read API requested!","classifai")}))},s=()=>{const a=document.getElementById("classifai-rescan-pdf");if(!a)return;const n=a.getAttribute("data-id");e.ajax({url:ajaxurl,type:"POST",data:{action:"classifai_get_read_status",attachment_id:n,nonce:ClassifAI.ajax_nonce},success:e=>{e?.success&&(e?.data?.running?(a.setAttribute("disabled","disabled"),a.textContent=(0,t.__)("In progress!","classifai")):e?.data?.read&&(a.textContent=(0,t.__)("Rescan","classifai")))}})};e(document).ready((function(){wp.media&&wp.media.view.Modal.prototype.on("open",(function(){wp.media.frame.on("selection:toggle",n),wp.media.frame.on("selection:toggle",s)})),wp.media.frame&&(wp.media.frame.on("edit:attachment",n),wp.media.frame.on("edit:attachment",s)),wp.Uploader&&wp.Uploader.queue&&wp.Uploader.queue.on("reset",n)}))}(jQuery)})();
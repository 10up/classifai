(()=>{const{subscribe:e,select:t,dispatch:r}=wp.data;let s=!1,o=!1;e((()=>{if(!1===s&&(s=!0===wp.data.select("core/editor").isSavingPost()),s&&!1===wp.data.select("core/editor").isSavingPost()&&!1===o){const e=t("core/editor").getCurrentPostAttribute("meta");if(e&&e._classifai_error){o=!0;const t=JSON.parse(e._classifai_error);r("core/notices").createErrorNotice(`Failed to classify content with the IBM Watson NLU API. Error: ${t.code} - ${t.message}`),s=!1,o=!1}}}))})();
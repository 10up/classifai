(()=>{"use strict";var e={n:t=>{var a=t&&t.__esModule?()=>t.default:()=>t;return e.d(a,{a}),a},d:(t,a)=>{for(var r in a)e.o(a,r)&&!e.o(t,r)&&Object.defineProperty(t,r,{enumerable:!0,get:a[r]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t)};const t=window.wp.apiFetch;var a=e.n(t);const r=window.wp.data,i=window.wp.i18n,s=window.wp.url,{classifaiDalleData:n}=window;(async e=>new Promise((e=>{const t=(0,r.subscribe)((()=>{((0,r.select)("core/edit-post")?.isInserterOpened()||(0,r.select)("core/edit-site")?.isInserterOpened()||(0,r.select)("core/edit-widgets")?.isInserterOpened?.())&&(t(),e())}))})))().then((()=>(0,r.dispatch)("core/block-editor")?.registerInserterMediaCategory?.(d())));const o=(e,t=250)=>{let a;return(...r)=>(clearTimeout(a),new Promise((i=>{a=setTimeout((()=>{i(e.apply(void 0,r))}),t)})))},c=async({search:e=""})=>e?await a()({path:(0,s.addQueryArgs)(n.endpoint,{prompt:e,format:"b64_json"}),method:"GET"}).then((t=>t.map((t=>({title:e,url:`data:image/png;base64,${t.url}`,previewUrl:`data:image/png;base64,${t.url}`,id:void 0,alt:e,caption:n.caption}))))).catch((()=>[])):[],d=()=>({name:"classifai-generate-image",labels:{name:n.tabText,search_items:(0,i.__)("Enter a prompt","classifai")},mediaType:"image",fetch:o(c,2500),isExternalResource:!0})})();
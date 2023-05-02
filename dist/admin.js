(()=>{"use strict";function e(e){if(null==e)return window;if("[object Window]"!==e.toString()){var t=e.ownerDocument;return t&&t.defaultView||window}return e}function t(t){return t instanceof e(t).Element||t instanceof Element}function n(t){return t instanceof e(t).HTMLElement||t instanceof HTMLElement}function r(t){return"undefined"!=typeof ShadowRoot&&(t instanceof e(t).ShadowRoot||t instanceof ShadowRoot)}var i=Math.max,o=Math.min,a=Math.round;function s(){var e=navigator.userAgentData;return null!=e&&e.brands&&Array.isArray(e.brands)?e.brands.map((function(e){return e.brand+"/"+e.version})).join(" "):navigator.userAgent}function c(){return!/^((?!chrome|android).)*safari/i.test(s())}function u(r,i,o){void 0===i&&(i=!1),void 0===o&&(o=!1);var s=r.getBoundingClientRect(),u=1,f=1;i&&n(r)&&(u=r.offsetWidth>0&&a(s.width)/r.offsetWidth||1,f=r.offsetHeight>0&&a(s.height)/r.offsetHeight||1);var p=(t(r)?e(r):window).visualViewport,l=!c()&&o,d=(s.left+(l&&p?p.offsetLeft:0))/u,m=(s.top+(l&&p?p.offsetTop:0))/f,v=s.width/u,h=s.height/f;return{width:v,height:h,top:m,right:d+v,bottom:m+h,left:d,x:d,y:m}}function f(t){var n=e(t);return{scrollLeft:n.pageXOffset,scrollTop:n.pageYOffset}}function p(e){return e?(e.nodeName||"").toLowerCase():null}function l(e){return((t(e)?e.ownerDocument:e.document)||window.document).documentElement}function d(e){return u(l(e)).left+f(e).scrollLeft}function m(t){return e(t).getComputedStyle(t)}function v(e){var t=m(e),n=t.overflow,r=t.overflowX,i=t.overflowY;return/auto|scroll|overlay|hidden/.test(n+i+r)}function h(t,r,i){void 0===i&&(i=!1);var o,s,c=n(r),m=n(r)&&function(e){var t=e.getBoundingClientRect(),n=a(t.width)/e.offsetWidth||1,r=a(t.height)/e.offsetHeight||1;return 1!==n||1!==r}(r),h=l(r),g=u(t,m,i),y={scrollLeft:0,scrollTop:0},b={x:0,y:0};return(c||!c&&!i)&&(("body"!==p(r)||v(h))&&(y=(o=r)!==e(o)&&n(o)?{scrollLeft:(s=o).scrollLeft,scrollTop:s.scrollTop}:f(o)),n(r)?((b=u(r,!0)).x+=r.clientLeft,b.y+=r.clientTop):h&&(b.x=d(h))),{x:g.left+y.scrollLeft-b.x,y:g.top+y.scrollTop-b.y,width:g.width,height:g.height}}function g(e){var t=u(e),n=e.offsetWidth,r=e.offsetHeight;return Math.abs(t.width-n)<=1&&(n=t.width),Math.abs(t.height-r)<=1&&(r=t.height),{x:e.offsetLeft,y:e.offsetTop,width:n,height:r}}function y(e){return"html"===p(e)?e:e.assignedSlot||e.parentNode||(r(e)?e.host:null)||l(e)}function b(e){return["html","body","#document"].indexOf(p(e))>=0?e.ownerDocument.body:n(e)&&v(e)?e:b(y(e))}function w(t,n){var r;void 0===n&&(n=[]);var i=b(t),o=i===(null==(r=t.ownerDocument)?void 0:r.body),a=e(i),s=o?[a].concat(a.visualViewport||[],v(i)?i:[]):i,c=n.concat(s);return o?c:c.concat(w(y(s)))}function x(e){return["table","td","th"].indexOf(p(e))>=0}function O(e){return n(e)&&"fixed"!==m(e).position?e.offsetParent:null}function E(t){for(var i=e(t),o=O(t);o&&x(o)&&"static"===m(o).position;)o=O(o);return o&&("html"===p(o)||"body"===p(o)&&"static"===m(o).position)?i:o||function(e){var t=/firefox/i.test(s());if(/Trident/i.test(s())&&n(e)&&"fixed"===m(e).position)return null;var i=y(e);for(r(i)&&(i=i.host);n(i)&&["html","body"].indexOf(p(i))<0;){var o=m(i);if("none"!==o.transform||"none"!==o.perspective||"paint"===o.contain||-1!==["transform","perspective"].indexOf(o.willChange)||t&&"filter"===o.willChange||t&&o.filter&&"none"!==o.filter)return i;i=i.parentNode}return null}(t)||i}var A="top",T="bottom",L="right",D="left",j="auto",k=[A,T,L,D],C="start",M="end",B="viewport",I="popper",H=k.reduce((function(e,t){return e.concat([t+"-"+C,t+"-"+M])}),[]),V=[].concat(k,[j]).reduce((function(e,t){return e.concat([t,t+"-"+C,t+"-"+M])}),[]),S=["beforeRead","read","afterRead","beforeMain","main","afterMain","beforeWrite","write","afterWrite"];function W(e){var t=new Map,n=new Set,r=[];function i(e){n.add(e.name),[].concat(e.requires||[],e.requiresIfExists||[]).forEach((function(e){if(!n.has(e)){var r=t.get(e);r&&i(r)}})),r.push(e)}return e.forEach((function(e){t.set(e.name,e)})),e.forEach((function(e){n.has(e.name)||i(e)})),r}var P={placement:"bottom",modifiers:[],strategy:"absolute"};function R(){for(var e=arguments.length,t=new Array(e),n=0;n<e;n++)t[n]=arguments[n];return!t.some((function(e){return!(e&&"function"==typeof e.getBoundingClientRect)}))}function N(e){void 0===e&&(e={});var n=e,r=n.defaultModifiers,i=void 0===r?[]:r,o=n.defaultOptions,a=void 0===o?P:o;return function(e,n,r){void 0===r&&(r=a);var o,s,c={placement:"bottom",orderedModifiers:[],options:Object.assign({},P,a),modifiersData:{},elements:{reference:e,popper:n},attributes:{},styles:{}},u=[],f=!1,p={state:c,setOptions:function(r){var o="function"==typeof r?r(c.options):r;l(),c.options=Object.assign({},a,c.options,o),c.scrollParents={reference:t(e)?w(e):e.contextElement?w(e.contextElement):[],popper:w(n)};var s,f,d=function(e){var t=W(e);return S.reduce((function(e,n){return e.concat(t.filter((function(e){return e.phase===n})))}),[])}((s=[].concat(i,c.options.modifiers),f=s.reduce((function(e,t){var n=e[t.name];return e[t.name]=n?Object.assign({},n,t,{options:Object.assign({},n.options,t.options),data:Object.assign({},n.data,t.data)}):t,e}),{}),Object.keys(f).map((function(e){return f[e]}))));return c.orderedModifiers=d.filter((function(e){return e.enabled})),c.orderedModifiers.forEach((function(e){var t=e.name,n=e.options,r=void 0===n?{}:n,i=e.effect;if("function"==typeof i){var o=i({state:c,name:t,instance:p,options:r});u.push(o||function(){})}})),p.update()},forceUpdate:function(){if(!f){var e=c.elements,t=e.reference,n=e.popper;if(R(t,n)){c.rects={reference:h(t,E(n),"fixed"===c.options.strategy),popper:g(n)},c.reset=!1,c.placement=c.options.placement,c.orderedModifiers.forEach((function(e){return c.modifiersData[e.name]=Object.assign({},e.data)}));for(var r=0;r<c.orderedModifiers.length;r++)if(!0!==c.reset){var i=c.orderedModifiers[r],o=i.fn,a=i.options,s=void 0===a?{}:a,u=i.name;"function"==typeof o&&(c=o({state:c,options:s,name:u,instance:p})||c)}else c.reset=!1,r=-1}}},update:(o=function(){return new Promise((function(e){p.forceUpdate(),e(c)}))},function(){return s||(s=new Promise((function(e){Promise.resolve().then((function(){s=void 0,e(o())}))}))),s}),destroy:function(){l(),f=!0}};if(!R(e,n))return p;function l(){u.forEach((function(e){return e()})),u=[]}return p.setOptions(r).then((function(e){!f&&r.onFirstUpdate&&r.onFirstUpdate(e)})),p}}var _={passive:!0};function q(e){return e.split("-")[0]}function U(e){return e.split("-")[1]}function F(e){return["top","bottom"].indexOf(e)>=0?"x":"y"}function z(e){var t,n=e.reference,r=e.element,i=e.placement,o=i?q(i):null,a=i?U(i):null,s=n.x+n.width/2-r.width/2,c=n.y+n.height/2-r.height/2;switch(o){case A:t={x:s,y:n.y-r.height};break;case T:t={x:s,y:n.y+n.height};break;case L:t={x:n.x+n.width,y:c};break;case D:t={x:n.x-r.width,y:c};break;default:t={x:n.x,y:n.y}}var u=o?F(o):null;if(null!=u){var f="y"===u?"height":"width";switch(a){case C:t[u]=t[u]-(n[f]/2-r[f]/2);break;case M:t[u]=t[u]+(n[f]/2-r[f]/2)}}return t}var $={top:"auto",right:"auto",bottom:"auto",left:"auto"};function X(t){var n,r=t.popper,i=t.popperRect,o=t.placement,s=t.variation,c=t.offsets,u=t.position,f=t.gpuAcceleration,p=t.adaptive,d=t.roundOffsets,v=t.isFixed,h=c.x,g=void 0===h?0:h,y=c.y,b=void 0===y?0:y,w="function"==typeof d?d({x:g,y:b}):{x:g,y:b};g=w.x,b=w.y;var x=c.hasOwnProperty("x"),O=c.hasOwnProperty("y"),j=D,k=A,C=window;if(p){var B=E(r),I="clientHeight",H="clientWidth";B===e(r)&&"static"!==m(B=l(r)).position&&"absolute"===u&&(I="scrollHeight",H="scrollWidth"),(o===A||(o===D||o===L)&&s===M)&&(k=T,b-=(v&&B===C&&C.visualViewport?C.visualViewport.height:B[I])-i.height,b*=f?1:-1),o!==D&&(o!==A&&o!==T||s!==M)||(j=L,g-=(v&&B===C&&C.visualViewport?C.visualViewport.width:B[H])-i.width,g*=f?1:-1)}var V,S=Object.assign({position:u},p&&$),W=!0===d?function(e,t){var n=e.x,r=e.y,i=t.devicePixelRatio||1;return{x:a(n*i)/i||0,y:a(r*i)/i||0}}({x:g,y:b},e(r)):{x:g,y:b};return g=W.x,b=W.y,f?Object.assign({},S,((V={})[k]=O?"0":"",V[j]=x?"0":"",V.transform=(C.devicePixelRatio||1)<=1?"translate("+g+"px, "+b+"px)":"translate3d("+g+"px, "+b+"px, 0)",V)):Object.assign({},S,((n={})[k]=O?b+"px":"",n[j]=x?g+"px":"",n.transform="",n))}const Y={name:"applyStyles",enabled:!0,phase:"write",fn:function(e){var t=e.state;Object.keys(t.elements).forEach((function(e){var r=t.styles[e]||{},i=t.attributes[e]||{},o=t.elements[e];n(o)&&p(o)&&(Object.assign(o.style,r),Object.keys(i).forEach((function(e){var t=i[e];!1===t?o.removeAttribute(e):o.setAttribute(e,!0===t?"":t)})))}))},effect:function(e){var t=e.state,r={popper:{position:t.options.strategy,left:"0",top:"0",margin:"0"},arrow:{position:"absolute"},reference:{}};return Object.assign(t.elements.popper.style,r.popper),t.styles=r,t.elements.arrow&&Object.assign(t.elements.arrow.style,r.arrow),function(){Object.keys(t.elements).forEach((function(e){var i=t.elements[e],o=t.attributes[e]||{},a=Object.keys(t.styles.hasOwnProperty(e)?t.styles[e]:r[e]).reduce((function(e,t){return e[t]="",e}),{});n(i)&&p(i)&&(Object.assign(i.style,a),Object.keys(o).forEach((function(e){i.removeAttribute(e)})))}))}},requires:["computeStyles"]};var J={left:"right",right:"left",bottom:"top",top:"bottom"};function G(e){return e.replace(/left|right|bottom|top/g,(function(e){return J[e]}))}var K={start:"end",end:"start"};function Q(e){return e.replace(/start|end/g,(function(e){return K[e]}))}function Z(e,t){var n=t.getRootNode&&t.getRootNode();if(e.contains(t))return!0;if(n&&r(n)){var i=t;do{if(i&&e.isSameNode(i))return!0;i=i.parentNode||i.host}while(i)}return!1}function ee(e){return Object.assign({},e,{left:e.x,top:e.y,right:e.x+e.width,bottom:e.y+e.height})}function te(n,r,o){return r===B?ee(function(t,n){var r=e(t),i=l(t),o=r.visualViewport,a=i.clientWidth,s=i.clientHeight,u=0,f=0;if(o){a=o.width,s=o.height;var p=c();(p||!p&&"fixed"===n)&&(u=o.offsetLeft,f=o.offsetTop)}return{width:a,height:s,x:u+d(t),y:f}}(n,o)):t(r)?function(e,t){var n=u(e,!1,"fixed"===t);return n.top=n.top+e.clientTop,n.left=n.left+e.clientLeft,n.bottom=n.top+e.clientHeight,n.right=n.left+e.clientWidth,n.width=e.clientWidth,n.height=e.clientHeight,n.x=n.left,n.y=n.top,n}(r,o):ee(function(e){var t,n=l(e),r=f(e),o=null==(t=e.ownerDocument)?void 0:t.body,a=i(n.scrollWidth,n.clientWidth,o?o.scrollWidth:0,o?o.clientWidth:0),s=i(n.scrollHeight,n.clientHeight,o?o.scrollHeight:0,o?o.clientHeight:0),c=-r.scrollLeft+d(e),u=-r.scrollTop;return"rtl"===m(o||n).direction&&(c+=i(n.clientWidth,o?o.clientWidth:0)-a),{width:a,height:s,x:c,y:u}}(l(n)))}function ne(e){return Object.assign({},{top:0,right:0,bottom:0,left:0},e)}function re(e,t){return t.reduce((function(t,n){return t[n]=e,t}),{})}function ie(e,r){void 0===r&&(r={});var a=r,s=a.placement,c=void 0===s?e.placement:s,f=a.strategy,d=void 0===f?e.strategy:f,v=a.boundary,h=void 0===v?"clippingParents":v,g=a.rootBoundary,b=void 0===g?B:g,x=a.elementContext,O=void 0===x?I:x,D=a.altBoundary,j=void 0!==D&&D,C=a.padding,M=void 0===C?0:C,H=ne("number"!=typeof M?M:re(M,k)),V=O===I?"reference":I,S=e.rects.popper,W=e.elements[j?V:O],P=function(e,r,a,s){var c="clippingParents"===r?function(e){var r=w(y(e)),i=["absolute","fixed"].indexOf(m(e).position)>=0&&n(e)?E(e):e;return t(i)?r.filter((function(e){return t(e)&&Z(e,i)&&"body"!==p(e)})):[]}(e):[].concat(r),u=[].concat(c,[a]),f=u[0],l=u.reduce((function(t,n){var r=te(e,n,s);return t.top=i(r.top,t.top),t.right=o(r.right,t.right),t.bottom=o(r.bottom,t.bottom),t.left=i(r.left,t.left),t}),te(e,f,s));return l.width=l.right-l.left,l.height=l.bottom-l.top,l.x=l.left,l.y=l.top,l}(t(W)?W:W.contextElement||l(e.elements.popper),h,b,d),R=u(e.elements.reference),N=z({reference:R,element:S,strategy:"absolute",placement:c}),_=ee(Object.assign({},S,N)),q=O===I?_:R,U={top:P.top-q.top+H.top,bottom:q.bottom-P.bottom+H.bottom,left:P.left-q.left+H.left,right:q.right-P.right+H.right},F=e.modifiersData.offset;if(O===I&&F){var $=F[c];Object.keys(U).forEach((function(e){var t=[L,T].indexOf(e)>=0?1:-1,n=[A,T].indexOf(e)>=0?"y":"x";U[e]+=$[n]*t}))}return U}function oe(e,t,n){return i(e,o(t,n))}function ae(e,t,n){return void 0===n&&(n={x:0,y:0}),{top:e.top-t.height-n.y,right:e.right-t.width+n.x,bottom:e.bottom-t.height+n.y,left:e.left-t.width-n.x}}function se(e){return[A,L,T,D].some((function(t){return e[t]>=0}))}var ce=N({defaultModifiers:[{name:"eventListeners",enabled:!0,phase:"write",fn:function(){},effect:function(t){var n=t.state,r=t.instance,i=t.options,o=i.scroll,a=void 0===o||o,s=i.resize,c=void 0===s||s,u=e(n.elements.popper),f=[].concat(n.scrollParents.reference,n.scrollParents.popper);return a&&f.forEach((function(e){e.addEventListener("scroll",r.update,_)})),c&&u.addEventListener("resize",r.update,_),function(){a&&f.forEach((function(e){e.removeEventListener("scroll",r.update,_)})),c&&u.removeEventListener("resize",r.update,_)}},data:{}},{name:"popperOffsets",enabled:!0,phase:"read",fn:function(e){var t=e.state,n=e.name;t.modifiersData[n]=z({reference:t.rects.reference,element:t.rects.popper,strategy:"absolute",placement:t.placement})},data:{}},{name:"computeStyles",enabled:!0,phase:"beforeWrite",fn:function(e){var t=e.state,n=e.options,r=n.gpuAcceleration,i=void 0===r||r,o=n.adaptive,a=void 0===o||o,s=n.roundOffsets,c=void 0===s||s,u={placement:q(t.placement),variation:U(t.placement),popper:t.elements.popper,popperRect:t.rects.popper,gpuAcceleration:i,isFixed:"fixed"===t.options.strategy};null!=t.modifiersData.popperOffsets&&(t.styles.popper=Object.assign({},t.styles.popper,X(Object.assign({},u,{offsets:t.modifiersData.popperOffsets,position:t.options.strategy,adaptive:a,roundOffsets:c})))),null!=t.modifiersData.arrow&&(t.styles.arrow=Object.assign({},t.styles.arrow,X(Object.assign({},u,{offsets:t.modifiersData.arrow,position:"absolute",adaptive:!1,roundOffsets:c})))),t.attributes.popper=Object.assign({},t.attributes.popper,{"data-popper-placement":t.placement})},data:{}},Y,{name:"offset",enabled:!0,phase:"main",requires:["popperOffsets"],fn:function(e){var t=e.state,n=e.options,r=e.name,i=n.offset,o=void 0===i?[0,0]:i,a=V.reduce((function(e,n){return e[n]=function(e,t,n){var r=q(e),i=[D,A].indexOf(r)>=0?-1:1,o="function"==typeof n?n(Object.assign({},t,{placement:e})):n,a=o[0],s=o[1];return a=a||0,s=(s||0)*i,[D,L].indexOf(r)>=0?{x:s,y:a}:{x:a,y:s}}(n,t.rects,o),e}),{}),s=a[t.placement],c=s.x,u=s.y;null!=t.modifiersData.popperOffsets&&(t.modifiersData.popperOffsets.x+=c,t.modifiersData.popperOffsets.y+=u),t.modifiersData[r]=a}},{name:"flip",enabled:!0,phase:"main",fn:function(e){var t=e.state,n=e.options,r=e.name;if(!t.modifiersData[r]._skip){for(var i=n.mainAxis,o=void 0===i||i,a=n.altAxis,s=void 0===a||a,c=n.fallbackPlacements,u=n.padding,f=n.boundary,p=n.rootBoundary,l=n.altBoundary,d=n.flipVariations,m=void 0===d||d,v=n.allowedAutoPlacements,h=t.options.placement,g=q(h),y=c||(g!==h&&m?function(e){if(q(e)===j)return[];var t=G(e);return[Q(e),t,Q(t)]}(h):[G(h)]),b=[h].concat(y).reduce((function(e,n){return e.concat(q(n)===j?function(e,t){void 0===t&&(t={});var n=t,r=n.placement,i=n.boundary,o=n.rootBoundary,a=n.padding,s=n.flipVariations,c=n.allowedAutoPlacements,u=void 0===c?V:c,f=U(r),p=f?s?H:H.filter((function(e){return U(e)===f})):k,l=p.filter((function(e){return u.indexOf(e)>=0}));0===l.length&&(l=p);var d=l.reduce((function(t,n){return t[n]=ie(e,{placement:n,boundary:i,rootBoundary:o,padding:a})[q(n)],t}),{});return Object.keys(d).sort((function(e,t){return d[e]-d[t]}))}(t,{placement:n,boundary:f,rootBoundary:p,padding:u,flipVariations:m,allowedAutoPlacements:v}):n)}),[]),w=t.rects.reference,x=t.rects.popper,O=new Map,E=!0,M=b[0],B=0;B<b.length;B++){var I=b[B],S=q(I),W=U(I)===C,P=[A,T].indexOf(S)>=0,R=P?"width":"height",N=ie(t,{placement:I,boundary:f,rootBoundary:p,altBoundary:l,padding:u}),_=P?W?L:D:W?T:A;w[R]>x[R]&&(_=G(_));var F=G(_),z=[];if(o&&z.push(N[S]<=0),s&&z.push(N[_]<=0,N[F]<=0),z.every((function(e){return e}))){M=I,E=!1;break}O.set(I,z)}if(E)for(var $=function(e){var t=b.find((function(t){var n=O.get(t);if(n)return n.slice(0,e).every((function(e){return e}))}));if(t)return M=t,"break"},X=m?3:1;X>0&&"break"!==$(X);X--);t.placement!==M&&(t.modifiersData[r]._skip=!0,t.placement=M,t.reset=!0)}},requiresIfExists:["offset"],data:{_skip:!1}},{name:"preventOverflow",enabled:!0,phase:"main",fn:function(e){var t=e.state,n=e.options,r=e.name,a=n.mainAxis,s=void 0===a||a,c=n.altAxis,u=void 0!==c&&c,f=n.boundary,p=n.rootBoundary,l=n.altBoundary,d=n.padding,m=n.tether,v=void 0===m||m,h=n.tetherOffset,y=void 0===h?0:h,b=ie(t,{boundary:f,rootBoundary:p,padding:d,altBoundary:l}),w=q(t.placement),x=U(t.placement),O=!x,j=F(w),k="x"===j?"y":"x",M=t.modifiersData.popperOffsets,B=t.rects.reference,I=t.rects.popper,H="function"==typeof y?y(Object.assign({},t.rects,{placement:t.placement})):y,V="number"==typeof H?{mainAxis:H,altAxis:H}:Object.assign({mainAxis:0,altAxis:0},H),S=t.modifiersData.offset?t.modifiersData.offset[t.placement]:null,W={x:0,y:0};if(M){if(s){var P,R="y"===j?A:D,N="y"===j?T:L,_="y"===j?"height":"width",z=M[j],$=z+b[R],X=z-b[N],Y=v?-I[_]/2:0,J=x===C?B[_]:I[_],G=x===C?-I[_]:-B[_],K=t.elements.arrow,Q=v&&K?g(K):{width:0,height:0},Z=t.modifiersData["arrow#persistent"]?t.modifiersData["arrow#persistent"].padding:{top:0,right:0,bottom:0,left:0},ee=Z[R],te=Z[N],ne=oe(0,B[_],Q[_]),re=O?B[_]/2-Y-ne-ee-V.mainAxis:J-ne-ee-V.mainAxis,ae=O?-B[_]/2+Y+ne+te+V.mainAxis:G+ne+te+V.mainAxis,se=t.elements.arrow&&E(t.elements.arrow),ce=se?"y"===j?se.clientTop||0:se.clientLeft||0:0,ue=null!=(P=null==S?void 0:S[j])?P:0,fe=z+ae-ue,pe=oe(v?o($,z+re-ue-ce):$,z,v?i(X,fe):X);M[j]=pe,W[j]=pe-z}if(u){var le,de="x"===j?A:D,me="x"===j?T:L,ve=M[k],he="y"===k?"height":"width",ge=ve+b[de],ye=ve-b[me],be=-1!==[A,D].indexOf(w),we=null!=(le=null==S?void 0:S[k])?le:0,xe=be?ge:ve-B[he]-I[he]-we+V.altAxis,Oe=be?ve+B[he]+I[he]-we-V.altAxis:ye,Ee=v&&be?function(e,t,n){var r=oe(e,t,n);return r>n?n:r}(xe,ve,Oe):oe(v?xe:ge,ve,v?Oe:ye);M[k]=Ee,W[k]=Ee-ve}t.modifiersData[r]=W}},requiresIfExists:["offset"]},{name:"arrow",enabled:!0,phase:"main",fn:function(e){var t,n=e.state,r=e.name,i=e.options,o=n.elements.arrow,a=n.modifiersData.popperOffsets,s=q(n.placement),c=F(s),u=[D,L].indexOf(s)>=0?"height":"width";if(o&&a){var f=function(e,t){return ne("number"!=typeof(e="function"==typeof e?e(Object.assign({},t.rects,{placement:t.placement})):e)?e:re(e,k))}(i.padding,n),p=g(o),l="y"===c?A:D,d="y"===c?T:L,m=n.rects.reference[u]+n.rects.reference[c]-a[c]-n.rects.popper[u],v=a[c]-n.rects.reference[c],h=E(o),y=h?"y"===c?h.clientHeight||0:h.clientWidth||0:0,b=m/2-v/2,w=f[l],x=y-p[u]-f[d],O=y/2-p[u]/2+b,j=oe(w,O,x),C=c;n.modifiersData[r]=((t={})[C]=j,t.centerOffset=j-O,t)}},effect:function(e){var t=e.state,n=e.options.element,r=void 0===n?"[data-popper-arrow]":n;null!=r&&("string"!=typeof r||(r=t.elements.popper.querySelector(r)))&&Z(t.elements.popper,r)&&(t.elements.arrow=r)},requires:["popperOffsets"],requiresIfExists:["preventOverflow"]},{name:"hide",enabled:!0,phase:"main",requiresIfExists:["preventOverflow"],fn:function(e){var t=e.state,n=e.name,r=t.rects.reference,i=t.rects.popper,o=t.modifiersData.preventOverflow,a=ie(t,{elementContext:"reference"}),s=ie(t,{altBoundary:!0}),c=ae(a,r),u=ae(s,i,o),f=se(c),p=se(u);t.modifiersData[n]={referenceClippingOffsets:c,popperEscapeOffsets:u,isReferenceHidden:f,hasPopperEscaped:p},t.attributes.popper=Object.assign({},t.attributes.popper,{"data-popper-reference-hidden":f,"data-popper-escaped":p})}}]}),ue="tippy-content",fe="tippy-arrow",pe="tippy-svg-arrow",le={passive:!0,capture:!0},de=function(){return document.body};function me(e,t,n){if(Array.isArray(e)){var r=e[t];return null==r?Array.isArray(n)?n[t]:n:r}return e}function ve(e,t){var n={}.toString.call(e);return 0===n.indexOf("[object")&&n.indexOf(t+"]")>-1}function he(e,t){return"function"==typeof e?e.apply(void 0,t):e}function ge(e,t){return 0===t?e:function(r){clearTimeout(n),n=setTimeout((function(){e(r)}),t)};var n}function ye(e){return[].concat(e)}function be(e,t){-1===e.indexOf(t)&&e.push(t)}function we(e){return[].slice.call(e)}function xe(e){return Object.keys(e).reduce((function(t,n){return void 0!==e[n]&&(t[n]=e[n]),t}),{})}function Oe(){return document.createElement("div")}function Ee(e){return["Element","Fragment"].some((function(t){return ve(e,t)}))}function Ae(e,t){e.forEach((function(e){e&&(e.style.transitionDuration=t+"ms")}))}function Te(e,t){e.forEach((function(e){e&&e.setAttribute("data-state",t)}))}function Le(e,t,n){var r=t+"EventListener";["transitionend","webkitTransitionEnd"].forEach((function(t){e[r](t,n)}))}function De(e,t){for(var n=t;n;){var r;if(e.contains(n))return!0;n=null==n.getRootNode||null==(r=n.getRootNode())?void 0:r.host}return!1}var je={isTouch:!1},ke=0;function Ce(){je.isTouch||(je.isTouch=!0,window.performance&&document.addEventListener("mousemove",Me))}function Me(){var e=performance.now();e-ke<20&&(je.isTouch=!1,document.removeEventListener("mousemove",Me)),ke=e}function Be(){var e,t=document.activeElement;if((e=t)&&e._tippy&&e._tippy.reference===e){var n=t._tippy;t.blur&&!n.state.isVisible&&t.blur()}}var Ie=!("undefined"==typeof window||"undefined"==typeof document||!window.msCrypto),He=Object.assign({appendTo:de,aria:{content:"auto",expanded:"auto"},delay:0,duration:[300,250],getReferenceClientRect:null,hideOnClick:!0,ignoreAttributes:!1,interactive:!1,interactiveBorder:2,interactiveDebounce:0,moveTransition:"",offset:[0,10],onAfterUpdate:function(){},onBeforeUpdate:function(){},onCreate:function(){},onDestroy:function(){},onHidden:function(){},onHide:function(){},onMount:function(){},onShow:function(){},onShown:function(){},onTrigger:function(){},onUntrigger:function(){},onClickOutside:function(){},placement:"top",plugins:[],popperOptions:{},render:null,showOnCreate:!1,touch:!0,trigger:"mouseenter focus",triggerTarget:null},{animateFill:!1,followCursor:!1,inlinePositioning:!1,sticky:!1},{allowHTML:!1,animation:"fade",arrow:!0,content:"",inertia:!1,maxWidth:350,role:"tooltip",theme:"",zIndex:9999}),Ve=Object.keys(He);function Se(e){var t=(e.plugins||[]).reduce((function(t,n){var r,i=n.name,o=n.defaultValue;return i&&(t[i]=void 0!==e[i]?e[i]:null!=(r=He[i])?r:o),t}),{});return Object.assign({},e,t)}function We(e,t){var n=Object.assign({},t,{content:he(t.content,[e])},t.ignoreAttributes?{}:function(e,t){return(t?Object.keys(Se(Object.assign({},He,{plugins:t}))):Ve).reduce((function(t,n){var r=(e.getAttribute("data-tippy-"+n)||"").trim();if(!r)return t;if("content"===n)t[n]=r;else try{t[n]=JSON.parse(r)}catch(e){t[n]=r}return t}),{})}(e,t.plugins));return n.aria=Object.assign({},He.aria,n.aria),n.aria={expanded:"auto"===n.aria.expanded?t.interactive:n.aria.expanded,content:"auto"===n.aria.content?t.interactive?null:"describedby":n.aria.content},n}function Pe(e,t){e.innerHTML=t}function Re(e){var t=Oe();return!0===e?t.className=fe:(t.className=pe,Ee(e)?t.appendChild(e):Pe(t,e)),t}function Ne(e,t){Ee(t.content)?(Pe(e,""),e.appendChild(t.content)):"function"!=typeof t.content&&(t.allowHTML?Pe(e,t.content):e.textContent=t.content)}function _e(e){var t=e.firstElementChild,n=we(t.children);return{box:t,content:n.find((function(e){return e.classList.contains(ue)})),arrow:n.find((function(e){return e.classList.contains(fe)||e.classList.contains(pe)})),backdrop:n.find((function(e){return e.classList.contains("tippy-backdrop")}))}}function qe(e){var t=Oe(),n=Oe();n.className="tippy-box",n.setAttribute("data-state","hidden"),n.setAttribute("tabindex","-1");var r=Oe();function i(n,r){var i=_e(t),o=i.box,a=i.content,s=i.arrow;r.theme?o.setAttribute("data-theme",r.theme):o.removeAttribute("data-theme"),"string"==typeof r.animation?o.setAttribute("data-animation",r.animation):o.removeAttribute("data-animation"),r.inertia?o.setAttribute("data-inertia",""):o.removeAttribute("data-inertia"),o.style.maxWidth="number"==typeof r.maxWidth?r.maxWidth+"px":r.maxWidth,r.role?o.setAttribute("role",r.role):o.removeAttribute("role"),n.content===r.content&&n.allowHTML===r.allowHTML||Ne(a,e.props),r.arrow?s?n.arrow!==r.arrow&&(o.removeChild(s),o.appendChild(Re(r.arrow))):o.appendChild(Re(r.arrow)):s&&o.removeChild(s)}return r.className=ue,r.setAttribute("data-state","hidden"),Ne(r,e.props),t.appendChild(n),n.appendChild(r),i(e.props,e.props),{popper:t,onUpdate:i}}qe.$$tippy=!0;var Ue=1,Fe=[],ze=[];function $e(e,t){var n,r,i,o,a,s,c,u,f=We(e,Object.assign({},He,Se(xe(t)))),p=!1,l=!1,d=!1,m=!1,v=[],h=ge(X,f.interactiveDebounce),g=Ue++,y=(u=f.plugins).filter((function(e,t){return u.indexOf(e)===t})),b={id:g,reference:e,popper:Oe(),popperInstance:null,props:f,state:{isEnabled:!0,isVisible:!1,isDestroyed:!1,isMounted:!1,isShown:!1},plugins:y,clearDelayTimeouts:function(){clearTimeout(n),clearTimeout(r),cancelAnimationFrame(i)},setProps:function(t){if(!b.state.isDestroyed){I("onBeforeUpdate",[b,t]),z();var n=b.props,r=We(e,Object.assign({},n,xe(t),{ignoreAttributes:!0}));b.props=r,F(),n.interactiveDebounce!==r.interactiveDebounce&&(S(),h=ge(X,r.interactiveDebounce)),n.triggerTarget&&!r.triggerTarget?ye(n.triggerTarget).forEach((function(e){e.removeAttribute("aria-expanded")})):r.triggerTarget&&e.removeAttribute("aria-expanded"),V(),B(),O&&O(n,r),b.popperInstance&&(K(),Z().forEach((function(e){requestAnimationFrame(e._tippy.popperInstance.forceUpdate)}))),I("onAfterUpdate",[b,t])}},setContent:function(e){b.setProps({content:e})},show:function(){var e=b.state.isVisible,t=b.state.isDestroyed,n=!b.state.isEnabled,r=je.isTouch&&!b.props.touch,i=me(b.props.duration,0,He.duration);if(!(e||t||n||r||j().hasAttribute("disabled")||(I("onShow",[b],!1),!1===b.props.onShow(b)))){if(b.state.isVisible=!0,D()&&(x.style.visibility="visible"),B(),N(),b.state.isMounted||(x.style.transition="none"),D()){var o=C();Ae([o.box,o.content],0)}var a,c,u;s=function(){var e;if(b.state.isVisible&&!m){if(m=!0,x.offsetHeight,x.style.transition=b.props.moveTransition,D()&&b.props.animation){var t=C(),n=t.box,r=t.content;Ae([n,r],i),Te([n,r],"visible")}H(),V(),be(ze,b),null==(e=b.popperInstance)||e.forceUpdate(),I("onMount",[b]),b.props.animation&&D()&&function(e,t){q(e,(function(){b.state.isShown=!0,I("onShown",[b])}))}(i)}},c=b.props.appendTo,u=j(),(a=b.props.interactive&&c===de||"parent"===c?u.parentNode:he(c,[u])).contains(x)||a.appendChild(x),b.state.isMounted=!0,K()}},hide:function(){var e=!b.state.isVisible,t=b.state.isDestroyed,n=!b.state.isEnabled,r=me(b.props.duration,1,He.duration);if(!(e||t||n)&&(I("onHide",[b],!1),!1!==b.props.onHide(b))){if(b.state.isVisible=!1,b.state.isShown=!1,m=!1,p=!1,D()&&(x.style.visibility="hidden"),S(),_(),B(!0),D()){var i=C(),o=i.box,a=i.content;b.props.animation&&(Ae([o,a],r),Te([o,a],"hidden"))}H(),V(),b.props.animation?D()&&function(e,t){q(e,(function(){!b.state.isVisible&&x.parentNode&&x.parentNode.contains(x)&&t()}))}(r,b.unmount):b.unmount()}},hideWithInteractivity:function(e){k().addEventListener("mousemove",h),be(Fe,h),h(e)},enable:function(){b.state.isEnabled=!0},disable:function(){b.hide(),b.state.isEnabled=!1},unmount:function(){b.state.isVisible&&b.hide(),b.state.isMounted&&(Q(),Z().forEach((function(e){e._tippy.unmount()})),x.parentNode&&x.parentNode.removeChild(x),ze=ze.filter((function(e){return e!==b})),b.state.isMounted=!1,I("onHidden",[b]))},destroy:function(){b.state.isDestroyed||(b.clearDelayTimeouts(),b.unmount(),z(),delete e._tippy,b.state.isDestroyed=!0,I("onDestroy",[b]))}};if(!f.render)return b;var w=f.render(b),x=w.popper,O=w.onUpdate;x.setAttribute("data-tippy-root",""),x.id="tippy-"+b.id,b.popper=x,e._tippy=b,x._tippy=b;var E=y.map((function(e){return e.fn(b)})),A=e.hasAttribute("aria-expanded");return F(),V(),B(),I("onCreate",[b]),f.showOnCreate&&ee(),x.addEventListener("mouseenter",(function(){b.props.interactive&&b.state.isVisible&&b.clearDelayTimeouts()})),x.addEventListener("mouseleave",(function(){b.props.interactive&&b.props.trigger.indexOf("mouseenter")>=0&&k().addEventListener("mousemove",h)})),b;function T(){var e=b.props.touch;return Array.isArray(e)?e:[e,0]}function L(){return"hold"===T()[0]}function D(){var e;return!(null==(e=b.props.render)||!e.$$tippy)}function j(){return c||e}function k(){var e,t,n=j().parentNode;return n?null!=(t=ye(n)[0])&&null!=(e=t.ownerDocument)&&e.body?t.ownerDocument:document:document}function C(){return _e(x)}function M(e){return b.state.isMounted&&!b.state.isVisible||je.isTouch||o&&"focus"===o.type?0:me(b.props.delay,e?0:1,He.delay)}function B(e){void 0===e&&(e=!1),x.style.pointerEvents=b.props.interactive&&!e?"":"none",x.style.zIndex=""+b.props.zIndex}function I(e,t,n){var r;void 0===n&&(n=!0),E.forEach((function(n){n[e]&&n[e].apply(n,t)})),n&&(r=b.props)[e].apply(r,t)}function H(){var t=b.props.aria;if(t.content){var n="aria-"+t.content,r=x.id;ye(b.props.triggerTarget||e).forEach((function(e){var t=e.getAttribute(n);if(b.state.isVisible)e.setAttribute(n,t?t+" "+r:r);else{var i=t&&t.replace(r,"").trim();i?e.setAttribute(n,i):e.removeAttribute(n)}}))}}function V(){!A&&b.props.aria.expanded&&ye(b.props.triggerTarget||e).forEach((function(e){b.props.interactive?e.setAttribute("aria-expanded",b.state.isVisible&&e===j()?"true":"false"):e.removeAttribute("aria-expanded")}))}function S(){k().removeEventListener("mousemove",h),Fe=Fe.filter((function(e){return e!==h}))}function W(t){if(!je.isTouch||!d&&"mousedown"!==t.type){var n=t.composedPath&&t.composedPath()[0]||t.target;if(!b.props.interactive||!De(x,n)){if(ye(b.props.triggerTarget||e).some((function(e){return De(e,n)}))){if(je.isTouch)return;if(b.state.isVisible&&b.props.trigger.indexOf("click")>=0)return}else I("onClickOutside",[b,t]);!0===b.props.hideOnClick&&(b.clearDelayTimeouts(),b.hide(),l=!0,setTimeout((function(){l=!1})),b.state.isMounted||_())}}}function P(){d=!0}function R(){d=!1}function N(){var e=k();e.addEventListener("mousedown",W,!0),e.addEventListener("touchend",W,le),e.addEventListener("touchstart",R,le),e.addEventListener("touchmove",P,le)}function _(){var e=k();e.removeEventListener("mousedown",W,!0),e.removeEventListener("touchend",W,le),e.removeEventListener("touchstart",R,le),e.removeEventListener("touchmove",P,le)}function q(e,t){var n=C().box;function r(e){e.target===n&&(Le(n,"remove",r),t())}if(0===e)return t();Le(n,"remove",a),Le(n,"add",r),a=r}function U(t,n,r){void 0===r&&(r=!1),ye(b.props.triggerTarget||e).forEach((function(e){e.addEventListener(t,n,r),v.push({node:e,eventType:t,handler:n,options:r})}))}function F(){var e;L()&&(U("touchstart",$,{passive:!0}),U("touchend",Y,{passive:!0})),(e=b.props.trigger,e.split(/\s+/).filter(Boolean)).forEach((function(e){if("manual"!==e)switch(U(e,$),e){case"mouseenter":U("mouseleave",Y);break;case"focus":U(Ie?"focusout":"blur",J);break;case"focusin":U("focusout",J)}}))}function z(){v.forEach((function(e){var t=e.node,n=e.eventType,r=e.handler,i=e.options;t.removeEventListener(n,r,i)})),v=[]}function $(e){var t,n=!1;if(b.state.isEnabled&&!G(e)&&!l){var r="focus"===(null==(t=o)?void 0:t.type);o=e,c=e.currentTarget,V(),!b.state.isVisible&&ve(e,"MouseEvent")&&Fe.forEach((function(t){return t(e)})),"click"===e.type&&(b.props.trigger.indexOf("mouseenter")<0||p)&&!1!==b.props.hideOnClick&&b.state.isVisible?n=!0:ee(e),"click"===e.type&&(p=!n),n&&!r&&te(e)}}function X(e){var t=e.target,n=j().contains(t)||x.contains(t);if("mousemove"!==e.type||!n){var r=Z().concat(x).map((function(e){var t,n=null==(t=e._tippy.popperInstance)?void 0:t.state;return n?{popperRect:e.getBoundingClientRect(),popperState:n,props:f}:null})).filter(Boolean);(function(e,t){var n=t.clientX,r=t.clientY;return e.every((function(e){var t=e.popperRect,i=e.popperState,o=e.props.interactiveBorder,a=i.placement.split("-")[0],s=i.modifiersData.offset;if(!s)return!0;var c="bottom"===a?s.top.y:0,u="top"===a?s.bottom.y:0,f="right"===a?s.left.x:0,p="left"===a?s.right.x:0,l=t.top-r+c>o,d=r-t.bottom-u>o,m=t.left-n+f>o,v=n-t.right-p>o;return l||d||m||v}))})(r,e)&&(S(),te(e))}}function Y(e){G(e)||b.props.trigger.indexOf("click")>=0&&p||(b.props.interactive?b.hideWithInteractivity(e):te(e))}function J(e){b.props.trigger.indexOf("focusin")<0&&e.target!==j()||b.props.interactive&&e.relatedTarget&&x.contains(e.relatedTarget)||te(e)}function G(e){return!!je.isTouch&&L()!==e.type.indexOf("touch")>=0}function K(){Q();var t=b.props,n=t.popperOptions,r=t.placement,i=t.offset,o=t.getReferenceClientRect,a=t.moveTransition,c=D()?_e(x).arrow:null,u=o?{getBoundingClientRect:o,contextElement:o.contextElement||j()}:e,f=[{name:"offset",options:{offset:i}},{name:"preventOverflow",options:{padding:{top:2,bottom:2,left:5,right:5}}},{name:"flip",options:{padding:5}},{name:"computeStyles",options:{adaptive:!a}},{name:"$$tippy",enabled:!0,phase:"beforeWrite",requires:["computeStyles"],fn:function(e){var t=e.state;if(D()){var n=C().box;["placement","reference-hidden","escaped"].forEach((function(e){"placement"===e?n.setAttribute("data-placement",t.placement):t.attributes.popper["data-popper-"+e]?n.setAttribute("data-"+e,""):n.removeAttribute("data-"+e)})),t.attributes.popper={}}}}];D()&&c&&f.push({name:"arrow",options:{element:c,padding:3}}),f.push.apply(f,(null==n?void 0:n.modifiers)||[]),b.popperInstance=ce(u,x,Object.assign({},n,{placement:r,onFirstUpdate:s,modifiers:f}))}function Q(){b.popperInstance&&(b.popperInstance.destroy(),b.popperInstance=null)}function Z(){return we(x.querySelectorAll("[data-tippy-root]"))}function ee(e){b.clearDelayTimeouts(),e&&I("onTrigger",[b,e]),N();var t=M(!0),r=T(),i=r[0],o=r[1];je.isTouch&&"hold"===i&&o&&(t=o),t?n=setTimeout((function(){b.show()}),t):b.show()}function te(e){if(b.clearDelayTimeouts(),I("onUntrigger",[b,e]),b.state.isVisible){if(!(b.props.trigger.indexOf("mouseenter")>=0&&b.props.trigger.indexOf("click")>=0&&["mouseleave","mousemove"].indexOf(e.type)>=0&&p)){var t=M(!1);t?r=setTimeout((function(){b.state.isVisible&&b.hide()}),t):i=requestAnimationFrame((function(){b.hide()}))}}else _()}}function Xe(e,t){void 0===t&&(t={});var n=He.plugins.concat(t.plugins||[]);document.addEventListener("touchstart",Ce,le),window.addEventListener("blur",Be);var r,i=Object.assign({},t,{plugins:n}),o=(r=e,Ee(r)?[r]:function(e){return ve(e,"NodeList")}(r)?we(r):Array.isArray(r)?r:we(document.querySelectorAll(r))).reduce((function(e,t){var n=t&&$e(t,i);return n&&e.push(n),e}),[]);return Ee(e)?o[0]:o}Xe.defaultProps=He,Xe.setDefaultProps=function(e){Object.keys(e).forEach((function(t){He[t]=e[t]}))},Xe.currentInput=je,Object.assign({},Y,{effect:function(e){var t=e.state,n={popper:{position:t.options.strategy,left:"0",top:"0",margin:"0"},arrow:{position:"absolute"},reference:{}};Object.assign(t.elements.popper.style,n.popper),t.styles=n,t.elements.arrow&&Object.assign(t.elements.arrow.style,n.arrow)}}),Xe.setDefaultProps({render:qe});const Ye=Xe;document.addEventListener("DOMContentLoaded",(function(){const e=document.getElementById("help-menu-template");if(!e)return;const t=document.createElement("div");t.appendChild(document.importNode(e.content,!0)),Ye(".classifai-help",{allowHTML:!0,content:t.innerHTML,trigger:"click",placement:"bottom-end",arrow:!0,animation:"scale",duration:[250,200],theme:"light",interactive:!0})})),(()=>{const e=document.getElementById("classifai-waston-cred-toggle"),t=document.getElementById("classifai-settings-watson_username");if(null===e||null===t)return;let n=null,r=null;t.closest("tr")?n=t.closest("tr"):t.closest(".classifai-setup-form-field")&&(n=t.closest(".classifai-setup-form-field")),document.getElementById("classifai-settings-watson_password").closest("tr")?[r]=document.getElementById("classifai-settings-watson_password").closest("tr").getElementsByTagName("label"):document.getElementById("classifai-settings-watson_password").closest(".classifai-setup-form-field")&&([r]=document.getElementById("classifai-settings-watson_password").closest(".classifai-setup-form-field").getElementsByTagName("label")),e.addEventListener("click",(i=>{if(i.preventDefault(),n.classList.toggle("hidden"),n.classList.contains("hidden"))return e.innerText=ClassifAI.use_password,r.innerText=ClassifAI.api_key,void(t.value="apikey");e.innerText=ClassifAI.use_key,r.innerText=ClassifAI.api_password}))})()})();
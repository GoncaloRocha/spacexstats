define(["knockout","jquery"],function(n,e){var i=function(){n.bindingHandlers.foreachNameAttr={update:function(e,i){n.bindingHandlers.attr.update(e,function(){var t=n.unwrap(i()),r=e.getAttribute("name");if(-1!=r.indexOf("[")){if(2==r.split("[").length)var r=r.substr(0,r.indexOf("["))+"["+t+"]"+r.substr(r.indexOf("["));else if(3==r.split("[").length)var a=r.split(new RegExp("[[0-9]+]")),r=a[0]+"["+t+"]"+a[1]}else var r=r+"["+t+"]";return{name:r}})}},n.bindingHandlers.slideVisible={update:function(i,t,r){var a=n.unwrap(t()),u=r.get("slideDuration")||400;1==a?e(i).slideDown(u):e(i).slideUp(u)}}};return i});
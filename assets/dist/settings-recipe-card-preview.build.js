(()=>{var e=function(){window.parent.postMessage(JSON.stringify({event:"setRecipeCardSize",height:document.querySelector(".tasty-recipes").getBoundingClientRect().bottom}),"*")};window.addEventListener("load",e),e(),window.addEventListener("message",(function(t){if(window.location.origin===t.origin&&"string"==typeof t.data){var i=JSON.parse(t.data);if("updateCustomization"===i.event){var n=i.data;document.querySelectorAll("[data-tasty-recipes-customization]").forEach((function(e){e.getAttribute("data-tasty-recipes-customization").split(" ").forEach((function(t){if(-1!==t.indexOf(".")){var i=t.split("."),o=i[0].replace(/-/g,"_");void 0!==n[o]&&("innerText"!==i[1]?"innerHTML"!==i[1]?n[o].length?e.style.setProperty(i[1],n[o]):e.style.removeProperty(i[1]):e.innerHTML=n[o]:e.innerText=n[o])}}))})),e()}}}))})();
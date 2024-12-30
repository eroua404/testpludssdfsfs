const onWindowResize = function() {
	window.parent.postMessage(JSON.stringify({
		event: 'setRecipeCardSize',
		height: document.querySelector('.tasty-recipes').getBoundingClientRect().bottom,
	}), '*');
};
window.addEventListener('load', onWindowResize);
onWindowResize();

window.addEventListener('message', (event) => {
	if ( window.location.origin !== event.origin
		|| typeof event.data !== 'string' ) {
		return;
	}
	const payload = JSON.parse( event.data );
	switch (payload.event) {
		case 'updateCustomization':
			const settings = payload.data;
			document.querySelectorAll('[data-tasty-recipes-customization]').forEach((el) => {
				const customizations = el.getAttribute('data-tasty-recipes-customization').split(' ');
				customizations.forEach((customization) => {
					if ( -1 === customization.indexOf( '.' ) ) {
						return;
					}
					const bits = customization.split('.');
					const settingsName = bits[0].replace(/-/g, '_');
					if ( typeof settings[settingsName] === 'undefined' ) {
						return;
					}
					if ( 'innerText' === bits[1] ) {
						el.innerText = settings[settingsName];
						return;
					}
					if ( 'innerHTML' === bits[1] ) {
						el.innerHTML = settings[settingsName];
						return;
					}

					if ( settings[settingsName].length ) {
						el.style.setProperty(bits[1], settings[settingsName]);
					} else {
						el.style.removeProperty(bits[1]);
					}
				});
			});
			onWindowResize();
			break;
	}
});

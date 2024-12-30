(function() {
	var buttons = document.querySelectorAll('button[name="tasty-recipes-video-toggle"]');
	if ( ! buttons ) {
		return;
	}
	buttons.forEach(function(button){
		button.addEventListener('click', function() {
			var container = event.target.closest('.tasty-recipes-instructions');
			/* Backwards compat for our other template. */
			if ( ! container ) {
				container = event.target.closest('.tasty-recipe-instructions');
			}
			if ( ! container ) {
				return;
			}
			var wasChecked = 'true' === button.getAttribute('aria-checked');
			button.setAttribute('aria-checked', wasChecked ? 'false' : 'true')
			container.querySelectorAll('iframe').forEach(function(iframe) {
				if ( ! wasChecked ) {
					iframe.style.removeProperty('display');
				} else {
					iframe.style.display = 'none';
				}
			});
			container.querySelectorAll('.tasty-recipe-responsive-iframe-container').forEach(function(container) {
				if ( ! wasChecked ) {
					container.style.removeProperty('display');
				} else {
					container.style.display = 'none';
				}
			});
		});
	});
}());

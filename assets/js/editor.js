(function( window, views, media, $, wp ){
	var recipe;

	recipeEditor = window.tastyRecipesEditorModal;

	recipe = {
		action: 'tasty_recipes_parse_shortcode',
		recipe: {},

		initialize: function() {
			var self = this;

			wp.ajax.post( this.action, {
				post_id: tastyRecipesEditor.currentPostId,
				nonce: tastyRecipesEditor.parseNonce,
				type: this.shortcode.tag,
				shortcode: this.shortcode.string()
			} )
			.done( function( response ) {
				self.render( response );
				self.recipe = response.recipe;
			} )
			.fail( function( response ) {
				self.setError( response.message || response.statusText, 'carrot' );
			} );
		},

		edit: function( text, update ) {
			var self = this;
			recipeEditor.open( self.recipe, function( response ){
				self.render( response, true );
				if ( typeof response.recipe !== 'undefined' ) {
					self.recipe = response.recipe;
				}
			});
		}
	};

	views.register( 'tasty-recipe', _.extend( {}, recipe ) );

	$(document).ready(function(){
		$('button.tasty-recipes-add-recipe').on('click', function( event ){
			var elem = $( event.currentTarget ),
				editorId = elem.data('editor');

			event.preventDefault();

			// Prevents Opera from showing the outline of the button above the modal.
			//
			// See: https://core.trac.wordpress.org/ticket/22445
			elem.blur();

			var defaults = {
				author_name: tastyRecipesEditor.defaultAuthorName
			};
			if ( $('input[name="post_title"]').length ) {
				defaults.title = $('input[name="post_title"]').val();
			}
			recipeEditor.open( defaults, function( response ){
				if ( typeof response.shortcode !== 'undefined' ) {
					if ( typeof tinyMCE !== 'undefined' && tinyMCE.get( editorId ) && ! tinyMCE.get( editorId ).isHidden() ) {
						tinyMCE.get( editorId ).focus( true );
						tinyMCE.activeEditor.execCommand('mceInsertContent', false, response.shortcode );
					} else {
						var textarea = $('#'+editorId);
						var content = textarea.val();
						content += "\n\n" + response.shortcode;
						textarea.val( content );
					}
				}
			});
		} );
	});
}(window, window.wp.mce.views, window.wp.media, window.jQuery, window.wp ))

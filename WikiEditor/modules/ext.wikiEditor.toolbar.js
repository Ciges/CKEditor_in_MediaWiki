/*
 * JavaScript for WikiEditor Toolbar
 */

$( document ).ready( function() {
	if ( !$.wikiEditor.isSupported( $.wikiEditor.modules.toolbar ) ) {
		$( '.wikiEditor-oldToolbar' ).show();
		return;
	}
	// The old toolbar is still in place and needs to be removed so there aren't two toolbars
	$( '#toolbar' ).remove();
	// Add toolbar module
	// TODO: Implement .wikiEditor( 'remove' )
	$( '#wpTextbox1' ).wikiEditor(
		'addModule', $.wikiEditor.modules.toolbar.config.getDefaultConfig()
	);

        // ---------------------------------------------
        // Added by José Manuel Ciges Regueiro - e198869
        // Load de wikiEditor if CKEditor is not loaded
        if (mw.config.get('wgCKeditorVisible')) {
            $('#wikiEditor-ui-toolbar').hide();
        }
        else {
            $('#wikiEditor-ui-toolbar').show();
        }
        // Add toggle button (if configured)
        if (mw.user.options.get('riched_use_toggle'))  {
            $('#ckTools').remove();
            if ($('#ckTools').length == 0)  {
                $("<div id='ckTools' style='float: right; display: block;'> [ <a id='toggleAnchor' class='fckToogle' href=''>Show RichTextEditor</a> ]</div>").insertBefore("#editform");
            }
        }
        // ---------------------------------------------
        
} );

/* all input needed fields */
var iiamTitleInput, iiamTextInput, iiamUrlInput, iiamImageWidthInput, iiamBackendColorInput,
		iiamTitleColorInput, iiamTextColorInput, iiamPreviewAd;

/* when document is ready... */
jQuery( document ).ready( function ()
{
	initColorPicker();	
	assignInputs();
	initColorInputBackgroundColors();
	bindEvents();
	updatePreviewAd();
	iiamImageWidthInput.trigger( 'change' );
} );

/**
 * Initializes the color picker.
 *
 * @since 1.0
 */
function initColorPicker()
{
	jQuery('.pickcolor').iris({
		change: function( event, ui ) {
			var input = jQuery( this );
			var color = ui.color.toString();
			
			input.val( color ).css( 'background', color );
			input.trigger( 'change' );
		},
		palettes: true
	} );
	
	jQuery( '.pickcolor' ).click( function ( event )
	{		
		jQuery( this ).iris( 'toggle' );
	} );
}

/**
 * Assigns all needed inputs.
 *
 * @since 1.0
 */
function assignInputs()
{
	iiamTitleInput = jQuery( '#iiam-title' );
	iiamTextInput = jQuery( '#iiam-text' );
	iiamUrlInput = jQuery( '#iiam-url' );
	iiamImageWidthInput = jQuery( '#iiam-image-width' );
	iiamBackendColorInput = jQuery( '#iiam-background-color' );
	iiamTitleColorInput = jQuery( '#iiam-title-color' );
	iiamTextColorInput = jQuery( '#iiam-text-color' );
	iiamPreviewAd = jQuery( '#iiam-preview-ad' );
}

/**
 * Binds all needed events.
 *
 * @since 1.0
 */
function bindEvents()
{
	iiamTitleInput.change( function ()
	{
		updatePreviewAd();
	} );
	iiamTextInput.keyup( function ()
	{
		updatePreviewAd();
	} );
	iiamUrlInput.change( function ()
	{
		updatePreviewAd();
	} );
	iiamBackendColorInput.change( function ()
	{
		updateBackgroundColor();
	} );
	iiamTitleColorInput.change( function ()
	{
		updateTitleColor();
	} );
	iiamTextColorInput.change( function ()
	{
		updateTextColor();
	} );
	iiamImageWidthInput.change( function ()
	{
		if ( this.value && !isNaN( this.value ) && this.value > 99 && this.value.length <= 4 ) {
			iiamPreviewAd.width( this.value );
		}
	} );
}

/**
 * Sets the initial background colors of the color inputs.
 *
 * @since 1.0
 */
function initColorInputBackgroundColors()
{
	iiamBackendColorInput.css( 'background', iiamBackendColorInput.val() );
	iiamTitleColorInput.css( 'background', iiamTitleColorInput.val() );
	iiamTextColorInput.css( 'background', iiamTextColorInput.val() );
}

/**
 * Updates the preview.
 *
 * @since 1.0
 */
function updatePreviewAd()
{
	iiamPreviewAd.html( '<a href="' + iiamUrlInput.val() + '" target="_blank"><span><span class="title">' + iiamTitleInput.val() + '</span>' + iiamTextInput.val() + '</span></a>' );
	updateBackgroundColor();
	updateTitleColor();
	updateTextColor();
}

/**
 * Updates background color of the preview.
 *
 * @since 1.0
 */
function updateBackgroundColor()
{
	iiamPreviewAd.css( 'background', iiamBackendColorInput.val() );
}

/**
 * Updates title color of the preview.
 *
 * @since 1.0
 */
function updateTitleColor()
{
	jQuery( iiamPreviewAd.children( 'a' ).children().children().get( 0 ) ).css( 'color', iiamTitleColorInput.val() );
}

/**
 * Updates text color of the preview.
 *
 * @since 1.0
 */
function updateTextColor()
{
	jQuery( iiamPreviewAd.children( 'a' ).get( 0 ) ).css( 'color', iiamTextColorInput.val() );
}
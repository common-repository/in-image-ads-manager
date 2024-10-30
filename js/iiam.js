/* default fade in/out duration for ad box overlay */
var fadeDuration = 500;

/* default ad box high */
var adBoxHeight = 50;

/* when document is ready... */
jQuery( document ).ready( function ()
{
	/* ensures that in image ads are given before calling initialization */
	if ( !!inImageAds ) {
		/* initializes ad functionality */
		initAds();
	}
} );

/**
 * Initializes the ads.
 *
 * @since 1.0
 */
function initAds()
{
	/* loops through all in image ads */
	jQuery.each( inImageAds, function ( index, adData )
	{
		/* gets image element(s) */
		var image = jQuery( '.' + adData['id'] );

		/* if more than one image with the same ad id found... */
		if ( image.length > 1 ) {
			/* loops through all image elements */
			image.each( function ()
			{
				/* if image has minimal height... */
				if ( hasImageMinHeight( jQuery( this ) ) ) {
					/* creates ad box for the image */
					var adBox = createAdBox( adData, jQuery( this ) );
					/* binds needed events for the ad box */
					bindAdBox( adBox, jQuery( this ) );
					/* binds needed events for the image */
					bindImage( jQuery( this ), adBox )
				}

			} );
		} else {
			if ( hasImageMinHeight( image ) ) {
				/* creates ad box for the image */
				var adBox = createAdBox( adData, image );
				/* binds ad box related events */
				bindAdBox( adBox, image );
				/* binds image related events */
				bindImage( image, adBox );
			}
		}
	} );
}

/**
 * Creates ad box for the given ad data.
 *
 * @since 1.0
 *
 * @param object data ad data
 * @param jQueryElement image image that will be overlayed by the ad
 *
 * @return jQueryElement adBox the ad box
 */
function createAdBox( data, image )
{
	/* creates ad box with the given data */
	var adBox = jQuery( '<div class="' + data['id'] + '-box iiam-box">' +
			'<a href="' + data.url + '" target="_blank"><span><span class="title">' + data.title + '</span>' + data.text + '</span></a>' +
			'</div>' );
	/* hides ad box */
	adBox.hide();
	/* sets ad box height */
	adBox.height( adBoxHeight );
	/* inserts ad box before image */
	image.before( adBox );

	return adBox;
}

/**
 * Binds needed events for the ad box.
 *
 * @since 1.0
 *
 * @param jQueryElement adBox the ad box
 * @param jQueryElement image image that will be overlayed by the ad
 */
function bindAdBox( adBox, image )
{
	/* binds mouseleave event of the ad box */
	adBox.mouseleave( function ( event )
	{
		/* calculating mouse and image positions */
		var offset = adBox.offset();
		var posX = event.pageX - offset.left;
		var posY = event.pageY - offset.top;
		var imageOffset = image.offset();
		var imagePosX = event.pageX - imageOffset.left;
		var imagePosY = event.pageY - imageOffset.top;

		/* if mouse is not over the ad box and not over the image... */
		if ( posX <= 0 || posX >= adBox.width() || posY <= 0 || posY >= adBox.height() ) {
			if ( imagePosX <= 0 || imagePosX >= image.outerWidth() || imagePosY <= 0 || imagePosY >= image.outerHeight() ) {
				/* fades out the ad box */
				adBox.fadeOut( fadeDuration );
			}
		}
	} );

	/* binds click event of the ad box */
	adBox.click( function ()
	{
		/* simple hide it after click */
		adBox.hide();
	} );
}

/**
 * Binds needed events for the image that will be overlayed by the ad.
 *
 * @since 1.0
 *
 * @param jQueryElement image image that will be overlayed by the ad
 * @param jQueryElement adBox the ad box
 */
function bindImage( image, adBox )
{
	/* binds mouseenter event of the image */
	image.mouseenter( function ( event )
	{
		if ( hasImageMinHeight( image ) ) {
			/* sets ad box width - outer width used so ad box overlays image border too */
			adBox.width( image.outerWidth() );

			/* calculating mouse positions */
			var offset = image.offset();
			var position = event.pageY - offset.top;
			var halfHeight = image.outerHeight() / 2;

			/* if mouse is in the upper half of the image... */
			if ( position < halfHeight ) {
				/* sets ad box position */
				adBox.css( 'left', offset.left );
				adBox.css( 'top', offset.top );
			} else if ( position > halfHeight ) {
				/* sets ad box position */
				adBox.css( 'left', offset.left );
				adBox.css( 'top', offset.top + image.outerHeight() - adBoxHeight );
			}

			/* fades in the ad box */
			adBox.fadeIn( fadeDuration );
		}
	} );

	/* binds mouseleave event of the image */
	image.mouseleave( function ( event )
	{
		/* calculating mouse positions */
		var offset = image.offset();
		var posX = event.pageX - offset.left;
		var posY = event.pageY - offset.top;

		/* if mouse is not over the image... */
		if ( posX <= 0 || posX >= image.outerWidth() || posY <= 0 || posY >= image.outerHeight() ) {
			/* fades out the ad box */
			adBox.fadeOut( fadeDuration );
		}
	} );
}

/**
 * Returns true if given image has minimal height otherwise false.
 *
 * @since 1.0
 *
 * @param jQueryElement image image which height should be checked
 *
 * @return bool true if given image has minimal height otherwise false
 */
function hasImageMinHeight( image )
{
	/* if image height is greater than or equal the default ad box height... */
	if ( image.height() >= adBoxHeight ) {
		return true;
	} else {
		return false;
	}
}
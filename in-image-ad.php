<?php
/**
 * In Image Ad
 *
 * @since 1.0
 */
class In_Image_Ad
{
	/* color constants */
	const COLOR_BACKGROUND = 0;
	const COLOR_TITLE = 1;
	const COLOR_TEXT = 2;

	/* ad properties */
	private $id = '';
	private $title = '';
	private $text = '';
	private $url = '';
	private $image_width = '';
	private $image_height = '';
	private $colors = array();

	/* indicator whether ad has errors or not */
	private $has_errors = false;

	/* holds error messages */
	private $errors = array();

	/* defaults colors */
	private $default_colors = array(
		self::COLOR_BACKGROUND => '#282828',
		self::COLOR_TITLE      => '#ffffff',
		self::COLOR_TEXT       => '#ffffff'
	);

	/**
	 * Constructor that sets id if given and sets default colors.
	 *
	 * @since 1.0
	 *
	 * @param null|int $id id of the ad
	 */
	public function __construct( $id = null )
	{
		if ( isset( $id ) && is_numeric( $id ) ) {
			$this->id = (int)$id;
		}

		$this->colors = $this->default_colors;
	}

	/**
	 * Returns the id of the ad.
	 *
	 * @since 1.0
	 *
	 * @return int id of the ad
	 */
	public function get_id()
	{
		return $this->id;
	}

	/**
	 * Returns the title of the ad.
	 *
	 * @since 1.0
	 *
	 * @return string title of the ad
	 */
	public function get_title()
	{
		return $this->title;
	}

	/**
	 * Sets the title of the ad and fires validation.
	 *
	 * @since 1.0
	 *
	 * @param string $title title of the ad
	 */
	public function set_title( $title )
	{
		/* sets the escaped title */
		$this->title = esc_html( $title, array() );

		/* fires validation */
		$this->validate_presence( __( 'Title', In_Image_Ads_Manager::TEXT_DOMAIN ), $title );
	}

	/**
	 * Returns the text of the ad.
	 *
	 * @since 1.0
	 *
	 * @return string text of the ad
	 */
	public function get_text()
	{
		return $this->text;
	}

	/**
	 * Sets the text of the ad and fires validation.
	 *
	 * @since 1.0
	 *
	 * @param string $text text of the ad
	 */
	public function set_text( $text )
	{
		/* sets the escaped text */
		$this->text = esc_html( $text, array() );

		/* fires validation */
		$this->validate_presence( __( 'Text', In_Image_Ads_Manager::TEXT_DOMAIN ), $text );
	}

	/**
	 * Returns the url of the ad.
	 *
	 * @since 1.0
	 *
	 * @return string url of the ad
	 */
	public function get_url()
	{
		return $this->url;
	}

	/**
	 * Sets the url of the ad and fires the needed validations.
	 *
	 * @since 1.0
	 *
	 * @param string $url url of the ad
	 */
	public function set_url( $url )
	{
		/* sets the escaped url */
		$this->url = esc_url_raw( $url );

		/* fires validations */
		$url_translation = __( 'URL', In_Image_Ads_Manager::TEXT_DOMAIN );
		$this->validate_presence( $url_translation, $url );
		$this->validate_url( $url_translation, $url );
	}

	/**
	 * Returns the image width of the image that gets overlayed by the ad.
	 *
	 * @since 1.0
	 *
	 * @return int image width of the image that gets overlayed by the ad
	 */
	public function get_image_width()
	{
		return $this->image_width;
	}

	/**
	 * Sets the image width of the image that gets overlayed by the ad and fires
	 * the needed validations.
	 *
	 * @since 1.0
	 *
	 * @param string $image_width image width of the image that gets overlayed by the ad
	 */
	public function set_image_width( $image_width )
	{
		/* sets image width as integer */
		$this->image_width = (int)$image_width;

		/* fires validation */
		$this->validate_image_size( __( 'Image Width', In_Image_Ads_Manager::TEXT_DOMAIN ), $image_width );
	}

	/**
	 * Returns the image height of the image that gets overlayed by the ad.
	 *
	 * @since 1.0
	 *
	 * @return int image height of the image that gets overlayed by the ad
	 */
	public function get_image_height()
	{
		return $this->image_height;
	}

	/**
	 * Sets the image height of the image that gets overlayed by the ad and fires
	 * the needed validations.
	 *
	 * @since 1.0
	 *
	 * @param string $image_height image height of the image that gets overlayed by the ad
	 */
	public function set_image_height( $image_height )
	{
		/* sets image height as integer */
		$this->image_height = (int)$image_height;

		/* fires validation */
		$this->validate_image_size( __( 'Image Height', In_Image_Ads_Manager::TEXT_DOMAIN ), $image_height );
	}

	/**
	 * Returns the colors of the ad.
	 *
	 * @since 1.0
	 *
	 * @return array colors of the ad
	 */
	public function get_colors()
	{
		return $this->colors;
	}

	/**
	 * Sets all colors and fires the needed validations.
	 *
	 * @since 1.0
	 *
	 * @param array $colors all ad colors
	 */
	public function set_colors( $colors )
	{
		/* sets colors */
		$this->colors = $colors;

		/* if no colors given... */
		if ( empty( $this->colors ) ) {
			/* sets defaults colors */
			$this->colors = $this->default_colors;
		} else {
			/* loops through all colors */
			foreach ( $this->colors as $key => $color ) {
				/* sets the escaped color */
				$this->colors[ $key ] = esc_html( $color, array() );
			}

			/* fires background color validations */
			$background_translation = __( 'Background Color', In_Image_Ads_Manager::TEXT_DOMAIN );
			$this->validate_presence( $background_translation, $colors[ self::COLOR_BACKGROUND ] );
			$this->validate_color( $background_translation, $colors[ self::COLOR_BACKGROUND ] );

			/* fires title color validations */
			$title_translation = __( 'Title Color', In_Image_Ads_Manager::TEXT_DOMAIN );
			$this->validate_presence( $title_translation, $colors[ self::COLOR_TITLE ] );
			$this->validate_color( 'Title', $colors[ self::COLOR_TITLE ] );

			/* fires text color validations */
			$text_translation = __( 'Text Color', In_Image_Ads_Manager::TEXT_DOMAIN );
			$this->validate_presence( $text_translation, $colors[ self::COLOR_TEXT ] );
			$this->validate_color( $text_translation, $colors[ self::COLOR_TEXT ] );
		}
	}

	/**
	 * Returns true or false as indicator whether ad is valid or not.
	 *
	 * @since 1.0
	 *
	 * @return bool true if ad is valid otherwise false
	 */
	public function is_valid()
	{
		return !$this->has_errors;
	}

	/**
	 * Validates the presence of the value of the given field.
	 *
	 * @since 1.0
	 *
	 * @param string $field name of the input field
	 * @param string $value value of the input field
	 */
	private function validate_presence( $field, $value )
	{
		/* trims the value to remove whitespaces */
		$value = trim( $value );

		/* if value is empty... */
		if ( empty( $value ) ) {
			/* adds an error for the given field */
			$this->add_error( $field, __( 'This field is required and must not be empty.', In_Image_Ads_Manager::TEXT_DOMAIN ) );
		}
	}

	/**
	 * Validates that the value of the given field is a valid image size.
	 *
	 * @since 1.0
	 *
	 * @param string $field name of the input field
	 * @param string $size  value of the input field - size expected
	 */
	private function validate_image_size( $field, $size )
	{
		/* integer typcast of size */
		$size = (int)$size;

		/* if size is not an positive integer or less than 100... */
		if ( !absint( $size ) && $size < 100 ) {
			/* adds an error for the given field */
			$this->add_error( $field, __( 'Must be an positive integer greater than or equal 100.', In_Image_Ads_Manager::TEXT_DOMAIN ) );
			/* if size has more than 4 digits... */
		} elseif ( strlen( (string)$size ) > 4 ) {
			/* adds an error for the given field */
			$this->add_error( $field, __( 'Maximum number of digits is 4.', In_Image_Ads_Manager::TEXT_DOMAIN ) );
		}
	}

	/**
	 * Validates that the value of the given field is a valid url.
	 *
	 * @since 1.0
	 *
	 * @param string $field name of the input field
	 * @param string $url value of the input field - url expected
	 */
	private function validate_url( $field, $url )
	{
		$url = esc_url( $url );

		if ( !preg_match( '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/i', $url ) ) {
			$this->add_error( $field, __( 'Must be a valid URL.', In_Image_Ads_Manager::TEXT_DOMAIN ) );
		}
	}

	/**
	 * Validates that the value of the given field is a valid color.
	 *
	 * @since 1.0
	 *
	 * @param string $field name of the input field
	 * @param string $color value of the input field - expected color
	 */
	private function validate_color( $field, $color )
	{
		if ( !preg_match( '/^#([\d|[abcdefABCDEF]{6})$/', $color ) ) {
			$this->add_error( $field, __( 'Must be a valid CSS-HEX color format.', In_Image_Ads_Manager::TEXT_DOMAIN ) );
		}
	}

	/**
	 * Adds an given error message for a given field name.
	 *
	 * @since 1.0
	 *
	 * @param string $field      name of the field that has an error
	 * @param string $error_text message of the error
	 */
	private function add_error( $field, $error_text )
	{
		$this->errors[ $field ] = $error_text;
		$this->has_errors = true;
	}

	/**
	 * Returns the error messages.
	 *
	 * @since 1.0
	 *
	 * @return array error messages
	 */
	public function get_errors()
	{
		return $this->errors;
	}
}
<?php
/**
 * Main plugin class.
 *
 * @package acf-taxonomy-relationship
 */

namespace Mihdan\ACF_Taxonomy_Relationship;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Main
 *
 * @package Mihdan\ACF_Taxonomy_Relationship
 */
class Main {

	/**
	 * Filed settings.
	 *
	 * @var array $settings
	 */
	private $settings;

	/**
	 *  __construct
	 *
	 *  This function will setup the class functionality
	 *
	 * @date    17/02/2016
	 *
	 * @return    void
	 * @since    1.0.0
	 */
	public function __construct() {

		// settings
		// - these will be passed into the field class.
		$this->settings = array(
			'version' => '1.0',
			'url'     => plugin_dir_url( ACF_TAXONOMY_RELATIONSHIP_FILE ),
			'path'    => plugin_dir_path( ACF_TAXONOMY_RELATIONSHIP_FILE ),
		);

		// include field.
		add_action( 'acf/include_field_types', array( $this, 'include_field' ) );
	}

	/**
	 * Include field.
	 *
	 * This function will include the field type class
	 *
	 * @date    17/02/2016
	 *
	 * @param int|boolean $version Major ACF version. Defaults to false.
	 *
	 * @return    void
	 * @since    1.0.0
	 */
	public function include_field( $version = false ) {

		// load textdomain.
		load_plugin_textdomain( 'acf-taxonomy-relationship', false, plugin_basename( dirname( __FILE__ ) ) . '/lang' );

		// include.
		include_once ACF_TAXONOMY_RELATIONSHIP_DIR . '/classes/class-taxonomy-relationship.php';
	}

}

// eol.

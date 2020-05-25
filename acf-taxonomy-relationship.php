<?php
/**
 * Plugin Name: Mihdan: ACF Taxonomy Relationship
 * Description: Extends Advanced Custom Fields to allow you to select and order Taxonomy Terms in the same way the standard Relationship field allows with Posts.
 * Version: 1.0
 * Author: Mikhail Kobzarev, Dan Beckett
 * Author URI: https://www.kobzarev.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package acf-taxonomy-relationship
 * @link https://github.com/AdvancedCustomFields/acf-field-type-template
 * @link https://www.advancedcustomfields.com/resources/creating-a-new-field-type/
 * @link https://github.com/AdvancedCustomFields/docs/blob/master/guides/javascript-api.md
 * @link https://github.com/DanBeckett/acf-taxonomy-relationship
 */

namespace Mihdan\ACF_Taxonomy_Relationship;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ACF_TAXONOMY_RELATIONSHIP_SLUG', 'acf-taxonomy-relationship' );
define( 'ACF_TAXONOMY_RELATIONSHIP_VERSION', '1.0' );
define( 'ACF_TAXONOMY_RELATIONSHIP_FILE', __FILE__ );
define( 'ACF_TAXONOMY_RELATIONSHIP_DIR', __DIR__ );
define( 'ACF_TAXONOMY_RELATIONSHIP_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

require_once ACF_TAXONOMY_RELATIONSHIP_DIR . '/classes/class-main.php';

new Main();

// eol.

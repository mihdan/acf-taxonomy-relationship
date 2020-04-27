<?php
/**
 * Taxonomy_Relationship
 *
 * @package acf-taxonomy-relationship
 */

namespace Mihdan\ACF_Taxonomy_Relationship;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Taxonomy_Relationship
 *
 * @package Mihdan\ACF_Taxonomy_Relationship
 */
class Taxonomy_Relationship extends \acf_field {

	/**
	 * Will hold info such as dir / path.
	 *
	 * @var array $settings
	 */
	private $settings;

	/**
	 * Will hold default field options.
	 *
	 * @var array $defaults
	 */
	public $defaults;


	/**
	 * __construct
	 *
	 * Set name / label needed for actions / filters
	 *
	 * @param array $settings .
	 *
	 * @since    3.6
	 * @date    23/01/13
	 */
	public function __construct( $settings ) {

		$this->name     = 'taxonomy_relationship';
		$this->label    = __( 'Taxonomy Relationship' );
		$this->category = __( 'Relational', 'acf-taxonomy-relationship' );
		$this->defaults = array(
			'max'           => '',
			'taxonomy'      => array( 'all' ),
			'filters'       => array( 'search' ),
			'return_format' => 'object',
		);
		$this->l10n     = array(
			'max'     => __( 'Maximum values reached ( {max} values )', 'acf-taxonomy-relationship' ),
			'tmpl_li' => '
							<li>
								<a href="#" data-term_id="<%= term_id %>"><%= title %><span class="acf-button-remove"></span></a>
								<input type="hidden" name="<%= name %>[]" value="<%= term_id %>" />
							</li>
							',
		);

		$this->settings = $settings;

		// do not delete!
		parent::__construct();

		// extra.
		add_action( 'wp_ajax_acf/fields/taxonomy_relationship/query_terms', array( $this, 'ajax_query' ) );
		add_action( 'wp_ajax_nopriv_acf/fields/taxonomy_relationship/query_terms', array( $this, 'ajax_query' ) );
	}

	/*
	*  load_field()
	*
	*  This filter is appied to the $field after it is loaded from the database
	*
	*  @type filter
	*  @since 3.6
	*  @date 23/01/13
	*
	*  @param $field - the field array holding all the field options
	*
	*  @return $field - the field array holding all the field options
	*/

	function load_field( $field ) {

		if ( ! $field['taxonomy'] || ! is_array( $field['taxonomy'] ) || in_array( '', $field['taxonomy'] ) ) {
			$field['taxonomy'] = array( 'all' );
		}

		// filters
		if ( ! is_array( $field['filters'] ) ) {
			$field['filters'] = array();
		}


		// return
		return $field;
	}


	/*
	* get_terms_and_filter
	*
	* @description: now we're querying terms instead of posts, this replaces posts_where
	* created: 16/07/14
	*/

	function get_terms_and_filter( $taxonomy, $like_title ) {
		$terms = get_terms( $taxonomy );
		foreach ( $terms as $key => $term ) {
			if ( stripos( $term->name, $like_title ) === false ) {
				unset( $terms[ $key ] );
			}
		}
		$filtered_terms = $terms;

		return $filtered_terms;
	}

	public function ajax_query() {

		// validate
		if( !acf_verify_ajax() ) die();


		// get choices
		$response = $this->get_ajax_query( $_POST );


		// return
		acf_send_ajax_results($response);

	}

	/*
	*  query_terms
	*
	*  @description:
	*  @since: 3.6
	*  @created: 27/01/13
	*/

	function get_ajax_query( $options = array() ) {
		// vars
		$r = array(
			'html' => '',
		);

		// defaults
		$options = wp_parse_args($options, array(
			's'         => '',
			'lang'      => false,
			'field_key' => '',
			'nonce'     => '',
			'ancestor'  => false,
		));

        die;

		// WPML
		if ( $options['lang'] ) {
			global $sitepress;

			if ( ! empty( $sitepress ) ) {
				$sitepress->switch_lang( $options['lang'] );
			}
		}


		// convert types
		$options['taxonomy'] = explode( ',', $options['taxonomy'] );

		// search
		if ( $options['s'] ) {
			$options['like_title'] = $options['s'];
		}

		unset( $options['s'] );


		// load field
		$field = array();
		if ( $options['ancestor'] ) {
			$ancestor = apply_filters( 'acf/load_field', array(), $options['ancestor'] );
			$field    = acf_get_child_field_from_parent_field( $options['field_key'], $ancestor );
		} else {
			$field = apply_filters( 'acf/load_field', array(), $options['field_key'] );
		}


		// get the post from which this field is rendered on
		$the_post = get_post( $options['post_id'] );

		// filters
		$options = apply_filters( 'acf/fields/taxonomy_relationship/query', $options, $field, $the_post );
		$options = apply_filters( 'acf/fields/taxonomy_relationship/query/name=' . $field['_name'],
			$options,
			$field,
			$the_post );
		$options = apply_filters( 'acf/fields/taxonomy_relationship/query/key=' . $field['key'],
			$options,
			$field,
			$the_post );

		// query

		$total_terms = array();
		if ( is_array( $options['taxonomy'] ) ) {
			$tax = $options['taxonomy'];
			if ( in_array( 'all', $tax ) ) {
				$taxonomy_args = array( 'public' => true );
				$taxonomies    = get_taxonomies( $taxonomy_args, 'names' );
				foreach ( $taxonomies as $t => $taxonomy ) {
					if ( $options['like_title'] ) {
						$terms = $this->get_terms_and_filter( $taxonomy, $options['like_title'] );
					} else {
						$terms = get_terms( $taxonomy );
					}
					$total_terms = array_merge( $total_terms, $terms );
				};
			} else {
				foreach ( $tax as $t => $taxonomy ) {
					if ( $options['like_title'] ) {
						$terms = $this->get_terms_and_filter( $taxonomy, $options['like_title'] );
					} else {
						$terms = get_terms( $taxonomy );
					}
					$total_terms = array_merge( $total_terms, $terms );
				}
			};
		} else {
			$tax = $options['taxonomy'];
			if ( $options['like_title'] ) {
				$total_terms = $this->get_terms_and_filter( $tax, $options['like_title'] );
			} else {
				$total_terms = get_terms( $tax );
			}
		};

		// global
		global $post;

		foreach ( $total_terms as $term_name => $term ) {
			$title = '<span class="relationship-item-info">';
			$title .= $term->taxonomy;
			$title .= '</span>';
			$title .= apply_filters( 'the_title', $term->name, $term->term_id );

			// WPML
			if ( $options['lang'] ) {
				$title .= ' (' . $options['lang'] . ')';
			}

			///update html
			$r['html'] .= '<li><a href="' . get_bloginfo( 'home' ) . '/' . $term->taxonomy . '/' . $term->slug . '" data-term_id="' . $term->term_id . '">' . $title . '<span class="acf-button-add"></span></a></li>';
		}

		wp_reset_postdata();

		// return JSON
		echo json_encode( $r );

		die();

	}


	/**
	 * Create_field().
	 *
	 * Create the HTML interface for your field
	 *
	 * @param    $field - an array holding all the field's data
	 *
	 * @type    action
	 * @since    3.6
	 * @date    23/01/13
	 */
	function render_field( $field ) { print_r($field);
		// global
		global $post;


		// no row limit?
		if ( ! $field['max'] || $field['max'] < 1 ) {
			$field['max'] = 9999;
		}


		// class
		$class = '';
		if ( $field['filters'] ) {
			foreach ( $field['filters'] as $filter ) {
				$class .= ' has-' . $filter;
			}
		}

		$attributes = array(
			'max'       => $field['max'],
			's'         => '',
			'taxonomy'  => implode( ',', $field['taxonomy'] ),
			'field_key' => $field['key'],
		);


		// Lang
		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$attributes['lang'] = ICL_LANGUAGE_CODE;
		}


		// parent
		preg_match( '/\[(field_.*?)\]/', $field['name'], $ancestor );
		if ( isset( $ancestor[1] ) && $ancestor[1] != $field['key'] ) {
			$attributes['ancestor'] = $ancestor[1];
		}

		?>
        <div class="acf_taxonomy_relationship<?php echo $class; ?>"<?php foreach ( $attributes as $k => $v ): ?> data-<?php echo $k; ?>="<?php echo $v; ?>"<?php endforeach; ?>>


            <!-- Hidden Blank default value -->
            <input type="hidden" name="<?php echo $field['name']; ?>" value=""/>


            <!-- Left List -->
            <div class="relationship_left">
                <table class="widefat">
                    <thead>
					<?php if ( in_array( 'search', $field['filters'] ) ): ?>
                        <tr>
                            <th>
                                <input class="relationship_search" placeholder="<?php _e( "Search...", 'acf' ); ?>"
                                       type="text" id="relationship_<?php echo $field['name']; ?>"/>
                            </th>
                        </tr>
					<?php endif; ?>
                    </thead>
                </table>
                <ul class="bl relationship_list">
                    <li class="load-more">
                        <div class="acf-loading"></div>
                    </li>
                </ul>
            </div>
            <!-- /Left List -->

            <!-- Right List -->
            <div class="relationship_right">
                <ul class="bl relationship_list">
					<?php
					if ( $field['value'] ) {
						foreach ( $field['value'] as $key => $term_id ) {
							$term_object = '';
							if ( is_array( $field['taxonomy'] ) ) {
								$tax = $field['taxonomy'];
								if ( in_array( 'all', $tax ) ) {
									$taxonomy_args = array( 'public' => true );
									$tax           = get_taxonomies( $taxonomy_args, 'names' );
									foreach ( $tax as $t => $taxonomy ) {
										if ( term_exists( $term_id, $taxonomy ) ) {
											$term_object = get_term( $term_id, $taxonomy );
										}
									}
								} else {
									foreach ( $tax as $t => $taxonomy ) {
										if ( term_exists( $term_id, $taxonomy ) ) {
											$term_object = get_term( $term_id, $taxonomy );
										}
									}
								};
							} else {
								if ( term_exists( $term_id, $taxonomy ) ) {
									$term_object = get_term( $term_id, $field['taxonomy'] );
								}
							};

							// right aligned info
							$title = '<span class="relationship-item-info">';
							$title .= $term_object->taxonomy;
							$title .= '</span>';

							// find title. Could use get_the_title, but that uses get_post(), so I think this uses less Memory

							$title .= apply_filters( 'the_title', $term_object->name, $term_object->term_id );

							$fieldnewslot = $field['name'] . "[]";
							$termlink     = get_term_link( $term_object->term_id, $term_object->taxonomy );

							if ( ! is_wp_error( $termlink ) ) {

								echo '<li>
						<a href="' . $termlink . '" class="" data-term_id="' . $term_object->term_id . '">' . $title . '
							<span class="acf-button-remove"></span>
						</a>
						<input type="hidden" name="' . $fieldnewslot . '" value="' . $term_object->term_id . '" />
					</li>';

							};

						}
					}

					?>
                </ul>
            </div>
            <!-- / Right List -->

        </div>
		<?php
	}

	/**
	 * Render field settings.
	 *
	 * Create extra options for your field. This is rendered when editing a field.
	 * The value of $field['name'] can be used (like bellow) to save extra data to the $field
	 *
	 * @param array $field Array holding all the field's data.
	 */
	public function render_field_settings( $field ) {

		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Return Value', 'acf-taxonomy-relationship' ),
				'instructions' => __( 'Specify the returned value on front end', 'acf-taxonomy-relationship' ),
				'type'         => 'radio',
				'name'         => 'return_format',
				'layout'       => 'horizontal',
				'choices'      => array(
					'object' => __( 'Term Object', 'acf-taxonomy-relationship' ),
					'id'     => __( 'Term ID', 'acf-taxonomy-relationship' ),
				),
			)
		);

		$choices = array(
			'' => array(
				'all' => __( 'All', 'acf-taxonomy-relationship' ),
			),
		);

		$taxonomy_args = array( 'public' => true );
		$taxonomies    = get_taxonomies( $taxonomy_args, 'objects' );

		foreach ( $taxonomies as $tax_name => $taxonomy ) {
			$labels_object            = $taxonomy->labels;
			$choices[''][ $tax_name ] = $labels_object->name;
		}

		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Taxonomy', 'acf-taxonomy-relationship' ),
				'instructions' => __( 'Select the taxonomy to be displayed', 'acf-taxonomy-relationship' ),
				'type'         => 'select',
				'name'         => 'taxonomy',
				'choices'      => acf_get_taxonomy_labels(),
				'multiple'     => 1,
				'ui'           => 1,
			)
		);

		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Filters', 'acf-taxonomy-relationship' ),
				'instructions' => __( 'Select the taxonomy to be displayed', 'acf-taxonomy-relationship' ),
				'type'         => 'checkbox',
				'name'         => 'filters',
				'choices'      => array(
					'search' => __( 'Search', 'acf-taxonomy-relationship' ),
				),
			)
		);

		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Maximum terms', 'acf-taxonomy-relationship' ),
				'instructions' => __( 'Select the taxonomy to be displayed', 'acf-taxonomy-relationship' ),
				'type'         => 'number',
				'name'         => 'max',
				'value'        => $field['max'],
			)
		);
	}

	/**
	 * Input_admin_enqueue_scripts()
	 *
	 * This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
	 * Use this action to add CSS + JavaScript to assist your render_field() action.
	 */
	public function input_admin_enqueue_scripts() {
		// register ACF scripts.
		wp_register_script(
			'acf-taxonomy-relationship',
			$this->settings['url'] . 'assets/js/input.js',
			array( 'underscore', 'acf-input' ),
			$this->settings['version'],
			true
		);

		wp_register_style(
			'acf-taxonomy-relationship',
			$this->settings['url'] . 'assets/css/input.css',
			array( 'acf-input' ),
			$this->settings['version']
		);

		// scripts.
		wp_enqueue_script( 'acf-taxonomy-relationship' );

		// styles.
		wp_enqueue_style( 'acf-taxonomy-relationship' );
	}

	/*
	*  format_value()
	*
	*  This filter is appied to the $value after it is loaded from the db and before it is passed to the create_field action
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value	- the value which was loaded from the database
	*  @param	$post_id - the $post_id from which the value was loaded
	*  @param	$field	- the field array holding all the field options
	*
	*  @return	$value	- the modified value
	*/

	function format_value( $value, $post_id, $field ) {
		// empty?
		if ( ! empty( $value ) ) {
			// Pre 3.3.3, the value is a string coma seperated
			if ( is_string( $value ) ) {
				$value = explode( ',', $value );
			}


			// convert to integers
			if ( is_array( $value ) ) {
				$value = array_map( 'intval', $value );

			}

		}


		// return value
		return $value;
	}

	/*
	*  format_value_for_api()
	*
	*  This filter is appied to the $value after it is loaded from the db and before it is passed back to the api functions such as the_field
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value	- the value which was loaded from the database
	*  @param	$post_id - the $post_id from which the value was loaded
	*  @param	$field	- the field array holding all the field options
	*
	*  @return	$value	- the modified value
	*/

	function format_value_for_api( $value, $post_id, $field ) {
		// empty?
		if ( ! $value ) {
			return $value;
		}


		// Pre 3.3.3, the value is a string coma seperated
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}


		// empty?
		if ( ! is_array( $value ) || empty( $value ) ) {
			return $value;
		}


		// convert to integers
		$value = array_map( 'intval', $value );


		// return format
		if ( $field['return_format'] == 'object' ) {
			$return_array = array();
			foreach ( $value as $key => $term_id ) {
				$term_object = '';
				if ( is_array( $field['taxonomy'] ) ) {
					$tax = $field['taxonomy'];
					if ( in_array( 'all', $tax ) ) {
						$taxonomy_args = array( 'public' => true );
						$tax           = get_taxonomies( $taxonomy_args, 'names' );
						foreach ( $tax as $t => $taxonomy ) {
							if ( term_exists( $term_id, $taxonomy ) ) {
								$return_array[] = get_term( $term_id, $taxonomy );
							}
						}
					} else {
						foreach ( $tax as $t => $taxonomy ) {
							if ( term_exists( $term_id, $taxonomy ) ) {
								$return_array[] = get_term( $term_id, $taxonomy );
							}
						}
					};
				} else {
					if ( term_exists( $term_id, $taxonomy ) ) {
						$return_array[] = get_term( $term_id, $field['taxonomy'] );
					}
				};

			};

			$value = $return_array;
		};


		// return
		return $value;

	}

}


// create field.
new Taxonomy_Relationship( $this->settings );

// eol.

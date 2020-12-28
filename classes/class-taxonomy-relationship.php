<?php
/**
 * Taxonomy_Relationship
 *
 * @package acf-taxonomy-relationship
 */

namespace Mihdan\ACF_Taxonomy_Relationship;

use acf_field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Taxonomy_Relationship extends acf_field {


	/**
	 *  __construct
	 *
	 *  This function will setup the field type data
	 *
	 *  @type	function
	 *  @date	5/03/2014
	 *  @since	5.0.0
	 *
	 *  @param	n/a
	 *  @return	n/a
	 */
	public function initialize() {

		// vars.
		$this->name     = 'taxonomy_relationship';
		$this->label    = __( 'Taxonomy Relationship', 'acf' );
		$this->category = 'relational';
		$this->defaults = array(
			'post_type'     => array(),
			'taxonomy'      => array(),
			'min'           => 0,
			'max'           => 0,
			'filters'       => array( 'search', 'post_type', 'taxonomy' ),
			'elements'      => array(),
			'return_format' => 'object',
		);

		// extra.
		add_action( 'wp_ajax_acf/fields/taxonomy_relationship/query', array( $this, 'ajax_query' ) );
		add_action( 'wp_ajax_nopriv_acf/fields/taxonomy_relationship/query', array( $this, 'ajax_query' ) );

		parent::initialize();
	}


	/**
	 * Input_admin_enqueue_scripts.
	 */
	public function input_admin_enqueue_scripts() {
		wp_enqueue_style( ACF_TAXONOMY_RELATIONSHIP_SLUG, ACF_TAXONOMY_RELATIONSHIP_URL . '/assets/css/input.css', array( 'acf-global' ), ACF_TAXONOMY_RELATIONSHIP_VERSION );
		wp_enqueue_script( ACF_TAXONOMY_RELATIONSHIP_SLUG, ACF_TAXONOMY_RELATIONSHIP_URL . '/assets/js/input.js', array( 'acf-input' ), ACF_TAXONOMY_RELATIONSHIP_VERSION, false );

		// localize.
		acf_localize_text(
			array(
				'Minimum values reached ( {min} values )' => __( 'Minimum values reached ( {min} values )', 'acf' ),
				'Maximum values reached ( {max} values )' => __( 'Maximum values reached ( {max} values )', 'acf' ),
				'Loading'                                 => __( 'Loading', 'acf' ),
				'No matches found'                        => __( 'No matches found', 'acf' ),
			)
		);
	}


	/*
	*  ajax_query
	*
	*  description
	*
	*  @type	function
	*  @date	24/10/13
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/

	function ajax_query() {

		// validate
		//if( !acf_verify_ajax() ) die();


		// get choices
		$response = $this->get_ajax_query( $_POST );


		// return
		acf_send_ajax_results($response);

	}


	/**
	 * Get ajax query.
	 *
	 * This function will return an array of data formatted for use in a select2 AJAX response
	 *
	 * @param array $options Options array.
	 *
	 * @return array
	 */
	public function get_ajax_query( $options = array() ) {

		$results   = array();
		$args      = array();
		$s         = false;
		$is_search = false;

		$options = wp_parse_args(
			$options,
			array(
				'post_id'   => 0,
				's'         => '',
				'field_key' => '',
				'paged'     => 1,
				'taxonomy'  => '',
			)
		);

		// load field.
		$field = acf_get_field( $options['field_key'] );
		if ( ! $field ) {
			return array();
		}

		// Default args.
		$args['hide_empty']      = false;
		$args['suppress_filter'] = true;
		$args['orderby']         = 'name';

		// paged.
		$args['posts_per_page'] = 20;
		$args['paged']          = intval( $options['paged'] );

		// taxonomy.
		if ( ! empty( $options['taxonomy'] ) ) {
			$args['taxonomy'] = acf_get_array( $options['taxonomy'] );
		} elseif ( ! empty( $field['taxonomy'] ) ) {
			$args['taxonomy'] = acf_get_array( $field['taxonomy'] );
		}

		// search.
		if ( '' !== $options['s'] ) {

			// strip slashes (search may be integer).
			$s = wp_unslash( strval( $options['s'] ) );

			// update vars.
			$args['name__like'] = $s;
			$is_search          = true;

		}

		// filters.
		// phpcs:disable WordPress.NamingConventions.ValidHookName.UseUnderscores
		$args = apply_filters( 'acf/fields/taxonomy_relationship/query', $args, $field, $options['post_id'] );
		$args = apply_filters( 'acf/fields/taxonomy_relationship/query/name=' . $field['name'], $args, $field, $options['post_id'] );
		$args = apply_filters( 'acf/fields/taxonomy_relationship/query/key=' . $field['key'], $args, $field, $options['post_id'] );
		// phpcs:enable

		$terms = get_terms( $args );
		usort( $terms, array( $this, 'sort_by_name' ) );
		/*
		$hierarchy = array();
		$this->sort_terms_hierarchically( $terms, $hierarchy );

		$terms = $hierarchy;

		if ( $terms ) {
			foreach ( $terms as $term ) {
				if ( count( $term->children ) > 0 ) {
					foreach ( $term->children as $child ) {
						$results[] = $this->get_post_result( $child->term_id, $this->get_post_title( $child, $field ) );
					}
				} else {
					$results[] = $this->get_post_result( $term->term_id, $this->get_post_title( $term, $field ) );
				}
			}
		}
		*/

		if ( $terms ) {
			foreach ( $terms as $term ) {
				$results[] = $this->get_post_result( $term->term_id, $this->get_post_title( $term, $field ) );
			}
		}

		return array(
			'results' => $results,
			'limit'   => 100000,
		);
	}

	/**
	 * Order by name ASC - change > to < to order by DESC
	 *
	 * @param \WP_Term $a Term name.
	 * @param \WP_Term $b Term name.
	 *
	 * @return bool
	 */
	private function sort_by_name( \WP_Term $a, \WP_Term $b ) {
		return $a->name > $b->name;
	}

	/**
	 * Sort terms Hierarchically.
	 *
	 * @param array $cats Array.
	 * @param array $into Array.
	 * @param int   $parent_id Array.
	 */
	private function sort_terms_hierarchically( array &$cats, array &$into, $parent_id = 0 ) {
		foreach ( $cats as $i => $cat ) {
			if ( $cat->parent === $parent_id ) {
				$into[ $cat->term_id ] = $cat;
				unset( $cats[ $i ] );
			}
		}

		foreach ( $into as $top_cat ) {
			$top_cat->children = array();
			$this->sort_terms_hierarchically( $cats, $top_cat->children, $top_cat->term_id );
		}
	}


	/**
	 * Get post result.
	 *
	 * This function will return an array containing id, text and maybe description data
	 *
	 * @param mixed  $id ID.
	 * @param string $text Text.
	 *
	 * @return array
	 */
	private function get_post_result( $id, $text ) {
		return array(
			'id'   => $id,
			'text' => $text,
		);
	}


	/**
	 * Get post title.
	 *
	 * This function returns the HTML for a result
	 *
	 * @param \WP_Term $term Term object.
	 * @param array    $field Field array.
	 * @param int      $post_id The post_id to which this value is saved to.
	 * @param int      $is_search Search or not.
	 *
	 * @return string
	 */
	private function get_post_title( \WP_Term $term, $field, $post_id = 0, $is_search = 0 ) {

		// get post_id.
		if ( ! $post_id ) {
			$post_id = acf_get_form_data( 'post_id' );
		}

		// vars.
		$title = $term->name;

		if ( $term->parent ) {
			$parent = get_term( $term->parent );
			$title  = sprintf( '%s (%s)', $title, $parent->name );
		} else {
			$title = sprintf( '<span style="color: #c56d00; font-weight: bold">%s</span>', $title );
		}

		// filters.
		// phpcs:disable WordPress.NamingConventions.ValidHookName.UseUnderscores
		$title = apply_filters( 'acf/fields/taxonomy_relationship/result', $title, $term, $field, $post_id );
		$title = apply_filters( 'acf/fields/taxonomy_relationship/result/name=' . $field['_name'], $title, $term, $field, $post_id );
		$title = apply_filters( 'acf/fields/taxonomy_relationship/result/key=' . $field['key'], $title, $term, $field, $post_id );
		// phpcs:enable

		return $title;
	}

	/**
	 *  Render field
	 *
	 *  Create the HTML interface for your field
	 *
	 *  @param array $field An array holding all the field's data.
	 */
	public function render_field( $field ) {

		$field['value'] = acf_get_array( $field['value'] );

		// vars.
		$taxonomy = acf_get_array( $field['taxonomy'] );
		$filters  = acf_get_array( $field['filters'] );

		// filters.
		$filter_count            = count( $filters );
		$filter_taxonomy_choices = array();

		// taxonomy filter.
		if ( in_array( 'taxonomy', $filters, true ) ) {

			$term_choices            = array();
			$filter_taxonomy_choices = array(
				'' => __( 'Select taxonomy', 'acf' ),
			);

			// check for specific taxonomy setting.
			if ( $taxonomy ) {
				$term_choices = acf_get_taxonomy_labels( $taxonomy );
			}

			// append term choices.
			$filter_taxonomy_choices = $filter_taxonomy_choices + $term_choices;

		}

		// div attributes.
		$atts = array(
			'id'             => $field['id'],
			'class'          => "acf-taxonomy-relationship {$field['class']}",
			'data-min'       => $field['min'],
			'data-max'       => $field['max'],
			'data-s'         => '',
			'data-paged'     => 1,
			'data-post_type' => '',
			'data-taxonomy'  => '',
		);

		?>
		<div <?php acf_esc_attr_e($atts); ?>>
			<?php acf_hidden_input( array('name' => $field['name'], 'value' => '') ); ?>
			<?php
			/* filters */
			if( $filter_count ): ?>
				<div class="filters -f<?php echo esc_attr($filter_count); ?>">
					<?php

					/* search */
					if( in_array('search', $filters) ): ?>
						<div class="filter -search">
							<?php acf_text_input( array('placeholder' => __("Search...",'acf'), 'data-filter' => 's') ); ?>
						</div>
					<?php endif;

					/* post_type */
					if( in_array('taxonomy', $filters) ): ?>
						<div class="filter -taxonomy">
							<?php acf_select_input( array('choices' => $filter_taxonomy_choices, 'data-filter' => 'taxonomy') ); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="selection">
				<div class="choices">
					<ul class="acf-bl list taxonomy-relationship-choices-list"></ul>
				</div>
				<div class="values">
					<ul class="acf-bl list taxonomy-relationship-values-list">
						<?php if ( ! empty( $field['value'] ) ) : ?>
							<?php
							// get posts.
							$args = array(
								'hide_empty' => false,
								'include'    => $field['value'],
								'taxonomy'   => $field['taxonomy'],
							);

							$terms = get_terms( $args );
							?>
							<?php foreach ( $terms as $term ) : ?>
								<li>
									<?php acf_hidden_input( array( 'name' => $field['name'] . '[]', 'value' => $term->term_id ) ); ?>
									<span data-id="<?php echo esc_attr( $term->term_id ); ?>" class="acf-rel-item">
										<?php echo $this->get_post_title( $term, $field ); ?>
										<a href="#" class="acf-icon -minus small dark" data-name="remove_item"></a>
									</span>
								</li>
							<?php endforeach; ?>
						<?php endif; ?>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 *  Render_field_settings
	 *
	 *  Create extra options for your field. This is rendered when editing a field.
	 *  The value of $field['name'] can be used (like bellow) to save extra data to the $field
	 *
	 *  @param array $field An array holding all the field's data.
	 */
	public function render_field_settings( $field ) {

		// vars.
		$field['min'] = empty( $field['min'] ) ? '' : $field['min'];
		$field['max'] = empty( $field['max'] ) ? '' : $field['max'];

		// taxonomy.
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Filter by Taxonomy', 'acf' ),
				'instructions' => '',
				'required'     => true,
				'type'         => 'select',
				'name'         => 'taxonomy',
				'choices'      => acf_get_taxonomy_labels(),
				'multiple'     => true,
				'ui'           => true,
				'placeholder'  => __( 'All taxonomies', 'acf' ),
			)
		);


		// filters.
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Filters', 'acf' ),
				'instructions' => '',
				'type'         => 'checkbox',
				'name'         => 'filters',
				'choices'      => array(
					'search'   => __( 'Search', 'acf' ),
					//'taxonomy' => __( 'Taxonomy', 'acf' ),
				),
			)
		);

		// min.
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Minimum posts', 'acf' ),
				'instructions' => '',
				'type'         => 'number',
				'name'         => 'min',
			)
		);

		// max.
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Maximum posts', 'acf' ),
				'instructions' => '',
				'type'         => 'number',
				'name'         => 'max',
			)
		);

		// return_format.
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Return Format', 'acf' ),
				'instructions' => '',
				'type'         => 'radio',
				'name'         => 'return_format',
				'choices'      => array(
					'object' => __( 'Post Object', 'acf' ),
					'id'     => __( 'Post ID', 'acf' ),
				),
				'layout'       => 'horizontal',
			)
		);
	}

	/**
	 * This filter is appied to the $value after it is loaded from the db.
	 *
	 * @param array $value The value found in the database.
	 * @param int   $post_id The $post_id from which the value was loaded from.
	 * @param array $field the Field array holding all the field options.
	 *
	 * @return array|false|\WP_Error
	 */
	public function load_value( $value, $post_id, $field ) {
		$info     = acf_get_post_id_info( $post_id );
		$term_ids = wp_get_object_terms( $info['id'], $field['taxonomy'], array( 'fields' => 'ids', 'orderby' => 'none' ) );

		// bail early if no terms.
		if ( empty( $term_ids ) || is_wp_error( $term_ids ) ) {
			return false;
		}


		// sort.
		if ( ! empty( $value ) ) {

			$order = array();

			foreach ( $term_ids as $i => $v ) {

				$order[ $i ] = array_search( $v, $value, true );

			}

			array_multisort( $order, $term_ids );

		}


		// update value.
		$value = $term_ids;

		return $value;
	}

	/*
	*  format_value()
	*
	*  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value which was loaded from the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*
	*  @return	$value (mixed) the modified value
	*/
	function format_value( $value, $post_id, $field ) {

		// bail early if no value
		if( empty($value) ) {

			return $value;

		}


		// force value to array
		$value = acf_get_array( $value );


		// convert to int
		$value = array_map('intval', $value);


		// load posts if needed
		if( $field['return_format'] == 'object' ) {

			// get posts
			$value = acf_get_posts(array(
				'post__in' => $value,
				'post_type'	=> $field['post_type']
			));

		}


		// return
		return $value;

	}


	/*
	*  validate_value
	*
	*  description
	*
	*  @type	function
	*  @date	11/02/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/

	function validate_value( $valid, $value, $field, $input ){

		// default
		if( empty($value) || !is_array($value) ) {

			$value = array();

		}


		// min
		if( count($value) < $field['min'] ) {

			$valid = _n( '%s requires at least %s selection', '%s requires at least %s selections', $field['min'], 'acf' );
			$valid = sprintf( $valid, $field['label'], $field['min'] );

		}


		// return
		return $valid;

	}


	/**
	 *  This filter is appied to the $value before it is updated in the db
	 *
	 * @param mixed $value   - the value which will be saved in the database
	 * @param int   $post_id - the $post_id of which the value will be saved
	 * @param    $field   - the field array holding all the field options
	 *
	 * @return    $value - the modified value
	 * @since     3.6
	 * @date      23/01/13
	 *
	 */
	public function update_value( $value, $post_id, $field ) {

		// Bail early if no value.
		if ( empty( $value ) ) {
			return $value;
		}

		$info     = acf_get_post_id_info( $post_id );
		$term_ids = acf_get_array( $value );
		$term_ids = array_map( 'intval', $term_ids );

		foreach ( $field['taxonomy'] as $taxonomy ) {
			wp_set_object_terms( $info['id'], $term_ids, $taxonomy, false );
		}

		delete_field( $field['key'], $info['id'] );

		return false;
	}

}


// initialize.
acf_register_field_type( __NAMESPACE__ . '\Taxonomy_Relationship' );

// eol.

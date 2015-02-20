<?php
/**
 *
 * @package America.gov
 * @author Office of Design, Bureau of International Information Programs
 * @license GPL-2.0+
 */

/**
 * Custom Featured Category widget class.
 *
 */
class America_Featured_Category extends WP_Widget {

	/**
	 * Holds widget settings defaults, populated in constructor.
	 *
	 * @var array
	 */
	protected $defaults;

	/**
	 * Constructor. Set the default widget options and create widget.
	 *
	 * @since 0.1.8
	 */
	function __construct() {

		$this->defaults = array(
			'title'           			=> '',
			'taxonomy'					=> 'category', //TODO: Make this more flexible, to include all taxonomies, even custom taxonomies
			'category_id'     			=> '',
			'category_description'		=> '',
			'image'						=> '', //TODO: Build this out to choose from media library, with image_size, etc.
			'image_alt'					=> '', //TODO: Build this out to choose from media library, with image_size, etc.
			'show_image'      			=> 0,
			'image_alignment' 			=> '',
			//'image_size'      		=> '',
			'show_title'      			=> 0,
			'show_description'			=> 0,
		);

		$widget_ops = array(
			'classname'   => 'featured-content featured-category',
			'description' => __( 'Displays featured category with thumbnails', 'genesis' ),
		);

		$control_ops = array(
			'id_base' => 'featured-category',
			'width'   => 200,
			'height'  => 250,
		);

		parent::__construct( 'featured-category', __( 'America - Featured Category', 'genesis' ), $widget_ops, $control_ops );

	}

	/**
	 * Echo the widget content.
	 *
	 * @since 0.1.8
	 *
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget
	 */
	function widget( $args, $instance ) {

		//* Merge with defaults
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		echo $args['before_widget'];

		//* Check for a Widget title to display
		if ( ! empty( $instance['title'] ) )
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) . $args['after_title'];


		//* Get the category. If it exists, it returns a category object. See: http://codex.wordpress.org/Function_Reference/get_term_by
		$category = get_term_by( 'id', $instance['category_id'], $instance['taxonomy'] );


		//* Get the category link and assign it to the category object.
		$category->url = get_category_link( $instance['category_id'] );


		if ( ! $category == false ) {
			genesis_markup( array(
				'html5'   => '<article %s>',
				'xhtml'   => sprintf( '<div class="%s">', implode( ' ', get_post_class() ) ),
				'context' => 'entry',
				) );


			if ( $instance['show_image'] && $instance['image'] && $instance['image_alt'] )
				printf( '<a href="%s" class="%s"><img src="%s" alt="%s" class="entry-image" itemprop="image"></a>', $category->url, esc_attr( $instance['image_alignment'] ), esc_html( $instance['image'] ), esc_html( $instance['image_alt'] ) );


			if ( ! empty( $instance['show_title'] ) ) {

				$title = $category->name;

				if ( genesis_html5() ) {
					printf( '<header class="entry-header"><h3 class="entry-title"><a href="%s">%s</a></h3></header>', $category->url, esc_html( $title ) );
				} else {
					printf( '<h3><a href="%s">%s</a></h3>', $category->url, esc_html( $title ) );
				}
			}


			if ( ! empty( $instance['show_description'] ) ) {

				echo genesis_html5() ? '<div class="entry-description">' : '';

				//* If custom category description isn't empty, assign it
				if ( ! empty( $instance['category_description'] ) ) {
					$description = $instance['category_description'];

					//* If custom category description is empty, assign normal category description
				} else if ( ! empty( $category->description ) ) {
					$description = $category->description;

					//* Otherwise print "No description"
				} else {
					$description = __( 'No description', 'genesis' );
				}

				printf( '<p>%s</p>', esc_html( $description ) );

				echo genesis_html5() ? '</div>' : '';

			}

			genesis_markup( array(
				'html5' => '</article>',
				'xhtml' => '</div>',
			) );
		}

		echo $args['after_widget'];

	}

	/**
	 * Update a particular instance.
	 *
	 * This function should check that $new_instance is set correctly.
	 * The newly calculated value of $instance should be returned.
	 * If "false" is returned, the instance won't be saved/updated.
	 *
	 * @since 0.1.8
	 *
	 * @param array $new_instance New settings for this instance as input by the user via form()
	 * @param array $old_instance Old settings for this instance
	 * @return array Settings to save or bool false to cancel saving
	 */
	function update( $new_instance, $old_instance ) {

		$new_instance['title']     = strip_tags( $new_instance['title'] );
		return $new_instance;

	}

	/**
	 * Echo the settings update form in the Admin Panel.
	 *
	 * @since 0.1.8
	 *
	 * @param array $instance Current settings
	 */
	function form( $instance ) {

		//* Merge with defaults
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Widget Title', 'genesis' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" />
		</p>


		<hr class="div" />


		<p>
			<label for="<?php echo $this->get_field_id( 'category_id' ); ?>"><?php _e( 'Category', 'genesis' ); ?>:</label>
			<?php wp_dropdown_categories( array( 'name' => $this->get_field_name( 'category_id' ), 'selected' => $instance['category_id'] ) ); ?>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'category_description' ); ?>"><?php _e( 'Custom Category Description', 'genesis' ); ?>:</label>
			<textarea placeholder="A custom description. If left blank, it will default to category description." rows="3" id="<?php echo $this->get_field_id( 'category_description' ); ?>" name="<?php echo $this->get_field_name( 'category_description' ); ?>" class="widefat"><?php echo esc_attr( $instance['category_description'] ); ?></textarea>
		</p>


		<hr class="div" />


		<p>
			<label for="<?php echo $this->get_field_id( 'image' ); ?>"><?php _e( 'Widget Image (link)', 'genesis' ); ?>:</label>
			<input type="url" id="<?php echo $this->get_field_id( 'image' ); ?>" name="<?php echo $this->get_field_name( 'image' ); ?>" value="<?php echo esc_attr( $instance['image'] ); ?>" class="widefat" placeholder="Link to thumbnail '-500x500.jpg'" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'image_alt' ); ?>"><?php _e( 'Image alt text', 'genesis' ); ?>:</label>
			<input type="url" id="<?php echo $this->get_field_id( 'image_alt' ); ?>" name="<?php echo $this->get_field_name( 'image_alt' ); ?>" value="<?php echo esc_attr( $instance['image_alt'] ); ?>" class="widefat" placeholder="Required for image to appear" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'image_alignment' ); ?>"><?php _e( 'Image Alignment', 'genesis' ); ?>:</label>
			<select id="<?php echo $this->get_field_id( 'image_alignment' ); ?>" name="<?php echo $this->get_field_name( 'image_alignment' ); ?>">
				<option value="alignnone">- <?php _e( 'None', 'genesis' ); ?> -</option>
				<option value="alignleft" <?php selected( 'alignleft', $instance['image_alignment'] ); ?>><?php _e( 'Left', 'genesis' ); ?></option>
				<option value="alignright" <?php selected( 'alignright', $instance['image_alignment'] ); ?>><?php _e( 'Right', 'genesis' ); ?></option>
				<option value="aligncenter" <?php selected( 'aligncenter', $instance['image_alignment'] ); ?>><?php _e( 'Center', 'genesis' ); ?></option>
			</select>
		</p>


		<hr class="div" />


		<p>
			<input id="<?php echo $this->get_field_id( 'show_image' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'show_image' ); ?>" value="1"<?php checked( $instance['show_image'] ); ?> />
			<label for="<?php echo $this->get_field_id( 'show_image' ); ?>"><?php _e( 'Show Featured Image', 'genesis' ); ?></label>
		</p>

		<p>
			<input id="<?php echo $this->get_field_id( 'show_title' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'show_title' ); ?>" value="1"<?php checked( $instance['show_title'] ); ?> />
			<label for="<?php echo $this->get_field_id( 'show_title' ); ?>"><?php _e( 'Show Category Title', 'genesis' ); ?></label>
		</p>

		<p>
			<input id="<?php echo $this->get_field_id( 'show_description' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'show_description' ); ?>" value="1"<?php checked( $instance['show_description'] ); ?> />
			<label for="<?php echo $this->get_field_id( 'show_description' ); ?>"><?php _e( 'Show Category Description', 'genesis' ); ?></label>
		</p>

		<?php

	}

}

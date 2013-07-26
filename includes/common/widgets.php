<?php

/**
 * Fiscaat Widgets
 *
 * Contains the year list, account list, record list and login form widgets.
 *
 * @package Fiscaat
 * @subpackage Widgets
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Fiscaat Views Widget
 *
 * Adds a widget which displays the view list
 *
 * @since Fiscaat (r3020)
 *
 * @uses WP_Widget
 */
class Fiscaat_Views_Widget extends WP_Widget {

	/**
	 * Fiscaat View Widget
	 *
	 * Registers the view widget
	 *
	 * @since Fiscaat (r3020)
	 *
	 * @uses apply_filters() Calls 'fiscaat_views_widget_options' with the
	 *                        widget options
	 */
	public function __construct() {
		$widget_ops = apply_filters( 'fiscaat_views_widget_options', array(
			'classname'   => 'widget_display_views',
			'description' => __( 'A list of registered optional account views.', 'fiscaat' )
		) );

		parent::__construct( false, __( '(Fiscaat) Account Views List', 'fiscaat' ), $widget_ops );
	}

	/**
	 * Register the widget
	 *
	 * @since Fiscaat (r3389)
	 *
	 * @uses register_widget()
	 */
	public static function register_widget() {
		register_widget( 'Fiscaat_Views_Widget' );
	}

	/**
	 * Displays the output, the view list
	 *
	 * @since Fiscaat (r3020)
	 *
	 * @param mixed $args Arguments
	 * @param array $instance Instance
	 * @uses apply_filters() Calls 'fiscaat_view_widget_title' with the title
	 * @uses fiscaat_get_views() To get the views
	 * @uses fiscaat_view_url() To output the view url
	 * @uses fiscaat_view_title() To output the view title
	 */
	public function widget( $args, $instance ) {

		// Only output widget contents if views exist
		if ( fiscaat_get_views() ) :

			extract( $args );

			$title = apply_filters( 'fiscaat_view_widget_title', $instance['title'] );

			echo $before_widget;
			echo $before_title . $title . $after_title; ?>

			<ul>

				<?php foreach ( fiscaat_get_views() as $view => $args ) : ?>

					<li><a class="fiscaat-view-title" href="<?php fiscaat_view_url( $view ); ?>" title="<?php fiscaat_view_title( $view ); ?>"><?php fiscaat_view_title( $view ); ?></a></li>

				<?php endforeach; ?>

			</ul>

			<?php echo $after_widget;

		endif;
	}

	/**
	 * Update the view widget options
	 *
	 * @since Fiscaat (r3020)
	 *
	 * @param array $new_instance The new instance options
	 * @param array $old_instance The old instance options
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	/**
	 * Output the view widget options form
	 *
	 * @since Fiscaat (r3020)
	 *
	 * @param $instance Instance
	 * @uses Fiscaat_Views_Widget::get_field_id() To output the field id
	 * @uses Fiscaat_Views_Widget::get_field_name() To output the field name
	 */
	public function form( $instance ) {
		$title = !empty( $instance['title'] ) ? esc_attr( $instance['title'] ) : ''; ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'fiscaat' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
			</label>
		</p>

		<?php
	}
}

/**
 * Fiscaat Year Widget
 *
 * Adds a widget which displays the year list
 *
 * @since Fiscaat (r2653)
 *
 * @uses WP_Widget
 */
class Fiscaat_Years_Widget extends WP_Widget {

	/**
	 * Fiscaat Year Widget
	 *
	 * Registers the year widget
	 *
	 * @since Fiscaat (r2653)
	 *
	 * @uses apply_filters() Calls 'fiscaat_years_widget_options' with the
	 *                        widget options
	 */
	public function __construct() {
		$widget_ops = apply_filters( 'fiscaat_years_widget_options', array(
			'classname'   => 'widget_display_years',
			'description' => __( 'A list of years with an option to set the parent.', 'fiscaat' )
		) );

		parent::__construct( false, __( '(Fiscaat) Years List', 'fiscaat' ), $widget_ops );
	}

	/**
	 * Register the widget
	 *
	 * @since Fiscaat (r3389)
	 *
	 * @uses register_widget()
	 */
	public static function register_widget() {
		register_widget( 'Fiscaat_Years_Widget' );
	}

	/**
	 * Displays the output, the year list
	 *
	 * @since Fiscaat (r2653)
	 *
	 * @param mixed $args Arguments
	 * @param array $instance Instance
	 * @uses apply_filters() Calls 'fiscaat_year_widget_title' with the title
	 * @uses get_option() To get the years per page option
	 * @uses current_user_can() To check if the current user can read
	 *                           private() To resety name
	 * @uses fiscaat_has_years() The main year loop
	 * @uses fiscaat_years() To check whether there are more years available
	 *                     in the loop
	 * @uses fiscaat_the_year() Loads up the current year in the loop
	 * @uses fiscaat_year_permalink() To display the year permalink
	 * @uses fiscaat_year_title() To display the year title
	 */
	public function widget( $args, $instance ) {
		extract( $args );

		$title        = apply_filters( 'fiscaat_year_widget_title', $instance['title'] );
		$parent_year = !empty( $instance['parent_year'] ) ? $instance['parent_year'] : '0';

		// Note: private and hidden years will be excluded via the
		// fiscaat_pre_get_posts_exclude_years filter and function.
		$widget_query = new WP_Query( array(
			'post_parent'    => $parent_year,
			'post_type'      => fiscaat_get_year_post_type(),
			'posts_per_page' => get_option( '_fiscaat_years_per_page', 50 ),
			'orderby'        => 'menu_order',
			'order'          => 'ASC'
		) );

		if ( $widget_query->have_posts() ) :

			echo $before_widget;
			echo $before_title . $title . $after_title; ?>

			<ul>

				<?php while ( $widget_query->have_posts() ) : $widget_query->the_post(); ?>

					<li><a class="fiscaat-year-title" href="<?php fiscaat_year_permalink( $widget_query->post->ID ); ?>" title="<?php fiscaat_year_title( $widget_query->post->ID ); ?>"><?php fiscaat_year_title( $widget_query->post->ID ); ?></a></li>

				<?php endwhile; ?>

			</ul>

			<?php echo $after_widget;

			// Reset the $post global
			wp_reset_postdata();

		endif;
	}

	/**
	 * Update the year widget options
	 *
	 * @since Fiscaat (r2653)
	 *
	 * @param array $new_instance The new instance options
	 * @param array $old_instance The old instance options
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                 = $old_instance;
		$instance['title']        = strip_tags( $new_instance['title'] );
		$instance['parent_year'] = $new_instance['parent_year'];

		// Force to any
		if ( !empty( $instance['parent_year'] ) && !is_numeric( $instance['parent_year'] ) ) {
			$instance['parent_year'] = 'any';
		}

		return $instance;
	}

	/**
	 * Output the year widget options form
	 *
	 * @since Fiscaat (r2653)
	 *
	 * @param $instance Instance
	 * @uses Fiscaat_Years_Widget::get_field_id() To output the field id
	 * @uses Fiscaat_Years_Widget::get_field_name() To output the field name
	 */
	public function form( $instance ) {
		$title        = !empty( $instance['title']        ) ? esc_attr( $instance['title']        ) : '';
		$parent_year = !empty( $instance['parent_year'] ) ? esc_attr( $instance['parent_year'] ) : '0'; ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'fiscaat' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'parent_year' ); ?>"><?php _e( 'Parent Year ID:', 'fiscaat' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'parent_year' ); ?>" name="<?php echo $this->get_field_name( 'parent_year' ); ?>" type="text" value="<?php echo $parent_year; ?>" />
			</label>

			<br />

			<small><?php _e( '"0" to show only root - "any" to show all', 'fiscaat' ); ?></small>
		</p>

		<?php
	}
}

/**
 * Fiscaat Account Widget
 *
 * Adds a widget which displays the account list
 *
 * @since Fiscaat (r2653)
 *
 * @uses WP_Widget
 */
class Fiscaat_Accounts_Widget extends WP_Widget {

	/**
	 * Fiscaat Account Widget
	 *
	 * Registers the account widget
	 *
	 * @since Fiscaat (r2653)
	 *
	 * @uses apply_filters() Calls 'fiscaat_accounts_widget_options' with the
	 *                        widget options
	 */
	public function __construct() {
		$widget_ops = apply_filters( 'fiscaat_accounts_widget_options', array(
			'classname'   => 'widget_display_accounts',
			'description' => __( 'A list of recent accounts, sorted by popularity or freshness.', 'fiscaat' )
		) );

		parent::__construct( false, __( '(Fiscaat) Recent Accounts', 'fiscaat' ), $widget_ops );
	}

	/**
	 * Register the widget
	 *
	 * @since Fiscaat (r3389)
	 *
	 * @uses register_widget()
	 */
	public static function register_widget() {
		register_widget( 'Fiscaat_Accounts_Widget' );
	}

	/**
	 * Displays the output, the account list
	 *
	 * @since Fiscaat (r2653)
	 *
	 * @param mixed $args
	 * @param array $instance
	 * @uses apply_filters() Calls 'fiscaat_account_widget_title' with the title
	 * @uses fiscaat_account_permalink() To display the account permalink
	 * @uses fiscaat_account_title() To display the account title
	 * @uses fiscaat_get_account_last_active_time() To get the account last active
	 *                                         time
	 * @uses fiscaat_get_account_id() To get the account id
	 */
	public function widget( $args, $instance ) {

		extract( $args );

		$title        = apply_filters( 'fiscaat_account_widget_title', $instance['title'] );
		$max_shown    = !empty( $instance['max_shown']    ) ? (int) $instance['max_shown'] : 5;
		$show_date    = !empty( $instance['show_date']    ) ? 'on'                         : false;
		$show_user    = !empty( $instance['show_user']    ) ? 'on'                         : false;
		$parent_year = !empty( $instance['parent_year'] ) ? $instance['parent_year']    : 'any';
		$order_by     = !empty( $instance['order_by']     ) ? $instance['order_by']        : false;

		// How do we want to order our results?
		switch ( $order_by ) {

			// Order by most recent records
			case 'freshness' :
				$accounts_query = array(
					'author'         => 0,
					'post_type'      => fiscaat_get_account_post_type(),
					'post_parent'    => $parent_year,
					'posts_per_page' => $max_shown,
					'post_status'    => join( ',', array( fiscaat_get_public_status_id(), fiscaat_get_closed_status_id() ) ),
					'show_stickes'   => false,
					'meta_key'       => '_fiscaat_last_active_time',
					'orderby'        => 'meta_value',
					'order'          => 'DESC',
					'meta_query'     => array( fiscaat_exclude_year_ids( 'meta_query' ) )
				);
				break;

			// Order by total number of records
			case 'popular' :
				$accounts_query = array(
					'author'         => 0,
					'post_type'      => fiscaat_get_account_post_type(),
					'post_parent'    => $parent_year,
					'posts_per_page' => $max_shown,
					'post_status'    => join( ',', array( fiscaat_get_public_status_id(), fiscaat_get_closed_status_id() ) ),
					'show_stickes'   => false,
					'meta_key'       => '_fiscaat_record_count',
					'orderby'        => 'meta_value',
					'order'          => 'DESC',
					'meta_query'     => array( fiscaat_exclude_year_ids( 'meta_query' ) )
				);			
				break;

			// Order by which account was created most recently
			case 'newness' :
			default :
				$accounts_query = array(
					'author'         => 0,
					'post_type'      => fiscaat_get_account_post_type(),
					'post_parent'    => $parent_year,
					'posts_per_page' => $max_shown,
					'post_status'    => join( ',', array( fiscaat_get_public_status_id(), fiscaat_get_closed_status_id() ) ),
					'show_stickes'   => false,
					'order'          => 'DESC',
					'meta_query'     => array( fiscaat_exclude_year_ids( 'meta_query' ) )
				);			
				break;
		}
		
		// Note: private and hidden years will be excluded via the
		// fiscaat_pre_get_posts_exclude_years filter and function.
		$widget_query = new WP_Query( $accounts_query );

		// Accounts exist
		if ( $widget_query->have_posts() ) : 
			
			echo $before_widget;
			echo $before_title . $title . $after_title; ?>

			<ul>

				<?php while ( $widget_query->have_posts() ) :

					$widget_query->the_post();
					$account_id    = fiscaat_get_account_id( $widget_query->post->ID ); 
					$author_link = fiscaat_get_account_author_link( array( 'post_id' => $account_id, 'type' => 'both', 'size' => 14 ) ); ?>

					<li>
						<a class="fiscaat-year-title" href="<?php fiscaat_account_permalink( $account_id ); ?>" title="<?php fiscaat_account_title( $account_id ); ?>"><?php fiscaat_account_title( $account_id ); ?></a>

						<?php if ( 'on' == $show_user ) : ?>

							<?php printf( _x( 'by %1$s', 'widgets', 'fiscaat' ), '<span class="account-author">' . $author_link . '</span>' ); ?>

						<?php endif; ?>

						<?php if ( 'on' == $show_date ) : ?>

							<div><?php fiscaat_account_last_active_time( $account_id ); ?></div>

						<?php endif; ?>

					</li>

				<?php endwhile; ?>

			</ul>

			<?php echo $after_widget;

			// Reset the $post global
			wp_reset_postdata();

		endif;
	}

	/**
	 * Update the account widget options
	 *
	 * @since Fiscaat (r2653)
	 *
	 * @param array $new_instance The new instance options
	 * @param array $old_instance The old instance options
	 */
	public function update( $new_instance, $old_instance ) {
		$instance              = $old_instance;
		$instance['title']     = strip_tags( $new_instance['title']     );
		$instance['max_shown'] = strip_tags( $new_instance['max_shown'] );
		$instance['show_date'] = strip_tags( $new_instance['show_date'] );
		$instance['show_user'] = strip_tags( $new_instance['show_user'] );
		$instance['order_by']  = strip_tags( $new_instance['order_by']  );

		return $instance;
	}

	/**
	 * Output the account widget options form
	 *
	 * @since Fiscaat (r2653)
	 *
	 * @param $instance Instance
	 * @uses Fiscaat_Accounts_Widget::get_field_id() To output the field id
	 * @uses Fiscaat_Accounts_Widget::get_field_name() To output the field name
	 */
	public function form( $instance ) {
		$title     = !empty( $instance['title']     ) ? esc_attr( $instance['title']     ) : '';
		$max_shown = !empty( $instance['max_shown'] ) ? esc_attr( $instance['max_shown'] ) : '';
		$show_date = !empty( $instance['show_date'] ) ? esc_attr( $instance['show_date'] ) : '';
		$show_user = !empty( $instance['show_user'] ) ? esc_attr( $instance['show_user'] ) : '';
		$order_by  = !empty( $instance['order_by']  ) ? esc_attr( $instance['order_by']  ) : ''; ?>

		<p><label for="<?php echo $this->get_field_id( 'title'     ); ?>"><?php _e( 'Title:',                  'fiscaat' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title'     ); ?>" name="<?php echo $this->get_field_name( 'title'     ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'max_shown' ); ?>"><?php _e( 'Maximum accounts to show:', 'fiscaat' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_shown' ); ?>" name="<?php echo $this->get_field_name( 'max_shown' ); ?>" type="text" value="<?php echo $max_shown; ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Show post date:',         'fiscaat' ); ?> <input type="checkbox" id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" <?php checked( 'on', $show_date ); ?> /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'show_user' ); ?>"><?php _e( 'Show account author:',      'fiscaat' ); ?> <input type="checkbox" id="<?php echo $this->get_field_id( 'show_user' ); ?>" name="<?php echo $this->get_field_name( 'show_user' ); ?>" <?php checked( 'on', $show_user ); ?> /></label></p>

		<p>
			<label for="<?php echo $this->get_field_id( 'order_by' ); ?>"><?php _e( 'Order By:',        'fiscaat' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'order_by' ); ?>" id="<?php echo $this->get_field_name( 'order_by' ); ?>">
				<option <?php selected( $order_by, 'newness' );   ?> value="newness"><?php _e( 'Newest Accounts',                'fiscaat' ); ?></option>
				<option <?php selected( $order_by, 'popular' );   ?> value="popular"><?php _e( 'Popular Accounts',               'fiscaat' ); ?></option>
				<option <?php selected( $order_by, 'freshness' ); ?> value="freshness"><?php _e( 'Accounts With Recent Records', 'fiscaat' ); ?></option>
			</select>
		</p>

		<?php
	}
}

/**
 * Fiscaat Records Widget
 *
 * Adds a widget which displays the records list
 *
 * @since Fiscaat (r2653)
 *
 * @uses WP_Widget
 */
class Fiscaat_Records_Widget extends WP_Widget {

	/**
	 * Fiscaat Records Widget
	 *
	 * Registers the records widget
	 *
	 * @since Fiscaat (r2653)
	 *
	 * @uses apply_filters() Calls 'fiscaat_records_widget_options' with the
	 *                        widget options
	 */
	public function __construct() {
		$widget_ops = apply_filters( 'fiscaat_records_widget_options', array(
			'classname'   => 'widget_display_records',
			'description' => __( 'A list of the most recent records.', 'fiscaat' )
		) );

		parent::__construct( false, __( '(Fiscaat) Recent Records', 'fiscaat' ), $widget_ops );
	}

	/**
	 * Register the widget
	 *
	 * @since Fiscaat (r3389)
	 *
	 * @uses register_widget()
	 */
	public static function register_widget() {
		register_widget( 'Fiscaat_Records_Widget' );
	}

	/**
	 * Displays the output, the records list
	 *
	 * @since Fiscaat (r2653)
	 *
	 * @param mixed $args
	 * @param array $instance
	 * @uses apply_filters() Calls 'fiscaat_record_widget_title' with the title
	 * @uses fiscaat_get_record_author_link() To get the record author link
	 * @uses fiscaat_get_record_author() To get the record author name
	 * @uses fiscaat_get_record_id() To get the record id
	 * @uses fiscaat_get_record_url() To get the record url
	 * @uses fiscaat_get_record_excerpt() To get the record excerpt
	 * @uses fiscaat_get_record_account_title() To get the record account title
	 * @uses get_the_date() To get the date of the record
	 * @uses get_the_time() To get the time of the record
	 */
	public function widget( $args, $instance ) {

		extract( $args );

		$title      = apply_filters( 'fiscaat_records_widget_title', $instance['title'] );
		$max_shown  = !empty( $instance['max_shown'] ) ? $instance['max_shown'] : '5';
		$show_date  = !empty( $instance['show_date'] ) ? 'on'                   : false;
		$show_user  = !empty( $instance['show_user'] ) ? 'on'                   : false;
		$post_types = !empty( $instance['post_type'] ) ? array( fiscaat_get_account_post_type(), fiscaat_get_record_post_type() ) : fiscaat_get_record_post_type();

		// Note: private and hidden years will be excluded via the
		// fiscaat_pre_get_posts_exclude_years filter and function.
		$widget_query = new WP_Query( array(
			'post_type'      => $post_types,
			'post_status'    => join( ',', array( fiscaat_get_public_status_id(), fiscaat_get_closed_status_id() ) ),
			'posts_per_page' => $max_shown,
			'meta_query'     => array( fiscaat_exclude_year_ids( 'meta_query' ) )
		) );

		// Get records and display them
		if ( $widget_query->have_posts() ) :

			echo $before_widget;
			echo $before_title . $title . $after_title; ?>

			<ul>

				<?php while ( $widget_query->have_posts() ) : $widget_query->the_post(); ?>

					<li>

						<?php

						$record_id    = fiscaat_get_record_id( $widget_query->post->ID );
						$author_link = fiscaat_get_record_author_link( array( 'post_id' => $record_id, 'type' => 'both', 'size' => 14 ) );
						$record_link  = '<a class="fiscaat-record-account-title" href="' . esc_url( fiscaat_get_record_url( $record_id ) ) . '" title="' . fiscaat_get_record_excerpt( $record_id, 50 ) . '">' . fiscaat_get_record_account_title( $record_id ) . '</a>';

						// Record author, link, and timestamp
						if ( ( 'on' == $show_date ) && ( 'on' == $show_user ) ) :

							// translators: 1: record author, 2: record link, 3: record timestamp
							printf( _x( '%1$s on %2$s %3$s', 'widgets', 'fiscaat' ), $author_link, $record_link, '<div>' . fiscaat_get_time_since( get_the_time( 'U' ) ) . '</div>' );

						// Record link and timestamp
						elseif ( $show_date == 'on' ) :

							// translators: 1: record link, 2: record timestamp
							printf( _x( '%1$s %2$s',         'widgets', 'fiscaat' ), $record_link,  '<div>' . fiscaat_get_time_since( get_the_time( 'U' ) ) . '</div>'              );

						// Record author and title
						elseif ( $show_user == 'on' ) :

							// translators: 1: record author, 2: record link
							printf( _x( '%1$s on %2$s',      'widgets', 'fiscaat' ), $author_link, $record_link                                                                 );

						// Only the record title
						else :

							// translators: 1: record link
							printf( _x( '%1$s',              'widgets', 'fiscaat' ), $record_link                                                                               );

						endif;

						?>

					</li>

				<?php endwhile; ?>

			</ul>

			<?php echo $after_widget;

			// Reset the $post global
			wp_reset_postdata();

		endif;
	}

	/**
	 * Update the record widget options
	 *
	 * @since Fiscaat (r2653)
	 *
	 * @param array $new_instance The new instance options
	 * @param array $old_instance The old instance options
	 */
	public function update( $new_instance, $old_instance ) {
		$instance              = $old_instance;
		$instance['title']     = strip_tags( $new_instance['title']     );
		$instance['max_shown'] = strip_tags( $new_instance['max_shown'] );
		$instance['show_date'] = strip_tags( $new_instance['show_date'] );
		$instance['show_user'] = strip_tags( $new_instance['show_user'] );

		return $instance;
	}

	/**
	 * Output the record widget options form
	 *
	 * @since Fiscaat (r2653)
	 *
	 * @param $instance Instance
	 * @uses Fiscaat_Records_Widget::get_field_id() To output the field id
	 * @uses Fiscaat_Records_Widget::get_field_name() To output the field name
	 */
	public function form( $instance ) {
		$title     = !empty( $instance['title']     ) ? esc_attr( $instance['title']     ) : '';
		$max_shown = !empty( $instance['max_shown'] ) ? esc_attr( $instance['max_shown'] ) : '';
		$show_date = !empty( $instance['show_date'] ) ? esc_attr( $instance['show_date'] ) : '';
		$show_user = !empty( $instance['show_user'] ) ? esc_attr( $instance['show_user'] ) : ''; ?>

		<p><label for="<?php echo $this->get_field_id( 'title'     ); ?>"><?php _e( 'Title:',                   'fiscaat' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title'     ); ?>" name="<?php echo $this->get_field_name( 'title'     ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'max_shown' ); ?>"><?php _e( 'Maximum records to show:', 'fiscaat' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_shown' ); ?>" name="<?php echo $this->get_field_name( 'max_shown' ); ?>" type="text" value="<?php echo $max_shown; ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Show post date:',          'fiscaat' ); ?> <input type="checkbox" id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" <?php checked( 'on', $show_date ); ?> /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'show_user' ); ?>"><?php _e( 'Show record author:',      'fiscaat' ); ?> <input type="checkbox" id="<?php echo $this->get_field_id( 'show_user' ); ?>" name="<?php echo $this->get_field_name( 'show_user' ); ?>" <?php checked( 'on', $show_user ); ?> /></label></p>

		<?php
	}
}

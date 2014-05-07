<?php

/**
 * Fiscaat Posts List Table base class
 *
 * @package Fiscaat
 * @subpackage List_Table
 * @since 0.0.7
 * @access private
 */

/**
 * Fiscaat posts base class
 *
 * @since 0.0.7
 *
 * @see WP_List_Table
 */
class FCT_Posts_List_Table extends WP_List_Table {

	/**
	 * Holds the table row alternate value. Originally static, this
	 * variable is now also used outside WP_List_Table::single_row()
	 *
	 * @since 0.0.8
	 * @var string
	 */
	var $alternate;

	function __construct( $args = array() ) {
		parent::__construct( $args );

		if ( method_exists( $this, '_column_content' ) ) {
			add_action( "manage_{$this->screen->post_type}_posts_custom_column", array( $this, '_column_content' ), 10, 2 );
		}
	}

	function ajax_user_can() {
		return current_user_can( get_post_type_object( $this->screen->post_type )->cap->edit_posts );
	}

	function prepare_items() {
		global $avail_post_stati, $wp_query, $per_page, $mode;

		// Setup post query. Post type is never given in $_GET params
		$query_args = array( 'post_type' => $this->screen->post_type );
		$avail_post_stati = wp_edit_posts_query( wp_parse_args( $query_args, $_GET ) );
		
		// Calls fct_has_{post}s
		// @todo Does not return avail_post_stati
		// $has_posts = fct_has_posts();
		// var_dump( $has_posts );
		// $avail_post_stati = array();

		$total_items = $wp_query->found_posts;

		$post_type = $this->screen->post_type;
		$per_page  = $this->get_items_per_page( 'edit_' . $post_type . '_per_page' );
 		$per_page  = apply_filters( 'edit_posts_per_page', $per_page, $post_type );

		$total_pages = $wp_query->max_num_pages;

		$this->is_trash = isset( $_REQUEST['post_status'] ) && $_REQUEST['post_status'] == 'trash';

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page'    => $per_page
		) );
	}

	function has_items() {
		return have_posts();
	}

	function no_items() {
		if ( isset( $_REQUEST['post_status'] ) && 'trash' == $_REQUEST['post_status'] ) {
			echo get_post_type_object( $this->screen->post_type )->labels->not_found_in_trash;
		} else {
			echo get_post_type_object( $this->screen->post_type )->labels->not_found;
		}
	}

	function get_views() {
		global $locked_post_status, $avail_post_stati;

		$post_type = $this->screen->post_type;

		if ( ! empty( $locked_post_status ) )
			return array();

		$status_links = array();
		$num_posts    = wp_count_posts( $post_type, 'readable' );
		$class        = '';
		$allposts     = '';
		$total_posts  = array_sum( (array) $num_posts );

		// Subtract post types that are not included in the admin all list.
		foreach ( get_post_stati( array( 'show_in_admin_all_list' => false ) ) as $state ) {
			$total_posts -= $num_posts->$state;
		}

		$class = empty( $class ) && empty( $_REQUEST['post_status'] ) ? ' class="current"' : '';
		$status_links['all'] = "<a href='edit.php?post_type=$post_type{$allposts}'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts' ), number_format_i18n( $total_posts ) ) . '</a>';

		foreach ( get_post_stati( array( 'show_in_admin_status_list' => true ), 'objects' ) as $status ) {
			$class = '';

			$status_name = $status->name;

			if ( ! in_array( $status_name, $avail_post_stati ) )
				continue;

			if ( empty( $num_posts->$status_name ) )
				continue;

			if ( isset( $_REQUEST['post_status'] ) && $status_name == $_REQUEST['post_status'] )
				$class = ' class="current"';

			$status_links[$status_name] = "<a href='edit.php?post_status=$status_name&amp;post_type=$post_type'$class>" . sprintf( translate_nooped_plural( $status->label_count, $num_posts->$status_name ), number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}

		return apply_filters( "fct_admin_{$this->_args['plural']}_get_views", $status_links );
	}

	function get_bulk_actions() {
		return apply_filters( "fct_admin_{$this->_args['plural']}_get_bulk_actions", $this->_get_bulk_actions() );
	}

	/**
	 * Return whether this table has bulk actions
	 * 
	 * @since 0.0.8
	 *
	 * @see WP_List_Table::bulk_actions()
	 * 
	 * @return bool Table has bulk actions
	 */
	function has_bulk_actions() {
		$no_new_actions = $actions = $this->get_bulk_actions();
		/** This filter is documented in wp-admin/includes/class-wp-list-table.php */
		$actions = apply_filters( "bulk_actions-{$this->screen->id}", $actions );
		$actions = array_intersect_assoc( $actions, $no_new_actions );

		return ! empty( $actions );
	}

	function extra_tablenav( $which ) { ?>
		<div class="alignleft actions">
			<?php 
				if ( 'top' == $which && ! is_singular() ) {

					/**
					 * Fires before the Filter button on the Posts and Pages list tables.
					 *
					 * The Filter button allows sorting by date and/or category on the
					 * Posts list table, and sorting by date on the Pages list table.
					 *
					 * @since 2.1.0
					 */
					do_action( 'restrict_manage_posts' );
					submit_button( __( 'Filter' ), 'button', false, false, array( 'id' => 'post-query-submit' ) );
				}

				if ( $this->is_trash && current_user_can( get_post_type_object( $this->screen->post_type )->cap->edit_others_posts ) ) {
					submit_button( __( 'Empty Trash' ), 'apply', 'delete_all', false );
				}
			?>
		</div>
		<?php
	}

	function get_table_classes() {
		return array( 'widefat', 'fixed', 'posts', $this->_args['plural'] );
	}

	function get_columns() {
		$post_type     = $this->screen->post_type;
		$posts_columns = $this->_get_columns();

		if ( post_type_supports( $post_type, 'author' ) ) {
			$posts_columns['author'] = __( 'Author' );
		}

		// Support custom taxonomies
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		$taxonomies = wp_filter_object_list( $taxonomies, array( 'show_admin_column' => true ), 'and', 'name' );

		/**
		 * Filter the taxonomy columns in the Posts list table.
		 *
		 * The dynamic portion of the hook name, $post_type, refers to the post
		 * type slug.
		 *
		 * @since 3.5.0
		 *
		 * @param array  $taxonomies Array of taxonomies to show columns for.
		 * @param string $post_type  The post type.
		 */
		$taxonomies = apply_filters( "manage_taxonomies_for_{$post_type}_columns", $taxonomies, $post_type );
		$taxonomies = array_filter( $taxonomies, 'taxonomy_exists' );

		foreach ( $taxonomies as $taxonomy ) {
			if ( 'category' == $taxonomy )
				$column_key = 'categories';
			elseif ( 'post_tag' == $taxonomy )
				$column_key = 'tags';
			else
				$column_key = 'taxonomy-' . $taxonomy;

			$posts_columns[ $column_key ] = get_taxonomy( $taxonomy )->labels->name;
		}

		// Support comments
		$post_status = !empty( $_REQUEST['post_status'] ) ? $_REQUEST['post_status'] : 'all';
		if ( post_type_supports( $post_type, 'comments' ) && ! in_array( $post_status, array( 'pending', 'draft', 'future' ) ) )
			$posts_columns['comments'] = '<span class="vers"><div title="' . esc_attr__( 'Comments' ) . '" class="comment-grey-bubble"></div></span>';

		/**
		 * Filter the columns displayed in the Posts list table.
		 *
		 * @since 1.5.0
		 *
		 * @param array  $posts_columns An array of column names.
		 * @param string $post_type     The post type slug.
		 */
		$posts_columns = apply_filters( 'manage_posts_columns', $posts_columns, $post_type );

		/**
		 * Filter the columns displayed in the Posts list table for a specific post type.
		 *
		 * The dynamic portion of the hook name, $post_type, refers to the post type slug.
		 *
		 * @since 3.0.0
		 *
		 * @param array $post_columns An array of column names.
		 */
		$posts_columns = apply_filters( "manage_{$post_type}_posts_columns", $posts_columns );

		return apply_filters( "fct_admin_{$this->_args['plural']}_get_columns", $posts_columns );
	}

	function get_sortable_columns() {
		return apply_filters( "fct_admin_{$this->_args['plural']}_get_sortable_columns", $this->_get_sortable_columns() );
	}

	function display_rows( $posts = array(), $level = 0 ) {
		global $wp_query;

		if ( empty( $posts ) ) {
			$posts = $wp_query->posts;
		}

		add_filter( 'the_title', 'esc_html' );

		$this->_display_rows( $posts, $level );
	}

	function _display_rows( $posts, $level = 0 ) {
		foreach ( $posts as $post ) {
			$this->single_row( $post, $level );
		}
		
		// while ( fct_posts() ) {
		// 	$this->single_row( fct_the_post(), $level );
		// }
	}

	function single_row( $post, $level = 0 ) {
		global $mode;
		$alternate =& $this->alternate;

		$global_post = get_post();
		$GLOBALS['post'] = $post;
		setup_postdata( $post );
		
		$edit_link = get_edit_post_link( $post->ID );
		$title = _draft_or_post_title();
		$post_type_object = get_post_type_object( $post->post_type );
		$can_edit_post = current_user_can( 'edit_post', $post->ID );

		$alternate = 'alternate' == $alternate ? '' : 'alternate';
		$classes = $alternate . ' iedit author-' . ( get_current_user_id() == $post->post_author ? 'self' : 'other' );

		$lock_holder = wp_check_post_lock( $post->ID );
		if ( $lock_holder ) {
			$classes .= ' wp-locked';
			$lock_holder = get_userdata( $lock_holder );
		}

		if ( $post->post_parent ) {
		    $count = count( get_post_ancestors( $post->ID ) );
		    $classes .= ' level-'. $count;
		} else {
		    $classes .= ' level-0';
		}
	?>
		<tr id="post-<?php echo $post->ID; ?>" class="<?php echo implode( ' ', get_post_class( $classes, $post->ID ) ); ?>" valign="top">
	<?php

		list( $columns, $hidden ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$class = "class=\"$column_name column-$column_name\"";

			$style = '';
			if ( in_array( $column_name, $hidden ) )
				$style = ' style="display:none;"';

			$attributes = "$class$style";

			switch ( $column_name ) {

			case 'cb':
			?>
			<th scope="row" class="check-column">
				<?php
				if ( $can_edit_post ) {

				?>
				<label class="screen-reader-text" for="cb-select-<?php the_ID(); ?>"><?php printf( __( 'Select %s' ), $title ); ?></label>
				<input id="cb-select-<?php the_ID(); ?>" type="checkbox" name="post[]" value="<?php the_ID(); ?>" />
				<div class="locked-indicator"></div>
				<?php
				}
				?>
			</th>
			<?php
			break;

			case 'title':
				$attributes = 'class="post-title page-title column-title"' . $style;
				$pad = str_repeat( '&#8212; ', $level );
				echo "<td $attributes><strong>";

				if ( $format = get_post_format( $post->ID ) ) {
					$label = get_post_format_string( $format );

					echo '<a href="' . esc_url( add_query_arg( array( 'post_format' => $format, 'post_type' => $post->post_type ), 'edit.php' ) ) . '" class="post-state-format post-format-icon post-format-' . $format . '" title="' . $label . '">' . $label . ":</a> ";
				}

				if ( $can_edit_post && $post->post_status != 'trash' ) {
					echo '<a class="row-title" href="' . $edit_link . '" title="' . esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $title ) ) . '">' . $pad . $title . '</a>';
				} else {
					echo $pad . $title;
				}
				_post_states( $post );

				if ( isset( $parent_name ) )
					echo ' | ' . $post_type_object->labels->parent_item_colon . ' ' . esc_html( $parent_name );

				echo "</strong>\n";

				if ( $can_edit_post && $post->post_status != 'trash' ) {
					if ( $lock_holder ) {
						$locked_avatar = get_avatar( $lock_holder->ID, 18 );
						$locked_text = esc_html( sprintf( __( '%s is currently editing' ), $lock_holder->display_name ) );
					} else {
						$locked_avatar = $locked_text = '';
					}

					echo '<div class="locked-info"><span class="locked-avatar">' . $locked_avatar . '</span> <span class="locked-text">' . $locked_text . "</span></div>\n";
				}

				$actions = array();
				if ( $can_edit_post && 'trash' != $post->post_status ) {
					$actions['edit'] = '<a href="' . get_edit_post_link( $post->ID, true ) . '" title="' . esc_attr( __( 'Edit this item' ) ) . '">' . __( 'Edit' ) . '</a>';
				}
				if ( $post_type_object->public ) {
					if ( in_array( $post->post_status, array( 'pending', 'draft', 'future' ) ) ) {
						if ( $can_edit_post )
							$actions['view'] = '<a href="' . esc_url( apply_filters( 'preview_post_link', set_url_scheme( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ) ) . '" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $title ) ) . '" rel="permalink">' . __( 'Preview' ) . '</a>';
					} elseif ( 'trash' != $post->post_status ) {
						$actions['view'] = '<a href="' . get_permalink( $post->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $title ) ) . '" rel="permalink">' . __( 'View' ) . '</a>';
					}
				}

				/**
				 * Filter the array of row action links on the Posts list table.
				 *
				 * The filter is evaluated only for non-hierarchical post types.
				 *
				 * @since 2.8.0
				 *
				 * @param array   $actions An array of row action links. Defaults are
				 *                         'Edit', 'Quick Edit', 'Restore, 'Trash',
				 *                         'Delete Permanently', 'Preview', and 'View'.
				 * @param WP_Post $post    The post object.
				 */
				$actions = apply_filters( 'post_row_actions', $actions, $post );
				echo $this->row_actions( $actions );

				echo '</td>';
			break;

			case 'date':
				if ( '0000-00-00 00:00:00' == $post->post_date ) {
					$t_time = $h_time = __( 'Unpublished' );
					$time_diff = 0;
				} else {
					$t_time = get_the_time( __( 'Y/m/d g:i:s A' ) );
					$m_time = $post->post_date;
					$time = get_post_time( 'G', true, $post );

					$time_diff = time() - $time;

					if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS )
						$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
					else
						$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
				}

				echo '<td ' . $attributes . '>';

				/**
				 * Filter the published time of the post.
				 *
				 * If $mode equals 'excerpt', the published time and date are both displayed.
				 * If $mode equals 'list' (default), the publish date is displayed, with the
				 * time and date together available as an abbreviation definition.
				 *
				 * @since 2.5.1
				 *
				 * @param array   $t_time      The published time.
				 * @param WP_Post $post        Post object.
				 * @param string  $column_name The column name.
				 * @param string  $mode        The list display mode ('excerpt' or 'list').
				 */
				echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post, $column_name, $mode ) . '</abbr>';

				echo '<br />';
				if ( 'publish' == $post->post_status ) {
					_e( 'Published' );
				} elseif ( 'future' == $post->post_status ) {
					if ( $time_diff > 0 )
						echo '<strong class="attention">' . __( 'Missed schedule' ) . '</strong>';
					else
						_e( 'Scheduled' );
				} else {
					_e( 'Last Modified' );
				}
				echo '</td>';
			break;

			case 'comments':
			?>
			<td <?php echo $attributes ?>><div class="post-com-count-wrapper">
			<?php
				$pending_comments = isset( $this->comment_pending_count[$post->ID] ) ? $this->comment_pending_count[$post->ID] : 0;

				$this->comments_bubble( $post->ID, $pending_comments );
			?>
			</div></td>
			<?php
			break;

			case 'author':
			?>
			<td <?php echo $attributes ?>><?php
				printf( '<a href="%s">%s</a>',
					esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'author' => get_the_author_meta( 'ID' ) ), 'edit.php' )),
					get_the_author()
				);
			?></td>
			<?php
			break;

			default:
				if ( 'categories' == $column_name )
					$taxonomy = 'category';
				elseif ( 'tags' == $column_name )
					$taxonomy = 'post_tag';
				elseif ( 0 === strpos( $column_name, 'taxonomy-' ) )
					$taxonomy = substr( $column_name, 9 );
				else
					$taxonomy = false;

				if ( $taxonomy ) {
					$taxonomy_object = get_taxonomy( $taxonomy );
					echo '<td ' . $attributes . '>';
					if ( $terms = get_the_terms( $post->ID, $taxonomy ) ) {
						$out = array();
						foreach ( $terms as $t ) {
							$posts_in_term_qv = array();
							if ( 'post' != $post->post_type )
								$posts_in_term_qv['post_type'] = $post->post_type;
							if ( $taxonomy_object->query_var ) {
								$posts_in_term_qv[ $taxonomy_object->query_var ] = $t->slug;
							} else {
								$posts_in_term_qv['taxonomy'] = $taxonomy;
								$posts_in_term_qv['term'] = $t->slug;
							}

							$out[] = sprintf( '<a href="%s">%s</a>',
								esc_url( add_query_arg( $posts_in_term_qv, 'edit.php' ) ),
								esc_html( sanitize_term_field( 'name', $t->name, $t->term_id, $taxonomy, 'display' ) )
							);
						}
						/* translators: used between list items, there is a space after the comma */
						echo join( __( ', ' ), $out );
					} else {
						echo '&#8212;';
					}
					echo '</td>';
					break;
				}
			?>
			<td <?php echo $attributes ?>><?php

				/**
				 * Fires in each custom column in the Posts list table.
				 *
				 * This hook only fires if the current post type is non-hierarchical,
				 * such as posts.
				 *
				 * @since 1.5.0
				 *
				 * @param string $column_name The name of the column to display.
				 * @param int    $post_id     The current post ID.
				 */
				do_action( 'manage_posts_custom_column', $column_name, $post->ID );

				/**
				 * Fires for each custom column of a specific post type in the Posts list table.
				 *
				 * The dynamic portion of the hook name, $post->post_type, refers to the post type.
				 *
				 * @since 3.1.0
				 *
				 * @param string $column_name The name of the column to display.
				 * @param int    $post_id     The current post ID.
				 */
				do_action( "manage_{$post->post_type}_posts_custom_column", $column_name, $post->ID );
			?></td>
			<?php
			break;
			}
		}
	?>
		</tr>
	<?php
		$GLOBALS['post'] = $global_post;
	}
}

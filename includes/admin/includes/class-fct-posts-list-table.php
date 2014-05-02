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
 * @see WP_Posts_List_Table
 */
class FCT_Posts_List_Table extends WP_List_Table {

	function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	function ajax_user_can() {
		return current_user_can( get_post_type_object( $this->screen->post_type )->cap->edit_posts );
	}

	function prepare_items() {
		global $avail_post_stati, $wp_query, $per_page, $mode;

		// Setup post query. Post type is never given in $_GET params
		$query_args = array( 'post_type' => $this->screen->post_type );
		$avail_post_stati = wp_edit_posts_query( wp_parse_args( $query_args, $_GET ) );

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

		$class = empty( $class ) && empty( $_REQUEST['post_status'] ) && empty( $_REQUEST['show_sticky'] ) ? ' class="current"' : '';
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

	function extra_tablenav( $which ) { ?>
		<div class="alignleft actions">
			<?php 
				if ( 'top' == $which && !is_singular() ) {

					$this->months_dropdown( $this->screen->post_type );

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

		if ( post_type_supports( $post_type, 'author' ) )
			$posts_columns['author'] = __( 'Author' );

		// Support custom taxonomies
		$taxonomies = array();
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		$taxonomies = wp_filter_object_list( $taxonomies, array( 'show_admin_column' => true ), 'and', 'name' );

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

		// Apply WP core filters
		$posts_columns = apply_filters( 'manage_posts_columns', $posts_columns, $post_type );
		$posts_columns = apply_filters( "manage_{$post_type}_posts_columns", $posts_columns );

		return apply_filters( "fct_admin_{$this->_args['plural']}_get_columns", $posts_columns );
	}

	function _get_columns() {
		return array();
	}

	function get_sortable_columns() {
		return apply_filters( "fct_admin_{$this->_args['plural']}_get_sortable_columns", $this->_get_sortable_columns() );
	}

	function _get_sortable_columns() {
		return array();
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
	}

	function single_row( $post, $level = 0 ) {
		global $mode;
		static $alternate;

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
				if ( $this->hierarchical_display ) {
					if ( 0 == $level && (int) $post->post_parent > 0 ) {
						//sent level 0 by accident, by default, or because we don't know the actual level
						$find_main_page = (int) $post->post_parent;
						while ( $find_main_page > 0 ) {
							$parent = get_post( $find_main_page );

							if ( is_null( $parent ) )
								break;

							$level++;
							$find_main_page = (int) $parent->post_parent;

							if ( !isset( $parent_name ) ) {
								/** This filter is documented in wp-includes/post-template.php */
								$parent_name = apply_filters( 'the_title', $parent->post_title, $parent->ID );
							}
						}
					}
				}

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

				if ( ! $this->hierarchical_display && 'excerpt' == $mode && current_user_can( 'read_post', $post->ID ) )
						the_excerpt();

				$actions = array();
				if ( $can_edit_post && 'trash' != $post->post_status ) {
					$actions['edit'] = '<a href="' . get_edit_post_link( $post->ID, true ) . '" title="' . esc_attr( __( 'Edit this item' ) ) . '">' . __( 'Edit' ) . '</a>';
					$actions['inline hide-if-no-js'] = '<a href="#" class="editinline" title="' . esc_attr( __( 'Edit this item inline' ) ) . '">' . __( 'Quick&nbsp;Edit' ) . '</a>';
				}
				if ( current_user_can( 'delete_post', $post->ID ) ) {
					if ( 'trash' == $post->post_status )
						$actions['untrash'] = "<a title='" . esc_attr( __( 'Restore this item from the Trash' ) ) . "' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ) . "'>" . __( 'Restore' ) . "</a>";
					elseif ( EMPTY_TRASH_DAYS )
						$actions['trash'] = "<a class='submitdelete' title='" . esc_attr( __( 'Move this item to the Trash' ) ) . "' href='" . get_delete_post_link( $post->ID ) . "'>" . __( 'Trash' ) . "</a>";
					if ( 'trash' == $post->post_status || !EMPTY_TRASH_DAYS )
						$actions['delete'] = "<a class='submitdelete' title='" . esc_attr( __( 'Delete this item permanently' ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently' ) . "</a>";
				}
				if ( $post_type_object->public ) {
					if ( in_array( $post->post_status, array( 'pending', 'draft', 'future' ) ) ) {
						if ( $can_edit_post )
							$actions['view'] = '<a href="' . esc_url( apply_filters( 'preview_post_link', set_url_scheme( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ) ) . '" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $title ) ) . '" rel="permalink">' . __( 'Preview' ) . '</a>';
					} elseif ( 'trash' != $post->post_status ) {
						$actions['view'] = '<a href="' . get_permalink( $post->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $title ) ) . '" rel="permalink">' . __( 'View' ) . '</a>';
					}
				}

				$actions = apply_filters( is_post_type_hierarchical( $post->post_type ) ? 'page_row_actions' : 'post_row_actions', $actions, $post );
				echo $this->row_actions( $actions );

				get_inline_data( $post );
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
				if ( 'excerpt' == $mode )
					echo apply_filters( 'post_date_column_time', $t_time, $post, $column_name, $mode );
				else
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
				if ( is_post_type_hierarchical( $post->post_type ) )
					do_action( 'manage_pages_custom_column', $column_name, $post->ID );
				else
					do_action( 'manage_posts_custom_column', $column_name, $post->ID );
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

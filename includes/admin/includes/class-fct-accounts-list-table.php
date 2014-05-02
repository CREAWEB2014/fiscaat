<?php

/**
 * Fiscaat Accounts List Table class
 *
 * @package Fiscaat
 * @subpackage List_Table
 * @since 0.0.7
 * @access private
 */

class FCT_Accounts_List_Table extends FCT_Posts_List_Table {

	/**
	 * Constructs the posts list table
	 * 
	 * @param array $args
	 */
	function __construct( $args = array() ) {
		parent::__construct( array(
			'plural' => 'accounts',
			'screen' => isset( $args['screen'] ) ? $args['screen'] : null,
		) );
	}

	function _get_bulk_actions() {
		$actions = array();

		if ( $this->is_trash )
			$actions['untrash'] = __( 'Restore' );
		else
			$actions['edit'] = __( 'Edit' );

		if ( $this->is_trash || !EMPTY_TRASH_DAYS )
			$actions['delete'] = __( 'Delete Permanently' );
		else
			$actions['trash'] = __( 'Move to Trash' );

		return $actions;
	}

	/**
	 * Return dedicated record columns
	 *
	 * @since 0.0.8
	 * 
	 * @return array Columns
	 */
	function _get_columns() {
		return array(
			'cb'                       => '<input type="checkbox" />',
			'fct_account_year'         => __( 'Year',                         'fiscaat' ),
			'fct_account_ledger_id'    => _x( 'No.', 'Account number column', 'fiscaat' ),
			'title'                    => __( 'Account',                      'fiscaat' ),
			'fct_account_type'         => __( 'Type',                         'fiscaat' ),
			'fct_account_record_count' => __( 'Records',                      'fiscaat' ),
			'fct_account_value'        => __( 'Value',                        'fiscaat' ),
		);
	}

	/**
	 * Return which columns are sortable
	 * 
	 * @since 0.0.8
	 *
	 * @return array Sortable columns as array( column => sort key )
	 */
	function _get_sortable_columns() {
		return array(
			'fct_account_ledger_id'    => 'account_ledger_id',
			'title'                    => 'post_title',
			'fct_account_type'         => 'account_type',
			'fct_account_record_count' => 'account_record_count',
			'fct_account_value'        => 'account_value',
		);
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

/**
 * Pin the top and bottom table rows while scrolling the page
 * 
 * @see wp-admin/js/editor-expand.js
 * 
 * @package Fiscaat
 * @subpackage Administration
 */
(function($) {

	jQuery( document ).ready( function($) {
		var $window = $( window ),
			$document = $( document ),
			$adminBar = $( '#wpadminbar' ),
			$footer = $( '#wpfooter' ),
			$wrap = $( '#wp-list-table-wrap' ),
			$table = $wrap.find( '.widefat' ),
			$tableTopCopy = $( '<table id="table-top-copy" class="widefat fixed"></table>' ),
			$tableBottomCopy = $( '<table id="table-bottom-copy" class="widefat fixed"></table>' ),
			$tableTop = $table.find( 'thead' ),
			$tableRows = $table.find( 'tbody' ),
			$lastRow = $table.find( 'tbody tr:last-child' ),
			fixedTop = false,
			fixedBottom = false,
			scrollTimer,
			pageYOffsetAtTop = 130,
			autoresizeMinHeight = 300,
			// These are corrected when adjust() runs, except on scrolling if already set.
			heights = {
				windowHeight: 0,
				windowWidth: 0,
				adminBarHeight: 0,
				tableTopHeight: 0,
				lastRowHeight: 0,
			};

		// Insert dummy tables before and after the main table
		$table.before( $tableTopCopy ).after( $tableBottomCopy );

		// Clone pinning elements into table copies, so the original table stays intact
		$tableTop.clone().appendTo( $tableTopCopy );
		$lastRow.clone().appendTo( $tableBottomCopy );

		function getHeights() {
			var windowWidth = $window.width();

			heights = {
				windowHeight: $window.height(),
				windowWidth: windowWidth,
				adminBarHeight: ( windowWidth > 600 ? $adminBar.outerHeight() : 0 ),
				tableTopHeight: $tableTop.outerHeight() || 0,
				lastRowHeight: $lastRow.outerHeight() || 0,
			};
		}

		// Adjust the table rows
		function adjust( type ) {
			var windowPos = $window.scrollTop(),
				resize = type !== 'scroll',
				buffer = autoresizeMinHeight,
				borderWidth = 1,
				tableWidth = $table.width() + ( borderWidth * 2 ),
				$top, $content, canPin, topPos, 
				topHeight, tablePos, tableHeight;

			// Refresh the heights
			if ( resize || ! heights.windowHeight ) {
				getHeights();
			}

			$top = $tableTopCopy;
			$content = $tableRows;
			topHeight = heights.tableTopHeight;

			topPos = $top.parent().offset().top;
			tablePos = $content.offset().top;
			tableHeight = $content.outerHeight();

			// Should we pin?
			canPin = autoresizeMinHeight + topHeight;
			canPin = tableHeight > ( canPin + 5 );

			if ( ! canPin ) {
				if ( resize ) {
					$.each( [ $top, $tableBottomCopy ], function( i, el ) {
						el.hide().attr( 'style', '' );
					});
				}
			} else {
				// Maybe pin the top.
				if ( ( ! fixedTop || resize ) &&
					// Handle scrolling down.
					( windowPos >= ( topPos - heights.adminBarHeight ) &&
					// Handle scrolling up.
					windowPos <= ( topPos - heights.adminBarHeight + tableHeight - buffer ) ) ) {
					fixedTop = true;

					$top.css( {
						position: 'fixed',
						top: heights.adminBarHeight,
						width: tableWidth,
						display: 'table' // Show
					} );
				// Maybe unpin the top.
				} else if ( fixedTop || resize ) {
					// Handle scrolling up.
					if ( windowPos <= ( topPos - heights.adminBarHeight ) ) {
						fixedTop = false;

						// Don't reset but hide it
						$top.hide().attr( 'style', '' );

					// Handle scrolling down.
					} else if ( windowPos >= ( topPos - heights.adminBarHeight + tableHeight - buffer ) ) {
						fixedTop = false;

						$top.css( {
							position: 'absolute',
							top: tableHeight - buffer,
							width: tableWidth,
							display: 'table' // Show
						} );
					}
				}

				// Maybe adjust the bottom bar.
				if ( ( ! fixedBottom || resize ) &&
					( windowPos + heights.windowHeight ) <= ( tablePos + tableHeight + borderWidth ) ) {
					fixedBottom = true;

					$tableBottomCopy.css( {
						position: 'fixed',
						bottom: 0,
						width: tableWidth,
						display: 'table' // Show
					} );
				} else if ( ( fixedBottom || resize ) &&
					( windowPos + heights.windowHeight ) > ( tablePos + tableHeight - borderWidth ) ) {
					fixedBottom = false;

					$tableBottomCopy.hide().attr( 'style', '' );
				}
			}
		}

		function initialResize( callback ) {
			for ( var i = 1; i < 6; i++ ) {
				setTimeout( callback, 500 * i );
			}
		}

		function afterScroll() {
			clearTimeout( scrollTimer );
			scrollTimer = setTimeout( adjust, 100 );
		}

		function on() {
			// Scroll to the top when triggering this from JS.
			// Ensures toolbars are pinned properly.
			if ( window.pageYOffset && window.pageYOffset > pageYOffsetAtTop ) {
				window.scrollTo( window.pageXOffset, 0 );
			}

			$wrap.addClass( 'fct-table-expand' );

			// Adjust when the window is scrolled or resized.
			$window.on( 'scroll.table-expand resize.table-expand', function( event ) {
				adjust( event.type );
				afterScroll();
			} );

			// Adjust when collapsing the menu
			$document.on( 'wp-collapse-menu.table-expand', adjust );

			adjust();
		}

		function off() {
			// Scroll to the top when triggering this from JS.
			// Ensures toolbars are reset properly.
			if ( window.pageYOffset && window.pageYOffset > pageYOffsetAtTop ) {
				window.scrollTo( window.pageXOffset, 0 );
			}

			$wrap.removeClass( 'fct-table-expand' );

			$window.off( '.table-expand' );
			$document.off( '.table-expand' );

			// Reset all css
			$.each( [ $tableTop, $lastRow, $wrap, $table, $tableRows, $tableTopCopy, $tableBottomCopy ], function( i, element ) {
				element && element.attr( 'style', '' );
			});

			fixedTop = fixedBottom = false;
		}

		// Start on load
		on();
		initialResize( adjust );

		// Expose on() and off()
		window.FiscaatTableExpand = {
			on: on,
			off: off
		};
	});
		
})( jQuery );

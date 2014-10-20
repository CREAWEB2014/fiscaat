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
		    $tableTop = $table.find( 'thead' ),
		    $tableBody = $table.find( 'tbody' ),
		    $tableBottom = $table.find( 'tbody tr:last-child' ),
		    $tableTopContainer = $( '<table id="table-top-container" class="widefat fixed"></table>' ),
		    $tableBottomContainer = $( '<table id="table-bottom-container" class="widefat fixed"></table>' ),
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
		    	tableBottomHeight: 0,
		    };

		/**
		 * Insert dummy tables before and after the main table
		 *
		 * Since rows outside of the <table> element behave incontrollably strange
		 * when it comes to styling, they are placed inside generated container tables.
		 * Then those container tables are the ones that are placed across the viewport.
		 */
		$table.before( $tableTopContainer ).after( $tableBottomContainer );

		// Clone pinning top element into container table
		$tableTop.clone().appendTo( $tableTopContainer );

		function getHeights() {
			var windowWidth = $window.width();

			heights = {
				windowHeight: $window.height(),
				windowWidth: windowWidth,
				adminBarHeight: ( windowWidth > 600 ? $adminBar.outerHeight() : 0 ),
				tableTopHeight: $tableTop.outerHeight() || 0,
				tableBottomHeight: $tableBottom.outerHeight() || 0
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

			// Set a placeholder for the last row to keep the table height
			if ( ! $tableBody.find( '#table-bottom-placeholder' ).length ) {
				$( '<tr id="table-bottom-placeholder"></tr>' ).css( {
					height: heights.tableBottomHeight
				} ).appendTo( $tableBody );
			}

			$top = $tableTopContainer;
			$content = $tableBody;
			$bottom = $tableBottom;
			topHeight = heights.tableTopHeight;

			topPos = $top.parent().offset().top;
			tablePos = $content.offset().top;
			tableHeight = $content.outerHeight();

			// Should we pin?
			canPin = autoresizeMinHeight + topHeight;
			canPin = tableHeight > ( canPin + 5 );

			if ( ! canPin ) {
				if ( resize ) {
					$top.hide().attr( 'style', '' );

					$tableBottomContainer.css( {
						position: 'absolute',
						top: tablePos + tableHeight - topHeight - heights.tableBottomHeight + ( borderWidth * 4 ),
						bottom: 'auto',
						width: tableWidth
					} );
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

					$tableBottomContainer.css( {
						position: 'fixed',
						top: 'auto',
						bottom: 0,
						width: tableWidth
					} );
				} else if ( ( fixedBottom || resize ) &&
					( windowPos + heights.windowHeight ) > ( tablePos + tableHeight - borderWidth ) ) {
					fixedBottom = false;

					$tableBottomContainer.css( {
						position: 'absolute',
						top: tablePos + tableHeight - topHeight - heights.tableBottomHeight + ( borderWidth * 4 ),
						bottom: 'auto',
						width: tableWidth
					} );
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

			$wrap.addClass( 'fct-table-scroll' );

			// Move last row into container
			$tableBottomContainer.append( $tableBottom );

			// Adjust when the window is scrolled or resized.
			$window.on( 'scroll.table-scroll resize.table-scroll', function( event ) {
				adjust( event.type );
				afterScroll();
			} );

			// Adjust when collapsing the menu
			$document.on( 'wp-collapse-menu.table-scroll', adjust );

			adjust();
		}

		function off() {
			// Scroll to the top when triggering this from JS.
			// Ensures toolbars are reset properly.
			if ( window.pageYOffset && window.pageYOffset > pageYOffsetAtTop ) {
				window.scrollTo( window.pageXOffset, 0 );
			}

			// Move last row back into position and remove placeholder
			$tableBody.append( $tableBottom ).find( '#table-bottom-placeholder' ).remove();

			$wrap.removeClass( 'fct-table-scroll' );

			$window.off( '.table-scroll' );
			$document.off( '.table-scroll' );

			// Reset all css
			$.each( [ $wrap, $table, $tableTop, $tableBody, $tableBottom, $tableTopContainer, $tableBottomContainer ], function( i, element ) {
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
		
})(jQuery);

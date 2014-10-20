/**
 * Scripts for the Records pages
 *
 * @package Fiscaat
 * @subpackage Administration
 */
(function($) {

	jQuery(document).ready( function($) {
		var $table = $( '.widefat.records' );
		    $debits = $table.find( '.debit_amount' ),
		    $credits = $table.find( '.credit_amount' ),
		    $sumRow = $( '#fct-total-records' ), // May not be in $table
		    $debitSum = $sumRow.find( '#fct_records_debit_total' ),
		    $creditSum = $sumRow.find( '#fct_records_credit_total' ),
		    debit = 'debit',
		    credit = 'credit',
		    debitSum = 0,
		    creditSum = 0;

		$.each( [ $debits, $credits ], function( i, list ) {
			list.each( function( j, input ) {
				$( input )

					// Store current value
					.on( 'focus', function() {
						$( this ).data( 'originalValue', this.value );
					})

					// Process input value
					.on( 'blur', function( e ) {
						var $this = $( this ),
						    which = list[0].classList.contains( 'debit_amount' ) ? debit : credit,
						    sanitized_value = formatNumber( this.value );

						// Display properly formatted input
						$this.val( sanitized_value );

						// When input is valid
						if ( ! isNaN( sanitized_value ) || 
							// When input is emptied
							!! $this.data( 'originalValue' ) ) {

							// Empty the adjacent input when entry is valid
							if ( ! isNaN( sanitized_value ) ) {
								var $adjacent = $this[ ( debit === which ) ? 'next' : 'prev' ](),
								    other_value = $adjacent.val();

								// Empty adjacent input
								$adjacent.val( '' );

								// Recalculate the other type
								if ( !! other_value ) {
									updateSum( otherType( which ) );
								}
							}

							// Recalculate this type
							updateSum( which );
						}
					});
			});
		});

		/**
		 * Return the other column type
		 * 
		 * @param {string} which The other's other column type
		 * @return {string} The other column type
		 */
		function otherType( which ) {
			return ( debit === which ) ? credit : debit;
		}

		/**
		 * Parse the argument as a formatted number
		 * 
		 * @param  {mixed} number Value to format
		 * @return {string} Formatted number with 2 digits
		 */
		function formatNumber( number ) {
			return parseFloat( number ).toFixed(2).toString();
		}

		/**
		 * Update the sum of the given column type
		 *
		 * Sets the value in the corresponding sum holder.
		 *
		 * @param {string} which The column type to sum. Defaults to both columns
		 */
		function updateSum( which ) {
			var v = 0;

			// Update both when no arg was passed
			if ( 'undefined' === typeof which ) {
				which = false;
			}

			// Sum debit values
			if ( false === which || debit === which ) {
				debitSum = 0;
				$debits.filter( function(){ return !! this.value; }).each( function( i, el ){
					v = parseFloat( el.value );
					debitSum += ( ! isNaN( v ) ) ? v : 0;
				});

				$debitSum.val( formatNumber( debitSum ) );
			}

			// Sum credit values
			if ( false === which || credit === which ) {
				creditSum = 0;
				$credits.filter( function(){ return !! this.value; }).each( function( i, el ){
					v = parseFloat( el.value );
					creditSum += ( ! isNaN( v ) ) ? v : 0;
				});

				$creditSum.val( formatNumber( creditSum ) );
			}

			// Handle sum inequality 
			if ( debitSum !== creditSum ) {
				// Add mismatch class for visual notification 
				$sumRow.addClass( 'mismatch' )
					// And toggle submit button disabler
					.find( 'input[name="submit-records"]' ).prop( 'disabled', true );
			} else if ( $sumRow.hasClass( 'mismatch' ) ) {
				// Remove mismatch class
				$sumRow.removeClass( 'mismatch' )
					// And toggle submit button disabler
					.find( 'input[name="submit-records"]' ).prop( 'disabled', false );
			}
		}
	});

})(jQuery);

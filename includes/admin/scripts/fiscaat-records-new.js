/**
 * WordPress Plugin: Fiscaat
 * 
 * Fiscaat New Records scripts
 */

/**
 * Debit/credit fields handler on doc ready
 */
jQuery(document).ready( function($) {

	var $fiscaat_record_total = $('#post--3 td.fiscaat_record_value'),
	    $fiscaat_debit_total  = $fiscaat_record_total.find('input#fiscaat_records_debit_total'),
	    $fiscaat_credit_total = $fiscaat_record_total.find('input#fiscaat_records_credit_total'),
	    // $submit_button        = $('#fiscaat_records_new_submit, #fiscaat_records_new_sumbit2'),
	    currency_format       = fiscaat_records_newL10n.currency_format,
	    currency              = fiscaat_records_newL10n.currency;

	// Setup i10n defaults
	$.formatCurrency.regions['fiscaat'] = {
		symbol: currency, 
		positiveFormat: fiscaat_records_newL10n.positive,
		negativeFormat: fiscaat_records_newL10n.negative,
		decimalSymbol: currency_format.dec_point,
		digitGroupSymbol: currency_format.thousands_sep
	};

	// Parse field inputs to currency. Sum the total inputs.
	$('.record td.fiscaat_record_value input').not('.fiscaat_record_total input').blur( function(){
		var $this = $(this),
		    type  = $this.attr('class').replace('_value small-text', '').replace('fiscaat_record_', ''); // Get 'debit' or 'credit'
		    console.log( type );

		$this.blur( function(){
			$twin = $this.siblings('input.small-text');

			alert('blur '+ type);

			// Empty when adjacent input has value
			if ( $twin.val().length !== 0 ) {
				$this.attr('value', '');
				return;
			}

			// Format currency
			$this.formatCurrency({ roundToDecimalPlace: currency_format.decimals, region: 'fiscaat' })
				
			// Set sum value 
			fiscaat_set_totals_sum( type );

			// Register change event
			$fiscaat_record_total
				.find('input#fiscaat_records_' + type + '_total')
					.change( fiscaat_able_submit() )
					.change(); // Trigger change event
		});
	});

	// Handle currency input and make it floatable in number[dot]decimals format
	fiscaat_parsefloat_currency = function( value ){

		// Don't do empty fields
		if ( value.length == 0 )
			return 0;

		// Strip currency symbol if present
		if ( value.indexOf( currency ) !== -1 )
			value = value.replace(currency, '');
		
		// Remove all thousands separators, transform decimal delimiter to dot
		value = value.replace(currency_format.thousands_sep, '').replace(currency_format.dec_point, '.');

		return parseFloat( value );
	}

	// Count sum per type
	fiscaat_set_totals_sum = function( type ){
		var sum = 0;

		$('.record td.fiscaat_record_value input.fiscaat_record_' + type + '_value')
			.each( function(){
				sum += fiscaat_parsefloat_currency( $(this).val() );
			});

		$fiscaat_record_total
			.find('input#fiscaat_records_' + type + '_total')
				.attr({ 'value': sum })
				.formatCurrency({ roundToDecimalPlace: currency_format.decimals, region: 'fiscaat' });
	}

	// Handle submit button. Enable or disable.
	// fiscaat_able_submit = function(){
	// 	var val_d  = $fiscaat_debit_total.val(),
	// 	    val_c  = $fiscaat_credit_total.val(),
	// 	    empty  = fiscaat_parsefloat_currency( val_d ) == 0 || fiscaat_parsefloat_currency( val_c ) == 0,
	// 	    nosync = val_d !== val_c;

	// 	$submit_button.attr('disabled', ( empty || nosync ));
	// }

	// Sum on document ready
	$.each( ['debit', 'credit'], function(){
		fiscaat_set_totals_sum( this );
		// fiscaat_able_submit();
	});

	/**
	 * Add additional num rows to list table
	 */
	$('#add-num-rows').click( function(e){
		e.preventDefault(); // block default action

		var $this    = $(this),
		    select   = $this.parent().find('#num-rows'),
		    num_rows = parseInt( select.val() );

		if ( num_rows < 1 )
			return;

		// Clone hidden <tr> with deep events
		var def_row = $('<div>').append( $('#new-default-container').find('tr').clone(true) ).html(),
		    html    = '';

		// Join rows
		for (var a = 0; a < num_rows; a += 1 )
			html += def_row;

		$('#post--3').before(html); // Insert rows
		select.val(1); // Reset input
	});

});

/**
 * AJAX record posting on submit
 *
jQuery(document).ready( function($) {

	// On submitting
	$('#fiscaat_insert_new_records_submit').click( function(){
		var values  = {},
		    missing = 0;

		// Setup values array
		$.each($('#posts-filter').serializeArray(), function(i, field) {
			if ( this.name.indexOf('fiscaat_new_record') !== -1 ) {
				if ( typeof values[this.name] === 'undefined' ) values[this.name] = [];
				values[this.name].push(this.value);
			} else {
				values[this.name] = this.value;
			}
		});

		// console.log(values);
		console.log(fiscaat_records_newL10n.required_fields);

		$.each( fiscaat_records_newL10n.required_fields, function(i, field) {
			console.log( i, typeof field, field);
		});

		// Find missing fields
		$.each(values, function(name, field) {
			console.log( typeof name, name, fiscaat_records_newL10n.required_fields.indexOf(name) );

			$.each(field, function(record, value) {
				if ( !value ) {
					$('.record [name="'+ name +'"]').eq(record).addClass('missing');
					missing++;
				}
			});

		});

		if ( missing.length > 0 )
			console.log( missing );

		return false;


		// var data = {
		// 	action: 'fiscaat_records_new',
		// 	values: values,
		// }

		// $.post( ajaxurl, data, function(response){
		// 	alert(response);
		// });

	});
});
*/

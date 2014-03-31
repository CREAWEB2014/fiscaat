/**
 * Fiscaat Import Records script
 *
 * Handles ajax record import
 */

// Process vars
var fct_import_is_running = false,
    fct_import_run_timer,
    fct_import_delay_time = 1; // Time in seconds between import processes

// Start import
function fct_import_start() {
	if ( false == fct_import_is_running ) {
		fct_import_is_running = true;
		jQuery('#fct_import_message').html('');
		fct_import_log( '<p class="loading">' + fct_importL10n.uploading + '</p>' );
		fct_import_run();
	}
}

// Run import
function fct_import_run() {
	jQuery('#fct_import_records').ajaxForm( function(response){
		jQuery('#fct_import_restart').attr('value', 0); // Cancel restart
		var response_length = response.length - 1;
		response = response.substring(0,response_length);
		fct_import_success(response);
	});
}

// Stop import
function fct_import_stop() {
	jQuery('#fct_import_restart').attr('value', 1);
	jQuery('#fct_import_message p').removeClass( 'loading' );
	fct_import_is_running = false;
	clearTimeout( fct_import_run_timer );
}

// Handle response
function fct_import_success(response) {
	fct_import_log(response);

	if ( response.toLowerCase().indexOf('error') > -1 ) {
		fct_import_log('<p>' + fct_importL10n.error + '</p>');
		fct_import_stop();
	} else if ( response == '<p class="loading">' + fct_importL10n.complete + '</p>' ) {
		fct_import_log('<p class="loading">' + fct_importL10n.redirect + '</p>' ); // redirect...
	} else if( fct_import_is_running ) { // keep going
		clearTimeout( fct_import_run_timer );
		fct_import_run_timer = setTimeout( 'fct_import_run()', fct_import_delay_time );
	} else {
		fct_import_stop();
	}
}

// Display messages
function fct_import_log(text) {
	var $msg = jQuery('#fct_import_message');
	if ( $msg.css('display') == 'none' ) {
		$msg.show();
	}
	if ( text ) {
		$msg.find('p').removeClass( 'loading' );
		$msg.append( text );
	}
}

jQuery(document).ready( function(){
	var $form  = jQuery('#fct_import_records');
	    $start = $form.find('#fct_import_start'),
	    $stop  = $form.find('#fct_import_stop'); // why?

	jQuery.each( [$start, $stop], function (){
		this.click( window[this.attr('name')] );
	});
});

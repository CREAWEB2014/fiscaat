/**
 * Fiscaat Import Records script
 *
 * Handles ajax record import
 */

// Process vars
var fiscaat_import_is_running = false,
    fiscaat_import_run_timer,
    fiscaat_import_delay_time = 1; // Time in seconds between import processes

// Start import
function fiscaat_import_start() {
	if ( false == fiscaat_import_is_running ) {
		fiscaat_import_is_running = true;
		jQuery('#fiscaat_import_message').html('');
		fiscaat_import_log( '<p class="loading">' + fiscaat_importL10n.uploading + '</p>' );
		fiscaat_import_run();
	}
}

// Run import
function fiscaat_import_run() {
	jQuery('#fiscaat_import_records').ajaxForm( function(response){
		jQuery('#fiscaat_import_restart').attr('value', 0); // Cancel restart
		var response_length = response.length - 1;
		response = response.substring(0,response_length);
		fiscaat_import_success(response);
	});
}

// Stop import
function fiscaat_import_stop() {
	jQuery('#fiscaat_import_restart').attr('value', 1);
	jQuery('#fiscaat_import_message p').removeClass( 'loading' );
	fiscaat_import_is_running = false;
	clearTimeout( fiscaat_import_run_timer );
}

// Handle response
function fiscaat_import_success(response) {
	fiscaat_import_log(response);

	if ( response.toLowerCase().indexOf('error') > -1 ) {
		fiscaat_import_log('<p>' + fiscaat_importL10n.error + '</p>');
		fiscaat_import_stop();
	} else if ( response == '<p class="loading">' + fiscaat_importL10n.complete + '</p>' ) {
		fiscaat_import_log('<p class="loading">' + fiscaat_importL10n.redirect + '</p>' ); // redirect...
	} else if( fiscaat_import_is_running ) { // keep going
		clearTimeout( fiscaat_import_run_timer );
		fiscaat_import_run_timer = setTimeout( 'fiscaat_import_run()', fiscaat_import_delay_time );
	} else {
		fiscaat_import_stop();
	}
}

// Display messages
function fiscaat_import_log(text) {
	var $msg = jQuery('#fiscaat_import_message');
	if ( $msg.css('display') == 'none' ) {
		$msg.show();
	}
	if ( text ) {
		$msg.find('p').removeClass( 'loading' );
		$msg.append( text );
	}
}

jQuery(document).ready( function(){
	var $form  = jQuery('#fiscaat_import_records');
	    $start = $form.find('#fiscaat_import_start'),
	    $stop  = $form.find('#fiscaat_import_stop'); // why?

	jQuery.each( [$start, $stop], function (){
		this.click( window[this.attr('name')] );
	});
});

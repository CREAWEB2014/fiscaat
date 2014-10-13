/* global _wpPluploadSettings */
(function($, wp){
	var media = wp.media, recordUploader, frame;

	/**
	 * Setup upload window for new-records page
	 *
	 * @see media.view.MediaFrame.Manage @ wp-includes/js/media-grid.js
	 */
	recordUploader = media.view.MediaFrame.extend({
		/**
		 * @global wp.Uploader
		 */
		initialize: function() {

			// Ensure core and media grid view UI is enabled.
			this.$el.addClass('wp-core-ui');

			// Force the uploader off if the upload limit has been exceeded or
			// if the browser isn't supported.
			if ( wp.Uploader.limitExceeded || ! wp.Uploader.browser.supported ) {
				this.options.uploader = false;
			}

			// Initialize a window-wide uploader.
			if ( this.options.uploader ) {
				this.uploader = new media.view.UploaderWindow({
					controller: this,
					uploader: {
						dropzone:  document.body,
						container: document.body
					}
				}).render();
				this.uploader.ready();
				$('body').append( this.uploader.el );

				this.options.uploader = false;
			}

			// Call 'initialize' directly on the parent class.
			media.view.MediaFrame.prototype.initialize.apply( this, arguments );
		}
	});

	// Launch
	frame = new recordUploader({ uploader: true });

}(jQuery, wp));

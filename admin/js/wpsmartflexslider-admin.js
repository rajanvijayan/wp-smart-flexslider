(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	 $(document).ready(function(){
    var add_slide_wpflexframe;
    var change_slide_frame;
	
		$('#flex_slide').on('click', function(event){
			event.preventDefault();
			var SliderID = $(this).data('slideid');
			
			if ( add_slide_wpflexframe ) {
				console.log("enter cond");
				add_slide_wpflexframe.open();
				return;
			}

			add_slide_wpflexframe = wp.media.frames.file_frame = wp.media({
				multiple: 'add',
				frame: 'post',
				library: {type: 'image'}
			});
			add_slide_wpflexframe.on('insert', function() {
				
				var selection = add_slide_wpflexframe.state().get('selection');
				var slide_attachmentids = [];

				selection.map(function(attachment) {
					attachment = attachment.toJSON();
					slide_attachmentids.push(attachment.id);
				}); 
				
				var data = {
					action: 'wpflexslider_ajax',
					slider_id: SliderID,
					selection: slide_attachmentids,
					mode: 'slider_save'
				};

				jQuery.post(ajax_var.ajax_url, data, function(response) {
					$('#flex_append').html(response);
				});
				
			
			});
			add_slide_wpflexframe.open();
			$(".media-menu a:contains('Media Library')").remove();
		});
		//Delete Slide 
		jQuery('.flexslider-wrap').on('click','.delete_slide', function(event){ 
			var conformation = confirm("Are you sure?");
			if(conformation == true){ 
				var attachment_key = $(this).data('delete');
				var SliderID = $(this).data('slider_id');
				var data = {
					action: 'wpflexslider_ajax',
					slider_id: SliderID,
					attachment_key: attachment_key,
					mode: 'slide_delete'
				};

				jQuery.post(ajax_var.ajax_url, data, function(response) {
					$('#flex_append').html(response);
				});
			}
				
		});
		
	 });

})( jQuery );

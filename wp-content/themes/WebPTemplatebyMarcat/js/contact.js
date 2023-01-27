call("//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js", function(){
    call("//jpostal-1006.appspot.com/jquery.jpostal.js", function(){
	    jQuery(function($){
	       $(window).ready( function() {
	            $('#post_num_01').jpostal({
	               postcode : [
	                  '#post_num_01',
	                  '#post_num_02'
	               ],
	               address : {
	                  '#pref_select'  : '%3',
	                  '#text_box_in_table1'  : '%4',
	                  '#text_box_in_table2'  : '%5'
	               }
	            });
	         });
	    });
	    jQuery( function( $ ) {
	      $( '#mw_wp_form_mw-wp-form-404 select option[value=""]' )
	      .html( '選択してください。' );
	    } );
    });
});
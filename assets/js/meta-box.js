jQuery(document).ready(function($) {
	$( '#mcc_copy_link' ).on('click', function(e) {
		e.preventDefault();

		var additional_options = new Object();

		var anchor = $(this);
		var url = anchor.attr( 'href' );

		var selected = $('input[name=mcc_dest_blog_type]:checked').val();
		var group = $('#mcc_blog_group').val();
		var nbt_group = $('#mcc_nbt_blog_group').val();

		if ( ! selected ) {
			alert( mcc_meta_texts.select_an_option );
			return false;
		}

		if ( selected == 'all' ) {
			additional_options['dest_blog_type'] = selected;
		}
		else if ( selected == 'group' && ! group ) {
			alert( mcc_meta_texts.select_a_group );
			return false;
		}
		else if ( selected == 'group' && group ) {
			additional_options['dest_blog_type'] = selected;
			additional_options['group'] = group;
		}
		else if ( selected == 'nbt_group' && ! nbt_group ) {
			alert( mcc_meta_texts.select_a_group );
			return false;
		}
		else if ( selected == 'nbt_group' && nbt_group ) {
			additional_options['dest_blog_type'] = selected;
			additional_options['nbt_group'] = nbt_group;
		}

		

		
		$('input.mcc_options:checked').each( function( i, item ) {
			additional_options[ $(item).val() ] = true;
		});

		
		url += '&' + $.param(additional_options);

		window.location = url;
	});

});
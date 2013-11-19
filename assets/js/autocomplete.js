jQuery(document).ready(function($) {
	var mcc_cache = {};
	var type = '';
	$( "#autocomplete" ).autocomplete({
	  minLength: 2,
	  source: function( request, response ) {
	    var term = request.term;
	    if ( term in mcc_cache ) {
	      response( mcc_cache[ term ] );
	      return;
	    }

	    type = $('#autocomplete').data('type');

		var data = {
			action: 'mcc_get_' + type + '_search',
			term: request.term
		};

		if ( 'posts' == type || 'users' == type ) {
			data.blog_id = $('#src_blog_id').val()
		}

	    $.ajax({
			url: ajaxurl,
			data: data,
			type: 'post',
			dataType: 'json'
		}).done(function( data ) {
			mcc_cache[ term ] = data;
			response( data );
		}).fail( function(data) {
			console.log(data);
		});

	  },
	  response: function( event, ui ) {
	  	if ( 'sites' == type ) {
			for ( var i = 0; i < ui.content.length; i++ ) {
		  		ui.content[i].label = ui.content[i].path + ' [' + ui.content[i].blog_name + ']';
		  		ui.content[i].value = ui.content[i].blog_name;
		  	}
		}
		if ( 'posts' == type ) {
			for ( var i = 0; i < ui.content.length; i++ ) {
		  		ui.content[i].label = ui.content[i].the_title + ' [' + ui.content[i].the_id + ']';
		  		ui.content[i].value = ui.content[i].the_title;
		  	}
		}
		if ( 'users' == type ) {
			for ( var i = 0; i < ui.content.length; i++ ) {
		  		ui.content[i].label = ui.content[i].username + ' [' + ui.content[i].user_id + ']';
		  		ui.content[i].value = ui.content[i].username;
		  	}
		}
	  	
	  },
	  select: function ( event, ui ) {
	  	if ( 'sites' == type ) {
			$( '#blog_id' ).val( ui.item.blog_id );
		}
		if ( 'posts' == type ) {
			$( '#post_id' ).val( ui.item.the_id );
		}
		if ( 'users' == type ) {
			$( '#user_id' ).val( ui.item.user_id );
		}
	  }
	});
});
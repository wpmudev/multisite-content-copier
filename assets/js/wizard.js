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

		if ( 'posts' == type ) {
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
	  	
	  },
	  select: function ( event, ui ) {
	  	if ( 'sites' == type ) {
			$( '#blog_id' ).val( ui.item.blog_id );
		}
		if ( 'posts' == type ) {
			$( '#post_id' ).val( ui.item.the_id );
		}
	  }
	});

	var current_posts = {};
	$( '#add-post' ).click( function( e ) { 
		e.preventDefault();

		var post_id = $( '#post_id' ).val().trim();

		post_id = parseInt( post_id );

		if ( ! isNaN( post_id ) && post_id !== 0 ) {
			var data = {
				action: 'mcc_retrieve_single_post_data',
				post_id: $('#post_id').val(),
				blog_id: $('#src_blog_id').val(),
			};
			$.ajax({
				url: ajaxurl,
				data: data,
				type: 'post',
			}).done(function( data ) {
				if ( data !== '' ) {
					$( '#posts-list' ).append( data );
					current_posts[ data.post_id ] = data.post_id;
					update_post_click_event();
				}
			});
		}
	});

	var current_blogs = {};
	$( '#add-blog' ).click( function( e ) { 
		e.preventDefault();

		var blog_id = $( '#blog_id' ).val().trim();

		blog_id = parseInt( blog_id );

		if ( ! isNaN( blog_id ) && blog_id !== 0 ) {
			var data = {
				action: 'mcc_retrieve_single_blog_data',
				blog_id: $('#blog_id').val()
			};
			$.ajax({
				url: ajaxurl,
				data: data,
				type: 'post',
			}).done(function( data ) {
				if ( data !== '' ) {
					$( '#blogs-list' ).append( data );
					current_blogs[ data.blog_id ] = data.blog_id;
					update_blog_click_event();
				}
			});
		}
	});

	update_blog_click_event();
	function update_blog_click_event() {
		$( '.mcc-remove-blog' ).click( function(e)  {
			e.preventDefault();
			var blog_id = $(this).data('blog-id');
			$('#blog-' + blog_id ).remove(); 
		});
	}

	update_post_click_event();
	function update_post_click_event() {
		$( '.mcc-remove-post' ).click( function(e)  {
			e.preventDefault();
			var post_id = $(this).data('post-id');
			$('#post-' + post_id ).remove(); 
		});
	}



	String.prototype.trim = function() { 
		return this.replace(/^\s+|\s+$/g, ''); 
	};
});
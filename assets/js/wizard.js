jQuery(document).ready(function($) {

	$( '#mcc-refresh-post-types' ).on( 'click', function(e) {
		e.preventDefault();

		var blog_id = $('#blog_id').val();
		var _ajaxurl = $('#blog_ajax_url').val();

		if ( blog_id == '' || blog_id == 0 )
			return false;

		$( '.spinner' ).show();

		$( '#mcc-cpt-list-wrap ul li' ).remove();

		var data = {
			action: 'mcc_retrieve_cpt_selectors_data',
			blog_id: $('#blog_id').val()
		};

		$.ajax({
			url: _ajaxurl,
			data: data,
			type: 'post',
		}).done(function( data ) {
			$('.spinner').hide();
			if ( data !== '' ) {
				$( '#mcc-cpt-list-wrap ul' ).append( data );
			}
		});
	});

	var current_posts = {};
	$( '#mcc-posts-list #doaction' ).click( function( e ) { 
		e.preventDefault();

		if ( $('#mcc-posts-list select[name=action]').val() != 'add' )
			return;

		var post_ids_selected = $( 'input.post_id:checked' );
		var post_ids = [];
		post_ids_selected.each( function (i, element) {
			var value = parseInt($(this).val());
			post_ids[i] = value;
		} );

		if ( post_ids.length == 0 )
			return;


		$('.spinner').show();
		var data = {
			action: 'mcc_retrieve_single_post_data',
			post_ids: post_ids,
			blog_id: $('#src_blog_id').val(),
			current_posts: current_posts
		};
		$.ajax({
			url: ajaxurl,
			data: data,
			type: 'post',
		}).done(function( data ) {
			$('.spinner').hide();
			if ( data !== '' ) {
				$( '#posts-list' ).append( data );
				update_post_click_event();
			}
		});
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

	var current_users = {};
	$( '#add-user' ).click( function( e ) { 
		e.preventDefault();

		var user_id = $( '#user_id' ).val().trim();

		user_id = parseInt( user_id );

		if ( ! isNaN( user_id ) && user_id !== 0 ) {
			$('.spinner').show();
			var data = {
				action: 'mcc_retrieve_single_user_data',
				user_id: $('#user_id').val()
			};
			$.ajax({
				url: ajaxurl,
				data: data,
				type: 'post',
			}).done(function( data ) {
				$('.spinner').hide();
				if ( data !== '' ) {
					$( '#users-list' ).append( data );
					current_blogs[ data.user_id ] = data.user_id;
					update_user_click_event();
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

	update_user_click_event();
	function update_user_click_event() {
		$( '.mcc-remove-user' ).click( function(e)  {
			e.preventDefault();
			var user_id = $(this).data('user-id');
			$('#user-' + user_id ).remove(); 
		});
	}



	String.prototype.trim = function() { 
		return this.replace(/^\s+|\s+$/g, ''); 
	};
});
jQuery(document).ready(function($) {

	// REFRESH CPTs
	$( '#mcc-refresh-post-types' ).on( 'click', refresh_post_types );
	function refresh_post_types(e) {
		e.preventDefault();
		$this = $(this);


		var blog_id = $('#blog_id').val();

		if ( blog_id == '' || blog_id == 0 )
			return false;

		$( '.spinner' ).show();
		$( '#mcc-refresh-post-types' ).attr('disabled', true);

		$.ajax({
			url: ajaxurl,
			data: {
				blog_id: blog_id,
				action: 'mcc_get_blog_ajax_url'
			},
			type: 'post',
		}).done(function( data ) {
			_ajaxurl = data;

			$( '#mcc-cpt-list-wrap ul li' ).slideUp().remove();

			if ( ! _ajaxurl || _ajaxurl.trim() == '' || _ajaxurl == '0' ) {
				$('.spinner').hide();
				$( '#mcc-refresh-post-types' ).attr('disabled', false);
				$( '#mcc-cpt-list-wrap ul' ).append( '<li>' + captions.blog_not_found + '</li>' );
				return false;
			}

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
					$( '#mcc-refresh-post-types' ).attr('disabled', false);
				}
			});

			
		});
						
	}

	// POSTS/PAGES/CPTs SELECTION
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

	// USERS SELECTION
	$('input[name=users_selection]').change(function( element ) {
		var value = $(this).val();
		if ( 'all' == value ) {
			$('#mcc-users-list input[type=checkbox]').attr('disabled', true).attr('checked',false);
		}
		else {
			$('#mcc-users-list input[type=checkbox]').attr('disabled', false);
		}
	});

	var current_users = [];
	$( '#mcc-users-list #doaction' ).click( function( e ) { 
		e.preventDefault();

		if ( $('#mcc-users-list select[name=action]').val() != 'add' )
			return false;

		var user_ids_selected = $( 'input.user_id:checked' );
		var user_ids = [];
		user_ids_selected.each( function (i, element) {
			var value = parseInt($(this).val());
			if ( ! current_users[value] ) {
				current_users[value] = true;
				user_ids[i] = value;
			}
		} );

		if ( user_ids.length == 0 )
			return;


		$('.spinner').show();
		var data = {
			action: 'mcc_retrieve_single_user_data',
			user_ids: user_ids,
			current_posts: current_posts
		};
		$.ajax({
			url: ajaxurl,
			data: data,
			type: 'post',
		}).done(function( data ) {
			$('.spinner').hide();
			if ( data !== '' ) {
				$( '#users-list' ).append( data );
				update_user_click_event();
			}
		});
	});

	// BLOGS SELECTIONS
	var current_blogs = {};
	$( '#add-blog' ).click( function( e ) { 
		e.preventDefault();

		$('.spinner').show();
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
					$('.spinner').hide();
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
			update_items_ids(post_id);
			$('#post-' + post_id ).remove(); 
		});
	}

	update_user_click_event();
	function update_user_click_event() {
		$( '.mcc-remove-user' ).click( function(e)  {
			e.preventDefault();
			var user_id = $(this).data('user-id');
			update_items_ids( user_id );
			$('#user-' + user_id ).remove(); 
		});
	}

	function update_items_ids( item_id ) {
		$.ajax({
			url: ajaxurl,
			type: 'post',
			data: {action: 'mcc_remove_item_id_from_list', item_id: item_id},
		});
	}



	String.prototype.trim = function() { 
		return this.replace(/^\s+|\s+$/g, ''); 
	};
});
jQuery(document).ready(function($) {

	// REFRESH CPTs
	$( '#mcc-refresh-post-types' ).on( 'click', refresh_post_types );
	function refresh_post_types(e) {
		e.preventDefault();
		$this = $(this);

		var blog_id = $('#blog_id').val();

		if ( blog_id == '' || blog_id == 0 ) {
			return false;
		}

		$( '.spinner' ).show();
		$( '#mcc-refresh-post-types' ).attr('disabled', true);

		$.ajax({
			url: ajaxurl,
			data: {
				blog_id: blog_id,
				action: 'mcc_get_blog_ajax_url'
			},
			type: 'post'
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
				type: 'post'
			}).done(function( data ) {
				if ( data !== '' ) {
					$( '#mcc-cpt-list-wrap ul' ).append( data );
				}
			})
			.always( function() {
				$.ajax({
					url: ajaxurl,
					type: 'post',
					data: {
						action: 'mcc_retrieve_cpt_custom_selector_data',
						blog_id: $('#blog_id').val()
					},
				})
				.done(function(data) {
					$( '#mcc-cpt-list-wrap ul' ).append( data );
				})
				.always(function() {
					$('.spinner').hide();
					$( '#mcc-refresh-post-types' ).attr('disabled', false);
				});				
			});

			
		});
						
	}

	$('#mcc-refresh-post-types').trigger('click');

	// POSTS/PAGES/CPTs SELECTION
	$( '#mcc-posts-list #doaction' ).click( function( e ) { 
		e.preventDefault();

		var $this = $(this);
		var post_ids_selected = $( 'input.post_id:checked' );
		var post_ids = [];
		post_ids_selected.each( function (i, element) {
			var value = parseInt($(this).val());

			if ( current_posts.indexOf(value) == -1 ) {
				post_ids[i] = value;
				current_posts.push(value);
			}
			
		} );

		if ( post_ids.length == 0 )
			return;

		$this.attr('disabled', true);
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
			type: 'post'
		}).done(function( data ) {
			$('.spinner').hide();
			if ( data !== '' ) {
				$( '#posts-list' ).append( data );
				update_post_click_event();
			}
		})
		.always(function() {
			$this.attr('disabled', false);
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

	$( '#mcc-users-list #doaction' ).click( function( e ) { 
		e.preventDefault();

		var $this = $(this);
		var user_ids_selected = $( 'input.user_id:checked' );
		var user_ids = [];
		user_ids_selected.each( function (i, element) {
			var value = parseInt($(this).val());

			if ( current_users.indexOf(value) == -1 ) {
				user_ids[i] = value;
				current_users.push(value);
			}

		} );

		if ( user_ids.length == 0 )
			return;

		$this.attr('disabled', true);
		$('.spinner').show();
		var data = {
			action: 'mcc_retrieve_single_user_data',
			user_ids: user_ids
		};
		$.ajax({
			url: ajaxurl,
			data: data,
			type: 'post'
		}).done(function( data ) {
			$('.spinner').hide();
			if ( data !== '' ) {
				$( '#users-list' ).append( data );
				update_user_click_event();
			}
		})
		.always(function() {
			$this.attr('disabled', false);
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
				type: 'post'
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
			var index = current_posts.indexOf(post_id);
			if ( index > -1 )
				current_posts.splice(index, 1);
		});
	}

	update_user_click_event();
	function update_user_click_event() {
		$( '.mcc-remove-user' ).click( function(e)  {
			e.preventDefault();
			var user_id = $(this).data('user-id');
			update_items_ids( user_id );
			$('#user-' + user_id ).remove(); 
			var index = current_users.indexOf(user_id);
			if ( index > -1 )
				current_users.splice(index, 1);
		});
	}

	function update_items_ids( item_id ) {
		$.ajax({
			url: ajaxurl,
			type: 'post',
			data: {action: 'mcc_remove_item_id_from_list', item_id: item_id}
		});
	}

	$( '#blogs-list-wrap' ).click(function() {
		$('#dest_blog_type_list').attr('checked', true );
	});
	$('#dest_blog_type_group_selector').click(function() {
		$('#dest_blog_type_group').attr('checked', true );
	});
	$('#dest_blog_type_nbt_group_selector').click(function() {
		$('#dest_blog_type_nbt_group').attr('checked', true );
	})

	String.prototype.trim = function() { 
		return this.replace(/^\s+|\s+$/g, ''); 
	};

	// Source: http://stackoverflow.com/questions/3697555/why-is-indexof-not-working-in-internet-explorer
	if (!Array.prototype.indexOf)
	{
	  Array.prototype.indexOf = function(elt /*, from*/)
	  {
	    var len = this.length >>> 0;

	    var from = Number(arguments[1]) || 0;
	    from = (from < 0)
	         ? Math.ceil(from)
	         : Math.floor(from);
	    if (from < 0)
	      from += len;

	    for (; from < len; from++)
	    {
	      if (from in this &&
	          this[from] === elt)
	        return from;
	    }
	    return -1;
	  };
	}
});
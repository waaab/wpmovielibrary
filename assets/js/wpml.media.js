
wpml = wpml || {};

var wpml_images, wpml_posters;

	wpml.media = {
		init: function() {},
		images: {},
		posters: {}
	};

		/**
		 * WPML Movie Media: Movie Images
		 */
		wpml.media.images = wpml_images = {

			init: function() {},
			frame: function() {},
			select: function() {},
			upload: function() {},
			close: function() {}
		};

			/**
			 * Init WPML Media Images
			 */
			wpml.media.images.init = function() {
				wpml_images.frame().open();
			};

			/**
			 * Media Images Modal. Extends WP Media Modal to show
			 * movie images from external API instead of regular WP
			 * Attachments.
			 */
			wpml.media.images.frame = function() {

				if ( this._frame )
					return this._frame;

				this._frame = wp.media({
					title: 'Import images from "' + $('#tmdb_data_title').val() + '"',
					frame: 'select',
					searchable: false,
					library: {
						// Dummy: avoid any image to be loaded
						type : 'gallery',
						post__in:[ $('#post_ID').val() ],
						post__not_in:[0],
						s: 'TMDb_ID='+$('#tmdb_data_tmdb_id').val()+',type=image'
					},
					multiple: true,
					button: {
						text: 'Import images'
					}
				}),

				this._frame.state('library').unset('router');
				this._frame.options.button.reset = false;
				this._frame.options.button.close = false;

				this._frame.state('library').unbind('select').on('select', this.select);

				return this._frame;
			};

			/**
			 * Select override for Modal
			 * 
			 * Handle selected images and custom progress bar
			 */
			wpml.media.images.select = function() {

				var $content = $(wpml_images._frame.content.selector);

				if ( ! $('#progressbar_bg').length )
					$content.append('<div id="progressbar_bg"><div id="progressbar"><div id="progress"></div></div><div id="progress_status">Please wait while the images are uploading.</div>');

				$('#progressbar_bg, #progressbar').show();

				var settings = wp.media.view.settings,
				    selection = this.get('selection'),
				    total = selection.length;

				$('.added').remove();

				wpml_images.total = total;
				selection.map( wpml_images.upload );

				return;
			};

			/**
			 * Upload select images.
			 * 
			 * @param    object    Image to upload
			 * @param    int       Image index to update the progress bar
			 */
			wpml.media.images.upload = function( image, i ) {

				var index = i + 1;
				var progress = index == wpml_images.total ? 100 : Math.round( ( index * 100 ) / wpml_images.total );

				wpml._post({
						action: 'wpml_upload_image',
						wpml_check: wpml_ajax.utils.wpml_check,
						image: image.attributes.tmdb_data,
						title: wpml_ajax.lang.image_from + ' ' + $('#tmdb_data_title').val(),
						post_id: $('#post_ID').val(),
						tmdb_id: $('#tmdb_data_tmdb_id').val()
					},
					function(_r) {
						if ( ! isNaN( _r ) && parseInt( _r ) == _r ) {
							$('#tmdb_load_images').parent('.tmdb_movie_images').before('<div class="tmdb_movie_images tmdb_movie_imported_image"><img width="' + image.attributes.sizes.medium.width + '" height="' + image.attributes.sizes.medium.height + '" src="' + image.attributes.sizes.medium.url + '" class="attachment-medium" class="attachment-medium" alt="' + $('#tmdb_data_title').val() + '" /></div>');
						}
					},
					function() {
						$('#progressbar #progress').width(''+progress+'%');
						if ( index == wpml_images.total ) {
							$('#progress_status').text('Done!');
							window.setTimeout( wpml_images.close(), 2000 );
						}
						else {
							var t = $('#progress_status').text();
							$('#progress_status').text(t+' .');
						}
					}
				);
			};

			/**
			 * Close the Modal
			 */
			wpml.media.images.close = function() {
				$('#progressbar_bg, #progressbar').remove();
				if ( undefined != wpml_images._frame )
					wpml_images._frame.close();
			};

		/**
		 * WPML Movie Media: Movie Posters
		 */
		wpml.media.posters = wpml_posters = {

			init: function() {},
			frame: function() {},
			select: function() {},
			set_featured: function() {},
			close: function() {}
		}

			/**
			 * Init WPML Media Posters
			 */
			wpml.media.posters.init = function() {
				wpml_posters.frame().open();
			};

			/**
			 * Media Posters Modal. Extends WP Media Modal to show
			 * movie posters from external API instead of regular WP
			 * Attachments.
			 */
			wpml.media.posters.frame = function() {

				if ( this._frame )
					return this._frame;

				this._frame = wp.media({
					title: 'Select a poster for "' + $('#tmdb_data_title').val() + '"',
					frame: 'select',
					searchable: false,
					library: {
						// Dummy: avoid any image to be loaded
						type : 'gallery',
						post__in:[ $('#post_ID').val() ],
						post__not_in:[0],
						s: 'TMDb_ID='+$('#tmdb_data_tmdb_id').val()+',type=poster'
					},
					multiple: false,
					button: {
						text: 'Import Poster'
					}
				}),

				this._frame.state('library').unset('router');
				this._frame.options.button.reset = false;
				this._frame.options.button.event = 'import_poster';
				this._frame.options.button.close = false;

				this._frame.state('library').unbind('select').on('import_poster', this.select);

				return this._frame;
			};

			/**
			 * Select override for Modal
			 * 
			 * Handle selected poster and custom progress bar
			 */
			wpml.media.posters.select = function() {

				var $content = $(wpml_posters._frame.content.selector);

				if ( ! $('#progressbar_bg').length )
					$content.append('<div id="progressbar_bg"><div id="progressbar"><div id="progress"></div></div><div id="progress_status">Please wait while the poster is uploading...</div>');

				$('#progressbar_bg, #progressbar').show();
				$('#progressbar #progress').width('40%');

				var settings = wp.media.view.settings,
				    selection = this.get('selection'),
				    total = selection.length;

				$('.added').remove();

				wpml_posters.total = total;
				selection.map( wpml_posters.set_featured );

				return;
			};

			/**
			 * Set Poster as featured image.
			 * 
			 * Upload the selected image and set it as the post's
			 * featured image.
			 */
			wpml.media.posters.set_featured = function( image ) {

				

				if ( undefined != image.attributes && undefined != image.attributes.tmdb_data ) {
					var _image = {file_path: image.attributes.tmdb_data.file_path};
				}
				else {
					if ( 0 <= parseInt( wp.media.featuredImage.get() ) ) {
						$('#progressbar #progress').width('100%');
						$('#progress_status').text('Done!');
						window.setTimeout( wpml_posters.close(), 2000 );
						return false;
					}

					var _image = {file_path: image};
				}

				wpml._post({
						action: 'wpml_set_featured',
						wpml_check: wpml_ajax.utils.wpml_check,
						image: _image,
						title: $('#tmdb_data_title').val(),
						post_id: $('#post_ID').val(),
						tmdb_id: $('#tmdb_data_tmdb_id').val()
					},
					function( response ) {
						if ( response ) {
							wp.media.featuredImage.set( response );
						}
					},
					function() {
						$('#progress_status').text('Done!');
						window.setTimeout( wpml_posters.close(), 2000 );
					}
				);

			};

			/**
			 * Close the Modal
			 */
			wpml.media.posters.close = function() {
				$('#progressbar_bg, #progressbar').remove();
				if ( undefined != wpml_posters._frame )
					wpml_posters._frame.close();
			};


		wpml.media.init = function() {

			$('#tmdb_load_images').on( 'click', function( e ) {
				e.preventDefault();
				wpml_images.init();
				wpml_images._frame.$el.addClass('movie-images');
			});

			$('#postimagediv').on( 'click', '#tmdb_load_posters', function( e ) {
				e.preventDefault();
				wpml_posters.init();
				wpml_posters._frame.$el.addClass('movie-posters');
			});
		};

	wpml.media.init();
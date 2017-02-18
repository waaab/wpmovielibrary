
wpmoly = window.wpmoly || {};

var Grid = wpmoly.view.Grid = {};

_.extend( Grid, {

	Grid: wp.Backbone.View.extend({

		template: wp.template( 'wpmoly-grid' ),

		/**
		 * Initialize the View.
		 * 
		 * @since    3.0
		 * 
		 * @return   void
		 */
		initialize: function( options ) {

			this.controller = options.controller || {};

			this.render();

			this.set_regions();
		},

		/**
		 * Set subviews.
		 * 
		 * @since    3.0
		 * 
		 * @return   Returns itself to allow chaining.
		 */
		set_regions: function() {

			var settings = this.controller.settings,
			        mode = this.controller.settings.get( 'mode' );

			if ( settings.get( 'show_menu' ) ) {
				this.menu = new wpmoly.view.Grid.Menu({ controller: this.controller });
				this.views.set( '.grid-menu.settings-menu', this.menu );
			}

			if ( settings.get( 'show_pagination' ) ) {
				this.pagination = new wpmoly.view.Grid.Pagination({ controller: this.controller });
				this.views.set( '.grid-menu.pagination-menu', this.pagination );
			}

			if ( settings.get( 'order_control' ) ) {
				this.settings = new wpmoly.view.Grid.Settings({ controller: this.controller });
				this.views.set( '.grid-settings', this.settings );
			}

			if ( settings.get( 'customs_control' ) ) {
				this.customs = new wpmoly.view.Grid.Customs({ controller: this.controller });
				this.views.set( '.grid-customs', this.customs );
			}

			if ( 'grid' === mode ) {
				this.content = new wpmoly.view.Grid.NodesGrid({ controller: this.controller });
			} else if ( 'list' === mode ) {
				this.content = new wpmoly.view.Grid.NodesList({ controller: this.controller });
			} else if ( 'archives' === mode ) {
				this.content = new wpmoly.view.Grid.NodesArchives({ controller: this.controller });
			} else {
				this.content = new wpmoly.view.Grid.Nodes({ controller: this.controller });
			}

			this.views.set( '.grid-content', this.content );

			return this;
		},

		/**
		 * Set $el class names depending on settings.
		 * 
		 * @since    3.0
		 * 
		 * @return   Returns itself to allow chaining.
		 */
		setClassName: function() {

			var settings = this.controller.settings,
			   className = [ 'wpmoly', 'grid' ];

			className.push( settings.get( 'type' ) );
			className.push( settings.get( 'mode' ) );
			className.push( 'theme-' + settings.get( 'theme' ) );

			this.className = className.join( ' ' );

			this.$el.addClass( this.className );

			return this;
		},

		/**
		 * Render the view.
		 * 
		 * @since    3.0
		 * 
		 * @return   Returns itself to allow chaining.
		 */
		render: function() {

			this.setClassName();

			this.$el.html( this.template() );

			return this;
		}

	})

} );
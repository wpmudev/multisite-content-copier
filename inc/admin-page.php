<?php

/**
 * This class is the parent of all admin pages
 * It has a WordPress structure and has the possibility to add tabs
 * and more stuff
 */

if ( ! class_exists( 'Multisite_Content_Copier_Admin_Page' ) ) {
abstract class Multisite_Content_Copier_Admin_Page {

	// Tabs for the screen
	private $tabs;

	// Menu slug
	private $menu_slug;

	// Parent Admin page
	private $parent;

	// Menu title
	private $menu_title;

	// Page title
	private $page_title;

	// User capability needed
	private $capability;

	// Slug for the icon (needed for styling)
	private $screen_icon_slug;

	// Message displayed when the user has no permissions to see this page
	private $forbidden_message;

	// Page ID
	protected $page_id;

	/**
	 * Constructor
	 * 
	 * @param String $slug Slug of the page
	 * @param String $capability WP Capability needed to see the page
	 * @param Array $args Array of arguments
	 * 		tabs => Array of tabs [tab_slug] => Tab Name
	 * 		parent => Slug of the parent menu or false if is a main menu
	 * 		menu_title => Title of the Menu
	 * 		page_title => Title of the page
	 * 		screen_icon_slug => Allows applying styles to screen icons
	 * 		network_menu => Boolean. Determines if the page is a Network Menu or Blog Menu
	 * 		enqueue_scripts => Boolean. Determines if the page need some additional scripts.
	 * 			Then, a custom function needs to be added to the subclass
	 * 		enqueue_scripts => Boolean. Determines if the page need some additional styles.
	 * 			Then, a custom function needs to be added to the subclass
	 * 		forbidden_message => String. Message displayed when the user has no permissions to see this page
	 */
	public function __construct( $slug, $capability = 'manage_options', $args = array() ) {

		// Default arguments
		$defaults = array(
			'tabs' => array(),
			'parent' => false,
			'menu_title' => 'Menu title',
			'page_title' => 'Page title',
			'screen_icon_slug' => '',
			'network_menu' => false,
			'enqueue_scripts' => false,
			'enqueue_styles' => false,
			'forbidden_message' => 'You do not have enough permissions to access to this page',
			'on_load' => array()
		);

		$args = wp_parse_args( $args, $defaults );
		extract( $args );


		if ( empty( $slug ) )
			return;

		$this->tabs = ! empty( $tabs ) ? $tabs : array();
		$this->menu_slug = $slug;
		$this->parent = $parent;
		$this->menu_title = $menu_title;
		$this->page_title = $page_title;
		$this->capability = $capability;
		$this->screen_icon_slug = $screen_icon_slug;
		$this->network_menu = $network_menu;
		$this->on_load = $on_load;

		// Network menu?
		if ( ! $network_menu )
			add_action( 'admin_menu', array( &$this, 'add_menu' ) );
		else
			add_action( 'network_admin_menu', array( &$this, 'add_menu' ) );

		// Need some scripts?
		if ( $enqueue_scripts )
			add_action( 'admin_enqueue_scripts', array( &$this, 'check_for_scripts' ) );

		// Need some styles?
		if ( $enqueue_styles )
			add_action( 'admin_enqueue_scripts', array( &$this, 'check_for_styles' ) );
	}

	/**
	 * If the admin page need scripts, this function
	 * will check if the method is declared in the child class
	 * and will call it or throw an exception
	 * 
	 * @param String $hook Hook of the page
	 */
	public function check_for_scripts( $hook ) {
		if ( $hook === $this->page_id ) {

			// Checking if enqueue_scripts method exists in he subclass
			try {
				if ( method_exists( $this, 'enqueue_scripts' ) )
					$this->enqueue_scripts();	
				else
					throw new Exception( 'You need to declare enqueue_scripts method in <i>' . get_class( $this ) . '</i> class', 1);
			} catch (Exception $e) {
				// There's no function declared in the subclass
				Multisite_Content_Copier_Errors_Handler::show_exception( $e->getMessage() );
			}
			
		}
	}

	/**
	 * If the admin page need styles, this function
	 * will check if the method is declared in the child class
	 * and will call it or throw an exception
	 */
	public function check_for_styles( $hook ) {
		if ( $hook === $this->page_id ) {

			// Checking if enqueue_styles method exists in he subclass
			try {
				if ( method_exists( $this, 'enqueue_styles' ) )
					$this->enqueue_styles();	
				else
					throw new Exception( 'You need to declare enqueue_styles method in <i>' . get_class( $this ) . '</i> class', 1);
					
			} catch ( Exception $e ) {
				// There's no function declared in the subclass
				Multisite_Content_Copier_Errors_Handler::show_exception( $e->getMessage() );
			}
			
		}
			
	}

	/**
	 * Add the menu to the WP Menu
	 */
	public function add_menu() {

		if ( ! empty( $this->parent ) ) {

			// For submenus
			$this->page_id = add_submenu_page( 
				$this->parent,
				$this->page_title, 
				$this->menu_title, 
				$this->capability, 
				$this->menu_slug, 
				array( &$this, 'render_page' )
			);	
		}
		else {
			// For main menus
			$this->page_id = add_menu_page( 
				$this->page_title, 
				$this->menu_title, 
				$this->capability, 
				$this->menu_slug, 
				array( &$this, 'render_page' ),
				'div'
			);
		}

		add_action( 'load-' . $this->page_id, array( $this, 'add_help_tabs' ) );

		if ( ! empty( $this->on_load ) ) {
			foreach( $this->on_load as $callback ) {
				add_action( 'load-' . $this->page_id, array( &$this, $callback ) );
			}
		}
	}

	public function add_help_tabs() {}


	/**
	 * Render the main wrap of the page.
	 * 
	 * This is common to all the pages.
	 * 
	 * @return type
	 */
	public function render_page() {

		if ( ! current_user_can( $this->get_capability() ) )
			wp_die( $this->forbidden_message );

		?>
			<div class="wrap">

				<?php $this->show_notice(); ?>
				
				<?php screen_icon( $this->screen_icon_slug ); ?>

				<?php if ( ! empty( $this->tabs ) ): ?>
					<?php $this->the_tabs(); ?>
				<?php else: ?>
					<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
				<?php endif; ?>

				<?php $this->render_content(); ?>

			</div>

		<?php
	}

	/**
	 * Show WP native tabs if the tabs argument is an array
	 */
	private function the_tabs() {
		$current_tab = $this->get_current_tab();

		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $this->tabs as $key => $name ): ?>
			<a href="?page=<?php echo $this->get_menu_slug(); ?>&tab=<?php echo $key; ?>" class="nav-tab <?php echo $current_tab == $key ? 'nav-tab-active' : ''; ?>"><?php echo $name; ?></a>
		<?php endforeach;
			
		echo '</h2>';
		
	}

	/**
	 * Render the content of the page
	 * 
	 * Need to be implemented in the subclass
	 */
	public abstract function render_content();

	/**
	 * Override this function if you need notice
	 * functionalities
	 */
	public function show_notice() {}

	/**
	 * Get the menu slug
	 * 
	 * @return String Menu Slug
	 */
	public function get_menu_slug() {
		return $this->menu_slug;
	}

	/**
	 * Get the menu Page ID
	 * 
	 * @return String WP Page ID
	 */
	public function get_page_id() {
		return $this->page_id;
	}

	/**
	 * Get the link to the menu
	 * 
	 * @return String URL to the Admin Page
	 */
	public function get_permalink() {
		return add_query_arg( 
			'page',
			$this->get_menu_slug(),
			is_multisite() ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' )
		);
	}

	/**
	 * Get the capability needed to see the page
	 * 
	 * @return String WP Capability
	 */
	public function get_capability() {
		return $this->capability;
	}

	/**
	 * Get the tabs array
	 * 
	 * @return Array Tabs for the page
	 */
	protected function get_tabs() {
		return $this->tabs;
	}

	/**
	 * Get the current tab selected (if the page have tabs)
	 * 
	 * Return the slug of the tab
	 * 
	 * @return String Tab Slug
	 */
	protected function get_current_tab() {
		$tabs = $this->get_tabs();
		if ( ! isset( $_GET['tab'] ) || ! array_key_exists( $_GET['tab'], $tabs ) ) {
			return key( $tabs );
		}

		return $_GET['tab'];
	}

	/**
	 * Want to render a WP native page? You can use this function
	 * Remember to set a table.form-table HTML tag before and after 
	 * 
	 * This function is useful when not using the WP Settings API.
	 * For example, Network Pages does not accept that API so you
	 * need to add fields manually. This function will save
	 * a loot of code.
	 * 
	 * @param String $title Title of the row
	 * @param string/Array $callback Method that will render the markup
	 */
	protected function render_row( $title, $callback ) {
		?>
			<tr valign="top">
				<th scope="row"><label for="site_name"><?php echo $title; ?></label></th>
				<td>
					<?php 
						if ( is_array( $callback ) ) {
							if ( ! is_object( $callback[0] ) || ( is_object( $callback[0] ) && ! method_exists( $callback[0], $callback[1] ) ) ) {
								echo '';
							}
							else {
								call_user_func( $callback );
							}
						}
						else {
							call_user_func( $callback );
						}
					?>
				</td>
			</tr>
		<?php
	}
}
}
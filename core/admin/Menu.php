<?php
namespace Eventin\Admin;

use Eventin\Interfaces\HookableInterface;

class Menu implements HookableInterface {
    /**
	 * Menu page title.
	 *
	 * @var string
	 */
	protected $page_title;

	/**
	 * Menu page title.
	 *
	 * @var string
	 */
	protected $menu_title;

	/**
	 * Menu page base capability.
	 *
	 * @var string
	 */
	protected $base_capability;

	/**
	 * Menu page base capability.
	 *
	 * @var string
	 */
	protected $capability;

	/**
	 * Menu page slug.
	 *
	 * @var string
	 */
	protected $menu_slug;

	/**
	 * Menu page icon url.
	 *
	 * @var string
	 */
	protected $icon;

	/**
	 * Menu page position.
	 *
	 * @var int
	 */
	protected $position;

	/**
	 * Submenu pages.
	 *
	 * @var array
	 */
	protected $submenus;

    /**
     * Initialize function
     *
     * @return  void
     */
    public function __construct() {
        $this->page_title      = __( 'Eventin', 'eventin' );
		$this->menu_title      = __( 'Eventin', 'eventin' );
		$this->base_capability = 'read';
		$this->capability      = 'manage_options';
		$this->menu_slug       = 'eventin';
		$this->icon            = $this->get_eventin_menu_icon();
		$this->position        = 10;
		$this->submenus        = [
			[
				'title'      => __( 'Events', 'eventin' ),
				'capability' => $this->base_capability,
				'url'        => 'admin.php?page=' . $this->menu_slug . '#/events',
                'position'   => 2,
			],
			[
				'title'      => __( 'Organizers', 'eventin' ),
				'capability' => $this->base_capability,
				'url'        => 'admin.php?page=' . $this->menu_slug . '#/speakers',
                'position'   => 3,
			],
            [
				'title'      => __( 'Schedules', 'eventin' ),
				'capability' => $this->base_capability,
				'url'        => 'admin.php?page=' . $this->menu_slug . '#/schedules',
                'position'   => 4,
			],
            [
				'title'      => __( 'Bookings', 'eventin' ),
				'capability' => $this->base_capability,
				'url'        => 'admin.php?page=' . $this->menu_slug . '#/purchase-report',
                'position'   => 5,
			],
            [
				'title'      => __( 'Settings', 'eventin' ),
				'capability' => $this->base_capability,
				'url'        => 'admin.php?page=' . $this->menu_slug . '#/settings',
                'position'   => 7,
			],
		];

		$is_attendee_registation = etn_get_option( 'attendee_registration' );
		
		if ( 'on' === $is_attendee_registation ) {
			$this->submenus[] = [
				'title'      => __( 'Attendees', 'eventin' ),
				'capability' => $this->base_capability,
				'url'        => 'admin.php?page=' . $this->menu_slug . '#/attendees',
                'position'   => 5,
			];
		}

		if ( ! class_exists( 'Wpeventin_Pro' ) ) {
			$this->submenus[] = [
				'title'      => __( 'Go Pro', 'eventin' ),
				'capability' => $this->base_capability,
				'url'        => 'https://themewinter.com/eventin/',
                'position'   => 999999,
			];
		}
    }

    /**
     * Register service
     *
     * @return  void
     */
    public function register_hooks(): void {
        add_action( 'admin_menu', [$this, 'register_menu'] );
		add_action( 'admin_head', [ $this, 'highlight_submenu' ] );

    }

    /**
     * Register menu
     *
     * @return  void
     */
    public function register_menu() {
        global $submenu;

		add_menu_page(
			$this->page_title,
			$this->menu_title,
			$this->base_capability,
			$this->menu_slug,
			[$this, 'render_menu_page'],
			$this->icon,
			$this->position,
		);

        $this->submenus = apply_filters( 'eventin_menu', $this->submenus );


		usort( $this->submenus, function($a, $b) {
			return $a['position'] <=> $b['position'];
		} );

		foreach ( $this->submenus as $item ) {
			$submenu[ $this->menu_slug ][] = [ $item['title'], $item['capability'], $item['url'] ]; // phpcs:ignore
		}
    }

	/**
	 * Render menu page
	 *
	 * @return  void
	 */
	public function render_menu_page() {

		// Eventin Version four Script and Styles 
		// wp_enqueue_script( 'etn-dashboard' );
		wp_enqueue_style( 'etn-dashboard' );


		// Block editor styles and scripts 
		\do_action('enqueue_block_assets');
		$settings = etn_editor_settings();
        wp_add_inline_script( 'etn-dashboard', 'window.eventinEditorSettings = ' . wp_json_encode( $settings ) . ';' );
		wp_enqueue_script('wp-edit-post');

		
		//experimental enqueue by Sajib
		wp_enqueue_script('etn-dashboard' , plugins_url('build/js/dashboard.js', __FILE__), array('wp-edit-post'), \Wpeventin::version(), true);
    	
		/**
		 * @method wp_set_script_translations
		 * It helps to load the translation file for the script
		 */ 
		wp_set_script_translations( 'etn-dashboard', 'eventin' );

		wp_localize_script('etn-dashboard' , 'eventinData', array(
        'publicPath' => plugins_url('../../build/', __FILE__),
    	));

	
		
		$versionFourView = \Wpeventin::plugin_dir() . "core/admin-view/dashboard.php";
		include $versionFourView;
	}

    /**
     * Get eventin main menu icon
     *
     * @return  string
     */
    protected function get_eventin_menu_icon() {
        return "data:image/svg+xml;base64,PHN2ZyBzdHlsZT0icGFkZGluLXRvcDogNnB4IiB3aWR0aD0iMjAiIGhlaWdodD0iMjIiIHZpZXdCb3g9IjAgMCAyNiA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTI1LjExMyAxOS4yNjA0TDE3LjU2OTcgMjYuODAyMkwxMi43MDY3IDMxLjY2NTJMMTAuMzI0IDI5LjI4MjVMNy44ODU3MyAyNi44NDVDNi43NTkyNyAyNS43MTgzIDYuMDYyNzkgMjQuMjMyNyA1LjkxNzEyIDIyLjY0NjFDNS43NzE0NSAyMS4wNTk1IDYuMTg1NzcgMTkuNDcyIDcuMDg4MjEgMTguMTU5QzcuOTkwNjUgMTYuODQ1OSA5LjMyNDI3IDE1Ljg5MDIgMTAuODU3NyAxNS40NTc3QzEyLjM5MTEgMTUuMDI1MSAxNC4wMjc2IDE1LjE0MyAxNS40ODMyIDE1Ljc5MDlMMTIuNjgzNCAxOC41OTA3QzEyLjEzNjEgMTkuMTM3OSAxMS43MDIgMTkuNzg3NSAxMS40MDU4IDIwLjUwMjVDMTEuMTA5NiAyMS4yMTc1IDEwLjk1NzIgMjEuOTgzOCAxMC45NTcyIDIyLjc1NzdDMTAuOTU3MiAyMy41MzE2IDExLjEwOTYgMjQuMjk3OSAxMS40MDU4IDI1LjAxMjlDMTEuNzAyIDI1LjcyNzggMTIuMTM2MSAyNi4zNzc1IDEyLjY4MzQgMjYuOTI0N0wxOS4zMDY3IDIwLjMwMTRMMjMuODAwNiAxNS44MDY2QzIzLjIzMiAxNC43ODk0IDIyLjUyNSAxMy44NTYxIDIxLjY5OTkgMTMuMDMzMUMyMS4xMTk3IDEyLjQ1MjMgMjAuNDg0OSAxMS45Mjg4IDE5LjgwNDMgMTEuNDY5OEMxOC45Mzk0IDEwLjg4NTggMTguMDA1MiAxMC40MTE5IDE3LjAyMzIgMTAuMDU5QzE1LjgxMjIgMTEuMDY3MiAxNC4yODYyIDExLjYxOTMgMTIuNzEwNCAxMS42MTkzQzExLjEzNDYgMTEuNjE5MyA5LjYwODYxIDExLjA2NzIgOC4zOTc1OSAxMC4wNTlDNi42MzcyOCAxMC42OTMyIDUuMDM5IDExLjcwODggMy43MTcyMSAxMy4wMzMxQy0wLjY0ODIyNyAxNy4zOTg2IC0xLjE2ODM1IDI0LjE3NDUgMi4xNTUzNCAyOS4xMTc5QzIuNjEzNjMgMjkuNzk4MiAzLjEzNjcgMzAuNDMyNSAzLjcxNzIxIDMxLjAxMkw2LjE1NTQ5IDMzLjQ0NzNMMTIuNzA2NyA0MEwyMS42OTY4IDMxLjAxMkMyMy4yMDkgMjkuNDk4OCAyNC4zMTQ4IDI3LjYyODUgMjQuOTExOSAyNS41NzQzQzI1LjUwOTEgMjMuNTIwMSAyNS41NzgyIDIxLjM0ODQgMjUuMTEzIDE5LjI2MDRaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTIuNzA2IDkuNzI0NTNDMTUuMzkxNCA5LjcyNDUzIDE3LjU2ODMgNy41NDc2MiAxNy41NjgzIDQuODYyMjdDMTcuNTY4MyAyLjE3NjkxIDE1LjM5MTQgMCAxMi43MDYgMEMxMC4wMjA3IDAgNy44NDM3NSAyLjE3NjkxIDcuODQzNzUgNC44NjIyN0M3Ljg0Mzc1IDcuNTQ3NjIgMTAuMDIwNyA5LjcyNDUzIDEyLjcwNiA5LjcyNDUzWiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+Cg== ";
    }

	/**
	 * Submenu high light
	 *
	 * @return  void
	 */
	public function highlight_submenu() {
		global $parent_file, $submenu_file, $pagenow;

		$post_types = [
			'etn-attendee',
			'etn-speaker'
		];

		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : '';

		if ( $pagenow == 'post-new.php' && in_array( $post_type, $post_types ) ) {
			$parent_file  = 'eventin'; // Parent menu slug
			$submenu_file = 'edit.php?post_type=' . $post_type; // Submenu slug
		}

		// Ensure the parent menu is highlighted on the main Attendee page as well
		if ( $pagenow == 'edit.php' && in_array( $post_type, $post_types ) ) {
			$parent_file  = 'eventin';
			$submenu_file = 'edit.php?post_type=' . $post_type;
		}

	}
}
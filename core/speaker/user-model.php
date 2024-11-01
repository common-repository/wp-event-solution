<?php
/**
 * Speaker Model Class
 *
 * @package Eventin
 */
namespace Etn\Core\Speaker;
use Etn\Traits\Singleton;

/**
 * User Model
 */
class User_Model {
    use Singleton;
    /**
     * Store speaker post type
     *
     * @var string
     */
    protected $role = 'etn-speaker';

    /**
     * Store meta prefix
     *
     * @var string
     */
    protected $meta_prefix = 'etn_speaker_';

    /**
     * Store speaker id
     *
     * @var integer
     */
    public $id;

    /**
     * Store speaker data
     *
     * @var array
     */
    protected $data = [
        'title'             => '',
        'designation'       => '',
        'email'             => '',
        'website_email'     => '',
        'summery'           => '',
        'social'            => [],
        'company_logo'      => '',
        'company_url'       => '',
        'image'             => '',
        'image_id'          => '',
        'user_login'        => '',
        'user_pass'         => '',
        'company_logo_id'   => '',
        'category'          => [],
        'company_name'      => '',
        'speaker_group'     => [],
        'date'              => '',
        'clone'             => false
    ];

    /**
     * Speaker Constructor
     *
     * @return void
     */
    public function __construct( $speaker = 0 ) {
        if ( $speaker instanceof self ) {
            $this->set_id( $speaker->get_id() );
        } elseif ( ! empty( $speaker->ID ) ) {
            $this->set_id( $speaker->ID );
        } elseif ( is_numeric( $speaker ) && $speaker > 0 ) {
            $this->set_id( $speaker );
        }
    }

    /**
     * Get user id
     *
     * @return  integer
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get speaker title
     *
     * @return string
     */
    public function get_speaker_title() {
        $value = get_userdata( $this->id )->display_name ;
        return $value ? esc_html( $value ) : '';
        
    }        
    
    
    /**
     * Get speaker email
     *
     * @return string
     */
    public function get_speaker_email() {
        $value = get_userdata( $this->id )->user_email ;
        return $value ? esc_attr( $value ) : '';
        
    }    
    
    /**
     * Get speaker designation
     *
     * @return string
     */
    public function get_speaker_designation() {
        $value = $this->get_prop( 'designation' ) ;
        return $value ? wp_kses( $value, 'post' ) : '';
    }    
    
    /**
     * Get speaker designation
     *
     * @return string
     */
    public function get_speaker_website_email() {
        $value = $this->get_prop( 'website_email' ) ;
        return $value ? esc_html( $value ) : '';
    }    
    
    /**
     * Get speaker summary
     *
     * @return string
     */
    public function get_speaker_summary() {
        $summary =  $this->get_prop( 'summery' ) ;
        if( ! $summary ) {
            $summary =  $this->get_prop( 'summary' ) ;
        }
        return $summary ? wp_kses( $summary, 'post' ) : '';
    }       
    
    /**
     * Get speaker group
     *
     * @return string
     */
    public function get_speaker_group() {
        $value = get_user_meta($this->id, 'etn_speaker_group', true );

        $group = maybe_unserialize( $value );
        return $group;
    }         
    
    /**
     * Get speaker name
     *
     * @return string
     */
    public function get_company_name() {
        $value = get_user_meta($this->id, 'etn_company_name', true );
        return $value ? esc_attr( $value ) : '';
    }

    /**
     * Get created date
     *
     * @return string
     */
    public function get_date() {
        $date = get_user_meta($this->id, 'date', true );
        return isset( $date ) ? $date : '';
    }         
    
    /**
     * Get created date
     *
     * @return string
     */
    public function get_author_url() {
        $user       = get_userdata( $this->id ); 
        $author_url = get_author_posts_url( $user->ID, $user->user_nicename );
        return esc_url($author_url);
    }        
    
    /**
     * Get speaker category
     *
     * @return string
     */
    public function get_speaker_category() {
        $categories = $this->get_prop( 'category' ) ;
        $categories = maybe_unserialize( $categories );
        $group = [];
        if ( is_array($categories) ) {
            foreach($categories as $category) {
                $group[] = $category;
            }    
        }
        return $group;
    }    
    
    /**
     * Get speaker socials
     *
     * @return string
     */
    public function get_speaker_socials() {
        $social = $this->get_prop( 'social' ) ;        
        $social = maybe_unserialize( $social );
        return $social ? $social : [];
    }

    /**
     * Get speaker url
     *
     * @return string
     */
    public function get_speaker_url() {
       $value = $this->get_prop( 'url' );
        return $value ? esc_url( $value ) : '';
    }    
    
    
    /**
     * Get speaker company logo
     *
     * @return string
     */
    public function get_speaker_company_logo() {
        $value =  $this->get_prop( 'company_logo' );
        if( is_numeric ( $value )){
            $logo =  wp_get_attachment_image_src( $value );
            return isset( $logo[0] ) ? $logo[0] : 0;
        }else{
            return $value ? esc_url( $value ) : '';
        }

    }

	/**
     * Get password
     *
     * @return  string
     */
    public function get_password() {
        $user = get_user_by( 'id', $this->id );
        return $user->user_pass;
    }

    /**
     * Get speaker display name
     *
     * @return  string
     */
    public function get_display_name() {
        $user         = get_userdata( $this->id );
        $display_name = '';

        if ( $user ) {
            $display_name = $user->display_name;
        }

        return $display_name;
    }

    /**
     * Get image
     *
     * @return  string
     */
    public function get_image() {

        $image = get_user_meta( $this->id, 'image', true );
        if ( ! $image ) {
            return '';
        }
        if ( filter_var( $image, FILTER_VALIDATE_URL ) ) {
            return $image;
        }

    }    
    
    /**
     * Get image ID
     *
     * @return  string
     */
    public function get_image_id() {
        $image_id = get_user_meta( $this->id, 'image_id', true );

        if ( ! $image_id ) {
            return '';
        }

        return intval ( $image_id );
    }    
    
    /**
     * Get company logo id
     *
     * @return  string
     */
    public function get_company_logo_id() {
        $image_id = get_user_meta($this->id, 'etn_company_logo_id', true);

        if ( ! $image_id ) {
            return '';
        }

        return intval ( $image_id );
    }

    /**
     * Get user name
     *
     * @return  string
     */
    public function get_user_name() {
        return get_userdata($this->id)->user_login;
    }    
    
    

    /**
     * Get full name;
     *
     * @return string
     */
    public function get_full_name() {
        $first_name = $this->get_first_name();
        $last_name = $this->get_last_name();

        if ( ! $first_name ) {
            return $this->get_display_name();
        }

        if ( ! $last_name ) {
            return $first_name;
        }

        return $first_name . ' ' . $last_name;
    }

    /**
     * Get user status
     *
     * @return  mixed
     */
    public function get_status() {
        $user = get_userdata( $this->id );

        if ( user_can( $user, 'manage_options' ) ) {
            return 1;
        }

        return $this->get_prop( 'status' ) ? $this->get_prop( 'status' ) : '';
    }

    /**
     * Get all data for a speaker
     *
     * @return array
     */
    public function get_data( $id ) {
        $this->set_id( $id );
        
        return [
            'id'                => $id,
            'name'              => $this->get_speaker_title(),
            'email'             => $this->get_speaker_email(),           
            'designation'       => $this->get_speaker_designation(),
            'summary'           => $this->get_speaker_summary(),
            'social'            => $this->get_speaker_socials(),
            'company_logo'      => $this->get_speaker_company_logo(),
            'company_logo_id'   => $this->get_company_logo_id(),
            'company_url'       => $this->get_speaker_url(),
            'image'             => $this->get_image(),
            'image_id'          => $this->get_image_id(),
            'category'          => $this->get_speaker_category(),
            'speaker_group'     => $this->get_speaker_group(),
            'company_name'      => $this->get_company_name(),
            'author_url'        => $this->get_author_url(),
            'date'              => $this->get_date()
        ];

    }    
    
    
    /**
     * Get clone data for a speaker
     *
     * @return array
     */
    public function clone_data( $id ) {
        $this->set_id( $id );
        
        $data = [
            'etn_speaker_title'         => $this->get_speaker_title(). ' Clone',
            'user_login'                => strtolower( get_userdata( $id )->user_login ).'-'.bin2hex(random_bytes(1)),
            'etn_speaker_website_email' => $this->get_speaker_email(). '-' . bin2hex(random_bytes(3)),          
            'etn_speaker_designation'   => $this->get_speaker_designation(),
            'etn_speaker_summery'       => $this->get_speaker_summary(),
            'etn_speaker_social'        => $this->get_speaker_socials(),
            'etn_speaker_company_logo'  => $this->get_speaker_company_logo(),
            'etn_company_logo_id'       => $this->get_company_logo_id(),
            'etn_speaker_url'           => $this->get_speaker_url(),
            'image'                     => $this->get_image(),
            'image_id'                  => $this->get_image_id(),
            'etn_speaker_category'      => $this->get_speaker_category(),
            'etn_speaker_group'         => $this->get_speaker_group(),
            'etn_company_name'          => $this->get_company_name(),
            'date'                      => $this->get_date(),
            'clone'                     => true,
        ];

        $this->set_id( 0 );
        return $data ;
    }

    /**
     * Get appointment data
     *
     * @param   string  $prop
     *
     * @return  mixed
     */
    private function get_prop( $prop = '', $default = false ) {
        $data = $this->get_metadata( $prop );

        if ( ! $data ) {
            return $default;
        }

        return $data;
    }

    /**
     * Get metadata
     *
     * @param   string  $prop
     *
     * @return  mixed
     */
    private function get_metadata( $prop = '' ) {
        $meta_key = $this->meta_prefix . $prop;

        return get_user_meta( $this->id, $meta_key, true );
    }

    /**
     * Set id
     *
     * @param   integer  $id  User ID
     *
     * @return void
     */
    public function set_id( $id ) {
        $this->id = $id;
    }

    /**
     * Set props
     *
     * @param   array  $args  User Data
     *
     * @return  void
     */
    public function set_props( $args = [] ) {
        $this->data = wp_parse_args( $args, $this->data );
    }

    /**
     * Create Speaker
     *
     * @param   array $args Speaker data
     *
     * @return integer | WP_Error
     */
    public function create( $args = [] ) {

        $this->data['user_pass'] = wp_generate_password( );

        $args       = wp_parse_args( $args, $this->data );
        $email      = ! empty( $args['etn_speaker_website_email'] ) ? $args['etn_speaker_website_email'] : '';

        $user     = get_user_by( 'email', $email );
        $is_clone = ! empty( $args['clone'] ) ? $args['clone'] : false;
    
        // If the user exists and it's not a clone, return an error
        if ( $user && ! $is_clone ) {
            return new \WP_Error( 'email_exists', 'A user with this email already exists.' );
        }

        if (!empty($args['etn_speaker_title'])) {
            $username = $this->generate_unique_username($args['etn_speaker_title']);
            $args['user_login'] = $username;
            $args['first_name'] = $args['etn_speaker_title'];
            $args['display_name'] = $args['etn_speaker_title'];
        }

        $args['user_email'] = $email;
        $additional_roles = [];

        if ( is_array( $args['etn_speaker_category'] ) && !empty( $args['etn_speaker_category'] ) ) {

            $filtered_categories = array_filter( $args['etn_speaker_category'], function( $category ) {
                return in_array( strtolower( $category ), ['speaker', 'organizer'] );
            });

               // If the filtered array is not empty, proceed with the foreach loop
            if ( ! empty( $filtered_categories ) ) {
                foreach ( $filtered_categories as $speaker_category ) {
                    $additional_roles[] = 'etn-' . strtolower( $speaker_category );
                }
                $args['role'] = array_shift( $additional_roles );
            }
        }else if ( $args['category'] ) {
            $args['role'] = 'etn-'.strtolower( $args['category'] );
        }

        $user_id = wp_insert_user( $args );

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return new \WP_Error('user_not_found', 'The user data could not be retrieved.');
        }

        // Add any additional roles.
        foreach ( $additional_roles as $role ) {
            if ( ! in_array($role, $user->roles ) ) {
                $user->add_role($role);
            }
        }

        $this->set_id( $user_id );
        $this->save_metadata( $args );
        $this->retrieve_password();

        return $user_id;
    }

    /**
     * Generate a unique username from a given title.
     * 
     * Replace spaces with dashes and sanitize the username.
     * Check if the username already exists and append a number if it does.
     * 
     * @param string $title The title to be used for generating the username.
     * 
     * @return string The generated username.
     */
    private function generate_unique_username($title) {
        $original_username = strtolower(str_replace(' ', '-', sanitize_user($title)));
        $username = $original_username;
        $counter = 1;
    
        // Check if the username already exists and append a number if it does
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
    
        return $username;
    }

    /**
     * Update speaker data
     *
     * @return  void
     */
    public function update( $args = [] ) {
        $user = get_userdata($this->id);
    
        if ( !$user ) {
            return new \WP_Error('user_not_found', 'The user data could not be retrieved.');
        }
    
        $email = !empty($args['etn_speaker_website_email']) ? $args['etn_speaker_website_email'] : '';
    
        $user_data = $user->to_array();
    
        if ( !empty($args['user_pass'] ) ) {
            $user_data['user_pass'] = $args['user_pass'];
        }        
        
        if ( !empty($args['etn_speaker_title'] ) ) {
            $user_data['first_name']    = $args['etn_speaker_title'];
            $user_data['display_name']  = $args['etn_speaker_title'];
        }
    
        if ( $email ) {
            $user_data['user_email'] = $email;
        }

        // Only update email if it has changed and is different from the current one
        if ( !empty($args['email']) && $args['email'] != $user->user_email ) {
            $user_data['user_email'] = $args['email'];
        }
    
        // Check if the current roles need to be updated
        if ( !empty($args['etn_speaker_category'] ) ) {
            foreach ( $user->roles as $role ) {
                if ( ! in_array($role, $args['etn_speaker_category'] ) ) {
                    $user->remove_role($role);
                }
            }
        }
    
        if ( is_array($args['etn_speaker_category']) && !empty($args['etn_speaker_category'] ) ) {
            $additional_roles = [];
            foreach ( $args['etn_speaker_category'] as $speaker_category ) {
                $role = 'etn-' . strtolower($speaker_category);
                $additional_roles[] = $role;
                if (!in_array($role, $user->roles)) {
                    $user->add_role($role);
                }
            }
        } else if ( !empty($args['etn_speaker_category'] ) ) {
            $role = 'etn-' . strtolower($args['etn_speaker_category']);
            if (!in_array($role, $user->roles)) {
                $user->add_role($role);
            }
        }
    
        $user_id = wp_update_user( $user_data );
    
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }
    
        $this->set_id( $user_id );
        $this->save_metadata( $args );
        $this->retrieve_password();
    
        return $user_id;
    }
    

    /**
     * Update meta data
     *
     * @return  void
     */
    public function save_metadata( $data = [] ) {
        foreach ( $data as $key => $value ) {

            // Update speaker meta data.
            update_user_meta( $this->id, $key, $value );
        }
    }

    /**
     * Send password reset email
     *
     * @return void
     */
    public function retrieve_password() {
        if ( 0 === intval( $this->get_status() ) ) {
            retrieve_password( $this->get_speaker_website_email() );
        }
    }

    /**
     * Delete speaker
     *
     * @return  bool
     */
    public function delete() {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        $user = get_userdata( $this->id );

        if ( ! $user ) {
            return new \WP_Error('user_not_found', 'The user data could not be retrieved.');
        }

        if ( $this->has_other_roles( $user->ID ) ) {
            $user->remove_role('etn-speaker'); 
            $user->remove_role('etn-organizer');
            
            return true;
        }

         // Get the super admin user ID
        $super_admin_id = $this->get_super_admin_id();

        if (is_multisite()) {
            require_once ABSPATH . 'wp-admin/includes/ms.php';
            return wpmu_delete_user($this->id, $super_admin_id);
        }

        return wp_delete_user($this->id, $super_admin_id);
    }

     /* Get the ID of the super admin user.
     *
     * If the user is on a multisite, this will return the ID of the super admin
     * user for the entire network. If the user is on a single site, this will
     * return the ID of the user with the 'administrator' role.
     *
     * If no super admin user is found, this will return 1 (the default user ID).
     *
     * @return int The ID of the super admin user.
     */
    private function get_super_admin_id() {
        if (is_multisite()) {
            $super_admins = get_super_admins();
            if (!empty($super_admins)) {
                $super_admin = get_user_by('login', $super_admins[0]);
                if ($super_admin) {
                    return $super_admin->ID;
                }
            }
        } else {
            $super_admin = get_user_by('role', 'administrator');
            if ($super_admin) {
                return $super_admin->ID;
            }
        }
    
        return 1; // Default to user ID 1 if no super admin found
    }

    /**
     * Check organizer is default WordPress user
     *
     * @param   integer  $user_id  Organizer id
     *
     * @return  bool
     */
    protected function has_other_roles( $user_id) {
        // Get the user object
        $user = get_userdata( $user_id );
        
        // Roles.
        $roles = [
            'administrator',
            'editor',
            'contributor',
            'subscriber',
            'author'
        ];

        // Check if the user exists and has any of the roles
        if ( $user && !empty($user->roles) ) {
            foreach ( $roles as $role ) {
                if ( in_array( $role, $user->roles ) ) {
                    return true; // User has one of the roles
                }
            }
        }
        
        return false; // No matching roles found.
    }

    /**
     * Check user is valid speaker
     *
     * @return  bool
     */
    public function is_speaker() {
        $user = get_userdata( $this->id );

        if ( $user ) {
            return in_array( 'etn-speaker', $user->roles, true );
        }

        return false;
    }    
    
    /**
     * Check user is valid speaker
     *
     * @return  bool
     */
    public function is_organizer() {
        $user = get_userdata( $this->id );

        if ( $user ) {
            return in_array( 'etn-organizer', $user->roles, true );
        }

        return false;
    }

    /**
     * Get all events
     *
     * @param   array  $args  Appointments args
     *
     * @return  array
     */
    public static function all( $args = [] ) {
        $defaults = [
            'role'   => 'etn-speaker',
            'number' => 10,
            'paged'  => 1,
        ];

        $args = wp_parse_args( $args, $defaults );

        $users = new WP_User_Query( $args );

        return [
            'total' => $users->get_total(),
            'items' => $users->get_results(),
        ];
    }
}

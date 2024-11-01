<?php
/**
 * Customer model
 */
namespace Eventin\Customer;

use Eventin\Input;
use Exception;

/**
 * Customer Model
 */
class CustomerModel {
    /**
     * Store customer id
     *
     * @var integer
     */
    public $id;

    /**
     * Store first name
     *
     * @var string
     */
    public $first_name;

    /**
     * Store last name
     *
     * @var string
     */
    public $last_name;

    /**
     * Store email
     *
     * @var string
     */
    public $email;

    /**
     * Constructor for the customer model
     *
     * @param   mixed  $customer  Customer class
     *
     * @return  void
     */
    public function __construct( $customer = null ) {
        if ( $customer instanceof self ) {
            $this->id = $customer->id;
        } elseif ( ! empty( $customer->id ) ) {
            $this->id = $customer->id;
        } elseif ( is_numeric( $customer ) && $customer > 0 ) {
            $this->id = $customer;
        }
    }

    /**
     * Create customer
     *
     * @param   array  $data  Customer data
     *
     * @return CustomerModel | WP_Error
     */
    public static function create( $data = [] ) {
        $input = new Input( $data );
        $user_login = sanitize_title( $input->get( 'first_name' ) );

        if ( get_user_by('login', $user_login ) ) {
            $user_login = $user_login . substr(time(), 0, 3);
        }

        $static = new self();

        $user_data = [
            'first_name' => $input->get( 'first_name' ),
            'last_name'  => $input->get( 'last_name' ),
            'user_login' => $user_login,
            'user_email' => $input->get( 'email' ),
            'user_pass'  => wp_hash_password(time()),
            'role'       => 'etn-customer'
        ];
        
        $user = wp_insert_user( $user_data );

        if ( is_wp_error( $user ) ) {
            return $user;
        }

        $static->id = $user;

        return $static->get_customer_object( $user );
    }

    /**
     * Update customer
     *
     * @param   array  $data  Customer data
     *
     * @return  
     */
    public function update( $data = [] ) {
        $input    = new Input( $data );
        $password = $input->get( 'password' );
        $email    = $input->get( 'email' );

        $user_data = [
            'ID'         => $this->id,
            'first_name' => $input->get( 'first_name' ),
            'last_name'  => $input->get( 'last_name' ),
            'user_email' => $email,
        ];

        if ( $password ) {
            $user_data['user_pass'] = wp_hash_password( $password );
        }

        $existing_user = get_userdata( $this->id );

        if ( $existing_user && $existing_user->user_email === $email ) {
            unset( $user_data['user_email'] );
        }

        $user = wp_update_user( $user_data );

        if ( is_wp_error( $user ) ) {
            return $user;
        }

        return $this->get_customer_object( $this->id );
    }

    /**
     * Delete user
     *
     * @return bool
     */
    public function delete() {
        $user = get_userdata( $this->id );

        if ( $user ) {
            $user->remove_role('etn-customer');  
        }

        $roles = $user->roles;

        if ( ! $roles ) {
            return wp_delete_user( $this->id );
        }

        return true;
    }

    /**
     * Assign role for a customer
     *
     * @param   array  $roles  [$roles description]
     *
     * @return  void
     */
    public function assign_role( $uesr_id = 0, $roles = [] ) {
        if ( ! $uesr_id ) {
            $uesr_id = $this->id;
        }

        $user = get_userdata( $uesr_id );

        if ( ! $user ) {
            return;
        }

        if ( $roles ) {
            foreach( $roles as $role ) {
                $user->add_role( $role );
            }
        }
    }

    /**
     * Get all customer
     *
     * @return  array
     */
    public static function all() {
        $args = [
            'role'    => 'etn-customer',
            'orderby' => 'user_login',
            'order'   => 'ASC',  
        ];

        $static = new self();

        $users = get_users( $args );

        $customers = [];

        if ( $users ) {
            foreach( $users as $user ) {
                $customers[] = $static->get_customer_object( $user->ID );
            }
        }

        return $customers;
    }

    /**
     * Find customer
     *
     * @param   interger  $id  Customer id
     *
     * @return  CustomerModel
     */
    public static function find( $id ) {
        $user   = get_userdata( $id );

        if ( ! $user ) {
            return null; 
        }

        $static = new self();

        $roles = $user->roles;

        if ( ! in_array( 'etn-customer', $roles ) ) {
            return null;
        }

        return $static->get_customer_object( $id );
    }

    /**
     * Get customer object
     *
     * @param   integer  $user_id  [$user_id description]
     *
     * @return  CustomerModel
     */
    private function get_customer_object( $user_id ) {
        $user_data  = get_userdata( $user_id );
        $customer   = new CustomerModel( $user_id );

        $customer->first_name = get_user_meta( $user_id, 'first_name', true );
        $customer->last_name  = get_user_meta( $user_id, 'last_name', true );
        $customer->email      = $user_data->user_email;

        return $customer;
    }
}
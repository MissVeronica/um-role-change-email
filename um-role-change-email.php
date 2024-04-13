<?php
/**
 * Plugin Name:     Ultimate Member - Role Change Email
 * Description:     Extension to Ultimate Member for sending Role Change Email.
 * Version:         1.2.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.8.5
 */

if ( ! defined( 'ABSPATH' ) ) exit; 
if ( ! class_exists( 'UM' ) ) return;

Class UM_Role_Change_Email {

    public $email_key = 'role_change_email';

    function __construct() {

        add_filter( 'um_email_notifications',                 array( $this, 'custom_email_notification_role_change' ), 90, 1 );
        add_action( 'um_extend_admin_menu',                   array( $this, 'copy_email_notification_role_change_email' ), 10 );
        add_action( 'set_user_role',                          array( $this, 'custom_wp_role_change_email' ), 10, 3 );
        add_filter( 'um_set_user_role',                       array( $this, 'custom_um_role_change_email' ), 10, 3 );
        add_filter( 'um_admin_settings_email_section_fields', array( $this, 'settings_email_section_role_change_email' ), 9, 2 );

        define( 'Role_Change_Email_Path', plugin_dir_path( __FILE__ ) );
    }

    public function copy_email_notification_role_change_email() {

        $located = UM()->mail()->locate_template( $this->email_key . '.php' );

        if ( ! is_file( $located ) || filesize( $located ) == 0 ) {
            $located = wp_normalize_path( STYLESHEETPATH . '/ultimate-member/email/' . $this->email_key . '.php' );
        }

        clearstatcache();
        if ( ! file_exists( $located ) || filesize( $located ) == 0 ) {

            wp_mkdir_p( dirname( $located ) );

            $template = Role_Change_Email_Path . $this->email_key . '.php';

            if ( file_exists( $template )) {
                $template_source = file_get_contents( $template );
                file_put_contents( $located, $template_source );

                if ( ! file_exists( $located ) ) {
                    file_put_contents( um_path . 'templates/email/' . $this->email_key . '.php', $template_source );
                }
            }
        }
    }

    public function custom_wp_role_change_email( $user_id, $role, $old_roles ) {

        $this->send_role_change_email( $user_id, $role );
    }

    public function custom_um_role_change_email( $new_role, $user_id, $user ) {

        $this->send_role_change_email( $user_id, $new_role );
        return $new_role;
    }

    public function custom_email_notification_role_change( $um_emails ) {

        $um_emails['role_change_email'] = array(
                                                'key'            => $this->email_key,
                                                'title'          => __( 'Account Role is changed email', 'ultimate-member' ),
                                                'subject'        => 'Role Change {site_name}',
                                                'body'           => '',
                                                'description'    => __( 'To send the user an email when the user role is changed', 'ultimate-member' ),
                                                'recipient'      => 'user',
                                                'default_active' => true, 
                                            );

        $email_option_on  = UM()->options()->get( $this->email_key . '_on' );

        if ( '' === $email_option_on ) {
            $email_on = empty( $um_emails['role_change_email']['default_active'] ) ? 0 : 1;
            UM()->options()->update( $this->email_key . '_on', $email_on );
            UM()->options()->update( $this->email_key . '_sub', 'Role Change {site_name}' );
        }

/*
        if ( ! array_key_exists( $this->email_key . 'role_change_email_on', UM()->options()->options ) ) {

            UM()->options()->options = array_merge( array(
                            $this->email_key . '_on'  =>  empty( $um_emails['role_change_email']['default_active'] ) ? 0 : 1,
                            $this->email_key . '_sub' => 'Role Change {site_name}', ),
                            UM()->options()->options,
                    );

        }
*/

        return $um_emails;
    }

    public function send_role_change_email( $user_id, $role ) {

        $notification_roles = UM()->options()->get( $this->email_key . '_new_roles' );

        if ( ! empty( $notification_roles )) {
            $notification_roles = array_map( 'sanitize_text_field', $notification_roles );

            if ( in_array( $role, $notification_roles )) {

                $all_roles = UM()->roles()->get_roles();

                $args['tags']         = array( '{role}' );
                $args['tags_replace'] = array( $all_roles[$role] );

                um_fetch_user( $user_id );
                UM()->mail()->send( um_user( 'user_email' ), $this->email_key, $args );
            }
        }
    }

    public function settings_email_section_role_change_email( $section_fields, $email_key ) {

        if ( $email_key == $this->email_key ) {

            $section_fields[] = array(
                            'id'            => $this->email_key . '_new_roles',
                            'type'          => 'select',
                            'multi'         => true,
                            'size'          => 'medium',
                            'options'       => UM()->roles()->get_roles(),
                            'label'         => __( 'Select User Roles for Notification emails', 'ultimate-member' ),
                            'description'   => __( 'Select single or multiple User Roles for Notification emails.', 'ultimate-member' ),
                            'conditional'   => array( $email_key . '_on', '=', 1 ),
                        );
        }

        return $section_fields;
    }

}

new UM_Role_Change_Email();



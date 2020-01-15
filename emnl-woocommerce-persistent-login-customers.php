<?php 
/**
 * Plugin Name: Woocommerce Persistent Login for Customers
 * Description: This plugin never logs out Woocommerce customers, which is WAY more customer-friendly. It hides the 'remember me' option and removes its functionality. The plugin does its magic by regularly replacing the native WP session cookies to a date far in the future. A custom cookie 'wordpress_logged_in_forever' is placed, which expires every 24 hours. The plugin renews the native WP session cookie as soon as this custom cookie has expired. This method works as a great fix to improve performance and prevent re-setting the native WP sessions cookies at EVERY page load, leading to strange results like: users who can't log out anymore (because the native WP cookie is set again during logout).
 * Author: Erik Molenaar
 * Author URI: https://erikmolenaar.nl
 * Version: 1.1
 */


// Exit if accessed directly
if ( ! defined ( 'ABSPATH' ) ) exit;

// Define constants
define ( 'EMNL_WPLFC_CUSTOM_COOKIE_NAME', 'wordpress_logged_in_forever_by_emnl_wplfc' );


// Function tied to every page load
add_action ( 'init', 'emnl_wplfc' );
function emnl_wplfc() {

    // Option to disable functionality of this plugin if 3rd party code requires so
    if ( ! apply_filters ( 'emnl_woocommerce_persistent_login_customers', true ) ) {

        // Expire the custom cookie (if it exists at all), so it will be deleted by the browser automatically. We don't want to leave anything behind!
        if ( isset ( $_COOKIE[EMNL_WPLFC_CUSTOM_COOKIE_NAME] ) ) {
            setcookie ( EMNL_WPLFC_CUSTOM_COOKIE_NAME, '', time() - ( 15 * MINUTE_IN_SECONDS ) , '/' );
        }

        return;

    }
    
    // If user is logged in, get user role
    $role = '';
    if ( is_user_logged_in() ) {

        $user = wp_get_current_user();
        $roles = ( array ) $user->roles;
        $role = $roles[0];

    }

    // Check if user is not logged in OR NOT a Customer. If so we'll stop.
    if ( ! is_user_logged_in() || $role !== 'customer' ) {

        // Expire the custom cookie (if it exists at all), so it will be deleted by the browser automatically. We don't want to leave anything behind!
        if ( isset ( $_COOKIE[EMNL_WPLFC_CUSTOM_COOKIE_NAME] ) ) {
            setcookie ( EMNL_WPLFC_CUSTOM_COOKIE_NAME, '', time() - ( 15 * MINUTE_IN_SECONDS ) , '/' );
        }

        return;
        
    }

    // Check if the custom cookie exists. If yes, the native WP session cookies are already extended by this plugin. So we can stop here.
    if ( isset ( $_COOKIE[EMNL_WPLFC_CUSTOM_COOKIE_NAME] ) ) { return; }

    // Set 'forever' filter for the new WP session cookie
    add_filter ( 'auth_cookie_expiration', 'emnl_wplfc_really_long_time_in_seconds' );

    // Set new 'forever' WP session cookie (now its filter is set to 'forever')
    $current_logged_user = get_current_user_id();
    wp_set_auth_cookie ( $current_logged_user, true );

    // Just a final check to make sure the Wordpress logged_in cookie really exists, before setting the custom cookie to confirm the plugin did its job
    if ( wp_validate_auth_cookie ( $_COOKIE[ LOGGED_IN_COOKIE ], 'logged_in' ) ) {

        // Set the custom cookie
        setcookie ( EMNL_WPLFC_CUSTOM_COOKIE_NAME, true, time() + DAY_IN_SECONDS , '/' );

    }

}

// Function to set cookie duration
function emnl_wplfc_really_long_time_in_seconds() {

    $years = 30;
    $duration = $years * 365 * DAY_IN_SECONDS;

    // Fix for the Year 2038 problem on 32-bit systems
    if ( PHP_INT_MAX - time() < $duration ) {
        $duration = PHP_INT_MAX - time() - 5;
    }

    return $duration;

}


// Action to apply CSS
add_action ( 'wp_enqueue_scripts', 'emnl_wplfc_css' );
function emnl_wplfc_css() {

    if ( is_account_page() ) {
        wp_enqueue_style ( 'emnl_woocommerce_persistent_login_customers', plugins_url ( 'emnl-woocommerce-persistent-login-customers.css', __FILE__ ) );
    }

}
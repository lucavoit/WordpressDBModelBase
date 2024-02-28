<?php
global $plugin_db_version;
$plugin_db_version = '1.0.0';

function Plugin_install(){
    global $wpdb;
    global $plugin_db_version;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    //Add Tables
    $table_types = array(
    	new User()
    );
    
    //install
    foreach ($table_types as $table) {
    	$table->install();
    }

    update_option( '$plugin_db_version', $plugin_db_version );
}

register_activation_hook( __FILE__, 'Plugin_install' );

 function update_db_check() {
    global $plugin_db_version;
    if ( get_option( '$plugin_db_version' ) != $plugin_db_version ) {
        Plugin_install();
    }
}
add_action( 'plugins_loaded', 'update_db_check' );

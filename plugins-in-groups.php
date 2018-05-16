<?php
/*
  Plugin Name: Plugins In Groups
  Description: Keep your plugins in the groups. Sort them by tags. Keep your plugins page clear and manage them in bulk.
  Version: 1.0.0
  Description: 
  Author: Karol Sawka
  Author URI: http://karlosky.pl
*/

define( 'PIG_VERSION', '0.0.1' );

if ( !class_exists( 'PIG_Plugin') ) {
    
    class PIG_Plugin {
        
        public function __construct() {
            //hooks
            add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
            add_action( 'admin_init', array( $this, 'add_group' ) );
            add_action( 'admin_init', array( $this, 'remove_group' ) );
            add_action( 'pre_current_active_plugins', array( $this, 'select_group' ) );
            add_filter( 'plugin_row_meta', array( $this, 'plugin_links' ), 10, 2 );
            //ajax functions
            add_action( 'wp_ajax_assign_to_group', array( $this, 'assign_to_group' ) );
            add_action( 'wp_ajax_reassign_from_group', array( $this, 'reassign_from_group' ) );
            
            add_filter( 'all_plugins', array( $this, 'filter_plugins' ) );
        }
        
        
        /*
        * Ajax function.
        * Add plugin to the group
        */
        public function assign_to_group() {
            $groups = unserialize( get_option( 'pig_groups' ) );
            $plugin_file = sanitize_text_field( $_POST['plugin-file'] );
            $selected_group = sanitize_text_field( $_POST['selected-group'] );
            check_ajax_referer( 'assign-to-group', 'security' );
            $plugin_groups = array();
            $plugin_groups = unserialize( get_option( 'pig_' . $plugin_file ) );
            if ( ( array_search( $selected_group, $plugin_groups ) ) == false ) {
                $plugin_groups[] = $selected_group;
                update_option( 'pig_' . $plugin_file, serialize( $plugin_groups ) );
            }
            $current_groups = unserialize( get_option( 'pig_' . $plugin_file ) );
            $groups = array_diff( $groups, $current_groups );
            $return['all-groups'] = array_values( $groups );
            $return['selected-groups'] = $current_groups;
            wp_send_json_success( $return );
        }
        
        /*
        * Ajax function.
        * Remove plugin from the group
        */
        public function reassign_from_group() {
            $groups = unserialize( get_option( 'pig_groups' ) );
            $plugin_file = sanitize_text_field( $_POST['plugin-file'] );
            $selected_group = sanitize_text_field( $_POST['selected-group'] );
            check_ajax_referer( 'reassign-from-group', 'security' );
            $plugin_groups = array();
            $plugin_groups = unserialize( get_option( 'pig_' . $plugin_file ) );
            $new_groups = array();
            foreach ( $plugin_groups as $group ) {
                if ( $group != $selected_group ) {
                    $new_groups[] = $group; 
                }
            }
            update_option( 'pig_' . $plugin_file, serialize( $new_groups ) );
            $current_groups = unserialize( get_option( 'pig_' . $plugin_file ) );
            $groups = array_diff( $groups, $current_groups );
            $return['all-groups'] = array_values( $groups );
            $return['selected-groups'] = $current_groups;
            wp_send_json_success( $return );
        }
        
        /*
        * Add JS script on the backend
        */
        public function add_scripts() {
            wp_enqueue_script( 'pig-script', plugin_dir_url( __FILE__ ) . 'admin/js/pig-script.js', array( 'jquery' ), time() );
        }
        
        
        /*
        * Plugins group section on the top of the plugins page
        */
        public function select_group( $plugins_all ) {
            $groups = unserialize( get_option( 'pig_groups' ) );
            $selected = isset( $_GET['group'] ) ? sanitize_text_field( $_GET['group'] ) : 'all';
            ?>
                <p>
                    <label for="pig_plugin_group">
                        <strong><?php _e( 'Choose plugins group', 'pig' ); ?>:</strong>
                    </label>
                    <select name="my_meta_box_select" id="pig_plugin_group">
                        <option value="all" <?php selected( $selected, 'all' ); ?>><?php _e( 'All', 'pig' ); ?></option>
                        <?php if ( $groups ) : ?>
                            <?php foreach ( $groups as $group ) : ?>
                                <option value="<?php echo esc_attr( $group ); ?>" <?php selected( $selected, $group ); ?>><?php echo $group; ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </p>
                <p id="pig-new-group-info">
                    <form method="post">
                        <input type="hidden" name="pig-add-group-nonce" id="pig-add-group-nonce" value="<?php echo wp_create_nonce( 'add-group' ) ?>">
                        <input type="text" placeholder="<?php _e( 'New group name', 'pig' ); ?>" name="pig_new_group_name" id="pig_new_group_name" />
                        <input type="submit" href="#" name="pig_add_new" id="pig_add_new" class="button button-primary" value="<?php _e( 'Add new group', 'pig' ); ?>"> <?php _e( 'or', 'pig' ); ?> <a href="#"<?php if ( $selected == 'all' ) : ?> class="button-secondary delete disabled" <?php else : ?> name="pig_remove_group" id="pig_remove_group" class="button-secondary delete"<?php endif; ?>><?php _e( 'Remove the current group', 'pig' ); ?></a>
                    </form>
                </p>
                <input type="hidden" id="pig-assign-to-group-nonce" value="<?php echo wp_create_nonce( 'assign-to-group' ) ?>">
                <input type="hidden" id="pig-reassign-from-group-nonce" value="<?php echo wp_create_nonce( 'reassign-from-group' ) ?>">
                <input type="hidden" name="pig-remove-group-nonce" id="pig-remove-group-nonce" value="<?php echo wp_create_nonce( 'remove-group' ) ?>">
            <?php
        }
        
        
        /*
        * Create new plugins group
        */
        public function add_group() {
            if ( isset( $_POST['pig_new_group_name'] ) && $_POST['pig_new_group_name'] ) {
                if ( wp_verify_nonce( $_REQUEST['pig-add-group-nonce'], 'add-group' ) ) {
                    $new_group = sanitize_text_field( $_POST['pig_new_group_name'] );
                    $groups = array();
                    $groups = unserialize( get_option( 'pig_groups' ) );
                    $groups[] = $new_group;
                    update_option( 'pig_groups', serialize( $groups ) );
                } else {
                    die();
                }
            }
        }
        
        
        /*
        * Remove plugins group
        * @todo: 
        * 1. remove group assigned to the plugins
        */
        public function remove_group() {
            if ( isset( $_GET['pig_remove_group_name'] ) && $_GET['pig_remove_group_name'] ) {
                if ( wp_verify_nonce( $_REQUEST['pig-remove-group-nonce'], 'remove-group' ) ) {
                    $removed_group = sanitize_text_field( $_GET['pig_remove_group_name'] );
                    $groups = array();
                    $groups = unserialize( get_option( 'pig_groups' ) );
                    if ( $removed_group !== 'all' ) {
                        if ( ( $key = array_search( $removed_group, $groups ) ) !== false ) {
                            unset( $groups[$key] );
                        }
                        update_option( 'pig_groups', serialize( $groups ) );
                        
                        $all_plugins = get_plugins();
                        if ( $all_plugins ) {
                            foreach ( $all_plugins as $plugin_name => $plugin_object ) {
                                $groups = unserialize( get_option( 'pig_' . $plugin_name ) );
                                if ( $groups ) {
                                    foreach ( $groups as $group ) {
                                        if ( $group !== $removed_group ) {
                                            $new_groups[] = $group;
                                        }
                                    }
                                    if ( $groups !== $new_groups ) {
                                        update_option( 'pig_' . $plugin_name, serialize( $new_groups ) );
                                    }
                                }
                            }
                        }
                    }
                } else {
                    die();
                }
            }
        }
        
        
        /*
        * Add groups section on the plugin row on the plugins page
        */
        public function plugin_links( $links, $file ) {

            $all_groups = unserialize( get_option( 'pig_groups' ) );
            $selected_groups = unserialize( get_option( 'pig_' . $file ) );
            $all_groups_list = '<option disabled selected>' . __( 'Choose the group', 'pig' ) . '</option>';
            $all_groups = is_array( $selected_groups ) ? array_diff( $all_groups, $selected_groups ) : $all_groups;
            if ( $all_groups ) {
                foreach ( $all_groups as $group ) {
                    $all_groups_list .= '<option value="' . esc_attr( $group ) . '">' . $group . '</option>';
                }
            }
            $current_groups = unserialize( get_option( 'pig_' . $file ) );
            $groups_list = '';
            if ( $current_groups ) {
                foreach ( $current_groups as $group ) {
                    $groups_list .= '<span class="pig-reassign"><a id="post_tag-check-num-0" class="ntdelbutton pig-reassign" tabindex="0" data-pig-group="' . $group . '" data-pig-plugin="' . $file . '">X</a>&nbsp' . $group . '</span>';
                }
            }
            $new_links = array(
                'groups' => '
                <select class="pig-select-group" data-plugin-file="' . $file . '">' . $all_groups_list . '</select>
                <div class="tagchecklist selected-groups-list" data-plugin-file="' . $file . '">' . $groups_list . '</div>'
            );
            
            $links = array_merge( $links, $new_links );
            
            return $links;
        } 
        
        /*
        * Filter plugins on the plugins list
        */
        public function filter_plugins( $all_plugins ) {
            if ( isset( $_GET['group'] ) ) {
                $active_group = sanitize_text_field( $_GET['group'] );
                $filtered_plugins = array();
                foreach ( $all_plugins as $name => $plugin ) {
                    $current_plugin_groups = unserialize( get_option( 'pig_' . $name ) );
                    foreach ( $current_plugin_groups as $plugin_group ) {
                        if ( $plugin_group == $active_group ) {
                            $filtered_plugins[$name] = $plugin;
                        }
                    }
                }
                return $filtered_plugins;
            }
            return $all_plugins;
        }
    }
    
    /*
    * Create plugin instance
    */
    $pig = new PIG_Plugin;

}

<?php
/*
  Plugin Name: Plugins In Groups
  Version: 0.0.1
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
        }
        
        
        /*
        * Ajax function.
        * Add plugin to the group
        */
        public function assign_to_group() {
            $groups = unserialize( get_option( 'pig_groups' ) );
            $plugin_file = sanitize_text_field( $_POST['plugin-file'] );
            $selected_group = sanitize_text_field( $_POST['selected-group'] );
            
            $plugin_groups = array();
            $plugin_groups = unserialize( get_option( 'pig_' . $plugin_file ) );
            if ( ( array_search( $selected_group, $plugin_groups ) ) == false ) {
                $plugin_groups[] = $selected_group;
                update_option( 'pig_' . $plugin_file, serialize( $plugin_groups ) );
            }
            $current_groups = unserialize( get_option( 'pig_' . $plugin_file ) );
            $return['all-groups'] = $groups;
            $return['selected-groups'] = $current_groups;
            wp_send_json_success( $return );
        }
        
        /*
        * Add JS script on the backend
        */
        public function add_scripts() {
            wp_enqueue_script( 'pig-script', plugin_dir_url( __FILE__ ) . 'admin/js/pig-script.js', array( 'jquery' ) );
        }
        
        
        /*
        * Plugins group section on the top of the plugins page
        * @todo: 
        */
        public function select_group( $plugins_all ) {
            $groups = unserialize( get_option( 'pig_groups' ) );
            ?>
                <p>
                    <label for="pig_plugin_group">
                        <strong><?php _e( 'Choose plugins group', 'pig' ); ?>:</strong>
                    </label>
                    <select name="my_meta_box_select" id="pig_plugin_group">
                        <option value="all" <?php selected( $selected, 'all' ); ?>>All</option>
                        <?php if ( $groups ) : ?>
                            <?php foreach ( $groups as $group ) : ?>
                                <option value="<?php echo esc_attr( $group ); ?>" <?php selected( $selected, $group ); ?>><?php echo $group; ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </p>
                <p id="pig-new-group-info">
                    <form method="post">
                        <input type="text" placeholder="<?php _e( 'New group name', 'pig' ); ?>" name="pig_new_group_name" id="pig_new_group_name" />
                        <input type="submit" href="#" name="pig_add_new" id="pig_add_new" class="button button-primary" value="<?php _e( 'Add new group', 'pig' ); ?>"> <?php _e( 'or', 'pig' ); ?> <a href="#" name="pig_remove_group" id="pig_remove_group" class="button-secondary delete"><?php _e( 'Remove the current group', 'pig' ); ?></a>
                    </form>
                </p>
                <!--
                <p id="pig-main-info">
                    You can <a href="#"><?php _e( 'Add new group', 'pig' ); ?></a> <?php _e( 'or', 'pig' ); ?> <a href="#"><?php _e( 'Remove the current group', 'pig' ); ?></a>
                </p>
                -->
            <?php
        }
        
        
        /*
        * Create new plugins group
        */
        public function add_group() {
            if ( isset( $_POST['pig_new_group_name'] ) && $_POST['pig_new_group_name'] ) {
                $new_group = sanitize_text_field( $_POST['pig_new_group_name'] );
                $groups = array();
                $groups = unserialize( get_option( 'pig_groups' ) );
                $groups[] = $new_group;
                update_option( 'pig_groups', serialize( $groups ) );
            }
        }
        
        
        /*
        * Remove plugins group
        * @todo: 
        * 1. remove group assigned to the plugins
        * 2. don't remove "All" group
        */
        public function remove_group() {
            if ( isset( $_GET['pig_remove_group_name'] ) && $_GET['pig_remove_group_name'] ) {
                $removed_group = sanitize_text_field( $_GET['pig_remove_group_name'] );
                $groups = array();
                $groups = unserialize( get_option( 'pig_groups' ) );
                if ( ( $key = array_search( $removed_group, $groups ) ) !== false ) {
                    unset( $groups[$key] );
                }
                update_option( 'pig_groups', serialize( $groups ) );
            }
        }
        
        
        /*
        * Add groups section on the plugin row on the plugins page
        */
        public function plugin_links( $links, $file ) {

            $all_groups = unserialize( get_option( 'pig_groups' ) );
            $all_groups_list = '<option value="All">' . __( 'All', 'pig' ) . '</option>';
            if ( $all_groups ) {
                foreach ( $all_groups as $group ) {
                    $all_groups_list .= '<option value="' . esc_attr( $group ) . '">' . $group . '</option>';
                }
            }
            $current_groups = unserialize( get_option( 'pig_' . $file ) );
            $groups_list = '';
            if ( $current_groups ) {
                foreach ( $current_groups as $group ) {
                    $groups_list .= '<span><a id="post_tag-check-num-0" class="ntdelbutton" tabindex="0">X</a>&nbsp' . $group . '</span>';
                }
            }
            $new_links = array(
                'groups' => '
                <a href="#" class="pig-add-to-group">' . __( 'Add to the group', 'pig' ) . '</a> <select class="pig-select-group" data-plugin-file="' . $file . '">' . $all_groups_list . '</select>
                <div class="tagchecklist selected-groups-list" data-plugin-file="' . $file . '">' . $groups_list . '</div>'
            );
            
            $links = array_merge( $links, $new_links );
            
            return $links;
        } 
    }
    
    /*
    * Create plugin instance
    */
    $pig = new PIG_Plugin;

}

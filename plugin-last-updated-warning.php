<?php
/**
 * Plugin Name:     Plugin Last Updated Warning
 * Plugin URI:      
 * Description:     This plugin will display a warning for plugins that haven't received updates on the WP.org plugin repo the past year.
 * Author:          Vincent S Hasselgård
 * Author URI:      https://vincenthasselgard.no
 * Text Domain:     plugin-last-updated-warning
 * Domain Path:     /languages
 * Version:         0.9.1
 *
 * @package         Plugin_Last_Updated_Warning
 */

namespace MilesToGo;

if( !class_exists('Plugin_Last_Updated_Warning') ) {

    class Plugin_Last_Updated_Warning{

        public static function init(){

            /**
             * Retrieves numbers of years since last plugin update on WP.org
             * 
             * @since 0.1.0
             * 
             * @param string $plugin_slug   The slug of the plugin that is being checked. Must match plugin slug on WP.org.
             * 
             */
            
            function get_last_updated_date($plugin_slug){
 
                $action = 'plugin_information';
                $args = array(
                    'slug' => $plugin_slug
                );

                $url = 'http://api.wordpress.org/plugins/info/1.2/';
                $ssl = wp_http_supports( array( 'ssl' ) );
                
                if ( $ssl ) {
                    $url = set_url_scheme( $url, 'https' );
                }

                $url = add_query_arg(
                    array(
                        'action'  => $action,
                        'request' => $args,
                    ),
                    $url
                );

                $http_args = array(
                    'timeout'    => 15,
                    'user-agent' => 'WordPress;' . home_url( '/' ),
                );

                $request = wp_remote_get( $url, $http_args );
                $plugin_info = json_decode($request['body']);

                $plugin_last_update = new \DateTime($plugin_info->last_updated);
                $today = new \DateTime( date('Y-m-d', time() ) ); // use to get a return of number of years since update

                $diff = $today->diff($plugin_last_update);

                return $diff->y ;

            }

            /**
             * Adds a warning for every plugin which has not received an update in the past year.
             * 
             * @since 0.1.0
             * 
             * @param string $plugin_file   String with directory and filename for plugin. IE 'akismet/akismet.php'
             * @param string $plugin_data   Array that contains plugin data.
             * 
             */

            function add_plugin_warning($plugin_file, $plugin_data){
                if( isset($plugin_data['slug'] ) ){
                    $last_updated = get_last_updated_date($plugin_data['slug']);
                    if( $last_updated > 0 ) {
                        printf (
                            '<tr class="plugin-old-tr">
                                <td colspan="3" class="plugin-old colspanchange">
                                    <div class="notice inline notice-warning notice-alt">
                                        <p>' 
                        );
                                        echo sprintf( __( 'It looks like %1$s hasn\'t received updates in the last %2$d year(s). It may no longer be maintained or supported and may have compatibility issues when used with more recent versions of WordPress.', 'plugin-last-updated-warning' ), $plugin_data['Name'], $last_updated );
                                        
                        printf(         
                                        '</p>
                                    </div>
                                </td>
                            </tr>'
                        );
                    }
                }
            }

            /**
             * Does a foreach loop to check all installed plugins.
             * 
             * @since 0.1.0
             * 
             */

            function get_all_plugins(){
                $plugins = \get_plugins();
                foreach ($plugins as $plugin_file=>$plugin_data){
                    add_action("after_plugin_row_" . $plugin_file . "", 'MilesToGo\\add_plugin_warning', 10, 3);
                }         
            }
            
            add_action('admin_init', 'MilesToGo\\get_all_plugins' );

        }
    }

    Plugin_Last_Updated_Warning::init();

}
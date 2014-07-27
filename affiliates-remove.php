<?php
/**
 * affiliates-remove.php
 *
 * Copyright (c) 2011,2012 Antonio Blanco http://www.blancoleon.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Antonio Blanco	
 * @package affiliates-purchase
 * @since affiliates-purchase 1.0.0
 *
 * Plugin Name: Affiliates Remove
 * Plugin URI: http://www.eggemplo.com
 * Description: Add bulk remove affiliates function.
 * Version: 1.0
 * Author: eggemplo
 * Author URI: http://www.eggemplo.com
 * License: GPLv3
 */

define( 'AFFILIATESREMOVE_DOMAIN', 'affiliatesremove' );

class AffiliatesRemove_Plugin {
	
	private static $notices = array ();
	
	public static function init() {
		
		add_action ( 'init', array ( __CLASS__, 'wp_init' ) );
		add_action ( 'admin_notices', array ( __CLASS__, 'admin_notices' ) );
	}
	
	public static function wp_init() {
		if (! defined ( 'AFFILIATES_PLUGIN_DOMAIN' )) {
			self::$notices [] = "<div class='error'>" . __ ( '<strong>AffiliatesRemove</strong> plugin requires <a href="http://www.itthinx.com/plugins/affiliates?affiliates=51" target="_blank">Affiliates</a> or <a href="http://www.itthinx.com/plugins/affiliates-pro?affiliates=51" target="_blank">Affiliates Pro</a> or <a href="http://www.itthinx.com/plugins/affiliates-enterprise?affiliates=51" target="_blank">Affiliates Enterprise</a>.', AFFILIATESREMOVE_DOMAIN ) . "</div>";
		} else {
			
			add_action ( 'admin_menu', array ( __CLASS__, 'admin_menu' ), 40 );
			
		}	

	}

	
	public static function admin_notices() {
		if (! empty ( self::$notices )) {
			foreach ( self::$notices as $notice ) {
				echo $notice;
			}
		}
	}
	
	/**
	 * Adds the admin section.
	 */
	public static function admin_menu() {
		$admin_page = add_submenu_page ( 
			'affiliates-admin', 
			__ ( 'Affiliates remove', AFFILIATESREMOVE_DOMAIN ), 
			__ ( 'Affiliates remove', AFFILIATESREMOVE_DOMAIN ), 
			'manage_options', 
			'affiliatesremove', 
			array (
				__CLASS__,
				'affiliatesremove' 
			) 
		);
		
	}
	
	
	public static function affiliatesremove() {
		$output = "";
		$output .= '
		<div class="wrap">
			<h2>' . __( "Affiliates Remove", AFFILIATESREMOVE_DOMAIN ) . '</h2>';
	
		if (isset ( $_POST ['remove'] )) {
			$num = AffiliatesRemove_Plugin::affiliates_do_remove();
			$output .= "<h3>" . $num . " affiliates removed.</h3>";
		}
				
		$output .= '<form method="post" action="">';
		
		$output .= get_submit_button ("Remove all affiliates", "primary", "remove");

		echo $output;
		
		wp_nonce_field( 'affiliates-remove', 'affiliates-remove' );
			
		$output = '</form>
		<hr>
		<p>
		<span class="description">By <a href="http://www.eggemplo.com" target="_blank" >eggemplo</a></span>
		</p>
		</div>';		
		echo $output;
	}
	
	public static function affiliates_do_remove() {
		global $wpdb;
		global $affiliates_db;
		
		$num_remove = 0;
		$result = false;
		
		if (! current_user_can ( AFFILIATES_ADMINISTER_AFFILIATES )) {
			wp_die ( __ ( 'Access denied.', AFFILIATES_PLUGIN_DOMAIN ) );
		}
		
		if (! wp_verify_nonce ( $_POST ['affiliates-remove'], 'affiliates-remove' )) {
			wp_die ( __ ( 'Access denied.', AFFILIATES_PLUGIN_DOMAIN ) );
		}
		
		$affiliates_table = _affiliates_get_tablename ( 'affiliates' );
		
		$affiliates = $affiliates_db->get_objects ( "SELECT * FROM " . $affiliates_table . " WHERE status='active'");
		
		if (count ( $affiliates )) {
			
			foreach ( $affiliates as $affiliate ) {
				
				$affiliate_id = $affiliate->affiliate_id;
				if ($affiliate_id) {
					$valid_affiliate = false;
					// do not mark the pseudo-affiliate as deleted: type != ...
					$check = $wpdb->prepare ( "SELECT affiliate_id FROM $affiliates_table WHERE affiliate_id = %d AND (type IS NULL OR type != '" . AFFILIATES_DIRECT_TYPE . "')", intval ( $affiliate_id ) );
					if ($wpdb->query ( $check )) {
						$valid_affiliate = true;
					}
					
					if ($valid_affiliate) {
						$result = false !== $wpdb->query ( $query = $wpdb->prepare ( "UPDATE $affiliates_table SET status = 'deleted' WHERE affiliate_id = %d", intval ( $affiliate_id ) ) );
						do_action ( 'affiliates_deleted_affiliate', intval ( $affiliate_id ) );
						if ( $result ) {
							$num_remove ++;
						}
					}
				}
			}
		}
		return $num_remove;
	}
}
AffiliatesRemove_Plugin::init();


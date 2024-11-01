<?php
/*
Plugin Name: Vishwa 301 Redirects
Plugin URI: http://vishwainfoways.com/wp-vishwa-301-redirects/
Description: Create 301 redirect for WordPress with wildcard support.
Version: 1.0.0
Author: Vishwa Infoways
Author URI: http://vishwainfoways.com/

*/

/*  Copyright 2009-2018  Vsihwa Infoways  (email : rakesh@vishwainfoways.com)

   This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!class_exists("Vishwa301redirects")) {
	
	class Vishwa301redirects {
		
		/**
		 * add_menu function
		 * generate the link to the options page under settings
		 * @access public
		 * @return void
		 */
		function add_menu() {
		  add_options_page('vishwa 301 Redirects', 'vishwa 301 Redirects', 'manage_options', 'vishwa_301options', array($this,'redirect_options_page'));
		}
		
		/**
		 * redirect_options_page function
		 * generate the options page in the wordpress admin
		 * @access public
		 * @return void
		 */
		function redirect_options_page() {
		?>
		<div class="wrap vishwa_301_redirects">
			<script>
				//todo: This should be enqued
				jQuery(document).ready(function(){
					jQuery('span.wps301-delete').html('Delete').css({'color':'red','cursor':'pointer'}).click(function(){
						var confirm_delete = confirm('Are you sure to delete This Redirect?');
						if (confirm_delete) {
							
							// remove element and submit
							jQuery(this).parent().parent().remove();
							jQuery('#vishwa_redirects_form').submit();
							
						}
					});
					
					
				});
			</script>
		
		<?php
			if (isset($_POST['vishwa_301_redirects'])) {
				echo '<div id="message" class="updated"><p>Settings successfully saved</p></div>';
			}
		?>
		
			<h2>Vishwa 301 Redirects</h2>
			
			<form method="post" id="vishwa_redirects_form" action="options-general.php?page=vishwa_301options&savedata=true">
			
			<?php wp_nonce_field( 'vishwa_save_redirects', '_s301r_nonce' ); ?>

			<table class="widefat">
				<thead>
					<tr>
						<th colspan="2">Request url</th>
						<th colspan="2">Destination url</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="2"><strong>example: /test.htm</strong></td>
						<td colspan="2"><strong>example: <?php echo get_option('home'); ?>/newtest/</strong></td>
					</tr>
					<?php echo $this->vishwa_list_redirects(); ?>
					<tr>
						<td style="width:35%;"><input type="text" name="vishwa_301_redirects[request][]" value="" style="width:99%;" /></td>
						<td style="width:2%;">&#8594;</td>
						<td style="width:60%;"><input type="text" name="vishwa_301_redirects[destination][]" value="" style="width:99%;" /></td>
						<td><span class="wps301-delete">Delete</span></td>
					</tr>
				</tbody>
			</table>
			
			<?php $wildcard_checked = (get_option('vishwa_301_redirects_wildcard') === 'true' ? ' checked="checked"' : ''); ?>
			<p><input type="checkbox" name="vishwa_301_redirects[wildcard]" id="wps301-wildcard"<?php echo $wildcard_checked; ?> /><label for="wps301-wildcard"> Use Wildcards?</label></p>
			<p><strong>Note:</strong>
            	<p>Wildcards</p>
				<p>To use wildcards, put an asterisk (*) after the folder name that you want to redirect.</p>
				<h4>Example</h4>
				<ul>
					<li><strong>Request:</strong> /old-folder/*</li>
					<li><strong>Destination:</strong> /redirect-everything-here/</li>
				</ul></p>
			<p class="submit"><input type="submit" name="submit_301" class="button-primary" value="<?php _e('Update') ?>" /></p>
			</form>
			
		</div>
		<?php
		} // end of function redirect_options_page
		
		/**
		 * vishwa_list_redirects function
		 * utility function to return the current list of redirects as form fields
		 * @access public
		 * @return string <html>
		 */
		function vishwa_list_redirects() {
			$redirects = get_option('vishwa_301_redirects');
			$output = '';
			if (!empty($redirects)) {
				foreach ($redirects as $request => $destination) {
					$output .= '
					
					<tr>
						<td><input type="text" name="vishwa_301_redirects[request][]" value="'.$request.'" style="width:99%" /></td>
						<td>&#8594;</td>
						<td><input type="text" name="vishwa_301_redirects[destination][]" value="'.$destination.'" style="width:99%;" /></td>
						<td><span class="wps301-delete"></span></td>
					</tr>
					
					';
				}
			} // end if
			return $output;
		}
		
		/**
		 * vishwa_save_redirects function
		 * save the redirects from the options page to the database
		 * @access public
		 * @param mixed $data
		 * @return void
		 */
		function vishwa_save_redirects($data) {
			if ( !current_user_can('manage_options') )  { wp_die( 'You do not have sufficient permissions to access this page.' ); }
			check_admin_referer( 'vishwa_save_redirects', '_s301r_nonce' );
			
			$data = $_POST['vishwa_301_redirects'];

			$redirects = array();
			
			for($i = 0; $i < sizeof($data['request']); ++$i) {
				$request = trim( sanitize_text_field( $data['request'][$i] ) );
				$destination = trim( sanitize_text_field( $data['destination'][$i] ) );
			
				if ($request == '' && $destination == '') { continue; }
				else { $redirects[$request] = $destination; }
			}
			
			update_option('vishwa_301_redirects', $redirects);
			
			if (isset($data['wildcard'])) {
				update_option('vishwa_301_redirects_wildcard', 'true');
			}
			else {
				delete_option('vishwa_301_redirects_wildcard');
			}
		}
		
		/**
		 * vishwa_redirect_on_load function
		 * Read the list of redirects and if the current page 
		 * is found in the list, send the visitor on her way
		 * @access public
		 * @return void
		 */
		function vishwa_redirect_on_load() {
			// this is what the user asked for (strip out home portion, case insensitive)
			$userrequest = str_ireplace(get_option('home'),'',$this->get_url_address());
			$userrequest = rtrim($userrequest,'/');
			
			$redirects = get_option('vishwa_301_redirects');
			if (!empty($redirects)) {
				
				$wildcard = get_option('vishwa_301_redirects_wildcard');
				$do_redirect = '';
				
				// compare user request to each 301 stored in the db
				foreach ($redirects as $storedrequest => $destination) {
					// check if we should use regex search 
					if ($wildcard === 'true' && strpos($storedrequest,'*') !== false) {
						// wildcard redirect
						
						// don't allow people to accidentally lock themselves out of admin
						if ( strpos($userrequest, '/wp-login') !== 0 && strpos($userrequest, '/wp-admin') !== 0 ) {
							// Make sure it gets all the proper decoding and rtrim action
							$storedrequest = str_replace('*','(.*)',$storedrequest);
							$pattern = '/^' . str_replace( '/', '\/', rtrim( $storedrequest, '/' ) ) . '/';
							$destination = str_replace('*','$1',$destination);
							$output = preg_replace($pattern, $destination, $userrequest);
							if ($output !== $userrequest) {
								// pattern matched, perform redirect
								$do_redirect = $output;
							}
						}
					}
					elseif(urldecode($userrequest) == rtrim($storedrequest,'/')) {
						// simple comparison redirect
						$do_redirect = $destination;
					}
					
					// redirect. the second condition here prevents redirect loops as a result of wildcards.
					if ($do_redirect !== '' && trim($do_redirect,'/') !== trim($userrequest,'/')) {
						// check if destination needs the domain prepended
						if (strpos($do_redirect,'/') === 0){
							$do_redirect = home_url().$do_redirect;
						}
						header ('HTTP/1.1 301 Moved Permanently');
						header ('Location: ' . $do_redirect);
						exit();
					}
					else { unset($redirects); }
				}
			}
		} // end funcion redirect
		
		/**
		 * get_url_address function
		 * utility function to get the full address of the current request
		 * credit: http://www.phpro.org/examples/Get-Full-URL.html
		 * @access public
		 * @return void
		 */
		function get_url_address() {
			// return the full address
			return $this->get_url_protocol().'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		} // end function get_url_address
		
		function get_url_protocol() {
			// Set the base protocol to http
			$protocol = 'http';
			// check for https
			if ( isset( $_SERVER["HTTPS"] ) && strtolower( $_SERVER["HTTPS"] ) == "on" ) {
    			$protocol .= "s";
			}
			
			return $protocol;
		} // end function get_protocol
		
	} // end class Vishwa301redirects
	
} // end check for existance of class

// instantiate
$redirect_plugin = new Vishwa301redirects();

if (isset($redirect_plugin)) {
	// add the redirect action, high priority
	add_action('init', array($redirect_plugin,'vishwa_redirect_on_load'), 1);

	// create the menu
	add_action('admin_menu', array($redirect_plugin,'add_menu'));

	// if submitted, process the data
	if (isset($_POST['vishwa_301_redirects'])) {
		add_action('admin_init', array($redirect_plugin,'vishwa_save_redirects'));
	}
}

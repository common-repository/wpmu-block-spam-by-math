<?php
/*
Plugin Name: WPMU-Block-Spam-By-Math
Plugin URI: http://www.jamespegram.com/wpmu-block-spam-by-math/
Description: This plugin protects your WPMU signup process against spambots with a simple math question. This plugin is based on the <a href="http://wordpress.org/extend/plugins/block-spam-by-math/">Block-Spam-By-Math</a> plugin created by <a href="http://www.grauonline.de">Alexander Grau</a>. This only protects the new blog creation process and the new user creation process. If you wish to have individual blog protection use the regular Block-Spam-By-Math plugin on individual blogs.
Author: James Pegram (based on the work by Alexander Grau)
Version: 1.3
Author URI: http://www.jamespegram.com
*/

/*  Copyright 2009  
	
    James Pegram (email : jwpegram [make-an-at] gmail [make-a-dot] com)
    Alexander Grau (email : alex [make-an-at] grauonline [make-a-dot] de)

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


if ( !class_exists( 'wpmuBlockSpamByMath' ) ) {
	class wpmuBlockSpamByMath {
		
		// Constructor
		function wpmuBlockSpamByMath() {
			
			// WPMU specific actions/filters 
			add_action('wpmu_options', array( &$this, 'blockspam_site_admin_options'));
			add_action('update_wpmu_options', array( &$this, 'blockspam_site_admin_options_process'));			
			add_action('wp_head', array( &$this, 'blockspam_stylesheet') );
			add_action( 'signup_extra_fields', array( &$this, 'wpmu_add_hidden_fields' ) );
			add_action( 'bp_before_registration_submit_buttons', array( &$this, 'wpmu_add_hidden_fields' ) );
			
			add_filter('wpmu_validate_user_signup', array( &$this, 'wpmu_validate_user_signup' ) );
			add_filter('bp_signup_validate', array( &$this, 'wpmu_validate_user_signup' ) );
			add_filter('signup_blogform', array( &$this, 'wpmu_add_hidden_blogfields' ) );
			
			// We don't want this filter applied if we're outside of the initial MU signup process (ie: don't apply it to the wp-ativate function calls).
			if ($_POST['stage'] != 'validate-user-signup') { remove_filter('wpmu_validate_user_signup',array( &$this, 'wpmu_validate_user_signup' ) ); }
		}
		
		// Initialize plugin
		function init() {
			if ( function_exists( 'load_plugin_textdomain' ) ) {
				load_plugin_textdomain( 'wpmu-block-spam-by-math', PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)) );
			}
		}
		

		
		// Protection function for submitted comment form
		function wpmu_validate_user_signup( $content ) {
			$this->wpmu_check_hidden_fields();
			return $content;
		}	
		
		// Add hidden fields to the signnup form
		function wpmu_add_hidden_fields() {
			$mathvalue0 = rand(2, 15);
			$mathvalue1 = rand(2, 15);
     			echo '<div id="spambot"><b>IMPORTANT!</b> To be able to proceed, you need to solve the following simple math (so we know that you are a human) :-) <br/><br/>';
			echo "What is $mathvalue0 + $mathvalue1 ?<br/>";			
			echo '<input type="text" name="mathvalue2" value="" />';
			echo '</div>';
			echo '<div style="display:none">Please leave these two fields as-is: ';
			echo "<input type='text' name='mathvalue0' value='$mathvalue0' />";
			echo "<input type='text' name='mathvalue1' value='$mathvalue1' />";
			echo '</div>';
		}

		// Pass the hidden fields to the blog form
		function wpmu_add_hidden_blogfields() {
			if ( !empty( $_POST['mathvalue0']) && !empty($_POST['mathvalue1'] ) && !empty($_POST['mathvalue2'])) {
				echo "<input type='hidden' name='mathvalue0' value='$_POST[mathvalue0]' />";
				echo "<input type='hidden' name='mathvalue1' value='$_POST[mathvalue1]' />";
				echo "<input type='hidden' name='mathvalue2' value='$_POST[mathvalue2]' />";
			}
		}

			
		
		
function blockspam_stylesheet() {
?>
<style type="text/css">
	.mu_register .error {font-size: 12px; margin:5px 0; }
	.mu_register #spambot {font-size: 12px; margin:10px 0; }
	#spambot {clear:both; font-size: 12px; margin:10px 0;}	
</style>
<?php
}		
		

		
		// If from WPMU wp-signup check for hidden fields and kick an error instead of dieing.
		function wpmu_check_hidden_fields() {

			// Get values from POST data
			$val0 = '';
			$val1 = '';
			$val2 = '';
			if ( isset( $_POST['mathvalue0'] ) ) {
				$val0 = $_POST['mathvalue0'];
			}
			if ( isset( $_POST['mathvalue1'] ) ) {
				$val1 = $_POST['mathvalue1'];
			}
			if ( isset( $_POST['mathvalue2'] ) ) {
				$val2 = $_POST['mathvalue2'];
			}
			
			// Check values
			if ( ( $val0 == '' ) || ( $val1 == '' ) || ( intval($val2) != (intval($val0) + intval($val1)) ) ) {
				$error = get_site_option('blockspam_error');
				
				if ( false === strpos( $_SERVER['SCRIPT_NAME'], 'wp-signup.php') && $_GET['action'] != 'register' ) { 
					wp_die( $error, '403 Forbidden', array( 'response' => 403 ) );
				} else {  
					echo '<div class="error">' . $error . '</div>';
				}		
				exit;
			}
			
		}

		function blockspam_site_admin_options_process() {
			update_site_option( 'blockspam_error' , $_POST['blockspam_error'] );
		}			
		
		
		function blockspam_site_admin_options() {
			?>
				<h3><?php _e('WPMU Block Spam By Math') ?></h3> 
				<table class="form-table">
					<tr valign="top"> 
						<th scope="row"><?php _e('Block Spam Error Message') ?></th> 
						<td><textarea name="blockspam_error"  id="blockspam_error" cols="45" rows="5"><?php echo stripslashes(get_site_option('blockspam_error', 'Bye Bye, SPAMBOT!')); ?></textarea>
							<br />
							<?php _e('This is the text that will be displayed when a user or spambot fails to answer the question.<br />') ?>
							<?php _e('<em>You can use html here.</em>') ?>
						</td>
					</tr>
				</table>
			<?php
		}

		
	}

	$wp_block_spam_by_math = new wpmuBlockSpamByMath();
}

?>
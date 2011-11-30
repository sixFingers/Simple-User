<?php

class wpSimpleProfile
{
	var $login_url = '';
	var $profile_url = '';
	var $user_id;
	
	public function __construct($login_url, $profile_url, $user_id = FALSE)
	{
		/**
		 * Add filters and actions
		 */
		$this->login_url = $login_url;
		$this->profile_url = $profile_url;
		$this->user_id = $user_id;
		
		register_activation_hook(__FILE__, array($this, 'pluginInstall'));  
		add_action('admin_menu', array($this, 'admin_menu'));
		$this->init();
	}
	
	function admin_print_scripts() {
        wp_enqueue_script('jquery');
    }
	
	function admin_menu() {
		$page = add_submenu_page('users.php', 'Impostazioni profilo', 'Impostazioni profilo', 'manage_options', 'simple-user-profile', array($this, 'profile_editor_parse'));
		add_action("admin_print_scripts-$page", array($this, 'admin_print_scripts'));
	}
    
	function pluginInstall(){
		
    }
	
	function init()
	{
		$this->get_profile_fields();
		add_shortcode('simple_profile', array($this, 'frontend_profile'));
	}
	
	function frontend_profile() {
			
		if($_POST['action'] == 'su_update_frontend_profile') {
			$showMessage = array();
			if (!wp_verify_nonce($_POST['simple_user_frontend_profile_nonce'], 'su_update_frontend_profile')) {
				$message .= '<p>ERRORE: I dati inviati non sono regolari.';
			}	
			else {
				$i = 0; 
				foreach($this->profileFields as $field => $properties) {
					if($this->profileFields[$field][1]!= '1') continue;
					if(array_key_exists($field, $_POST['su_field'])) {
	        			$value = esc_html(trim($_POST['su_field'][$field]));
						$error = false;
						
						// If it's submitted, but empty and the field is required
	        			if ($value == '' && $this->profileFields[$field][1] == '1') {
	        				$showMessage[] = '<p>Il campo <strong>'.$this->profileFields[$field][0].'</strong> è richiesto.';
							$error = true;
						}
						// Do data format validation
						if($field == 'user_email' && !is_email($value)) {
							$showMessage[] = '<p>Inserisci una <strong>'.$this->profileFields[$field][0].'</strong> valida.';
							$error = true;
						}
						
						if(!$error) {
							update_user_meta($this->user_id, $field, $value);
						}
	        		}
					else {
						// If it's not being submitted, but requested
						if($this->profileFields[$field][1] == '1') $showMessage[] = '<p>Il campo <strong>'.$this->profileFields[$field][0].'</strong> è richiesto.';
					}
	        		$i++; 
				}
			}	
		}
		
		//echo su_profile_complete($this->user_id, $this->profileFields) ? 'si': 'no';
		
		if(!empty($showMessage)) echo '<div class="message error">'.implode('', $showMessage).'</div>'; ?>
    	<form action="" method="post">
    		<?php wp_nonce_field('su_update_frontend_profile','simple_user_frontend_profile_nonce'); ?>
    		<input type="hidden" name="action" value="su_update_frontend_profile"> 
    		<?php $i = 0; foreach($this->profileFields as $field => $properties): if($properties[2] == '1'): ?>
        		<p>
        			<label><?php echo $properties[0]; ?></label><br />
        			<input type="text" value="<?php echo get_user_meta($this->user_id, $field, true); ?>" name="su_field[<?php echo $field; ?>]" />
        		</p>
        	<?php $i++; endif; endforeach; ?>
			<input type="submit" value="Salva impostazioni" />
		</form>
		<?php
	}
	
	function get_profile_fields() {
		//delete_option('su_profile_settings');
		$this->profileFields = get_option('su_profile_settings');
		
		if(!$this->profileFields) {
			$this->profileFields = array( 
	    		'user_login' => array('Username', '1', '1', '0'), 
				'user_email' => array('Email', '1', '1', '1'), 
				'user_pass' => array('Password', '1', '0', '2'), 
				'user_nicename' => array('Nicename', '0', '0', '3'), 
				'user_url' => array('Website', '0', '0', '4'), 
				'display_name' => array('Display Name', '0', '0', '5'), 
				'nickname' => array('Nickname', '0', '0', '6'), 
				'first_name' => array('First Name', '1', '1', '7'), 
				'last_name' => array('Last Name', '1', '1', '8'),  
				'description' => array('Description', '0', '0', '9'), 
				'user_registered' => array('Registration Date', '0', '0', '10'), 
				'role' => array('Role', '1', '0', '11'), 
				'jabber' => array('Jabber', '1', '0', '12'), 
				'aim' => array('Aim', '1', '0', '13'), 
				'yim' => array('Yim', '1', '0', '14'), 
			);
			update_option('su_profile_settings', $this->profileFields);
		}
		//print_r($this->profileFields);
		return $this->profileFields;
    }
	
	function profile_editor_parse() {
        if(isset($_POST)) {
        	if($_POST['action'] == 'su_update_settings') {
            	// We are in the backend	
            	if (!check_admin_referer('simple_user_profile_nonce', 'su_update_settings')) die('Errore nella richiesta.');
            	$this->update_profile_settings($_POST);
                echo "<br /><div class='updated'>Impostazioni del profilo salvate.</div>";
            }
        }
        $this->profile_editor_render(); 
    }
	
	function update_profile_settings($args) {
		extract( $args );
		$option = array();
		$this->profileFields = array();
		$f = 0;
		foreach($su_field_order as $field => $order) {
			$option[$field] = $this->profileFields[$field] = array(
				array_key_exists($field, $su_field_title) ? $su_field_title[$field]: '', 
				in_array($field, $su_field_requested) ? '1': '0', 
				in_array($field, $su_field_editable) ? '1': '0', 
				''.$f
			);
			$f ++;
		}
		
		function field_order_sort($a, $b) {
			return (intval($a[3]) > intval($b[3])); //only doing string comparison
		}
		
		uasort($option, 'field_order_sort');
		uasort($this->profileFields, 'field_order_sort');
		
		update_option('su_profile_settings', $option);
		return $this->profileFields;
	}
	
	function profile_editor_render() {
		$this->profile_editor_scripts(); 
	?>
		
		<div class="wrap">
        	<?php if($showMessage) echo "<br /><div class='$showMessage[0]'>$showMessage[1]</div>"; ?>
        	<form action="?page=simple-user-profile" method="post">
        		<?php wp_nonce_field( 'simple_user_profile_nonce','su_update_settings' ); ?>
        		<input type="hidden" name="action" value="su_update_settings"> 
	        	<div id="icon-users" class="icon32"><br /></div>
	        	<h2>User Meta Editor</h2>    
	            
	            <h3>Campi dell'utente</h3>
	            <table class="form-table" style="width:500px;">
				<?php $i = 0; foreach($this->profileFields as $field => $properties): ?>
					<tr valign="top">
						<th scope="row" width="40%"><input type="text" value="<?php echo $properties[0]; ?>" name="su_field_title[<?php echo $field; ?>]" /></th>
						<td width="20%"><label for="su_field_requested[]">Richiesto</label> <input type="checkbox" name="su_field_requested[]" value="<?php echo $field; ?>" <?php checked(true, $properties[1]) ?> /></td>
						<td width="20%"><label for="su_field_editable[]">Visibile nel profilo</label> <input type="checkbox" name="su_field_editable[]" value="<?php echo $field; ?>" <?php checked('1', $properties[2]) ?> /></td>
						<td width="20%"><a href="javascript:void(0);" class="su_move_field_up">↑</a> <a href="javascript:void(0);" class="su_move_field_down">↓</a> <input type="text" class="su_field_order small-text" name="su_field_order[<?php echo $field; ?>]" value="<?php echo $i; ?>" /></td>
					</tr>
				<?php $i++; endforeach; ?>
				</table>
				<?php submit_button('Salva impostazioni'); ?>
			</form>
        </div>                			          
        <?php
    }

	function profile_editor_scripts() {
	?>
<script type="text/javascript">
	var $ = jQuery;
	
	function switch_field(e) {
		var $self = $(e.target), 
			$tr = $self.parents('tr'), 
			$target = e.data.dir > 0 ? $tr.next(): $tr.prev(), 
			$field = $self.siblings('input.su_field_order'), 
			$target_field = $target.find('input.su_field_order');
		
		if(e.data.dir < 0) {
			$val = parseInt($field.val())-1;
			$tval = parseInt($target_field.val())+1;
			$target.before($tr.detach());
		}
		else {
			$val = parseInt($field.val())+1;
			$tval = parseInt($target_field.val())-1;
			$target.after($tr.detach());
		}
		
		$field.val($val);
		$target_field.val($tval);
	}
	
	$(document).ready(function () {
		$('.su_move_field_up').bind('click', {dir: -1}, function(event) { switch_field(event); });
		$('.su_move_field_down').bind('click', {dir: 1}, function(event) { switch_field(event); });
	});
</script>
	<?php
	}
}
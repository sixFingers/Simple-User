<?php

class wpSimpleProfile
{
	var $login_url = '';
	var $profile_url = '';
	var $user_id;
	
	public function __construct($user_id = FALSE)
	{
		/**
		 * Add filters and actions
		 */
		$this->login_url = wpSimpleUser::$login_url;
		$this->profile_url = wpSimpleUser::$profile_url;
		$this->user_id = $user_id;
		//register_activation_hook(__FILE__, array($this, 'pluginInstall'));  
		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('register_form',array($this, 'register_form'));
		add_action('register_post',array($this, 'register_post'),10,3);
		//add_action('user_register', array($this, 'register_additional_fields'));
		$this->init(); // only variables setup and short code setup here, so no actions needed.
	}
	
	function admin_print_scripts() {
        wp_enqueue_script('jquery');
    }
	
	function admin_menu() {
		$page = add_submenu_page('users.php', 'Impostazioni profilo', 'Impostazioni profilo', 'manage_options', 'simple-user-profile', array($this, 'profile_editor_parse'));
		add_action("admin_print_scripts-$page", array($this, 'admin_print_scripts'));
	}
    
	function init()
	{
		$this->get_profile_fields();
		add_shortcode('profile', array($this, 'frontend_profile'));
	}
	
	function validate_fields($page = 'login', $showMessage = false) {
		$condition = $page == 'login' ? 'reg_required': 'visible';
		$i = 0; 
		$showMessage = $showMessage ? $showMessage: array();
		foreach($this->profileFields as $field => $properties) {
			if($this->profileFields[$field][$condition]!= '1') continue;
			if(isset($_POST['su_field']) && array_key_exists($field, $_POST['su_field'])) {
    			$value = esc_html(trim($_POST['su_field'][$field]));
				$error = false;
				// If it's submitted, but empty and the field is required
    			if ($value == '') {
    				$error = 'Il campo <strong>'.$this->profileFields[$field]['label'].'</strong> è richiesto.<br />';
    				$showMessage[] = $error;
					$error = true;
				}
				// Do data format validation
				if($field == 'user_email' && !is_email($value)) {
					$error = 'Inserisci una <strong>'.$this->profileFields[$field]['label'].'</strong> valida.<br />';
					$showMessage[] = $error;
					$error = true;
				}
				if(!$error) {
					update_user_meta($this->user_id, $field, $value);
				}
    		}
			else {
				// If it's not being submitted, but required
				if($this->profileFields[$field][$condition] == '1') {
					$error = 'Il campo <strong>'.$this->profileFields[$field]['label'].'</strong> è richiesto.<br />';
    				$showMessage[] = $error;
				}
			}
    		$i++; 
		}
		
		return $showMessage;
	}
	
	function frontend_profile() {
		$showMessage = array();	
		if($_POST['action'] == 'su_update_frontend_profile') {
			if (!wp_verify_nonce($_POST['simple_user_frontend_profile_nonce'], 'su_update_frontend_profile')) {
				$showMessage[] = '<strong>ERRORE:</strong> I dati inviati non sono regolari.';
			}	
			else {
				$showMessage = $this->validate_fields('profile', $showMessage);
			}	
		}
		
		if(!empty($showMessage)) echo '<div class="message error">'.implode('', $showMessage).'</div>'; ?>
		<form action="" method="post" name="profileform" id="profileform">
    		<?php wp_nonce_field('su_update_frontend_profile','simple_user_frontend_profile_nonce'); ?>
    		<input type="hidden" name="action" value="su_update_frontend_profile"> 
    		<?php $this->show_additional_fields('profile'); ?>
			<input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="Get New Password" tabindex="100">
		</form>
		<?php
	}
	
	function register_form() {
		$this->show_additional_fields('login');
	}
	
	function register_post($login, $email, $errors) {
		$showMessage = $this->validate_fields('login', array());
		$e = 0;
		foreach($showMessage as $error) {
			$errors->add('registration_field_error'.$e, $error);
			$e ++;
		}
	}
	
	function show_additional_fields($page = 'login') {
		$condition = $page == 'login' ? 'reg_required': 'visible';
		//print_r($this->profileFields);
		$i = 0; foreach($this->profileFields as $field => $properties): if($properties[$condition] == '1'): ?>
		
		<?php
		switch($properties['type']) {
			case 'text':?>
<p><label><?php echo $properties['label']; ?></label><br />
<input type="text" value="<?php echo get_user_meta($this->user_id, $field, true); ?>" name="su_field[<?php echo $field; ?>]" /></p>
			<?php break;
			case 'checkbox':
				$value = get_user_meta($this->user_id, $field, true);
				?>
<p class="checkbox"><label><input type="checkbox" value="1" name="su_field[<?php echo $field; ?>]" <?php echo isset($_POST['su_field'][$field]) ? 'checked="checked"': ''; ?> /> <?php echo $properties['label']; ?></label></p>
			<?php break;
			case 'select':?>
				
			<?php break;
		}
		?>
    	<?php $i++; endif; endforeach;
	}
	
	function get_profile_fields() {
		//delete_option('su_profile_settings');
		$this->profileFields = get_option('su_profile_settings');
		//die(print_r($this->profileFields));
		if(!$this->profileFields) {
			$this->profileFields = array( 
	    		'user_login' => array(
	    			'label' => 'Username', 
	    			'required' => '1', 
	    			'visible' => '1', 
					'reg_required' => '1', 
	    			'order' => '0', 
	    			'type' => 'text'), 
				'user_email' => array(
					'label' => 'Email', 
					'required' => '1', 
					'visible' => '1', 
					'reg_required' => '1', 
					'order' => '1', 
					'type' => 'text'), 
				'user_pass' => array(
					'label' => 'Password', 
					'required' => '1', 
					'visible' => '0', 
					'reg_required' => '1', 
					'order' => '2', 
					'type' => 'text'), 
				'user_nicename' => array(
					'label' => 'Nicename', 
					'required' => '0', 
					'visible' => '0', 
					'reg_required' => '0', 
					'order' => '3', 
					'type' => 'text'), 
				'user_url' => array(
					'label' => 'Website', 
					'required' => '0', 
					'visible' => '0', 
					'reg_required' => '0', 
					'order' => '4', 
					'type' => 'text'), 
				'display_name' => array(
					'label' => 'Display Name', 
					'required' => '0', 
					'visible' => '0', 
					'reg_required' => '0', 
					'order' => '5', 
					'type' => 'text'), 
				'nickname' => array(
					'label' => 'Nickname', 
					'required' => '0', 
					'visible' => '0', 
					'reg_required' => '0', 
					'order' => '6', 
					'type' => 'text'), 
				'first_name' => array(
					'label' => 'First Name', 
					'required' => '1', 
					'visible' => '1', 
					'reg_required' => '0', 
					'order' => '7', 
					'type' => 'text'), 
				'last_name' => array(
					'label' => 'Last Name', 
					'required' => '1', 
					'reg_required' => '0', 
					'visible' => '1', 
					'order' => '8', 
					'type' => 'text'),  
				'description' => array(
					'label' => 'Description', 
					'required' => '0', 
					'visible' => '0', 
					'reg_required' => '0', 
					'order' => '9', 
					'type' => 'text'), 
				'user_registered' => array(
					'label' => 'Registration Date', 
					'required' => '0', 
					'visible' => '0', 
					'reg_required' => '0', 
					'order' => '10', 
					'type' => 'text'), 
				'role' => array(
					'label' => 'Role', 
					'required' => '1', 
					'visible' => '0', 
					'reg_required' => '0', 
					'order' => '11', 
					'type' => 'text'), 
				'jabber' => array(
					'label' => 'Jabber', 
					'required' => '1', 
					'visible' => '0', 
					'reg_required' => '0', 
					'order' => '12', 
					'type' => 'text'), 
				'aim' => array(
					'label' => 'Aim', 
					'required' => '1', 
					'visible' => '0', 
					'reg_required' => '0', 
					'order' => '13', 
					'type' => 'text'), 
				'yim' => array(
					'label' => 'Yim', 
					'required' => '1', 
					'visible' => '0', 
					'reg_required' => '0', 
					'order' => '14', 
					'type' => 'text'), 
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
				'label' => array_key_exists($field, $su_field_title) ? $su_field_title[$field]: '', 
				'reg_required' => in_array($field, $su_field_reg_required) ? '1': '0', 
				'required' => in_array($field, $su_field_required) ? '1': '0', 
				'visible' => in_array($field, $su_field_editable) ? '1': '0', 
				'order' => ''.$f, 
				'type' => $su_field_type[$field]
			);
			$f ++;
		}
		
		function field_order_sort($a, $b) {
			return (intval($a['order']) > intval($b['order'])); //only doing string comparison
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
        	<form action="?page=simple-user-profile" method="post" name="profileform" id="profileform">
        		<?php wp_nonce_field( 'simple_user_profile_nonce','su_update_settings' ); ?>
        		<input type="hidden" name="action" value="su_update_settings"> 
	        	<div id="icon-users" class="icon32"><br /></div>
	        	<h2>User Meta Editor</h2>    
	            
	            <h3>Campi dell'utente</h3>
	            <div class="tablenav top">
					<div class="alignleft actions">
						Nome del campo 
						<input type="text" value="" name="new_field_name" id="new_field_name" /> 
						Etichetta del campo 
						<input type="text" value="" name="new_field_label" id="new_field_label" /> 
						Tipo di campo 
						<select name="new_field_type" id="new_field_type">
							<option value="text">Text</option>
							<option value="checkbox">Checkbox</option>
							<option value="select">Select</option>
						</select>
						<input type="button" onclick="add_field()" class="button-secondary action" value="Aggiungi"  /> 
					</div>
				</div>
	            <table class="wp-list-table widefat" style="width:840px;">
	            	<thead>
	            		<tr>
	            			<th scope="col">Campo</th>
	            			<th scope="col">Registrazione</th>
	            			<th scope="col">Profilo</th>
	            			<th scope="col">Visibile</th>
	            			<th scope="col">Tipo</th>
	            			<th scope="col">Ordine</th>
	            		</tr>
	            	</thead>
					<tbody id="field_table">
						<?php $i = 0; foreach($this->profileFields as $field => $properties): ?>
							<tr valign="top">
								<td width="30%"><input type="text" value="<?php echo $properties['label']; ?>" name="su_field_title[<?php echo $field; ?>]" /></td>
								<td width="10%"><input type="checkbox" name="su_field_reg_required[]" value="<?php echo $field; ?>" <?php checked(true, $properties['reg_required']) ?> /></td>
								<td width="10%"><input type="checkbox" name="su_field_required[]" value="<?php echo $field; ?>" <?php checked(true, $properties['required']) ?> /></td>
								<td width="10%"><input type="checkbox" name="su_field_editable[]" value="<?php echo $field; ?>" <?php checked('1', $properties['visible']) ?> /></td>
								<td width="25%">
									<select name="su_field_type[<?php echo $field; ?>]" />
										<option value="text" <?php selected( $properties['type'], 'text' ); ?>>Text</option>
										<option value="checkbox" <?php selected( $properties['type'], 'checkbox' ); ?>>Checkbox</option>
										<option value="select" <?php selected( $properties['type'], 'select' ); ?>>Select</option>
									</select>
								</td>
								<td width="15%"><a href="javascript:void(0);" class="su_move_field_up">↑</a> <a href="javascript:void(0);" class="su_move_field_down">↓</a> <input type="text" class="su_field_order small-text" name="su_field_order[<?php echo $field; ?>]" value="<?php echo $i; ?>" /></td>
							</tr>
						<?php $i++; endforeach; ?>
					</tbody>
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
	
	function add_field() {
		$fieldname = $("#new_field_name").val();
		$fieldlabel = $("#new_field_label").val();
		$fieldtype = $("#new_field_type").val();
		if($fieldname == '') return;
		$i = $('tbody').children().length;
		
		$('#field_table').append(
'<tr valign="top"><td width="30%"><input type="text" value="'+$fieldlabel+'" name="su_field_title['+$fieldname+']" /></td><td width="10%"><input type="checkbox" name="su_field_reg_required['+$fieldname+']" value="1" /></td><td width="10%"><input type="checkbox" name="su_field_required['+$fieldname+']" value="1" /></td><td width="10%"><input type="checkbox" name="su_field_editable['+$fieldname+']" value="1" /></td><td width="25%"><select name="su_field_type['+$fieldname+']"><option value="text" '+($fieldtype=='text' ? 'selected="selected"': '')+'>Text</option><option value="checkbox" '+($fieldtype=='checkbox' ? 'selected="selected"': '')+'>Checkbox</option><option value="select" '+($fieldtype=='select' ? 'selected="selected"': '')+'>Select</option></select></td><td width="15%"><a href="javascript:void(0);" class="su_move_field_up">↑</a> <a href="javascript:void(0);" class="su_move_field_down">↓</a> <input type="text" class="su_field_order small-text" name="su_field_order['+$fieldname+']" value="'+$i+'" /></td></tr>'
		);
	}
	
	$(document).ready(function () {
		$('.su_move_field_up').bind('click', {dir: -1}, function(event) { switch_field(event); });
		$('.su_move_field_down').bind('click', {dir: 1}, function(event) { switch_field(event); });
	});
</script>
	<?php
	}
}
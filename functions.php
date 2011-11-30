<?php 

//Some common function to share over the plugin
//Create input field 
function su_createInput($name, $value='', $type='', $config=array(), $options=array()){
    $id = isset($config['id']) ? $config['id'] : $name;
    $class = isset($config['class']) ? $config['class'] : '';
    $style = isset($config['style']) ? $config['style'] : '';
    
    if(!$type OR $type == 'text' OR  $type == 'textbox'){
        $input = "<input type='text' name='$name' id='$id' class='$class' value='$value' />";
    }elseif($type == 'select' OR $type == 'dropdown'){
        if(isset($options)){
            $input = "<select name='$id' id='$name' class='$class'>";
            foreach($options as $key => $val){
                $key = ($config['have_key']) ? $key : $val;                         
                $input .= ($val == $value) ? "<option value='$key' selected='true'>$val</option>" : "<option value='$key'>$val</option>";
            }
            $input .= "</select>";
            $input;
        }
    }
    elseif($type == 'file'){
        $input = "<input type='file' name='$name' id='$id' class='$class' value='$value' />";
        $form_name = isset($config['form_name']) ? $config['form_name'] : '';
        
    	/*?><script type="text/javascript">
    		var form = document.getElementById($form_name);
    		form.encoding = 'multipart/form-data';
    		form.setAttribute('enctype', 'multipart/form-data');
    	</script><?php  */               
    }   
    return $input;
}

//Upload file. return uploaded array(file,url,type,error) on success
function su_fileUpload($name, $mimes = array()){           
    if ( !empty( $_FILES[$name]['name'] ) ){
        if(!$mimes){
			$mimes = array(
				'jpg|jpeg|jpe' => 'image/jpeg',
				'gif' => 'image/gif',
				'png' => 'image/png',
				'bmp' => 'image/bmp',
				'tif|tiff' => 'image/tiff'
			);                    
        }
	
		// front end (theme my profile etc) support
		if (!function_exists( 'wp_handle_upload'))
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
	
		return wp_handle_upload( $_FILES[$name], array( 'mimes' => $mimes, 'test_form' => false ) );                      
    }              
}

function su_profile_complete($userId) {
	$profileTemplate = get_option('su_profile_settings');	
	foreach($profileTemplate as $field => $properties) {
		if($profileTemplate[$field][1]!= '1') continue;
		$meta = get_user_meta($userId, $field, true);
		if(empty($meta)) return false;
	}
	
	return true;
}

<?php

/*

poform - PHP 5 form object generator
====================================

Ronaldo Barbachano Oct 2011

 -- Idea is to create a library that will automatically generate a form to manipulate
 a php object. Why? Ultimately to tie this in with my other libraries (couchCurl/html5_core)
 to create a couch/php framework. This component would aid the the manipulation of 
 json objects (when using jser)
 
 HTML 5 Stuff to Support 
 slider, required validation tag 
*/

define('SHOW_CLASS',false);

// without fieldsets may be useful for quick 'list' views ..
define('SHOW_OUTER_FIELDSET',true);

define('SHOW_INNER_FIELDSET',true);

// Display either a label (if show label true) or text of the variable (class parameter) name
define('SHOW_VAR',false);
// will probably want to override this setting for radios/checkboxes so we can select something...
define('SHOW_LABEL',true);

// for 'text' input definitions
define('SHOW_PLACEHOLDER',true);

define('SHOW_CSS_CLASS',true);

define('FORM_METHOD','POST');
// turns the input type into a class for easier selection
define('SHOW_CSS_ID',false);
// get current page ??
define('FORM_ACTION',$_SERVER["REQUEST_URI"]);
// would be cooler to just pass around html5_core tag objects ..
class poform{

	public static function make($object,$i=false,$settings=NULL){
	// settings would be an array to define how to handle basic form settings, and 
	// maybe some options ? settings like form attribute settings basically
		// puts a loaded object into a form with fields
		
		$r .= ($i == false?'<form action="'.FORM_ACTION.'" method="'.FORM_METHOD.'">' . (SHOW_OUTER_FIELDSET?'<fieldset>':'') :'');
		
		foreach($object as $a=>$b){
			$r .=  (is_array($b) || is_object($b)?self::make($b,true): $b) ;
		}
		return $r. (SHOW_OUTER_FIELDSET && $i == false?'</fieldset>':'') . ($i == false?'<input '.(SHOW_CSS_CLASS?'class="submit" ' :'').'type ="submit"/></form>':'');
	}
	
	public static function build_selection_string($array){
	// codes an assoc. array into the proper syntax for creating a simple select list
		foreach($array as $key=>$value)
			$result .= $key.'-'.$value.':';
		return 'select '.$result;
	
	}

	private static function build_arr($array,$field_name,$type,$required=NULL){
	// three functions in one1
		$field_name = trim($field_name);
		if($type == 'number' || $type == 'range'){
		// the first 'min' processing is tricky ... because of the zero value setting off a false false case
		// may want to attempt to type cast each of these values as strings use foreach loop to build instead of all this array processing code
			return (SHOW_INNER_FIELDSET && $type != 'radio' && $type != 'checkbox' ?'<fieldset>':'') . self::labeler($field_name) . "\n<input type='$type' name='$field_name'" . ($required!=NULL?' required ': NULL).(SHOW_CSS_CLASS?" class='$type' ":'') . (SHOW_CSS_ID?" id='$field_name' ":'') .(count($array) > 1? ($array[0] !== 0?" min='".$array[0]."' ":'') . ($array[1]?" max='".$array[1]."' ":'') . ($array[2]?" step='".$array[2]."' ":'') . ($array[3] || $_POST[$field_name] ?" value='".($_POST[$field_name]?$_POST[$field_name]:$array[3])."'":'') : '')."/>\n" . (SHOW_INNER_FIELDSET && $type != 'radio' && $type != 'checkbox' ?'</fieldset>':'');	
		}else{
		// add inner fieldset processing here... avoid writing fields for options ..
			foreach($array as $k=>$v){
			// force a label to show for certain fields like radio/checkboxes ??
				if($type == 'radio' || $type == 'checkbox')
					$v = self::labeler($v,$force);	
				
				$r .= (SHOW_INNER_FIELDSET?'<fieldset>':'') . ($type != 'select'? ($type == 'checkbox' || $type == 'radio' ? $v:'')   . "<input type='$type' name='$field_name'". ($required!=NULL?' required ': NULL) .(SHOW_CSS_CLASS?" class='$type' ":'') . (SHOW_CSS_ID?" id='$field_name' ":'').( $k != '' && $k != '0' ?" value='".($_POST[$field_name]?$_POST[$field_name]:$k) ."'" . (($_POST[$field_name] == $k || ($k == 1 && $_POST[$field_name] == 'on'))  ?  ($type == 'radio'?' checked ':'') :'' ): ($_POST[$field_name]?" value='".$_POST[$field_name]."'":''))."/>" .(SHOW_INNER_FIELDSET?'</fieldset>':'') : "<option value='$k' ".($_POST[$field_name] == $k?' selected="selected" ':'').">$v</option>\n" ) ;
				
			}
			return   self::labeler($field_name) .($type != 'select'? $r : "\n" .(SHOW_INNER_FIELDSET?'<fieldset>':'').'<select name="'.$field_name.'"'.($required!=NULL?' required ': NULL).'>'."\n".'<option value="">Select '.str_replace('_',' ',$field_name).'</option>' . $r . '</select>' . "\n" . (SHOW_INNER_FIELDSET?'</fieldset>':''));
		}
	}
	
	private static function labeler($field_name,$force=false){
	// Might want to test if the show_var is (commented out) disabled.. may not return NULL
	// checks definitions to determine wether to show labels or just the var name..
		$ident = ucwords(str_replace('_',' ',$field_name));
		return (SHOW_LABEL|| $force?"<label for='$field_name'>$ident</label>":(SHOW_VAR?$ident:''));
	}

	private static function decode_string($s){
		$s = trim($s);
		$s = explode(' ',$s,2);
		$s = explode(':',$s[1]);
		// if the first s is 'number' then we process the inner values slightly differently
		// we could just return the text it needs to fill in min/max ?
		// otherwise nothing will be returned?
		
		foreach($s as $key=>$value){
			$temp = explode('-',$value,2);
			$select[$temp[0]]=$temp[1];
		}
		return $select;	
	}

	public static function load($object,$id=NULL,$classname=NULL,$alt_id=NULL){
	// creates the insides of a form based on an object
	// _d refers to parameters to store as hidden , useful for multi-tiered and conditional form selections
	// to store the previous screens/states selection to reduce user confusion
		if($object->_r){
		// _r refers to 'required', still implementing this functionality to do object validation
		// before attempting to make a couch call
			$_r = $object->_r;
			unset($object->_r);
		}

		if($object->_d && $_POST){
		// refers to 'hidden' variables, those used for forms or database identification
			foreach($object as $k=>$v){
				if(in_array($k,$object->_d)){
				//	$object->hidden [$k]= $_POST[$k];
					// 	public $email = array('date:email_address'=> array(''=>'date') );
					// this doesnt set the value although ...
					$object->$k = array("hidden:$k"=>array(''=>'hidden'));
					//unset($object->$k);
				}
			
			}
			unset($object->_d);
		}
		
		$select_array = array('select','checkbox','password','radio','sumbit','email','search','number','date','hidden');
		
		if($classname != NULL && $id != NULL){
		
		$id = explode(':',$id,2);
			if(count($id) == 2)
				$a_id = $id[1];
			$id = $id[0];
			
			if(is_array($object) && in_array($id ,$select_array )){
				if($id == 'hidden') return "<input type='hidden' name ='$alt_id' value='".$_POST[$alt_id]."'/>";
				
				return self::build_arr($object , $classname.($a_id?$a_id:$alt_id),$id,NULL,(is_array($_r) && in_array($alt_id,$_r) ?true:NULL));
			}
			// amprohphise this statement...		
			elseif(!is_array($object) && !is_object($object)){
				foreach($select_array as $s){
					if(!(strpos($object,"$s ") === false )){
						return self::build_arr(self::decode_string($object),$classname.$id,$s,NULL,(is_array($_r) && in_array($id,$_r) ?true:NULL));
						}
						// need to pass build_arr a 'required' flag 
				}		
			// used to check for post var etc.. 
			$final_id = trim($classname.$id);
			return self::labeler($final_id,$id) . " <input type='text'". (is_array($_r) && in_array($id,$_r) ?' required ':NULL) . (!SHOW_PLACEHOLDER?NULL: " placeholder='".ucwords(str_replace('_',' ',$id))."' ") ." name='".$final_id."' ".($object != '0' || $object != ''?  ($_POST[$final_id] ?  " value='".$_POST[$final_id]."'":'')  ." ":'')."/>";
			}
		}
		// Recurse... 
		elseif(is_array($object)){
			foreach($object as $x=>$y){
				$r [$x] = self::load($y,$x,$classname);
			}		
		}else{
			// to pass back to recursive functions to build the input
			$class_name = (!SHOW_CLASS? ' ':get_class($object));
			foreach($object as $x=>$y){
			// check to see if an entry is contained in the a parameter
			// to do validations or create drop down menus with values 
				if(is_array($y) || is_object($y)){
					foreach($y as $a=>$b){
						$r[$x][$a] = self::load($b,$a,$class_name,$x);
					}
				}else{
					$r[$x] = self::load($y,$x,$class_name,$x);
					}
				}	
			}
			return $r;
		}
}
<?php
/*

poform - PHP 5 form object generator
====================================

Ronaldo Barbachano Oct 2011

 -- Idea is to create a library that will automatically generate a form to manipulate
 a php object. Why? Ultimately to tie this in with my other libraries (couchCurl/html5_core)
 to create a couch/php framework. This component would aid the the manipulation of 
 json objects (when using jser)
 
 To DO - Automatic Post variable processing ... prefill values where available..
 
 HTML 5 Stuff to Support 
 number, date,slider, required validation tag (maybe look for '*' char in key name
*/

define('SHOW_CLASS',false);

// without fieldsets may be useful for quick 'list' views ..
define('SHOW_FIELDSET',true);

// Display either a label (if show label true) or text of the variable (class parameter) name
define('SHOW_VAR',true);

define('SHOW_LABEL',true);

// for 'text' input definitions
define('SHOW_PLACEHOLDER',true);

define('FORM_METHOD','GET');

define('FORM_ACTION','');
// would be cooler to just pass around html5_core tag objects ..
class poform{

	public static function make($object,$i=false,$settings=NULL){
	// settings would be an array to define how to handle basic form settings, and 
	// maybe some options ? settings like form attribute settings basically
		// puts a loaded object into a form with fields
		
		echo ($i == false?'<form action="'.FORM_ACTION.'" method="'.FORM_METHOD.'">':'') . (SHOW_FIELDSET?"<fieldset>":'');
		
		foreach($object as $a=>$b)
			echo(is_array($b) || is_object($b)?self::make($b,true): $b);
		
		echo (SHOW_FIELDSET?'</fieldset>':'') . ($i == false?'<input type ="submit"/></form>':'');
	}
	
	public static function build_selection_string($array){
	// codes an assoc. array into the proper syntax for creating a simple select list
		foreach($array as $key=>$value)
			$result .= $key.'-'.$value.':';
		return 'select '.$result;
	
	}

	private static function build_arr($array,$field_name,$type){
	// three functions in one1
	$field_name = trim($field_name);

		foreach($array as $k=>$v)
			$r .=  ($type != 'select'? "<input type='$type' name='$field_name'".( $k != '' && $k != '0' ?" value='$k'":'')."/>" . ($type == 'checkbox' || $type == 'radio' ? $v:'') : "<option value='$k'>$v</option>\n" ) ;
		return   self::labeler($field_name,$ident) .($type != 'select'? $r : "\n" .'<select name="'.$field_name.'">'."\n".'<option value="">Select '.str_replace('_',' ',$field_name).'</option>' . $r . '</select>' . "\n");
	
	}
	
	private static function labeler($field_name,$ident){
	// Might want to test if the show_var is (commented out) disabled.. may not return NULL
		if(!SHOW_VAR) return NULL;
	// checks definitions to determine wether to show labels or just the var name..
		$ident = ucwords(str_replace('_',' ',$field_name));
		return (!SHOW_LABEL?$ident:"<label for='$field_name'>$ident</label>");
	
	}

	private static function decode_string($s){
	// turns a coded string into a usable array for 'build_arr'
		$s = trim($s);
	// add extra space to allow designation of special options like 
	// * = required
		$s = explode(' ',$s,2);
		$s = explode(':',$s[1]);
		foreach($s as $key=>$value){
			$temp = explode('-',$value,2);
			$select[$temp[0]]=$temp[1];
		}
		return $select;	
	}

	public static function load($object,$id=NULL,$classname=NULL,$alt_id=NULL){
	// creates the insides of a form based on an object
	// support more types like text area .. also support html 5 types where available
	// for date and email / phone number etc.

		$select_array = array('select','checkbox','password','radio','sumbit','email','search','email');
		if($classname != NULL && $id != NULL){
			$id = explode(':',$id,2);
			if(count($id) == 2)
				$a_id = $id[1];
			$id = $id[0];
			if(is_array($object) && in_array($id ,$select_array )){
					return self::build_arr($object , $classname.($a_id?$a_id:$alt_id),$id);
			}
			// amprohphise this statement...		
			elseif(!is_array($object) && !is_object($object)){
				foreach($select_array as $s){
					if(!(strpos($object,"$s ") === false ))
						return self::build_arr(self::decode_string($object),$classname.$id,$s);
				}		
			// used to check for post var etc.. 
			$final_id = trim($classname.$id);
			return self::labeler($final_id,$id) . " <input type='text' ".(!SHOW_PLACEHOLDER?NULL: "placeholder='".ucwords(str_replace('_',' ',$id))."' ") ."id='".$final_id."' ".($object != '0' || $object != ''?"value='$object' ":'')."/>";
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
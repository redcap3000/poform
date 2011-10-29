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

// allow couch to automatically handle and commpress ID's based on parameter names
// all params with _id will be converted into ID's .. (need to do an implicit end check to 
// avoid issues however with naming conventions)
define('COUCH_AUTO_ID', true);
// when used with above removes the 'id' from the recorded record
define('COUCH_REMOVE_ID',true);

// would be cooler to just pass around html5_core tag objects ..
class poform{

	public static function make($object,$i=false,$settings=NULL){
	// settings would be an array to define how to handle basic form settings, and 
	// maybe some options ? settings like form attribute settings basically
		// puts a loaded object into a form with fields
		// unset any values with an underscore ??
		unset ($object->_r,$object->_d,$object->_f,$object->_p);
		
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
			return (SHOW_INNER_FIELDSET && $type != 'radio' && $type != 'checkbox' ?'<fieldset>':'') . self::labeler($field_name) . "\n<input type='$type' name='$field_name'" . (SHOW_CSS_CLASS?" class='$type' ":'') . (SHOW_CSS_ID?" id='$field_name' ":'') .(count($array) > 1? ($array[0] !== 0?" min='".$array[0]."' ":'') . ($array[1]?" max='".$array[1]."' ":'') . ($array[2]?" step='".$array[2]."' ":'') . ($array[3] || $_POST[$field_name] ?" value='".($_POST[$field_name]?$_POST[$field_name]:$array[3])."'":'') : '')."/>\n" . (SHOW_INNER_FIELDSET && $type != 'radio' && $type != 'checkbox' ?'</fieldset>':'');	
		}else{
		// add inner fieldset processing here... avoid writing fields for options ..
			foreach($array as $k=>$v){
			// force a label to show for certain fields like radio/checkboxes ??
				if($type == 'radio' || $type == 'checkbox')
					$v = self::labeler($v,$force);	
				
				$r .= (SHOW_INNER_FIELDSET?'<fieldset>':'') . ($type != 'select'? ($type == 'checkbox' || $type == 'radio' ? $v:'')   . "<input type='$type' name='$field_name'".(SHOW_CSS_CLASS?" class='$type' ":'') . (SHOW_CSS_ID?" id='$field_name' ":'').( $k != '' && $k != '0' ?" value='".($_POST[$field_name]?$_POST[$field_name]:$k) ."'" . (($_POST[$field_name] == $k || ($k == 1 && $_POST[$field_name] == 'on'))  ?  ($type == 'radio'?' checked ':'') :'' ): ($_POST[$field_name]?" value='".$_POST[$field_name]."'":''))."/>" .(SHOW_INNER_FIELDSET?'</fieldset>':'') : "<option value='$k' ".($_POST[$field_name] == $k?' selected="selected" ':'').">$v</option>\n" ) ;
				
			}
			return   self::labeler($field_name) .($type != 'select'? $r : "\n" .(SHOW_INNER_FIELDSET?'<fieldset>':'').'<select name="'.$field_name.'"'.'>'."\n".'<option value="">Select '.str_replace('_',' ',$field_name).'</option>' . $r . '</select>' . "\n" . (SHOW_INNER_FIELDSET?'</fieldset>':''));
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
	

	public static function load($object,$id=NULL,$classname=NULL,$alt_id=NULL,$required=NULL){
	// uses a variable _r to define a value that will allow a record to be inserted into a database.
	// in the object class that gets passed into load, simply set the _r to the desired variables
	// inside the construct method.
	
	// If you have conditional fields, that depend on others, you can create a logical flow by
	// manipulating the _d variable and the _r variables to pass values to the next object selection
	// state
	
	
		if($object->_r){
			$_r = $object->_r;
			unset($object->_r);
		}
	// this _p is for a framework I'm developing that uses poform and a _p array structure
	// to allow developers to define conditional forms (through additional manipulation of _r,_f and
	// _d, evetually it will be put here, am having class focus issues
		unset($object->_p);
		
			unset($missing);
		if($_r && $_POST && $required == NULL){
			if($_POST){
				foreach($_r as $value)
					if(!array_key_exists($value,$_POST) || $_POST[$value] == '')
						$missing []= $value;	
					else
						$missing = false;
					
				
			}
		if($missing == false){
		// probably should abstract this more, and possibly class it, this is getting 
		// verbose/specialized
			// convert 'select action' into database name
			$db = $_POST['select_action'];
			unset($_POST['select_action']);
			$id [] = time();
			// additional 'id' fields in the _id can be designated by determining the load
			// order for each class, in relation to the _d parameter
			// stores keys with _id as the doc title (_id) and unsets them from post 
			// to save space
			// each structure is designated inside the class that relates to the database in couch
			
			if(COUCH_AUTO_ID)
				foreach($_POST as $k=>$v){
				// make option to automatically add post variables ending in _id to the couch_id...
				
					if($v!= 0 && is_int($v + 0) && strpos($k,'_id',0)){
						$id []= $v;
						if(COUCH_REMOVE_KEY) unset($_POST[$k]);
					}
				
				}
			$id = implode(':',$id);
			foreach($_POST as $key =>$value){
			// forcing integers to store as so.. instead of quoted integers
				
				if($value != '' && is_int($int =$value + 0 ) && $int != 0)
					$_POST[$key] =  $int;
			}
			
			$_POST = array_filter($_POST);
			// numerical values still not forcing to numbers...
			$json = json_encode($_POST);
			// create config option for number base encoding
			// to do create something to convert a encoded id to a time stamp, and eventually
			// into a displayable object ?
			$result =couchCurl::put($json,couchCurl::handle_couch_id($id,36),$db );			
			// look up new record and get the REV so we can update the record properly
			// this result gets added to the top and then displays a blank form .. need to create
			// array structures to handle this functionality after we clean this code up a loott..
			echo ($result?'<h3>Record added</h3>': 'Problem With Insert');
			// if/when successful then take care to either hide the form or what ? for tasks we dont want people
			// to edit existing tasks ... but dont want to add that functionality in poform
			}
		}

		if($object->_d && $_POST){
		// refers to 'hidden' variables, those used for forms or database identification
			foreach($_POST as $k=>$v){
				if(in_array($k,$object->_d)){
					// 	public $email = array('date:email_address'=> array(''=>'date') );
					// this doesnt set the value although ...
					$object->$k = array("hidden:$k"=>array(''=>'hidden'));
					//unset($object->$k);
				}
			
			}
		unset($object->_d);
	
		}		
		$select_array = array('select','checkbox','password','radio','sumbit','email','search','number','date','hidden');
	
		unset($object->_d,$object->_f,$object->_p,$object->_r);	
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
			unset($object->_d);
			$final_id = trim($classname.$id);
			return self::labeler($final_id,$id) . " <input type='text'". (is_array($required) && array_key_exists($id,$required) ?' required ':NULL) . (!SHOW_PLACEHOLDER?NULL: " placeholder='".ucwords(str_replace('_',' ',$id))."' ") ." name='".$final_id."' ".($object != '0' || $object != ''?  ($_POST[$final_id] ?  " value='".$_POST[$final_id]."'":'')  ." ":'')."/>";
			}
		}
		// Recurse... 
		elseif(is_array($object)){
			foreach($object as $x=>$y){
				$r [$x] = self::load($y,$x,$classname,NULL,$required);
			}		
		}else{
			// to pass back to recursive functions to build the input
			$class_name = (!SHOW_CLASS? ' ':get_class($object));
			if(is_object($object) || is_array($object))
			foreach($object as $x=>$y){
			// check to see if an entry is contained in the a parameter
			// to do validations or create drop down menus with values 
				if(is_array($y) || is_object($y)){
					foreach($y as $a=>$b){
						$r[$x][$a] = self::load($b,$a,$class_name,$x,$required);
					}
				}else{
					$r[$x] = self::load($y,$x,$class_name,$x,$required);
					}
				}	
			}
			return $r;
		}
}

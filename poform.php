<?php
define('SHOW_CSS_CLASS',false);
/*

poform
January 2012
Ronaldo Barbachano 

Dev version -


What is poform

poform is a PHP class that works in conjunction with a design pattern to create web forms in
an object oriented fashion relying mostly on passing the $_POST parameter and the creation
of array structures to define specific field functionality.


So what do I need to do?

Create classes, define class parameters (that will eventually become form input) and use 
special array structures to manipulate (dynamically and statically) what gets rendered and how.

What about Sessions?

Use couchCookie, which plugs directly into poform.

What about my couch database?

Use couchCurl, poform currently only works with this library, but potientially the objects generated
by poform could be stored into any database or even a file system as json.

*/

class poform{
	function __construct(){
	// yess... so if a class extends poform it will automatically load and 
	// render when you create an object that extends poform, given each class has a parent::__construct call
		self::make(self::load($this));
	}

	public static function make($object,$i=false,$settings=NULL){
		$r = ($i == false?"\n".'<form action="'.$_SERVER["REQUEST_URI"].'" method="POST">' . "\n<fieldset>" :'');
		foreach($object as $a=>$b){
			$r .=  (is_array($b) || is_object($b)?self::make($b,true): $b) ;
		}
		return $r. ($i == false?"\n</fieldset>":'') 
		.
		($i == false  ? self::make_input('submit') . 
		
		"\n</form>\n":'');
	}
	
	private static function make_input($type,$name=NULL,$value=NULL,$placeholder=NULL,$inner=NULL){
		$name = trim($name);
		$type = trim($type);

		if($type == 'html')
			return $value;
		$a_names = array('type','name','value','placeholder','inner');

		foreach(func_get_args() as $loc=>$value)
			if($value != NULL && $loc != 4)
				$a[trim($a_names[$loc])] = trim($value);

		if(isset($_POST[$name]) && $_POST[$name] != ''){
		// check value against type and 'select' and populate $inner value with updated settings .. .?
			$a['value'] = $_POST[$name];
			// weird bug with radio types that adds an extra comma @ such a PITA to debug ..
			if($type == 'radio' || $type = 'checkbox'){
				if( trim($value)  == $_POST[$name]. "'" || $_POST[$name] == 'on' )
				$inner .=   ' checked ';
			}elseif($type == 'select'){
			// to do ... must write option instead of input ... a bit tricky ...
			
			}
			// do select/ and checkbox processing here too at some point ...
		}		
				
		if($type == 'radio' && $value != NULL)
		// str replace is a quick hack to remove the extra quotes when working with radio values ... not sure why its there... yet..
			$a['value'] = str_replace("'","",$value);
		foreach($a as $key=>$value)
			$r []= "$key='$value'";

		return    '<input ' . implode($r,' ') .  ($inner != NULL? $inner : NULL) . '/>' ;
	}
	
	private static function pf_check($field_name,$alt){
		return ($alt !=NULL? ($_POST[$field_name]?" value='" . $_POST[$field_name]:$alt) ."'" :NULL) ;
	}
	
	private static function build_arr($array,$field_name,$type,$required=NULL){
		if($type == 'number' || $type == 'range'){
			return ( $type != 'radio' && $type != 'checkbox' ?'<fieldset>':'') ."\t\t" .self::labeler($field_name) . 
			self::make_input($type,$field_name, NULL,NULL, (count($array) > 1? ($array[0] !== 0?" min='".$array[0]."' ":'') . ($array[1]?" max='".$array[1]."' ":'') . ($array[2]?" step='".$array[2]."' ":'') .  self::pf_check($field_name,$array[3])   : ''))."\n" . ( $type != 'radio' && $type != 'checkbox' ?'</fieldset>':'');	
		}else{
			foreach($array as $k=>$v){
				if($type == 'radio' || $type == 'checkbox')
					$v = "\n\t\t<fieldset><em>$v</em>\n";	
				// not a great place for this complex statement ...
				$r .=   ($type != 'select'? ($type == 'checkbox' || $type == 'radio' ? "\t\t$v":'')   .
						"\t\t".
						self::make_input($type,$field_name, 
						 ( $k != '' && $k != '0' ?   self::pf_check($field_name,$k) .
						 // MOVE THIS ... handles select/checkbox / radio population from post values ... but is repeated for the radio
						 // values
						(($_POST[$field_name] == $k || ($k == 1 && $_POST[$field_name] == 'on'))  
						?($type == 'radio'
						?' checked '
						:'') 
						:'' )
						: ($_POST[$field_name]?" value='".$_POST[$field_name]."'"
						:''))
						)
						: "\t\t\t<option value='$k' ".
						($_POST[$field_name] == $k
						?
						' selected="selected" '
						:
						'')
						.">$v</option>\n" )  . 
						($type == 'radio' || $type == 'checkbox'? '</fieldset>':NULL);
				
			}
			return    "\n\t<fieldset>\n\t\t" . self::labeler($field_name) . 
			($type != 'select'? $r : "\t\t" .'<select name="'.$field_name.'"'.'>'."\n\t\t\t".'<option value="">Select '.str_replace('_',' ',$field_name)."</option>\n" . $r . "\t\t</select>" ) . "\n\t</fieldset>\n";
		}
	}
	
	private static function labeler($field_name){
		$field_name = trim($field_name);
		return "<label for='$field_name'>".ucwords(str_replace('_',' ',$field_name))."</label>\n";
	}

	private static function decode_string($s){
		$s = explode(' ',$s,2);
		foreach(explode(':',$s[1]) as $v){
			$s = explode('-',$v,2);
			$x[$s[0]]=trim($s[1]);
		}
		return $x;	
	}
	
	public static function load($object,$id=NULL,$alt_id=NULL,$required=NULL){
	

		if($object->_r){
			$_r = $object->_r;
		}
		
		if($object->_f || $object->_d){
			$a = $object->_f;
			if($object->_d && is_array($a)) {
				$b = $object->_d;
				$a = array_merge($a,$b);		
			}
		}
		
		// to ensure for loop below doesnt unset the _d value 
		$a [] = '_d';

		// remove values that are not in the _f or _d lists
		foreach($object as $key=>$value){
			if(is_array($a) && !in_array($key,$a)){
				unset($object->$key);
				}
			}
		
		if($_r && $required == NULL){
			foreach($_r as $value)
				if(!array_key_exists($value,$_POST) || $_POST[$value] == '')
					$missing []= $value;	
				else
					$missing = false;
		}
		if($object->_d ){
		// refers to 'hidden' variables, those used for forms or database identification
			foreach($_POST as $k=>$v)
				if(in_array($k,$object->_d))
					// 	public $email = array('date:email_address'=> array(''=>'date') );
					// this doesnt set the value although ...
					$object->$k = array("hidden:$k"=>array(''=>'hidden'));
			unset($object->_d);
		}		
		$select_array = array('select','checkbox','password','radio','sumbit','email','search','number','date','hidden','html');
		if($id != NULL){
			$id = explode(':',$id,2);
			if(count($id) == 2)
				$a_id = $id[1];
			
			$id = $id[0];
			if($id == 'html') return($object);
			if(is_array($object) && in_array($id ,$select_array )){
			// hope this works ...
				if($id == 'hidden') return self::make_input($id,$alt_id,$_POST[$alt_id]) ."\n";
				// extra spaces only for raiod values wtf ??? 
				return self::build_arr($object , ($a_id?$a_id:$alt_id),$id,NULL,(is_array($_r) && in_array($alt_id,$_r) ?true:NULL));
			}
			// amprohphise this statement...		
			elseif(!is_array($object) && !is_object($object)){
				foreach($select_array as $s)
					if(!(strpos($object,"$s ") === false ))
						return self::build_arr(self::decode_string($object),$id,$s,NULL,(is_array($_r) && in_array($id,$_r) ?true:NULL));
						
			$final_id = trim($id);
			return  "\t<fieldset>\n\t\t". self::labeler($final_id,$id) . 
			"\t\t". self::make_input('text',$final_id,($object != '0' || $object != ''?  ($_POST[$final_id] ?  $_POST[$final_id]:NULL)  :NULL), ucwords(str_replace('_',' ',$id)) ).
			"\n\t</fieldset>\n";
			}
		}
		// Recurse... 
		elseif(is_array($object))
			foreach($object as $x=>$y)
				$r [$x] = self::load($y,$x,NULL,$required);
					
		else
			// to pass back to recursive functions to build the input
			// bizzaro behavior... 
			if(is_object($object) || is_array($object))
				foreach($object as $x=>$y)
				// check to see if an entry is contained in the a parameter
				// to do validations or create drop down menus with values 
					if(is_array($y) || is_object($y))
						foreach($y as $a=>$b)
							$r[$x][$a] = self::load($b,$a,$x,$required);
					else
						$r[$x] = self::load($y,$x,$x,$required);
		return $r;
		}
}


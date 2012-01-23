<?php
/*

poform
January 2012
Ronaldo Barbachano 

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
			if($a != 'missing')
			$r .=  (is_array($b) || is_object($b)?self::make($b,true): $b) ;
		}
		return $r. ($i == false?"\n</fieldset>":'') 
		.
		($i == false  ? self::make_input('submit','Go') . 
		"\n</form>\n":'');
	}
	
	private static function make_input($type,$name=NULL,$inner=NULL,$value=NULL,$placeholder=NULL){
	// This needs a tad bit of work. Simply builds an input HTML tag by the use of constructing an assoc. array
	// Could make use of array functions instead of foreach loops 
		$name = trim($name);
		$type = trim($type);
		// down and dirty 'complicated' types that would require a bit too much work to use in the flow below...
		if($type == 'radio')
			return '<input type="radio" '. " name ='$name' $inner >";
		elseif($type == 'html')
			return $value;
		elseif($type == 'submit' )
			return '<input type ="submit" value ="' . $name . '">';
		elseif($type == 'textarea')
		// better support coming soon ....
			return '<textarea name="'.$name.'" '. "$inner>$value</textarea>";
		else{
			$a_names = array('type','name','inner','value','placeholder');
			foreach(func_get_args() as $loc=>$value)
				if($value != NULL && $loc != 2)
					$a[trim($a_names[$loc])] = trim($value);
			// avoid syntax errors .. should be debugged and eventually removed once I iron out all the remaining bugs
			if( $type != 'checkbox' && !isset($a['value'])  && strpos($inner,'value') == 0 )
				$a['value'] = $inner;
				
			if($a['value'] == $inner)
				unset($inner);
			foreach($a as $key=>$value)
				$r []= "$key='$value'";	
			return    '<input ' . implode($r,' ') .  ($inner != NULL? $inner : NULL) . '/>' ;
		}	
	}
	
	private static function build_arr($array,$field_name,$type,$required=NULL){
	// Designed for radio/checkboxes and select html types that need sets of values as its input.
	// Also handles proper 'selected' radio on/offs values based on $_POST object
	// Checkboxes are still under development, as well as multi-select items ...
	
		$field_name = trim($field_name);
		if($type == 'number' || $type == 'range'){
			// this needs work...
			return "\t\t<fieldset>" .self::labeler($field_name) . 
			self::make_input($type,$field_name, (count($array) > 0? ($array[0] !== 0?" min='".$array[0]."' ":'') . ($array[1]?" max='".$array[1]."' ":'') . ($array[2]?" step='".$array[2]."' ":'') .  ' value="' . $_POST[$field_name].'"'   : ''))."\n" . ( $_POST[$field_name] == '' && in_array(trim($field_name),$required)? '<b class="req">*required</b>' : NULL    ) . '</fieldset>';	
		}else{
		foreach($array as $k=>$v){
				if($type == 'radio' || $type == 'checkbox'){
					$r .= "\n\t\t<fieldset><em>$v</em>\n";	
					}
				// not a great place for this complex statement ...
				$r .=   ($type != 'select'?
						"\t\t".
						self::make_input($type,$field_name, 
					( 
					
					$k != '' && $k != '0' 
					
					?   
					// causing probs ....for radio buttons and setting both values to pf_check ... we shouldn't rely on any sort of post value
					// and stick to defaults ?? like the $v ...
						' value = "' . $k . '"'.
						 // MOVE THIS ... handles select/checkbox / radio population from post values ... but is repeated for the radio
						 // values
						(
							($_POST[$field_name] == $k || ($k == 1 && $_POST[$field_name] == 'on'))  
							?
							($type == 'radio' ?' checked ':'') 
							:
						'')
						
					: 
						($_POST[$field_name]?$_POST[$field_name]:'') 
					)
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
			return   '<fieldset>'.self::labeler($field_name ).
			($type != 'select'? $r : "\t\t" .'<select name="'.$field_name.'"'.'>'."\n\t\t\t".'<option value="">Select '.str_replace('_',' ',$field_name)."</option>\n" . $r . "\t\t</select>" ) . ( $_POST[$field_name] == '' && in_array(trim($field_name),$required)? '<b class="req">*required</b>' : NULL    ) .'</fieldset>';
		}
	}
	
	private static function labeler($field_name,$inner=NULL){
		return  "<label for='$field_name'>". ($inner == NULL? NULL:$inner)  . ucwords(str_replace('_',' ',$field_name))."</label>\n";
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
		if(isset($object->_f) || isset($object->_d)){
			$a = $object->_f;
			if($object->_d && is_array($a)) {
				$b = $object->_d;
				$a = array_merge($a,$b);		
			}
		}
		
		$a [] = '_d';
		if(isset($object->_r) && $required == NULL){
			$_r = $object->_r;
			$required = $_r;
			}
		// remove values that are not in the _f or _d lists
		
		if(is_object($object))
			foreach($object as $key=>$value){
				if(is_array($a) && !in_array($key,$a)){
					unset($object->$key);
					}
				}
		if(isset($object->_d) ){
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
				if($id == 'hidden') return self::make_input($id,$alt_id,NULL,$_POST[$alt_id]) ."\n";
				return self::build_arr($object , ($a_id?$a_id:$alt_id),$id,$required);
			}
			elseif(!is_array($object) && !is_object($object)){
				foreach($select_array as $s)
					if(!(strpos($object,"$s ") === false ))
						return self::build_arr(self::decode_string($object),$id,$s,$required);
						
			$final_id = trim($id);
			
			return  "\t<fieldset>\n\t\t". self::labeler($final_id,( $_POST[$final_id] == '' && in_array($final_id,$required)? '<b class="req">*required</b>' : NULL    )) . 
			"\t\t". self::make_input('text',$final_id,NULL,($object != '0' || $object != ''?  ($_POST[$final_id] ?  $_POST[$final_id]:NULL)  :NULL), ucwords(str_replace('_',' ',$id)) ).
			"\n\t</fieldset>\n";
			}
		}
		// Recurse... 
		elseif(is_array($object))
			foreach($object as $x=>$y)
				$r [$x] = self::load($y,$x,NULL,$required);
					
		elseif(is_object($object))
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

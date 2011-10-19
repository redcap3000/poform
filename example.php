<?php
/*

	Example shows a custom class, use of load and make, coded string syntax for
	checkbox, radio and select options (which require assoc. arrays).
	
	To Do : Allow more control over labels/ tag attributes by using html5_core tag
	objects
	
*/
require('poform.php');

class form_class{

	public $setting = array('form_type'=>'form',array('setting3'=>'select 1-Setting 1:2-Setting 2:3-Setting 3','setting2'=>''));
	
	// A blank parameter, will default to a text input w/o value.
	public $param;
	// to define a selection list set its value to something similar to below
	// a-  b- etc refers to the value, while the text after it and before the ':' defines
	// what appears in the selection list, or use helper function to convert assoc. arrays
	public $selection = 'select a-Value A:b-value B:c-value C';
	
	public $a = array('select:numbers'=> array('1'=>'Value 1','2'=>'Value 2','3'=>'value 3'));
	
	public $checkbox = array('checkbox:fav_number'=> array('1'=>'Value 1','2'=>'Value 2','3'=>'value 3'));
		
	public $radio = array('radio:preference'=> array('y'=>'No','n'=>'Yes','m'=>'Maybe'));
	
	// this syntax needs improvement, but works with existing structures for special input tag types
	public $email = array('email:email_address'=> array(''=>'email') );

}

// or create custom coded arrays that specify the action of the array (checkbox/radio/select)

$a = new form_class();

print_r($a);

$b = poform::load($a);

print_r($b);

echo poform::make($b);

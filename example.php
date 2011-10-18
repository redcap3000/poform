<?php
/*

	Example shows a custom class, and use of load and make.
	
	To Do : Provide an additional class for poform classes to inherit that 
	will influence the way the forms fields appear... 
*/
require('poform.php');

class form_class{

	public $setting = array('form_type'=>'form',array('setting3'=>'select 1-Setting 1:2-Setting 2:3-Setting 3','setting2'=>''));
	
	public $param;
	// to define a selection list set its value to something similar to below
	// a-  b- etc refers to the value, while the text after it and before the ':' defines
	// what appears in the selection list, or use helper function to convert assoc. arrays
	public $selection = 'select a-Value A:b-value B:c-value C';

}

$a = new form_class();

print_r($a);

$b = poform::load($a);

print_r($b);

poform::make($b);

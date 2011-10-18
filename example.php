<?php
/*

	Example shows a custom class, and use of load and make.
	
	To Do : Provide an additional class for poform classes to inherit that 
	will influence the way the forms fields appear... 


*/

class form_class{
// use a to define how your forms will behave

	public $setting = array('form_type'=>'form',array('setting'=>'value','setting2'=>'value2'));
	
	public $param = 'a param';

}

$a = new form_class();

print_r($a);

$b = poform::load($a);

print_r($b);
poform::make($b);

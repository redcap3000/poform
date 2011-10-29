
poform - PHP 5 form object generator
====================================

Ronaldo Barbachano Oct 2011

 Pass objects created from classes that are specifically designed for poform, using keywords and array
	structures to define form behaviors and inputs, and $_POST variable handling. 
	Poform is designed with high performance and scalabilty in mind due to its heavy use array processing to polymorphise 
	imporatant web application functionality and help create linked smaller datasets.
	

Core Features
=============
* Supports couchCurl integration,

* Automatic time-based (COUCH_KEY_TIMEOUT, UNIX_DATE_FORMAT) keys.

* Key integers compressed using base conversions.

* Natural (array) handling of radio, checkboxes and select input items.

* Option to use parameters with _id as keys (COUCH_AUTO_ID)

* Option to remove above fields in record (COUCH_REMOVE_ID) 

* Rhobust options to better control how fieldsets, labels and variabless, id's, html classes are rendered
for both debugging and display purposes. 

* Supports defining form interactions including related forms, required parameters, relational keys, and 
conditional required parameters when using simple class definition naming conventions. 

* Supports most new HTML5 input types.

Simple Example
==============
<pre><code>
class myForm(){
	
		public $selection_menu = array('select:selection_menu'=> array('1'=>'Select Item 1','2'=>'Select Item 2'));

		public $radio_button = array('radio:radio_button'=> array('1'=>'Option 1','2'=>'Option 2'));
		
		public $hidden_item = 	array('hidden_item'=>'hidden ');

		public $nested_range_input = array('number_range_1'=>'number :0-0:1-260 ','number_range_2'=>'number :0-10:1-250 ');

	}
$form_object = new myForm();
$form_object = poform::load($form_object);
poform::make($form_object);
	
</code></pre>
 
couchCurl Notes
===============

If using couchCurl the database will default to the name of the class. 
	
couchCurl requires $_d,$_r, $_f and optionally $_f, to function properly. These public class 
variables store arrays containing entries that are class variable names which may be manipulated 
(usually within the classes' construct method) to represent many different form states in a \
single class. $_d refers to variables to pass from one post state to another 
(i.e. persistant variables that are hidden after encountered.) 

$_f refers to variables to show, most useful if your calling class inherits others, to show
only variables contained in this array. $_p will contain an array structure that defines how post
states will behave, by defining ['_d'],['_r'] and so on in a nested fashion that is evaluated
when post variables are present. 

Also this construct will eventually support a $_o, to define 
and set of variables that are dependent on each other, but exist within a set of fields where only
one is required.
	
Ex: If you have two fields for a date, you need both. But you do not require a date for your record
So if one field is present for the date, we will not issue the insert command because the date is
incomplete. But if both fields aren't present we are not concerned, but only if any of the other
variables are not present.

To do
=====
HTML 5 Stuff to Support 
slider, required validation tag, file uploads, more rhobust and contained couch support, textarea input type..
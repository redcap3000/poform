<?php

/*

poform - PHP 5 form object generator

Ronaldo Barbachano Oct 2011

 -- Currently in Proof of concept stage
 
 -- Idea is to create a library that will automatically generate a form to manipulate
 a php object. Why? Ultimately to tie this in with my other libraries (couchCurl/html5_core)
 to create a couch/php framework. This component would aid the the manipulation of 
 json objects (when using jser)
 
 For now only generates a very simple html form, but will be rapidly developed..

I will support selection lists, checkboxes, radio boxes etc, and the new html5
form elements when tied in to html5_core.

*/

class poform{

	function make($object,$i=false,$settings=NULL){
	// settings would be an array to define how to handle basic form settings, and 
	// maybe some options ? settings like form attribute settings basically
		// puts a loaded object into a form with fields
		if($i == false){
			echo '<form><fieldset>';
		}else{
			echo "<fieldset>";
		}
		
		foreach($object as $a=>$b){
			if(is_array($b) || is_object($b)){
				self::make($b,true);
			}else{
				echo $b;
			}
		
		}
		
		if($i == false){
			echo '</fieldset><input type ="submit"/></form>';
		}else{
			echo '</fieldset>';
		}
	
	}

	function load($object,$id=NULL,$classname=NULL){
	// creates the insides of a form based on an object
	if(!is_array($object) && !is_object($object) &&$id != NULL && $classname !=NULL){
	// by default we create all fields as text inputs (for now)
		return ucwords(str_replace('_',' ',$id)) . " <input type='text' id='".$classname.'_'.$id."' value='$object'/>";
	}elseif(is_array($object)){
		foreach($object as $x=>$y){
			$r [$x] = self::load($y,$x,$classname);
		}		
	}else{
		// to pass back to recursive functions to build the input
		$class_name = get_class($object);
		foreach($object as $x=>$y){
		// check to see if an entry is contained in the a parameter
		// to do validations or create drop down menus with values 
			if(is_array($y) || is_object($y)){
				foreach($y as $a=>$b){
					$r[$x][$a] = self::load($b,$a,$class_name);
				}
			}else{
				$r[$x] = self::load($y,$x,$class_name);
				}
			}	
		}
		return $r;
	}
}
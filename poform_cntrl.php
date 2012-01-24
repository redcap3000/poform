<?php

/*

 This is a controller class for use with poform ... extend this class with classes you'd
 like to store into a couch database... as long as the parent construct method is invoked 
 this will do couchCurl magic on your child class given the presense of required class variables. (_r, _d, _f etc.)

*/

class poform_cntrl{

	public function popRecord($id){
	// this will look up a document ID inside the appropriate database and set the values
	// of the function to it ...
		$class = get_called_class();
		$r = json_decode(couchCurl::get($id,NULL,$class),true);
		if(isset($r['_id']) && isset($r['_rev']) )
			foreach($r as $key=>$value)
				$_POST[$key] = $value;
		else		
			die('<h3>Document does not exist.</h3></body></html>');
		// make better...
		// would like to use array merge but it never seems to work properly...
		if($_POST['_id'] && $_POST['_rev']){
		// need to review this...
			$this->_d [] = '_id';
			$this->_d [] = '_rev';
			$this->_id = $_POST['_id'];
			$this->_rev = $_POST['_rev'];
		}	
		
	}
	
	public function unique_field_check($unique_field,$view_name = NULL){
	// looks up field in corresponding couch database via named view inside the 'cntrl' design doc,
	// or view named after the unique field in the called class (as according to the design conventions followed elsewhere
		
		if(isset($_POST[$unique_field]) && $_SERVER['REQUEST_METHOD'] == "POST" ){
			if($view_name == NULL) $view_name = $unique_field ;
			$r = couchCurl::view($_POST[$unique_field] ,$view_name,'cntrl',get_called_class());
			if( (int) count($r['rows']) > 0  ){
				echo '<div class="err">The field '.str_replace('_',' ',$unique_field).' exists '.$_POST[$unique_field].', please use another value.</div>';
				unset($_POST[$unique_field]);

			}
	}
	
	function __construct(){
		// consider incorporating the class name here ? 
		$called_class = get_called_class();	
		if(isset($_GET['id']))
			$_POST['_get_id'] = $_GET['id'];
		if(isset( $_POST['_get_id'] ) ){
			$this->popRecord($_POST['_get_id']);
		}
		
		if ($_SERVER['REQUEST_METHOD'] == "POST"){
		// filter the array? Wont work for updates...
			if(isset($_POST['_id']) && isset($_POST['_rev'])  ) {
			// this update can only pass if we have nothing missing ???
			// issue statement to update record and return message stating update
			// status
			// do a down and dirty check to see that the record has changed  ?
			// allow the class to designate which fields cannot be updated from an editor
			// ex : we do not (or should not) allow a user to change a users' contact
			// do a missing field check first ...
				foreach($_POST as $key=>$value){
					if($value =='' || (is_array($this->_r) && !in_array($key,$this->_r))){
					// how is this right ??
						$this->missing [] = $key;
					}
				}
				if(!is_array($this->missing)){
					$check = json_decode(couchCurl::get($_POST['_id'],NULL,get_called_class()),true)  ;
					// is this ok to compare like this? 
					// this many take the mis-ordering of the fields as different ? 
					if($check != $_POST){
						echo couchCurl::update(json_encode($_POST),get_called_class());
						}
					else
						die('no change');
				}else{
					echo '<div class ="err">You are missing '. implode($this->missing,', ') . '.' . '</div>';			
				}
			}else{
				$missing = array();
				foreach($this->_r as $key=>$value){
					if(!array_key_exists($value,$_POST) || $_POST[$value] == '' ){
						$this->missing []= $value;					
					}
				}
				
				if(count($this->missing) > 0){
					echo '<div class ="err">You are missing '. implode($this->missing,', ') . '.' . '</div>';			
				}else{
					$b = array();
					foreach($_POST as $key=>$value)
						if(strpos($key,'_') === 0)
						// ignore values with a '_' at the beginning of the key (_r,_d,_f and so on..)
						;
						elseif(is_numeric($value))
							$b[$key] = (int) $value;
						else
							$b[$key] = $value;
						
					if(!empty($b) && !isset($_POST['_id']) && !isset($_POST['_rev'] )){
					// creates empty values .. should be fine for put but not for updates..
						$b = array_filter($b);
						// create a more dynamic way of defining couch key values to help make it easier to prevent duplicated values
						// we may want to define another class parameter like _key , that would contain an array with the fields to use for the final 'document id'
						// specifically for couch
						// check to see if a similar record already exists ? but how .... (based on address_id ?
												
						echo couchCurl::put(json_encode($b),NULL,get_called_class());		
						}
				}
			}
		}
	}
}

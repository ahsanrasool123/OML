<?php
class helper{
	
	function helper(){
		
	}
	
	// ---------------------------------------------
	
	function update(){
		
		while(list($k, $v)=each($_POST)){
			$this->$k = trim($v);
			$$k = esc($this->$k);	
		}
		
		if(is_numeric($this->id)){
			$sql = "UPDATE `helpers` SET `desc` = '$desc', `title` = '$title', `text` = '$text', `text_agent` = '$text_agent', `text_seller` = '$text_seller', `text_buyer` = '$text_buyer' WHERE `id` = $id LIMIT 1";
		}else{
			$sql = "INSERT INTO `helpers` (
				`desc`, 
				`title`,
				`text`, 
				`text_agent`, 
				`text_seller`, 
				`text_buyer`
			)VALUES(
				'$desc', 
				'$title', 
				'$text', 
				'$text_agent', 
				'$text_seller', 
				'$text_buyer'
			)";
		}
		
		//echo $sql;
		query($sql);
		
		header('Location: helper_list.php');
		exit();
	}
		
	// ---------------------------------------------

	function retrieve($id){
		if(!is_numeric($id)){return;}
		$res = query("SELECT * FROM `helpers` WHERE `id` = $id LIMIT 1");
		if(!num_rows($res)){return false;}
		$rs = fetch_assoc($res);
		while(list($k, $v)=each($rs)){
			$this->$k = $v;
		}
	}
	
	// ---------------------------------------------
	
	function delete($id){
		$sql = "DELETE FROM `helpers` WHERE `id` = '".$id."' LIMIT 1";
		#echo $sql;
		query($sql);
		header('Location: helper_list.php');
		exit();
	}
}
?>
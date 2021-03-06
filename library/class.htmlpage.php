<?php
// page class

class htmlpage{

	// Constructor
	function htmlpage(){
		
		$this->slug	= strtolower(substr($_SERVER['PHP_SELF'], strrpos($_SERVER['PHP_SELF'], '/')+1, strlen($_SERVER['PHP_SELF'])));
		
		$this->createCatIds();
		$this->retrieve();
	
	}
	
	#-------------------------------------------------
	
	function build_htaccess(){
	
		global $teststub;
	
		$filename = $_SERVER['DOCUMENT_ROOT'].'/.htaccess';
		
		$default = 'RewriteEngine on'.chr(10).chr(10);
		
		$default .= '# >> This page is generated by the page object, no point changing it here as it will be overwritten'.chr(10).chr(10);
		
		// Pages
		$res = query("SELECT `slug` FROM `pages` ");
		while($rs = fetch_assoc($res)){
			if($rs['slug'] != 'index.php'){
				$default .= 'RewriteRule ^'.$rs['slug'].'$ page.php?slug='.$rs['slug'].chr(10);
			}
		}
		
		$default .= 'ErrorDocument 404 /404.php';
	
		// Write the file
		if (!$handle = fopen($filename, 'w')) {
			 echo "Cannot open file `$filename`";
			 exit();
		}
	
		if (fwrite($handle, $default) === FALSE) {
			exit('htaccess could not be written to');
		}


		fclose($handle);
	}
	
	#-------------------------------------------------
	
	// Delete a dynamin page
	function delete($id){
		if(!is_numeric($id)){return false;}
		query("DELETE FROM `pages` WHERE `id` = '".esc($id)."' LIMIT 1");
		$this->build_htaccess();
	}
	
	#-------------------------------------------------

	// Update the database
	function update(){
		
		global $a_errors;
		
		while(list($k, $v) = each($_POST)){
			$this->$k = trim(stripslashes($v));
			$$k = esc($this->$k);
		}
		
		// Validate
		if(!$this->title){addError("Please enter a page title");}
		
		if(!$this->slug && $this->slug_name != 'home'){
			addError("Please enter a URL name for your new page");
			return false;
		}
		
		$this->slug = trim(strtolower($this->slug));
		$this->slug = str_replace(' ', '-', $this->slug);
		$this->slug = str_replace('&', '', $this->slug);
		$this->slug = str_replace('?', '', $this->slug);
		$this->slug = str_replace('@', '', $this->slug);
		$this->slug = str_replace('--', '-', $this->slug);
		$this->slug = str_replace('--', '-', $this->slug);
		
		if(strpos($this->slug, ' ')){
			addError("No spaces are allowed in URL names");
		}
		if(strpos($this->slug, '&')){
			addError("Amphasands are not allowed in URL names");
		}
		if(strpos($this->slug, '?')){
			addError("Question marks are not allowed in URL names");
		}
		#if(strpos($this->slug, '/')){
			#addError("Slashes are not allowed in URL names");
		#}
		if(strpos($this->slug, '.')){
			addError("Dots are not allowed in URL names");
		}
		if($this->slug == 'articles'){
			addError("The URL `news` is reserved for the articles section");
		}
		
		if($this->logged_in == '-'){
			$this->logged_in = $logged_in = '';
		}
		
		$exists = $this->slugExists($this->slug);
		
		// If creating a new page we need to check the slug doesn't already exist
		if(!$this->slug_name){
			if($exists){
				addError("The page `".$this->slug."` already exists, please choose another file name");
			}
		}
		
		if(!$this->slug && $this->slug_name){
			$this->slug = $this->slug_name;
		}
			
		// Any errors?
		if(count($a_errors)){return false;}

		// Build SQL
		if($this->id){// update
			
			$sql = "UPDATE `pages` SET 
				`slug` = '".$this->slug."',
				`title` = '".$title."', 
				`menu_title` = '".$menu_title."', 
				`html` = '".$html."', 
				`css` = '".$css."', 
				`submenu_of` = '".$submenu_of."', 
				`meta_title` = '".$meta_title."',
				`meta_desc` = '".$meta_desc."',
				`meta_keywords` = '".$meta_keywords."',
				`logged_in` = '".$logged_in."',
				`helper_text` = '$helper_text',
				`include_file` = '$include_file'
				WHERE `id` = '".$id."'";
		
		}else{ // insert
		
			$max = result(query("SELECT MAX(`cat_id`) FROM `pages`"),0);
			$cat_id = $max+1;
		
			$sql = "INSERT INTO `pages` (
				`slug`,
				`title`, 
				`cat_id`,
				`menu_title`, 
				`html`,
				`css`,
				`submenu_of`,
				`meta_title`,
				`meta_desc`,
				`meta_keywords`,
				`logged_in`,
				`helper_text`,
				`include_file`
			)VALUES(
				'".$this->slug."',
				'".$title."', 
				'".$cat_id."',
				'".$menu_title."', 
				'".$html."',
				'".$css."',
				'".$submenu_of."',
				'".$meta_title."',
				'".$meta_desc."',
				'".$meta_keywords."',
				'".$logged_in."',
				'".$helper_text."',
				'$include_file'
			)";
			$insert = 1;
		
		}
	
		
		// Do the query
		query($sql);
		if($insert){
			$this->id = insert_id();
		}
		
		// Reset main menu flag if this is a submenu item
		if($this->submenu_of){
			query("UPDATE `pages` SET `main_menu` = 0 WHERE `id` = '".$this->id."'");
		}
			
		$this->build_htaccess();
		
		// Make sure all pages have a unique cat_id
		$this->createCatIds();
		
		header('Location: page_list.php');
		exit();
		
	}
	
	#-------------------------------------------------
	
	function toggleMain($slug){
		
		global $a_errors;
		
		if(!$slug){return false;}
		
		$online = result(query("SELECT `main_menu` FROM `pages` WHERE `slug` = '".esc($slug)."'"), 0);
		
		if($online){
			query("UPDATE `pages` SET `main_menu` = 0 WHERE `slug` = '".esc($slug)."' LIMIT 1");
		}else{
			query("UPDATE `pages` SET `main_menu` = 1 WHERE `slug` = '".esc($slug)."' LIMIT 1");
		}
	}
	
	#-------------------------------------------------
	
	function toggleBoxed($slug){
		
		global $a_errors;
		
		if(!$slug){return false;}
		
		$box = result(query("SELECT `boxed` FROM `pages` WHERE `slug` = '".esc($slug)."'"), 0);
		
		if($box){
			query("UPDATE `pages` SET `boxed` = 0 WHERE `slug` = '".esc($slug)."' LIMIT 1");
		}else{
			query("UPDATE `pages` SET `boxed` = 1 WHERE `slug` = '".esc($slug)."' LIMIT 1");
		}
		header('Location: page_list.php?terms='.$_GET['terms']);
		exit();
	}
	
	#-------------------------------------------------
	
	function toggleAgent($slug){
		
		global $a_errors;
		
		if(!$slug){return false;}
		
		$online = result(query("SELECT `menu_agent` FROM `pages` WHERE `slug` = '".esc($slug)."'"), 0);
		
		if($online){
			query("UPDATE `pages` SET `menu_agent` = 0 WHERE `slug` = '".esc($slug)."' LIMIT 1");
		}else{
			query("UPDATE `pages` SET `menu_agent` = 1 WHERE `slug` = '".esc($slug)."' LIMIT 1");
		}
	}
	
	#-------------------------------------------------
	
	function toggleSeller($slug){
		
		global $a_errors;
		
		if(!$slug){return false;}
		
		$online = result(query("SELECT `menu_seller` FROM `pages` WHERE `slug` = '".esc($slug)."'"), 0);
		
		if($online){
			query("UPDATE `pages` SET `menu_seller` = 0 WHERE `slug` = '".esc($slug)."' LIMIT 1");
		}else{
			query("UPDATE `pages` SET `menu_seller` = 1 WHERE `slug` = '".esc($slug)."' LIMIT 1");
		}
	}
	
	#-------------------------------------------------
	
	function toggleBuyer($slug){
		
		global $a_errors;
		
		if(!$slug){return false;}
		
		$online = result(query("SELECT `menu_buyer` FROM `pages` WHERE `slug` = '".esc($slug)."'"), 0);
		
		if($online){
			query("UPDATE `pages` SET `menu_buyer` = 0 WHERE `slug` = '".esc($slug)."' LIMIT 1");
		}else{
			query("UPDATE `pages` SET `menu_buyer` = 1 WHERE `slug` = '".esc($slug)."' LIMIT 1");
		}
	}
	
	
	
	#-------------------------------------------------
	
	function toggleCategory($slug){
		
		global $a_errors;
		
		if(!$slug){return false;}
		
		$online = result(query("SELECT `news_category` FROM `pages` WHERE `slug` = '".esc($slug)."'"), 0);
		
		if($online){
			query("UPDATE `pages` SET `news_category` = 0 WHERE `slug` = '".esc($slug)."' LIMIT 1");
		}else{
			query("UPDATE `pages` SET `news_category` = 1 WHERE `slug` = '".esc($slug)."' LIMIT 1");
		}
	}
	
	#-------------------------------------------------

	// Check if a page already exists
	function slugExists($page){
		if(!$page){return FALSE;}
		
		if(result(query("SELECT COUNT(`slug`) FROM `pages` WHERE `slug` = '$slug'"),0)){
			return true;
		}
	}

	#-------------------------------------------------
	
	function h1($title=false){
		if($title){
			echo '<h1 style="margin-bottom:15px">'.html($title).'</h1>'.chr(10);
		}else{
			$this->title = html_entity_decode($this->title);
			echo '<h1 style="margin-bottom:15px">'.html($this->title).'</h1>'.chr(10);
		}
	}
	
	#-------------------------------------------------
	
	function h2($title=false){
		if($title){
			echo '<h2 style="margin-bottom:15px">'.html($title).'</h2>'.chr(10);
		}else{
			$this->title = html_entity_decode($this->title);
			echo '<h2 style="margin-bottom:15px">'.html($this->title).'</h2>'.chr(10);
		}
	}
	
	#-------------------------------------------------

	// Renders the full article to the news page
	function content($slug=''){
		
		if(!$slug){
			$this->slug = $GLOBALS['pagename'];
		}else{
			$this->slug = '';
		}
		// Has the page been loaded alredy?
		// If not we need to retrieve it
		if(!$this->title){
			if($this->slug){
				$this->retrieve($this->slug);
			}else{
				if(!eregi('dmin.', $this->slug)){
					addAlert('The system variable `'.$GLOBALS['pagename'].'` was not set');
				}
			}
		}
		
		if($this->title){
		
			// Title
			if($GLOBALS['show_page_h1_heading']){
				#echo '<h1>'.$this->title.'</h1>'.chr(10);
			}
			
			// HTML body
			echo $this->html.chr(10).chr(10);
			
		}else{
			echo '<h1>Page not found</h1>';
			echo '<p>This page may have been moved.</p>';
		}
	
	}
	
	#-------------------------------------------------

	// Retrieve a page item
	function retrieve($slug=''){
		
		global $a_errors, $meta_title, $meta_desc, $meta_keywords;
		
		if(!$slug){
			if($_SERVER['REQUEST_URI'] == '/'){
				if(!$slug){
					$slug = 'home';
				}
			}
		}
		
		if(is_numeric($slug)){
			$res = query("SELECT * FROM `pages` WHERE `id` = '".$slug."'");
		}else{
			$res = query("SELECT * FROM `pages` WHERE `slug` = '".esc($slug)."'");
		}
		
		$rs = fetch_assoc($res);
		
		if(num_rows($res)){
			while(list($k, $v)=each($rs)){
				$this->$k = stripslashes($v);
			}
		}
		
		if(!$page){
			$this->title = html($this->title);
		}
		
		$this->html = str_replace('�', '-', $this->html);
		$this->html = str_replace('�', '-', $this->html);
		
		// Replace YouTube links with embed
		$this->html = preg_replace("/\s*[a-zA-Z\/\/:\.]*youtube.com\/watch\?v=([a-zA-Z0-9\-_]+)([a-zA-Z0-9\/\*\-\_\?\&\;\%\=\.]*)/i","<iframe width=\"420\" height=\"315\" src=\"//www.youtube.com/embed/$1\" frameborder=\"0\" allowfullscreen></iframe>",$this->html);
		
		// Meta data defauls
		if(!$this->meta_title){$this->meta_title = $this->title;}
		
		return true;
	}
	
	#-------------------------------------------------

	// Convert POST array
	function convertVars(){
		while(list($k, $v) = each($_POST)){
			$this->$k = trim(stripslashes($v));
		}
	}
	
	#-------------------------------------------------
	
	// List all editable pages (for admin sectio)]
	function listpages(){
		$res = query("SELECT * FROM `pages` ORDER by `title`");
		echo '<table width="96%" cellpadding="0" cellspacing="0" border="0" class="producttable">'.chr(10);
		echo '<tr style="font-weight:bold"><td>Title</td><td>Page (preview)</td><td>&nbsp;</td><td>&nbsp;</td></tr>'.chr(10);
		while($rs = fetch_assoc($res)){
			
			echo '<tr>';
			echo '<td><strong>'.clean($rs['title']).'</strong><br/><a href="'.$linky.'" target="_blank"><span style="font-size:small">'.$url.'<a/></span></td>';
			echo '<td><a href="'.$linky.'" target="_blank">'.clean($rs['slug']).'</a></td>';
			echo '<td><a href="htmlpage_edit.php?page='.clean($rs['slug']).'">Edit</a></td>'.chr(10);
			if(!$rs['static']){
				echo '<td><a href="page_list.php?action=deletepage&page='.clean($rs['slug']).'">Delete</a></td>'.chr(10);
			}else{
				echo '<td style="color:#cccccc">Delete</td>';
			}
			echo '</tr>'.chr(10);
		}
		echo '</table>'.chr(10);
	}
	
	#-------------------------------------------------
	
	// Creates cat_id for each page for use in publishing news items/articles
	function createCatIds(){
		$max = result(query("SELECT MAX(`cat_id`) FROM `pages`"),0);
		$start = $max+1;
		$res = query("SELECT * FROM `pages` WHERE `cat_id` = 0 || `cat_id` = NULL");
		while($rs = fetch_assoc($res)){
			$sql = "UPDATE `pages` SET `cat_id` = $start WHERE `slug` = '".esc($rs['slug'])."' LIMIT 1";
			#echo $sql.'<hr/>';
			query($sql);
			$start++;
		}
	}
	
	#-------------------------------------------------
	
} // End of class
?>
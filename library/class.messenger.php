<?php // Messaging class 
class messenger{
	
	function messenger(){
	
	}
	
	// -----------------------------------------
	
	function retrieve($msg_id){
		if(!$msg_id && !is_numeric($msg_id)){return false;}
		$res = query("SELECT * FROM `messages` WHERE `msg_id` = $msg_id LIMIT 1");
		if(!num_rows($res)){return false;}
		$rs = fetch_assoc($res);
		while(list($k, $v)=each($rs)){
			$this->$k = $v;
		}
		
		if($this->from_cust_id){
			$this->from_firstname = result("SELECT `firstname` FROM `customers` WHERE `cust_id` = '".esc($this->from_cust_id)."'");
			$this->from_name = result("SELECT CONCAT(`firstname`, ' ', `surname`) FROM `customers` WHERE `cust_id` = '".esc($this->from_cust_id)."'");
			$this->to_name = result("SELECT CONCAT(`firstname`, ' ', `surname`) FROM `agents` WHERE `agent_id` = '".esc($this->to_agent_id)."'");
			$this->to_firstname = result("SELECT `firstname` FROM `agents` WHERE `agent_id` = '".esc($this->to_agent_id)."'");
		}else if($this->from_agent_id){
			$a = new agent;
			$a->retrieve($this->from_agent_id);
			$this->from_firstname = $a->firstname;
			$this->from_name = $a->full_name;
			if($a->photo){
				$this->agent_photo = $a->photo_url;
			}
			$this->to_name = result("SELECT CONCAT(`firstname`, ' ', `surname`) FROM `customers` WHERE `cust_id` = '".esc($this->to_cust_id)."'");
			$this->to_firstname = result("SELECT `firstname` FROM `customers` WHERE `cust_id` = '".esc($this->to_cust_id)."'");
		}else{// From the admin user
			$this->to_name = result("SELECT CONCAT(`firstname`, ' ', `surname`) FROM `customers` WHERE `cust_id` = '".esc($this->to_cust_id)."'");
			$this->to_firstname = result("SELECT `firstname` FROM `customers` WHERE `cust_id` = '".esc($this->to_cust_id)."'");
		}
		
		if(!$this->to_cust_id && !$this->to_agent_id){
			$this->to_name = 'Customer Support';
		}
		
		$this->message_with_headers = 'Original message: '.chr(10).chr(10).$this->message.chr(10).chr(10);
		$this->message_with_headers .= 'From: '.$this->from_name.chr(10);
		$this->message_with_headers .= 'To: '.$this->to_name.chr(10);
		$this->message_with_headers .= 'Date: '.datetime($this->date_sent).chr(10);
		$this->message_with_headers .= 'Subject: '.$this->subject.chr(10);
		
		// Admin messages
		if($this->user_type == 'admin'){
			$this->from_name = $GLOBALS['system_name'];
			$this->agent_photo = 'images/om-message-logo.gif';
		}
		
	}
	
	
	// -----------------------------------------
	
	
	// Delete a message
	function deleteMessage($msg_id){
		if(!is_numeric($msg_id)){return false;}
		$this->retrieve($msg_id);
		if($_SESSION['cust_id']){
			query("UPDATE `messages` SET `deleted` = 1 WHERE `msg_id` = $msg_id AND (`from_cust_id` = ".$_SESSION['cust_id']." OR `to_cust_id` = ".$_SESSION['cust_id'].") LIMIT 1");
		}
		if($_SESSION['agent_id']){
			query("UPDATE `messages` SET `deleted` = 1 WHERE `msg_id` = $msg_id AND (`from_agent_id` = ".$_SESSION['agent_id']." OR `to_agent_id` = ".$_SESSION['agent_id'].") LIMIT 1");
		}
		return true;
	}
	
	
	// -----------------------------------------
	
	
	// Restore a deleted message to inbox
	function restoreMessage($msg_id){
		if(!is_numeric($msg_id)){return false;}
		$this->retrieve($msg_id);
		if($_SESSION['cust_id']){
			query("UPDATE `messages` SET `deleted` = 01 WHERE `msg_id` = $msg_id AND (`from_cust_id` = ".$_SESSION['cust_id']." OR `to_cust_id` = ".$_SESSION['cust_id'].") LIMIT 1");
		}
		if($_SESSION['agent_id']){
			query("UPDATE `messages` SET `deleted` = 0 WHERE `msg_id` = $msg_id AND (`from_agent_id` = ".$_SESSION['agent_id']." OR `to_agent_id` = ".$_SESSION['agent_id'].") LIMIT 1");
		}
		return true;
	}
	
	// -----------------------------------------
	
	
	// Toggle a messages stared status
	function starMessage($msg_id){
		if(!is_numeric($msg_id)){return false;}
		$this->retrieve($msg_id);
		
		if($this->starred){
			$starval = 0;
		}else{
			$starval = 1;
		}
		
		if($_SESSION['cust_id']){
			query("UPDATE `messages` SET `starred` = $starval WHERE `msg_id` = $msg_id AND (`from_cust_id` = ".$_SESSION['cust_id']." OR `to_cust_id` = ".$_SESSION['cust_id'].") LIMIT 1");
		}
		if($_SESSION['agent_id']){
			query("UPDATE `messages` SET `starred` = $starval WHERE `msg_id` = $msg_id AND (`from_agent_id` = ".$_SESSION['agent_id']." OR `to_agent_id` = ".$_SESSION['agent_id'].") LIMIT 1");
		}
		return true;
	}
	
	// -----------------------------------------
	
	// read a message
	function read($msg_id){
		if(!is_numeric($msg_id)){return false;}
		$res = query("SELECT * FROM `messages` WHERE `msg_id` = $msg_id");
		$rs = fetch_assoc($res);
		
		if($_SESSION['agent_id']){// Agent opening
			if($_SESSION['agent_id'] == $rs['to_agent_id']){
				if(!$rs['date_read']){// Timestamp when read if not opened already
					query("UPDATE `messages` SET `date_read` = ".time()." WHERE `msg_id` = $msg_id LIMIT 1");
				}
			}
		}
		
		if($_SESSION['cust_id']){// Customer opening
			if($_SESSION['cust_id'] == $rs['to_cust_id']){
				if(!$rs['date_read']){// Timestamp when read if not opened already
					query("UPDATE `messages` SET `date_read` = ".time()." WHERE `msg_id` = $msg_id LIMIT 1");
				}
			}
		}
		
	}
	
	
	// -----------------------------------------
	
	// Show a counter of new messgaes
	function newMessageCount(){
		if($_SESSION['agent_id']){
			$sql = "SELECT COUNT(`msg_id`) FROM `messages` WHERE `to_agent_id` = '".esc($_SESSION['agent_id'])."' AND `deleted` = 0";
		}else if($_SESSION['cust_id']){
			$sql = "SELECT COUNT(`msg_id`)  FROM `messages` WHERE `to_cust_id` = '".esc($_SESSION['cust_id'])."' AND `deleted` = 0";
		}
		if(!$sql){return;}
		$rows = result($sql);
		if(!$rows){echo '<p>You have no new messages</p>'; return;}
		
		echo '<p class="red">You have '.$rows.' new messages</p>';
	}
	
	// -----------------------------------------
	
	
	// List all messages for logged in users (used in old dash only)
	function listAll($start_row=0, $rows=10){
		
		// Is this a seller?
		if($_SESSION['cust_id']){
			$seller = result("SELECT `seller` FROM `customers` WHERE `cust_id` = '".esc($_SESSION['cust_id'])."'");
		}
		
		// Open wrapper
		echo '<div id="messagesWrapper">'.chr(10).chr(10);
		
		// tabs
		echo '<ul class="tabs">'.chr(10);
		echo '<li id="unreadTab" class="tab tabOn">Unread messages [<span class="msgCounter">'.$this->unreadMessageCount().'</span>]</li>'.chr(10);
		if($seller || $_SESSION['agent_id']){
			echo '<li id="pitchesTab" class="tab">Pitches [<span class="pitchCounter">'.$this->pitchesCount().'</span>]</li>'.chr(10);
		}
		echo '<li id="inboxTab" class="tab">Received messages</li>'.chr(10);
		echo '<li id="outboxTab" class="tab">Sent messages</li>'.chr(10);
		echo '<li id="deletedTab" class="tab">Deleted messages</li>'.chr(10);
		echo '</ul>'.chr(10);
		
		
		// Unread
		echo '<div id="unread" class="messageWin">'.chr(10);
		$this->listUnreadMessages($start_row, $rows);
		echo '</div>'.chr(10).chr(10);
		
		// Pitches
		echo '<div id="pitches" class="messageWin">'.chr(10);
		$this->listPitches($start_row, $rows);
		echo '</div>'.chr(10).chr(10);
		
		// Inbox
		echo '<div id="inbox" class="messageWin">'.chr(10);
		$this->listReceivedMessages($start_row, $rows);
		echo '</div>'.chr(10).chr(10);
		
		//Out box
		echo '<div id="outbox" class="messageWin">'.chr(10);
		$this->listSentMessages($start_row, $rows);
		echo '</div>'.chr(10).chr(10);
		
		// Deleted
		echo '<div id="deleted" class="messageWin">'.chr(10);
		$this->listDeletedMessages($start_row, $rows);
		echo '</div>'.chr(10).chr(10);
		
		// Close wrapper
		echo '</div>'.chr(10).chr(10);
		
		// Message window
		echo '<div id="messageContent"></div>';
	}
	
	// -----------------------------------------
	
	function listInbox(){
		
		$this->listReceivedMessages($start_row, $rows);
	
	}
	
	// -----------------------------------------
	
	// Returns the number of unread messages as an integer
	function unreadMessageCount(){
		if($_SESSION['agent_id']){
			$sql = "SELECT COUNT(`msg_id`) FROM `messages` WHERE `to_agent_id` = '".esc($_SESSION['agent_id'])."' AND `deleted` = 0 AND `date_read` = 0 ORDER BY `date_sent` DESC";
		}else if($_SESSION['cust_id']){
			$sql = "SELECT COUNT(`msg_id`) FROM `messages` WHERE `to_cust_id` = '".esc($_SESSION['cust_id'])."' AND `deleted` = 0 AND `date_read` = 0 ORDER BY `date_sent` DESC";
		}
		return result($sql);
	}
	
	// -----------------------------------------
	
	// Returns the number of pitches
	function pitchesCount(){
		if($_SESSION['agent_id']){
			$sql = "SELECT COUNT(`msg_id`) FROM `messages` WHERE `from_agent_id` = '".esc($_SESSION['agent_id'])."' AND `deleted` = 0 AND `prop_id` <> 0 ORDER BY `date_sent` DESC";
		}else if($_SESSION['cust_id']){
			$sql = "SELECT COUNT(`msg_id`) FROM `messages` WHERE `to_cust_id` = '".esc($_SESSION['cust_id'])."' AND `deleted` = 0 AND `prop_id` <> 0 ORDER BY `date_sent` DESC";
		}
		return result($sql);
	}
	
	// -----------------------------------------
	
	function listUnreadMessages($start_row=0, $rows=10){
		if($_SESSION['agent_id']){
			$sql = "SELECT * FROM `messages` WHERE `to_agent_id` = '".esc($_SESSION['agent_id'])."' AND `deleted` = 0 AND `date_read` = 0 ORDER BY `date_sent` DESC";
		}else if($_SESSION['cust_id']){
			$sql = "SELECT * FROM `messages` WHERE `to_cust_id` = '".esc($_SESSION['cust_id'])."' AND `deleted` = 0 AND `date_read` = 0 ORDER BY `date_sent` DESC";
		}
		
		$sql .= " LIMIT $start_row, $rows";
		
		$res = query($sql);
		
		if(!num_rows($res)){// No messages
			echo '<p>You have no unread messages</p>';
			return;
		}
		
		// List the message headers
		$this->dashboardMessageHeader($res);
	}
	
	// -----------------------------------------
	
	function listReceivedMessages($start_row=0, $rows=10){
		if($_SESSION['agent_id']){
			$sql = "SELECT * FROM `messages` WHERE `to_agent_id` = '".esc($_SESSION['agent_id'])."' AND `deleted` = 0 ORDER BY `date_sent` DESC";
		}else if($_SESSION['cust_id']){
			$sql = "SELECT * FROM `messages` WHERE `to_cust_id` = '".esc($_SESSION['cust_id'])."' AND `deleted` = 0 ORDER BY `date_sent` DESC";
		}
		
		$sql .= " LIMIT $start_row, $rows";
		
		$res = query($sql);
		
		if(!num_rows($res)){// No messages
			echo '<div class="noMessages">You have no messages</div>';
			return;
		}
		
		// List the message headers
		$this->dashboardMessageHeader($res);
	}
	
	
	// -----------------------------------------
	
	// Agents only message list
	function listReceivedAgentMessages($start_row=0, $rows=10, $type='seller'){
		
		if($type == 'seller'){
			$sql = "SELECT * FROM `messages` WHERE `to_agent_id` = '".esc($_SESSION['agent_id'])."' AND `deleted` = 0 AND `user_type` = 'seller' ORDER BY `date_sent` DESC";
		}else{
			$sql = "SELECT * FROM `messages` WHERE `to_agent_id` = '".esc($_SESSION['agent_id'])."' AND `deleted` = 0 AND `user_type` = 'buyer'  ORDER BY `date_sent` DESC";
		}
		$sql .= " LIMIT $start_row, $rows";
		
		$res = query($sql);
		
		if(!num_rows($res)){// No messages
			echo '<div class="noMessages">You currently have no messages from '.$type.'s </div>';
			return;
		}
		
		// List the message headers
		$this->dashboardMessageHeaderAgent($res);
	}
	
	// -----------------------------------------
	
	// List pitches made or recieved
	function listPitches($start_row=0, $rows=10){
	
		if($_SESSION['agent_id']){
			$sql = "SELECT * FROM `messages` WHERE `from_agent_id` = '".esc($_SESSION['agent_id'])."' AND `deleted` = 0 AND `prop_id` != 0 ORDER BY `date_sent` DESC";
		}else if($_SESSION['cust_id']){
			$sql = "SELECT * FROM `messages` WHERE `to_cust_id` = '".esc($_SESSION['cust_id'])."' AND `deleted` = 0 AND `prop_id` != 0 ORDER BY `date_sent` DESC";
		}
		
		$sql .= " LIMIT $start_row, $rows";
		
		$res = query($sql);
		
		if(!num_rows($res)){// No messages
			echo '<p>You have no pitches</p>';
			return;
		}
		
		// List the message headers
		$this->dashboardMessageHeader($res);
	}
	
	// -----------------------------------------
	
	function listSentMessages($start_row=0, $rows=10){
		if($_SESSION['agent_id']){
			$sql = "SELECT * FROM `messages` WHERE `from_agent_id` = '".esc($_SESSION['agent_id'])."' AND `deleted` = 0 ORDER BY `date_sent` DESC";
		}else if($_SESSION['cust_id']){
			$sql = "SELECT * FROM `messages` WHERE `from_cust_id` = '".esc($_SESSION['cust_id'])."' AND `deleted` = 0 ORDER BY `date_sent` DESC";
		}
		
		$sql .= " LIMIT $start_row, $rows";
		$res = query($sql);
		
		if(!num_rows($res)){// No messages
			echo '<p>You have no sent messages</p>';
			return;
		}
		
		// List the message headers
		$this->dashboardMessageHeader($res);

	}
	
	// -----------------------------------------
	
	function listDeletedMessages($start_row=0, $rows=10){
		if($_SESSION['agent_id']){
			$sql = "SELECT * FROM `messages` WHERE `from_agent_id` = '".esc($_SESSION['agent_id'])."' AND `deleted` = 1";
		}else if($_SESSION['cust_id']){
			$sql = "SELECT * FROM `messages` WHERE `from_cust_id` = '".esc($_SESSION['cust_id'])."' AND `deleted` = 1";
		}
		
		$sql .= " LIMIT $start_row, $rows";
		$res = query($sql);
		
		if(!num_rows($res)){// No messages
			echo '<p>You have no deleted messages</p>';
			return;
		}
		
		// List the message headers
		$this->dashboardMessageHeader($res);

	}
	
	// -----------------------------------------
	
	// List searched messages on dashboard
	function searchReceivedMessages($terms, $start_row=0, $rows=10){
	
		$clause = " AND (`subject` LIKE '%".esc($terms)."%' OR `message` LIKE '%".esc($terms)."%')";
	
		if($_SESSION['agent_id']){
			$sql = "SELECT * FROM `messages` WHERE `to_agent_id` = '".esc($_SESSION['agent_id'])."' AND `deleted` = 0 $clause ORDER BY `date_sent` DESC";
		}else if($_SESSION['cust_id']){
			$sql = "SELECT * FROM `messages` WHERE `to_cust_id` = '".esc($_SESSION['cust_id'])."' AND `deleted` = 0 $clause ORDER BY `date_sent` DESC";
		}
		
		$sql .= " LIMIT $start_row, $rows";
		
		$res = query($sql);
		
		if(!num_rows($res)){// No messages
			echo '<p style="text-align:center">No messages found</p>';
			return;
		}
		
		// List the message headers
		$this->dashboardMessageHeader($res);
	}
	
	// -----------------------------------------
	
	// Display list of messages in the mail tabs - CUSTOMERS ONLY
	function dashboardMessageHeader($res){
	
		while($rs = fetch_assoc($res)){
			if($rs['to_cust_id']){$recipient = $this->getSenderCustomer($rs['to_cust_id']);}
			if($rs['to_agent_id']){$recipient = $this->getSenderAgent($rs['to_agent_id']);}
			
			if($rs['date_read'] > 1){
				$class = 'msgRead';
			}else{
				$class = 'msgUnread';
			}
			
			$msg = new messenger;
			$msg->retrieve($rs['msg_id']);
			
			
			echo '<div class="row dashMsgRow" id="msgWin_'.$msg->msg_id.'"  onclick="openMsg('.$msg->msg_id.')">';
			
			// Photo
    		echo '<div class="cell">';
			
			if($msg->user_type == 'admin'){// Admin welcome message
				echo '<div class="msgThumb adminMessageDash"></div>';
			}else if($msg->agent_photo){// Agent photo
				echo '<div class="msgThumb" style="background-image:url('.$msg->agent_photo.')"></div>';
			}
			
			echo '</div>';
			
            echo '<div class="cell descr">';
				
				// New message indicator
				if(!$msg->date_read){
                	if($_SESSION['current_dash'] == 'buyer'){
						echo '<div class="newMessage"><img src="images/icon-new-blue.png" alt=""/></div>'.chr(10);
            		}else{
						echo '<div class="newMessage"><img src="images/icon-new.png" alt=""/></div>'.chr(10);
					}
				}
				
				// Headers
				if(strlen($rs['subject'])>40){
					$subject = trim(substr($rs['subject'], 0, 40)).'...';
				}else{$subject = $rs['subject'];}
				
				if( strlen($msg->from_name) > 32){
					$from_name = trim(substr($msg->from_name, 0, 32)).'...';
				}else{
					$from_name = $msg->from_name;
				}
				
				if($msg->user_type == 'admin'){// Admin message from name
					$from_name = '<strong>'.$GLOBALS['system_name'].'</strong>';
				}
				
				echo '<p class="messageTitle">';
				echo html($from_name);
				echo '<br/>';
				echo '<span class="smaller">'.html($subject).'</span></p>';
				echo '<div class="messageBody">';
				echo '<strong>Subject: </strong>'.html($rs['subject']).'<br/>';
				echo '<strong>From: </strong>'.html($msg->from_name).'<br/>';
				echo '<strong>To: </strong>'.html($msg->to_name).'<br/>';
				echo '<strong>Sent: </strong>'.datetime($msg->date_sent).'<br/>';
				echo '<strong>Subject: </strong>'.html($msg->subject).'<br/>';
				echo '<br/>'.nl2br(html($msg->message)).'</div>';
				
				// Date read
				if($msg->date_read){
					//echo '<p><strong>Date read: </strong>'.datetime($msg->date_read).'</p>'.chr(10);
				}
				
			echo '</div>';
			echo '</div>';

		}
	}
	
	// -----------------------------------------
	
	// Display list of messages in the mail tabs - AGENTS ONLY
	function dashboardMessageHeaderAgent($res){
	
		while($rs = fetch_assoc($res)){
			if($rs['to_cust_id']){$recipient = $this->getSenderCustomer($rs['to_cust_id']);}
			if($rs['to_agent_id']){$recipient = $this->getSenderAgent($rs['to_agent_id']);}
			
			if($rs['date_read'] > 1){
				$class = 'msgRead';
			}else{
				$class = 'msgUnread';
			}
			
			$msg = new messenger;
			$msg->retrieve($rs['msg_id']);
			
			// Headers
			if(strlen($msg->subject)>40){
				$subject = trim(substr($msg->subject, 0, 40)).'...';
			}else{
				$subject = $msg->subject;
			}
			
			if( strlen($msg->from_name) > 32){
				$from_name = trim(substr($msg->from_name, 0, 32)).'...';
			}else{
				$from_name = $msg->from_name;
			}
			
			echo '<div class="vendMsg" id="msgWin_'.$msg->msg_id.'" onclick="openMsg('.$msg->msg_id.')">';
 			echo '<div><p>'.html($from_name).'<br/>'.html($rs['subject']).'</p>';

			echo '</div>'.chr(10).chr(10);
			
			echo '<div class="newMsg newMsg'.$_SESSION['current_dash'].'">NEW</div>'.chr(10);
			
        	echo '</div>'.chr(10);
			
		}
	}
	
	// -----------------------------------------
	
	
	function getSenderCustomer($cust_id){
		$sender = result("SELECT CONCAT(`firstname`, ' ', `surname`) FROM `customers` WHERE `cust_id` = $cust_id");
		return($sender);
	}
	
	// -----------------------------------------
	
	function getSenderAgent($agent_id){
		$res = query("SELECT `name`, `firstname`, `surname` FROM `agents` WHERE `agent_id` = $agent_id");
		$rs = fetch_assoc($res);
		$sender = html($rs['firstname'].' '.$rs['surname'].', '.$rs['name']);
		return($sender);
	}
	
	// -----------------------------------------
	
	// Render a send message window
	function messageWin($id){
		if(!$id || !is_numeric($id)){return;}
		$c = new customer;
		$a = new agent;
		
		if($_SESSION['agent_id']){// Agent messaging a customer
			$c->retrieve($id);
			$a->retrieve($_SESSION['agent_id']);
			$agent_sending = 1;
			$contact_name = $c->name;
		}
		if($_SESSION['cust_id']){// Customer messaging an agent
			$c->retrieve($_SESSION['cust_id']);
			$a->retrieve($id);
			$agent_sending = 0;
			$contact_name = $a->contact_name;
		}
		
		// Closer
		echo '<div class="closer" onclick="messageClose()"></div>';
		
		// title
		echo '<h2>Send a Message</h2>';
		
		// Fields
		echo '<div>To: '.html($contact_name).'</div>'.chr(10);
		echo '<div>Agency: '.html($a->name).'</div>'.chr(10);
		echo '<div><input type="text" name="subject" id="subject" value="Subject" onclick="clearVal(\'subject\', \'Subject\')"/></div>'.chr(10);
		echo '<div><textarea name="message" id="message" rows="15" onclick="clearVal(\'message\', \'Your message\')">Your message</textarea>'.chr(10);
		
		// IDs
		if($agent_sending){
			echo '<input type="hidden" name="to_cust_id" id="to_cust_id" value="'.$c->cust_id.'"/>';
		}else{
			echo '<input type="hidden" name="to_agent_id" id="to_agent_id" value="'.$a->agent_id.'"/>';
		}
		// Send it
		echo '<div><input type="button" name="send" value="Send" onclick="sendMessage()"/></div>'.chr(10);
	}
	
	// -----------------------------------------
	
	// Send a new message (or saves a draft)
	function send($draft=0){
		while(list($k, $v)=each($_POST)){
			$this->$k = cleanInput($v);
			$$k = esc($this->$k);
		}
		
		$this->message = $this->msgContent;
		$message = esc($this->message);
		
		if(!$this->subject){exit("Please enter a subject");}
		if(!$this->message){exit("Please enter your message");}
		
		if(count($GLOBALS['a_errors'])){return false;}
		
		if(!is_numeric($from_cust_id)){$from_cust_id = 0;}
		if(!is_numeric($to_cust_id)){$to_cust_id = 0;}
		if(!is_numeric($from_agent_id)){$from_agent_id = 0;}
		if(!is_numeric($to_agent_id)){$to_agent_id = 0;}
		
		if($_SESSION['cust_id']){
			$from_cust_id = $_SESSION['cust_id'];
		}
		if($_SESSION['agent_id']){
			$from_agent_id = $_SESSION['agent_id'];
		}

		if(!is_numeric($this->to_agent_id)){$this->to_agent_id = 0;}
		if(!is_numeric($this->to_cust_id)){$this->to_cust_id = 0;}
		
		if(!$to_cust_id && !$to_agent_id){
			echo '<div class="pad">No recipient selected for this message</div>';
			return;
		}
		
		// Set the user's role
		if($_SESSION['agent_id']){
			$user_type = 'agent';
		}else if($_SESSION['current_dash'] == 'seller'){
			$user_type = 'seller';
		}else if($_SESSION['current_dash'] == 'buyer'){
			$user_type = 'buyer';
		}
		
		if($draft){$status = 'draft';}else{$status = 'sent';}
		
		$sql = "INSERT INTO `messages` (
			`subject`, 
			`message`, 
			`from_cust_id`, 
			`from_agent_id`, 
			`to_cust_id`, 
			`to_agent_id`, 
			`date_sent`,
			`status`,
			`user_type`
		)VALUES(
			'$subject', 
			'$msgContent', 
			'$from_cust_id', 
			'$from_agent_id', 
			'$to_cust_id', 
			'$to_agent_id', 
			'".time()."',
			'$status',
			'$user_type'
		)";
		#exit($sql);
		query($sql);
		$this->msg_id = insert_id();
		
		// If it's a draft we go no further
		if($draft){
			return;
		}
		
		// If this was a draft being sent we need to delete the original
		if(is_numeric($_POST['msg_id'])){
			$status = result("SELECT `status` FROM `messages` WHERE `msg_id` = '".$_POST['msg_id']."' AND (`from_cust_id` = '".$_SESSION['cust_id']."' OR `from_agent_id` = '".$_SESSION['agent_id']."')");
			if($status == 'draft'){
				query("DELETE FROM `messages` WHERE `msg_id` = '".$_POST['msg_id']."' AND `status` = 'draft' LIMIT 1");
			}
		}
		
		// Send the recipient an email notification
		$email = new email;
		$email->sendNewMessageNotification($this->msg_id);
		
		// Replacement content for the message window
		echo '<div class="pad" style="text-align:center">';
		echo '<p>Thank you. Your message has been sent.</p>';
		echo '<div>';
		exit();
		return true;
	}
	
	// -----------------------------------------
	
	// Load a message in the AJAX function to read a message
	function loadMessage($msg_id){

		if(!$_SESSION['agent_id'] && !$_SESSION['cust_id']){return false;}
		
		$this->retrieve($msg_id);

		if(!$this->msg_id){
			echo '<p>The message could not be found</p>';
			return;
		}

		$ok = 0;
		if($_SESSION['cust_id']){
			if($this->from_cust_id == $_SESSION['cust_id'] || $this->to_cust_id == $_SESSION['cust_id']){
				$ok = true;
			}
		}
		if($_SESSION['agent_id']){
			if($this->from_agent_id == $_SESSION['agent_id'] || $this->to_agent_id == $_SESSION['agent_id']){
				$ok = true;
			}
		}
		
		if(!$ok){echo 'You don\'t have permission to read this message'; return;}
		
		echo '<div class="pad">';
		
		echo '<p><strong>From: </strong>'.html($this->from_name).'<br/>';
		echo '<strong>To: </strong>'.html($this->to_name).'<br/>';
		echo '<strong>Sent: </strong>'.datetime($this->date_sent).'<br/>';
		echo '<strong>Subject: </strong>'.html($this->subject).'</p>';
		echo '<p>'.nl2br(html($this->message)).'</p>';
		
		echo '</div>';
		
		$_SESSION['msg_id'] = $this->msg_id;
		
		// Mark this message as having been read
		$this->read($msg_id);
	}
	
	// -----------------------------------------
	
	// Show a message in the AJAX function to read a message
	function replyWindow($msg_id){

		if(!$_SESSION['agent_id'] && !$_SESSION['cust_id']){return false;}
		
		if($msg_id){// Is this a reply?
			$this->retrieve($msg_id);
			if(!$this->msg_id){
				echo '<p>The message could not be found</p>';
				return;
			}
		}
		
		$ok = 0;
		if($_SESSION['cust_id']){
			if($this->from_cust_id == $_SESSION['cust_id'] || $this->to_cust_id == $_SESSION['cust_id']){
				$ok = true;
			}
			$name = result("SELECT CONCAT(`firstname`, ' ', `surname`) FROM `agents` WHERE `agent_id` = $this->from_agent_id");
		}
		if($_SESSION['agent_id']){
			if($this->from_agent_id == $_SESSION['agent_id'] || $this->to_agent_id == $_SESSION['agent_id']){
				$ok = true;
			}
			$name = result("SELECT CONCAT(`firstname`, ' ', `surname`) FROM `customers` WHERE `cust_id` = $this->from_cust_id");
		}
		
		if(!$ok){echo 'You don\'t have permission to read this message'; return;}
		
		// Form
		echo '<form name="replyform" id="replyform" method="post">'.chr(10);
		echo '<p><strong>To: </strong>'.$this->from_name.'</p>';
		echo '<p>Subject:<br/><input type="text" name="subject" id="subject" value="Re: '.html($this->subject).'" style="width:90%"/></p>';
		echo '<p>Message:<br/><textarea name="message" id="message" rows="15" style="width:90%">';
		#echo '<!-- Original message sent '.datetime($this->date_sent).' by '.$name.' -->'.chr(10).'"'.nl2br(html($this->message)).'"';
		echo '</textarea></p>';
		// Buttons
		echo '<div class="button" onclick="sendMessage('.$msg_id.')">Send message</div>';
		echo '<div class="button" onclick="closeMessage()">Close message</div>';
		
		echo '<input type="hidden" name="to_cust_id" id="to_cust_id" value="'.$this->from_cust_id.'"/>';
		echo '<input type="hidden" name="to_agent_id" id="to_agent_id" value="'.$this->from_agent_id.'"/>';
		
		echo '</form>';
		
		// Mark this message as having been read
		$this->read($msg_id);
	}
	
	// -----------------------------------------
	
	// Show a message in the AJAX function to read a message
	function newMessage($id){

		if(!$_SESSION['agent_id'] && !$_SESSION['cust_id']){return false;}
		
		if($_SESSION['cust_id']){
			$a = new agent;
			$a->retrieve($id);
			if($a->contact_name){
				$name = $a->contact_name.', ';
			}
			$name .= $a->contact_name;
		}
		if($_SESSION['agent_id']){
			$c = new customer;
			$c->retrieve($id);
			$name = $c->name;
		}
		
		// Form
		echo '<form name="replyform" id="replyform" method="post">'.chr(10);
		echo '<p><strong>To:</strong> '.html($name).'</p>';
		echo '<p>Subject:<br/><input type="text" name="subject" id="subject" value="'.html($this->subject).'" style="width:90%"/></p>';
		echo '<p>Message:<br/><textarea name="message" id="message" rows="15" style="width:90%">';
		echo '</textarea></p>';
		// Buttons
		echo '<div class="button" onclick="sendMessage('.$msg_id.')">Send message</div>';
		echo '<div class="button" onclick="closeMessage()">Close message</div>';
		
		// Hidden fields
		if($_SESSION['cust_id']){
			echo '<input type="hidden" name="to_agent_id" id="to_agent_id" value="'.$id.'"/>';
		}
		if($_SESSION['agent_id']){
			echo '<input type="hidden" name="to_cust_id" id="to_cust_id" value="'.$id.'"/>';
		}
		
		echo '</form>';
		
	}
	
	// -----------------------------------------
	
	function loadAllMessages(){
		
		echo '<div id="msg_inbox" class="msgFolder">';
		$this->listFolder('inbox');
		echo '</div>';
		
		
		echo '<div id="msg_proposals" class="msgFolder">';
		$this->listFolder('proposals');
		echo '</div>';
		
		
		echo '<div id="msg_starred" class="msgFolder">';
		$this->listFolder('starred');
		echo '</div>';
		
		
		echo '<div id="msg_drafts" class="msgFolder">';
		$this->listFolder('drafts');
		echo '</div>';
		
		
		echo '<div id="msg_sent" class="msgFolder">';
		$this->listFolder('sent');
		echo '</div>';
		
		
		echo '<div id="msg_deleted" class="msgFolder">';
		$this->listFolder('deleted');
		echo '</div>';
		
		$agent = new agent;
		echo '<div id="msg_buyers" class="msgFolder">';
		$agent->listMyCustomers('buyer');
		echo '</div>';
		
		echo '<div id="msg_sellers" class="msgFolder">';
		$agent->listMyCustomers('seller');
		echo '</div>';
		
	}
	
	
	// -----------------------------------------
	
	function listFolder($type){
		#echo $type.'<hr/>';
		if($_SESSION['agent_id']){
			$from_clause = " AND `from_agent_id` = ".$_SESSION['agent_id'];
			$to_clause = " AND `to_agent_id` = ".$_SESSION['agent_id'];
			$proposal_clause = " AND `from_agent_id` = ".$_SESSION['agent_id'];
		}
		
		if($_SESSION['cust_id']){
			$from_clause = " AND `from_cust_id` = ".$_SESSION['cust_id'];
			$to_clause = " AND `to_cust_id` = ".$_SESSION['cust_id'];
			$proposal_clause = " AND `to_cust_id` = ".$_SESSION['cust_id'];
			if($_SESSION['load_proposals_agent_id'] && $type == 'proposals'){// It's a filter on agent proposal
				$proposal_clause .= " AND `from_agent_id` = ".$_SESSION['load_proposals_agent_id'];
				unset($_SESSION['load_proposals_agent_id']);
			}
		}
		
	
		if($type == 'inbox'){
			$sql = "SELECT `msg_id` FROM `messages` WHERE `status` = 'sent' AND `deleted` = 0 $to_clause ORDER BY `date_sent` DESC";
		}
		if($type == 'proposals'){
			$sql = "SELECT `msg_id` FROM `messages` WHERE `status` = 'sent' AND `deleted` = 0 AND `prop_id` <> 0 $proposal_clause ORDER BY `date_sent` DESC";	
		}
		if($type == 'starred'){
			$sql = "SELECT `msg_id` FROM `messages` WHERE `starred` = 1 AND `deleted` = 0 $to_clause ORDER BY `date_sent` DESC";
		}
		if($type == 'drafts'){
			$sql = "SELECT `msg_id` FROM `messages` WHERE `status` = 'draft' AND `deleted` = 0 $from_clause ORDER BY `date_sent` DESC";
		}
		if($type == 'sent'){
			$sql = "SELECT `msg_id` FROM `messages` WHERE `status` = 'sent' AND `deleted` = 0 $from_clause ORDER BY `date_sent` DESC";
		}
		if($type == 'deleted'){
			$sql = "SELECT `msg_id` FROM `messages` WHERE `deleted` = 1 $to_clause ORDER BY `date_sent` DESC";
		}
		
		
		// Customer panels
		if($_SESSION['agent_id']){
			if($type == 'sellers'){
				$agent = new agent;
				$agent->listMyCustomers('seller');
				return;
			}
			if($type == 'buyers'){
				$agent = new agent;
				$agent->listMyCustomers('buyer');
				return;
			}
		}
		
		#echo $sql;
		$res = query($sql);
		
		if(!num_rows($res)){echo '<p>Folder contains no messages</p>'; return;}
		
		while( $rs = fetch_assoc($res) ){// loop
			$this->messageRow($rs['msg_id']);
		}
		
	}
	
	// -----------------------------------------
	
	// Single message row in the main message screen
	function messageRow($msg_id){
		$m = new messenger;
		$m->retrieve($msg_id);
		if($m->user_type == 'buyer'){$user_class="buyer_msg";}
		if($m->user_type == 'seller'){$user_class="seller_msg";}
		if($m->user_type == 'agent'){$user_class="agent_msg";}
		if($m->date_read > 1){$style = ' style="background-color:#ebebeb" ';}
		
		// Which javascript function to call?
		if($m->status == 'draft'){
			$javascript_funct = 'loadDraft';
		}else{
			$javascript_funct = 'loadMessage';
		}
		
		// Start the row div
		echo '<div class="messagerow vCenter '.$user_class.'" onclick="'.$javascript_funct.'('.$msg_id.');"'.$style.'>'.chr(10);
		
		// Starred?
		if($m->starred && $m->date_read ){
			if($_SESSION['agent_id'] == $m->to_agent_id || $_SESSION['cust_id'] == $m->to_cust_id){
				echo '<div class="starred"></div>';
			}
		}
		
		// New message
		if(!$m->date_read && $m->status != 'draft'){
			if($_SESSION['agent_id'] == $m->to_agent_id || $_SESSION['cust_id'] == $m->to_cust_id){
				echo '<div class="newMsg newMsg'.$_SESSION['current_dash'].'">NEW</div>';
			}
		}
		
		// Trim the subject it it's too long
		if(strlen($m->subject) >36){$m->subject = trim(substr($m->subject, 0, 36)).'...';}
		if(strlen($m->from_name) >36){$m->from_name = trim(substr($m->from_name, 0, 36)).'...';}
		
		// Admin message from name
		if($m->user_type == 'admin'){
			$m->from_name = '<strong>'.html($GLOBALS['system_name']).'</strong>';
		}
		
		// Message + thumb
		echo '<div>';
		if($m->user_type == 'admin'){//Admin message
			echo '<div class="agentMsgThumb adminMessage"></div>';
		}else if($m->from_agent_id){
			$a = new agent;
			$a->retrieve($m->from_agent_id);
			echo '<div class="agentMsgThumb" style="background-image:url('.$a->photo_url.')"></div>';
		}else if($m->to_agent_id){
			$a = new agent;
			$a->retrieve($m->to_agent_id);
			if($_SESSION['cust_id']){
				echo '<div class="agentMsgThumb" style="background-image:url('.$a->photo_url.')"></div>';
			}
		}
		
		
		// To/from
		echo '<div class="msgBrief" title="Click here to read this message">';
		if($m->status == 'draft'){
			echo '<strong>'.html($m->subject).'</strong><br/>To: '.$m->to_name.'</div>';
		}else{
			echo '<strong>'.html($m->subject).'</strong><br/>'.$m->from_name.'</div>';
		}
		echo '</div>';
		
		
		echo '</div>'.chr(10);
	}
	
	
	// -----------------------------------------
	
	
	// Generate a message form
	function messageForm($msg_id=0){
		
		$m = new messenger;

		// If it's a new message
		if(!$msg_id){

			if($_SESSION['agent_id']){// Agent sending the message
			
				$m->to_cust_id = $_POST['cust_id']*1;
				if(!$m->to_cust_id || !is_numeric($m->to_cust_id)){
					echo 'Please select a recipient for your message first';
					exit();
				}
				
				$m->from_cust_id = $m->to_cust_id;
			}
			
			if($_SESSION['cust_id']){// Customer sending the message
			
				$m->to_agent_id = $_POST['agent_id']*1;
				if(!$m->to_agent_id || !is_numeric($m->to_agent_id)){
					echo 'Please select a recipient for your message first';
					exit();
				}
				
				$m->from_agent_id = $m->to_agent_id;
			}
			
		}else{// There's a message stored
			
			$m = new messenger;
			$m->retrieve($msg_id);
			
			if($m->status == 'draft'){// It's recalling a draft message	
				// Do nothing at this stage
			}else{// It's a reply
			
				if($m->from_agent_id == $_SESSION['agent_id']){
					return;
				}
				
			}
			
		}
		
		// Is there a subject line?
		if(!$m->subject){
			$m->subject = 'A new message';
		}
		
		echo '<form id="messageForm" name="messageForm" method="post">';
		
		if($m->status == 'draft'){// Recalling a draft
		
			// Replying to a message
			$field_type = 'hidden';
			echo '<input type="'.$field_type.'" id="to_agent_id" name="to_agent_id" value="'.$m->to_agent_id.'"/>';
			echo '<input type="'.$field_type.'" id="to_cust_id" name="to_cust_id" value="'.$m->to_cust_id.'"/>';
			echo '<input type="'.$field_type.'" id="subject" name="subject" value="Re: '.$m->subject.'"/>';
			echo '<input type="'.$field_type.'" id="msg_id" name="msg_id" value="'.$m->msg_id.'"/>';
			echo '<textarea name="msgContent" id="msgContent" autofocus>'.$m->message.'</textarea>';
		
		}else{
			
			// Replying to a message
			$field_type = 'hidden';
			echo '<input type="'.$field_type.'" id="to_agent_id" name="to_agent_id" value="'.$m->from_agent_id.'"/>';
			echo '<input type="'.$field_type.'" id="to_cust_id" name="to_cust_id" value="'.$m->from_cust_id.'"/>';
			echo '<input type="'.$field_type.'" id="subject" name="subject" value="Re: '.$m->subject.'"/>';
			echo '<textarea name="msgContent" id="msgContent" autofocus>'.chr(10).chr(10).chr(10).chr(10).$m->message_with_headers.'</textarea>';
			
		}

		echo '</form>';
	}
	
	// -----------------------------------------
	
	// List or search a customer's contact hostory with this agent
	function customerContactHistory($agent_id, $terms=''){
		
		if(!$agent_id || !is_numeric($agent_id)){return;}
		if(!$_SESSION['cust_id']){return;}
		
		if($terms){
			$clause = " AND (`subject` LIKE '%".esc($terms)."%' OR `message` LIKE '%".esc($terms)."%')";
		}
		$sql = "SELECT `msg_id` FROM `messages` WHERE `from_cust_id` = '".$_SESSION['cust_id']."' AND `to_agent_id` = $agent_id AND `status` = 'sent' AND `deleted` = 0 $clause ORDER BY `date_sent` DESC";
		#echo $sql;
		$res = query($sql);
		
		if(!num_rows($res)){
			if($terms){
				echo '<div class="centerMe" style="height:60px">There are no matching items</div>';
			}else{
				echo '<div class="centerMe" style="height:60px">You have no contact history with this agent</div>';
			}
			return;
		}
		
		while($rs = fetch_assoc($res)){
			$this->messageRow($rs['msg_id']);
		}
		
	}
	
	// -----------------------------------------
	
	// List or search an agent's contact hostory with this customer
	function agentContactHistory($cust_id, $terms=''){
		
		if(!$cust_id || !is_numeric($cust_id)){return;}
		if(!$_SESSION['agent_id']){return;}
		
		if($terms){
			$clause = " AND (`subject` LIKE '%".esc($terms)."%' OR `message` LIKE '%".esc($terms)."%')";
		}
		$sql = "SELECT `msg_id` FROM `messages` WHERE `from_agent_id` = '".$_SESSION['agent_id']."' AND `to_cust_id` = $cust_id AND `status` = 'sent' AND `deleted` = 0 $clause ORDER BY `date_sent` DESC";
		#echo $sql;
		$res = query($sql);
		
		if(!num_rows($res)){
			if($terms){
				echo '<div class="centerMe" style="height:60px">There are no matching items</div>';
			}else{
				echo '<div class="centerMe" style="height:60px">You have no contact history with this user</div>';
			}
			return;
		}
		
		while($rs = fetch_assoc($res)){
			$this->messageRow($rs['msg_id']);
		}
		
	}
	
	// -----------------------------------------
	
	// Adds a welcome message to the user's mailbox when they sign up
	function addWelcomeMessage($type){
		
		
		
		if($type == 'buyer' && $this->cust_id){
			$agent = new agent;
			$matching_agents = $agent->customersAgentCount($type);
			$subject = 'Here\'s what happens next';
			$msg = 'Hi '.$this->firstname.',

Welcome to Off Market London and thanks for registering. 

There are currently '.$matching_agents.' active agents in your area. To get started, visit your dashboard to select who you’d like to contact you. The agents you enable to communicate with you will be sent an alert that you’ve registered and will have the opportunity to view the information you’ve provided. They can then send you proposals if they’ve matched you to a potential property. 

Any agent you have had contact with will appear at the top of your ‘My Agents’ list. You can click on any agent in this list to view their profile. 

We recommend only giving out your personal contact details if you’re happy to take things further with that agent.

Remember, you can control the amount of notification emails you receive by going to the ‘My Settings’ tab on your dashboard. You can choose to have a daily or weekly email.

Let us know what you think or spread the word by following us on Twitter, Facebook and Instagram.

The Off Market London team
';
			$sql = "INSERT INTO `messages` (`to_cust_id`, `subject`, `message`, `status`, `user_type`, `date_sent`)VALUES(".$this->cust_id.", '".esc($subject)."', '".esc($msg)."', 'sent', 'admin', ".time().")";
		}
		
		if($type == 'seller' && $this->cust_id){
			$agent = new agent;
			$matching_agents = $agent->customersAgentCount($type);
			$subject = 'Here\'s what happens next';
			$msg = 'Hi '.$this->firstname.',

Welcome to Off Market London and thanks for registering. 

There are currently '.$matching_agents.' active agents in your area. To get started, visit your dashboard to select who you’d like to contact you. The agents you enable to communicate with you will be sent an alert that you’ve registered and will have the opportunity to view the information you’ve provided. They can then send you proposals if they’ve matched you to a potential buyer

Any agent you have had contact with will appear at the top of your ‘My Agents’ list. You can click on any agent in this list to view their profile. 

We recommend only giving out your personal contact details if you’re happy to take things further with that agent.

Remember, you can control the amount of notification emails you receive by going to the ‘My Settings’ tab on your dashboard. You can choose to have a daily or weekly email.

Let us know what you think or spread the word by following us on Twitter, Facebook and Instagram.

The Off Market London team
';
			$sql = "INSERT INTO `messages` (`to_cust_id`, `subject`, `message`, `status`, `user_type`, `date_sent`)VALUES(".$this->cust_id.", '".esc($subject)."', '".esc($msg)."', 'sent', 'admin', ".time().")";
		}
		
		if($type == 'agent' && $this->agent_id){
			$subject = 'Here\'s what happens next';
			$msg = 'The message for the agent goes here...';
			$sql = "INSERT INTO `messages` (`to_agent_id`, `subject`, `message`, `status`, `user_type`, `date_sent`)VALUES(".$this->agent_id.", '".esc($subject)."', '".esc($msg)."', 'sent', 'admin', ".time().")";
		}
		
		if($sql){
			query($sql);
		}
		
	}
	
	// -----------------------------------------
	
	
	// -----------------------------------------
	
}
?>
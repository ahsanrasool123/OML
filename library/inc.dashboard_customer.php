<?php
// Customer dashboard
$cust->retrieve($_SESSION['cust_id']);
?>

<div class="table">
	<div class="row">
    
    
    	<!-- col 1 -->
    	<div class="cell dashCol1">
			
            <div id="dashMenuWrap">
            
            	<div class="welcomeBox">
            	<!--
                <img src="../images/nologo.png" alt=""/>
                -->
            	Welcome<br/>
            	<?=html($cust->name);?>
            	</div>
            
            	<ul id="dashMenu">
            	    <li><a href="javascript:dashNav('messages')">My Messages</a></li>
            	    <li><a href="javascript:dashNav('profile')">My Profile</a></li>
            	    <? if($cust->buyer){?>
            	    <li><a href="javascript:dashNav('postcodes')">My Postcodes</a></li>
            	    <? } ?>
            	    <? if($cust->seller){?>
            	    <li><a href="javascript:dashNav('properties')">My Properties</a></li>
            	    <? } ?>
            	    <li><a href="javascript:dashNav('agents')">My Agents</a></li>
            	</ul>
            
            </div>

		</div>
        
        <!-- col 2 -->
        <div class="cell dashCol2">
        
        <h2>My Dashboard</h2>
		
	<div class="dashBox" id="requirements">
    	<a href="#" class="editbutton">EDIT</a>
    	<h2>Your Property Requirements</h2>
        <div class="pad">
        	<p><strong>Price range: 200k - 400k</strong> </p>
        	<p><strong>Bedrooms: </strong> 4</p>
        	<p><strong>Size: </strong> 120 sqr tf</p>
        	<p>Lorem ipsum content goes here</p>
        </div>
    </div>
    
    
   
    
    <div class="dashBox" id="messages">
    	<h2>My Messages</h2>
        <div class="pad">
        	<?
			// Messages
			echo '<div id="dbMessagesWrap">';
			$msg = new messenger;
			$msg->listAll();
			echo '</div>';
			?>
        </div>
    </div>
    


	<div class="dashBox" id="profile" style="display:block">
    	<a href="javascript:dashNav('editprofile')" class="editbutton">EDIT</a>
    	<h2>Profile</h2>
        <div class="pad">
        	<? $cust->showProfile();?>
        </div>
    </div>
    
    
    
    <div class="dashBox" id="editprofile">
    	<a href="javascript:dashNav('editprofile')" class="editbutton">EDIT</a>
    	<h2>Edit Profile</h2>
        <div class="pad">
		<form enctype="multipart/form-data" name="uploadphoto" method="post">

            <div id="errorboxcorp" class="errorbox"></div>
			  <div class="table">
              	
                <div class="row">
              		<div class="cell">

						<?
                        // First Name
						formField('firstname', $cust->firstname, 'First name');
						
						// Last Name
						formField('surname', $cust->surname, 'Last name');
						
						// Email
						formField('email', $cust->email, 'Email address');
						
						// Telephone
						formField('tel', $cust->tel, 'Telephone');
						?>
                        
                        </div>
                        <div class="cell">
                        <?
						// Bedrooms
						dropDown(array(1, 2, 3, 4, 5, 6, 7, 8), $cust->bedrooms, 'Number of Bedrooms Required');
						
						// Price Range
						dropdown(array('Coming soon...'), $cust->price_range, 'My Price range');
						
						// Telephone
						textarea('requirements', $cust->requirements, 'Requirements');
						?>
                        
			  		</div>
              	</div>
                
              </div>
			
            <div id="okboxcorp" class="okbox"><p>Your profile has been updated.</p></div>
            <input type="hidden" name="agent_id" id="agent_id" value=""/>
             <input type="button" name="updateProf" value="Update profile" onclick="updateCorporateProfile()"/>
            </form>
        </div>
    </div>
    
    
    
    <div class="dashBox" id="postcodes">
    	<a href="#" class="editbutton">EDIT</a>
    	<h2>My postcodes</h2>
        <div class="pad">
        	<?
			// Render the map of selected postcodes
			echo '<div id="markerMap">';
			$map = new map;
			$map->showCustomerPostcodes();
			echo '</div>';
			// Render the postcode selector
			$map = new map;
			$map->postcodeSelector();
			?>
        </div>
    </div>
    
    
    
    
    <div class="dashBox" id="properties">
    	<a href="#" class="editbutton">EDIT</a>
    	<h2>My Properties</h2>
        <div class="pad">
        	<? if($cust->seller){?>
            <div id="dbPropertyWrap">
            <? $cust->listMyProperty(); ?>
            </div>
            <? } ?>
            <p><a href="/seller/property/" class="button">Add a property</a></p>
        </div>
    </div>
    
    
    
    
    <div class="dashBox" id="agents">
    	<a href="#" class="editbutton">EDIT</a>
    	<h2>My Agents</h2>
        <div class="pad">
        	<?
            // Select agents from a list
			$agent->agentSelector();
			?>
        </div>
    </div>





		</div><!-- </td> -->
	</div><!-- </tr> -->
</div><!-- close table -->



<!-- ends content -->
</div>




<footer>
	<p>Join us on</p>
    <ul id="socials">
    	<li><a href="<?=$GLOBALS['url_facebook'];?>" target="_blank" title="Join us on Facebook"><img src="images/icon-fb.svg" alt="Join us on Facebook"/></a></li>
    	<li><a href="<?=$GLOBALS['url_twitter'];?>" target="_blank" title="Join us on Twitter"><img src="images/icon-twitter.svg" alt="Join us on Twitter"/></a></li>
    	<li><a href="<?=$GLOBALS['url_linkedin'];?>" target="_blank" title="Join us on LinkedIn"><img src="images/icon-linkedin.svg" alt="Join us on LinkedIn"/></a></li>
    	<li><a href="<?=$GLOBALS['url_instagram'];?>" target="_blank" title="Join us on Instagram"><img src="images/icon-inst.svg" alt="Join us on Instagram"/></a></li>
    </ul>
    <p>16 High Holborn, London, WC1V 6BX | +44 (0)207 917 6800 | <a href="mailto:info@offmarketlondon.co.uk">info@offmarketlondon.co.uk</a></p>
</footer>


<!-- ends wrap -->
</div>


<? if($_SERVER['REMOTE_ADDR'] == '82.33.188.18' || $_SERVER['REMOTE_ADDR'] == '82.37.145.171'){ ?>
<div id="debug">
<p>DEBUG OUTPUT - FOR DEVELOPMENT ONLY</p>
<? listMe($_SESSION);?>
</div>
<? } ?>

<div id="null"><!-- Container for AJAX returns --></div>

</body>
</html>
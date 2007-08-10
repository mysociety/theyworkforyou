<p><strong>Link Policy:</strong> No person or organisation needs permission to link to us. Be our guest. You can deep link to any page you like, from any page, at any time, without asking.</p>

<p>Alternatively, if you would like to place a TheyWorkForYou search box on your site, like this one&hellip;</p>

<? $link_to_us_form = <<<END
<!-- TheyWorkForYou box, begin -->
<div style="position: relative; width: 17em; color: #000000; background-color: #E8FDCB; font-family: Arial, Geneva, Sans-serif; margin-bottom: 1em; border: 1px solid #4d6c25; padding: 0 10px 2em 10px;">
	<h5 style="font-family: Arial, Geneva, Sans-serif; font-size: 1.4em; position: absolute; margin: 0; bottom: 2px; right: 10px;"  title="TheyWorkForYou.com"><a style="color: #880101; text-decoration: none; font-weight: normal;" href="http://www.TheyWorkForYou.com"><em style="font-weight: bold; font-style: normal;">TheyWorkFor</em><strong style="color: #cc0000; font-weight: bolder;">You</strong>.com</a></h5>
	<form style="margin: 0; padding: 5px 0 0 0;" title="Find out about your MP" action="http://www.theyworkforyou.com/mp/" method="get">
		<label style="display: block; font-size: small; font-weight: bold; margin: 0 0 2px 0;" for="pc">Find out more about your <abbr class="display: inline; speak: spell-out;" title="Member of Parliament">MP</abbr></label>
		<input id="pc" name="pc" style="width: 12em; border: solid 1px #4D6C25;" title="Enter your UK postcode here" tabindex="1" type="text" size="8" maxlength="20" value="Your Postcode"> 
		<input id="Submit1" name="Submit1" style="border: solid 0px #4d6c25; background-color: #4D6C25; color: #ffffff; font-weight: bold; text-transform: uppercase;" title="Submit search" tabindex="2" type="submit" value="Go">
	</form>
	<form style="margin: 0; padding: 5px 0 0 0;" title="Search everything said in Parliament since 2001" action="http://www.theyworkforyou.com/search/" method="get">
		<label style="display: block; font-size: small; font-weight: bold; margin: 0 0 2px 0;" for="s">Search Parliament</label>
		<input id="s" name="s" style="width: 12em; border: solid 1px #4D6C25;" title="Type what you're looking for" type="text" tabindex="3" size="15" maxlength="100" value="Your Search">
		<input id="Submit2" name="Submit2" style="border: solid 0px #4d6c25; background-color: #4D6C25; color: #ffffff; font-weight: bold; text-transform: uppercase;" title="Submit search" tabindex="4" type="submit" value="Go">
		<br>
	</form>
</div> 
<!-- TheyWorkForYou box, end -->
END;
print $link_to_us_form;
?>

<p>Cut and paste the code below into your webpage:</p>
<textarea class="sourcecode" style="width: 100%; height: 20em;">
<? print htmlspecialchars($link_to_us_form); ?>
</textarea>

<p>Please retain the link to <a href="http://www.theyworkforyou.com">theyworkforyou.com</a> (Google points mean prizes). Any questions, just drop us a line at: <a href="http://www.theyworkforyou.com/contact/">http://www.theyworkforyou.com/contact/</a>
</p>
																																																																																																																																																													

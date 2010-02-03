<?php
/*
 * survey/index.php:
 * Ask and store questionnaire for research.
 *  
 * Copyright (c) 2009 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: index.php,v 1.4 2010-01-20 10:48:58 matthew Exp $
 * 
 */

include_once "../../includes/easyparliament/init.php";
require_once "../../../commonlib/phplib/random.php";
require_once "../../../commonlib/phplib/auth.php";
$this_page = 'survey';
$PAGE->page_start();

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

if (get_http_var('ignore')) { # non-JS clicking of Close survey teaser
	setcookie('survey', '1b', time()+60*60*24*365, '/');
	if (!$referer)
		$referer = '/';
	header('Location: ' . $referer);
	exit;
}

$find = get_http_var('answer');
if ($find != 'yes' && $find != 'no') {
	$PAGE->error_message('Illegal answer provided', true);
	exit;
}

if (!isset($_COOKIE['survey'])) {
	$PAGE->error_message('You need to have got a survey cookie before coming here, sorry.', true);
	exit;
}

$show_survey_qn = $_COOKIE['survey'];
if ($show_survey_qn == 2) {
	header('Location: http://' . DOMAIN . '/survey/done', true, 301);
	exit;
}

setcookie('survey', '1b', time()+60*60*24*365, '/');
if ($show_survey_qn == 1) {
	$db = new ParlDB;
	$db->query("UPDATE survey SET $find = $find + 1");
}

$user_code = bin2hex(urandom_bytes(16));
$auth_signature = auth_sign_with_shared_secret($user_code, OPTION_SURVEY_SECRET);

if ($find == 'yes') { ?>
<div style="margin:1em; border: solid 2px #cc9933; background-color: #ffffcc; padding: 4px; font-size:larger;">
Glad we could help you!
Maybe you could help us by answering some questions in our user survey which will contribute to make TheyWorkForYou even better &ndash; five minutes should be enough.
If you don&rsquo;t want to participate, thanks anyway<? if ($referer) print ', <a href="' . $referer . '">return to where you were</a>'; ?>.
</div>
<? } else { ?>
<div style="margin:1em; padding: 4px; border: solid 2px #cc9933; background-color: #ffffcc; font-size:larger;">
We&rsquo;re sorry to hear that.
Maybe you could help us make TheyWorkForYou better by answering some questions in our user survey &ndash;
five minutes should be enough.
If you don&rsquo;t want to participate, thanks anyway<? if ($referer) print ', <a href="' . $referer . '">return to where you were</a>'; ?>.
</div>
<?
}
?>

<h2> TheyWorkForYou&rsquo;s quick and painless user survey </h2>

<div id="survey">

<div id="disclaimer">
<p>We (ie. the people from mySociety, the independent non-profit who runs this
site) hate to bother you with this but we really would like to know what you
(dis)like about our site and whether it is used by a representative share of
the population (we would not want to only serve one particular group
exclusively). We know we ask some personal stuff but be assured:</p>

<ul>
	<li>The questions from here on are  <strong>completely anonymous</strong>.</li>
	<li>This also means that we cannot connect your answers to whatever you do on our sites.</li>
	<li>Of course we would like you to answer all questions but you don't have to if you feel it's none of our business.</li>
	<li>These are all the questions.  <strong>We won't ask more.</strong> So it should take only 5 minutes.</li>
</ul>

<p>Your feedback will help us  <strong> make the site better </strong> and help us tell more people about it.</p>
</div>

<form method="post" action="<?=OPTION_SURVEY_URL?>">
<input type="hidden" name="version" value="2.2">
<input type="hidden" name="sourceidentifier" value="twfy">
<input type="hidden" name="datetime" value="<?=time() ?>">
<input type="hidden" name="subgroup" value="0">

<input type="hidden" name="user_code" value="<?=$user_code ?>">
<input type="hidden" name="auth_signature" value="<?=$auth_signature ?>">

<input type="hidden" name="came_from" value="<?=$referer ?>">
<input type="hidden" name="find_what_looking_for" value="<?=$find ?>">
<input type="hidden" name="return_url" value="http://www.theyworkforyou.com/survey/done">

<table cellpadding=4 cellspacing=0 id="survey_table">
<colgroup>
	<col id="col_label">
	<col id="col_formelelemt">
</colgroup>
<tr>
	<td class="us_label">Within the last twelve months: How often have you used TheyWorkForYou?</td>
	<td class="us_formelement"><select name="usefrequency" >
<option selected="selected" value="please select what applies to you">please select what applies to you</option>
<option id="id_usefrequency_0" value="0">never, this is the first time in the last 12 months</option>
<option id="id_usefrequency_1" value="1">about once within the last 12 months</option>
<option id="id_usefrequency_5" value="2">2 - 5 times within the last 12 months</option>
<option id="id_usefrequency_9" value="3">6 - 10 times within the last 12 months</option>
<option id="id_usefrequency_10" value="4">roughly every month within the last 12 months</option>
<option value="5">roughly every week within the last 12 months</option>
<option value="6">roughly every day within the last 12 months</option>
<option id="id_usefrequency_NA" value="95">don't want to answer</option>
</select></td>
</tr>
<tr class="alt">
	<td class="us_label">
	Before you used this site, did you know who your Member of Parliament in the House of Commons was?
</td>
	<td class="us_formelement">
	<label><input type="radio" name="knowledge" value="1"  id="id_knowledge_yes">yes</label>
	<label><input type="radio" name="knowledge" value="0"  id="id_knowledge_no">no</label>
	<label><input type="radio" name="knowledge" value="95"  id="id_knowledge_NA">don't want to answer</label>
	</td>
</tr>
<tr>
	<td class="us_label">
Before you used TheyWorkForYou did you ever look up information on what your representatives were doing?
</td>
	<td class="us_formelement">
	<label><input type="radio" name="firsttimer" value="1"  id="id_firsttimer_yes">yes</label>
	<label><input type="radio" name="firsttimer" value="0"  id="id_firsttimer_no">no</label>
	<label><input type="radio" name="firsttimer" value="95"  id="id_firsttimer_NA">don't want to answer</label>
</td>
</tr>
<tr class="alt">
	<td class="us_label">
	How would you describe your latest use of this site?
</td>
	<td class="us_formelement"><label>
	 <input type="radio" name="purpose" value="1"  id="id_purpose_work">just generally browsing out of interest/curiosity
</label>
<br> <label>
	 <input type="radio" name="purpose" value="2"  id="id_purpose_personal">obtaining information on my representative
</label>
<br> <label>
	 <input type="radio" name="purpose" value="3"  id="id_purpose_campaigning">obtaining information on a particular debate
</label>
<br> <label>
	 <input type="radio" name="purpose" value="4"  id="id_purpose_otherbutton">checking a particular fact
</label>
<br> <label>
	 <input type="radio" name="purpose" value="5">keeping an eye on what (my) representatives do
</label>
<br> <label>
	 <input type="radio" name="purpose" value="6">other </label><label><input type="text" name="purpose_other" value="please specify" size="50" maxlength="500" onclick="this.form.purpose[5].checked = true;if (this.value=='please specify') this.value=''" id="id_purpose_other">
</label>
<br> <label>
	 <input type="radio" name="purpose" value="95"  id="id_purpose_NA">don't want to answer
</label>
<br></td>
</tr>
<tr>
	<td class="us_label">
	Is your use of this site in any way related to your work?
</td>
	<td class="us_formelement"><label>
	 <input type="radio" name="purpose_work" value="1"  id="id_purpose_work_yes">yes
</label> <label>
	 <input type="radio" name="purpose_work" value="0"  id="id_purpose_work_no">no
</label> <label>
	 <input type="radio" name="purpose_work" value="95"  id="id_purpose_work:NA">don't want to answer
</label></td>
</tr>
<tr class="alt">
	<td class="us_label">
	Are you a registered user of TheyWorkForYou? <small>
		(This means do you have a log-in that allows you to add annotations?)
	</small>
</td>
	<td class="us_formelement">
	<label><input type="radio" name="registration" value="1"  id="id_registration_yes">yes</label>
<label> <input type="radio" name="registration" value="0"  id="id_registration_no">no </label>
<label> <input type="radio" name="registration" value="95"  id="id_registration_NA">don't want to answer </label>
</td>
</tr>
<tr>
	<td class="us_label">
	How likely is it that you would recommend this site to a friend or colleague? (assuming they would be interested in such a service)
</td>
	<td class="us_formelement">
	<select name="netpromoter" >
	<option selected="selected" value="please select what applies to you">please select what applies to you</option>
	<option value="0">0 - not at all likely</option>
	<option value="1">1</option>
	<option value="2">2</option>
	<option value="3">3</option>
	<option value="4">4</option>
	<option value="5">5 - neutral</option>
	<option value="6">6</option>
	<option value="7">7</option>
	<option value="8">8</option>
	<option value="9">9</option>
	<option value="10">10 - extremely likely</option>
	<option value="95">don't want to answer</option>
	</select><br>
	<br>
	<textarea name="netpromoter_reason"  rows="2" cols="50" onclick="if (this.value=='feel free to tell us why') this.value=''" id="id_netpromoter_reason">feel free to tell us why</textarea></td>
</tr>
<tr class="alt">
	<td class="us_label">
	How did you find out about this site?
</td>
	<td class="us_formelement"><label>
	 <input type="radio" name="referrer" value="1">from another mySociety site
</label>
<br> <label>
	 <input type="radio" name="referrer" value="2">from media such as newspapers, etc
</label>
<br> <label>
	 <input type="radio" name="referrer" value="3">from a search engine (e.g. Google or Yahoo)
</label>
<br> <label>
	 <input type="radio" name="referrer" value="4">recommendation by friends or colleagues
</label>
<br> <label>
	 <input type="radio" name="referrer" value="5">from a campaigning website
</label>
<br> <label>
	 <input type="radio" name="referrer" value="6">used this site before
</label>
<br> <label>
	 <input type="radio" name="referrer" value="9">other </label><label><input type="text" name="referrer_other" value="please specify" size="50" maxlength="500" onclick="this.form.referrer[6].checked = true;if (this.value=='please specify') this.value=''" id="id_referrer_other">
</label>
<br> <label>
	 <input type="radio" name="referrer" value="98">can't remember
</label>
<br> <label>
	 <input type="radio" name="referrer" value="95">don't want to answer
</label>
<br></td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr class="alt">
	<td class="us_label">
	Is there any feature that you would like to see on the site for which you would be willing to donate some money so that it can be developed?
</td>
	<td class="us_formelement"><textarea name="donate_comment"  rows="3" cols="50" id="id_donate_comment"></textarea></td>
</tr>
<tr>
	<td class="us_label">
	How much do you agree with the following statements?
</td>
	<td class="us_formelement"><table style="text-align:center;table-layout:fixed">
		<tr>
			<th style="text-align:left;" scope="col">
				statement
			</th>
			<th style="width:10%" scope="col">
				strongly disagree
			</th>
			<th style="width:10%" scope="col">
				disagree
			</th>
			<th style="width:10%" scope="col">
				agree
			</th>
			<th style="width:10%" scope="col">
				strongly agree
			</th>
			<th style="width:10%" scope="col">
				don't want to answer
			</th>
		</tr>
		<tr class="alt">
			<td style="text-align:left;">TheyWorkForYou is easy to navigate</td>
			<td><input type="radio" name="opinion_navigation" value="0"></td>
			<td><input type="radio" name="opinion_navigation" value="1"></td>
			<td><input type="radio" name="opinion_navigation" value="2"></td>
			<td><input type="radio" name="opinion_navigation" value="3"></td>
			<td><input type="radio" name="opinion_navigation" value="99"></td>
		</tr>
		<tr>
			<td style="text-align:left;">TheyWorkForYou is well structured</td>
			<td><input type="radio" name="opinion_structure" value="0"></td>
			<td><input type="radio" name="opinion_structure" value="1"></td>
			<td><input type="radio" name="opinion_structure" value="2"></td>
			<td><input type="radio" name="opinion_structure" value="3"></td>
			<td><input type="radio" name="opinion_structure" value="99"></td>
		</tr>
		<tr class="alt">
			<td style="text-align:left;">TheyWorkForYou provides information in an unbiased and unpartisan way</td>
			<td><input type="radio" name="opinion_objectivity" value="0"></td>
			<td><input type="radio" name="opinion_objectivity" value="1"></td>
			<td><input type="radio" name="opinion_objectivity" value="2"></td>
			<td><input type="radio" name="opinion_objectivity" value="3"></td>
			<td><input type="radio" name="opinion_objectivity" value="99"></td>
		</tr>
		<tr>
			<td style="text-align:left;">TheyWorkForYou is pretty to look at</td>
			<td><input type="radio" name="opinion_design" value="0"></td>
			<td><input type="radio" name="opinion_design" value="1"></td>
			<td><input type="radio" name="opinion_design" value="2"></td>
			<td><input type="radio" name="opinion_design" value="3"></td>
			<td><input type="radio" name="opinion_design" value="99"></td>
		</tr>
		<tr class="alt">
			<td style="text-align:left;">TheyWorkForYou has improved my <em>knowledge</em> about my representative</td>
			<td><input type="radio" name="opinion_representative_knowledge" value="0"></td>
			<td><input type="radio" name="opinion_representative_knowledge" value="1"></td>
			<td><input type="radio" name="opinion_representative_knowledge" value="2"></td>
			<td><input type="radio" name="opinion_representative_knowledge" value="3"></td>
			<td><input type="radio" name="opinion_representative_knowledge" value="99"></td>
		</tr>
		<tr>
			<td style="text-align:left;">TheyWorkForYou has improved my <em>opinion</em> about my representative</td>
			<td><input type="radio" name="opinion_representative_opinion" value="0"></td>
			<td><input type="radio" name="opinion_representative_opinion" value="1"></td>
			<td><input type="radio" name="opinion_representative_opinion" value="2"></td>
			<td><input type="radio" name="opinion_representative_opinion" value="3"></td>
			<td><input type="radio" name="opinion_representative_opinion" value="99"></td>
		</tr>
	</table></td>
</tr>
<tr class="alt">
	<td class="us_label">
	In the last twelve months have you been involved with a political or a community group, e.g. by being a formal member or by volunteering?
</td>
	<td class="us_formelement"><label>
	 <input type="radio" name="groups" value="2"  id="id_groups_yes_political">a political group <small>(e.g. a party, an union, a civic organisation e.g. for human rights)</small>
</label>
<br> <label>
	 <input type="radio" name="groups" value="1"  id="id_groups_yes_community">a community group <small>(e.g.a charity, an initiative, a church, a sports club, a volunteer organisation)</small>
</label>
<br> <label>
	 <input type="radio" name="groups" value="3"  id="id_groups_yes_both">both community as well as political group(s)
</label>
<br> <label>
	 <input type="radio" name="groups" value="0"  id="id_groups_no">none of the above
</label>
<br> <label>
	 <input type="radio" name="groups" value="95"  id="id_groups_NA">don't want to answer
</label>
<br></td>
</tr>
<tr>
	<td class="us_label">
	Apart from your use of this website: Within the last twelve months have you taken part in any broadly political activity? <br>
	<small>
		(This includes for example demonstrations, signing a petition, contacting a politician, boycotting a product, donating money or displaying a campaign badge)
	</small>
</td>
	<td class="us_formelement"><label>
	 <input type="radio" name="activity" value="2"  id="id_activity_yes_online">yes, online
</label>
<br> <label>
	 <input type="radio" name="activity" value="1"  id="id_activity_yes_offline">yes, offline
</label>
<br> <label>
	 <input type="radio" name="activity" value="3"  id="id_activity_yes_both">yes both online as well as offline
</label>
<br> <label>
	 <input type="radio" name="activity" value="0"  id="id_community_no">none of the above
</label>
<br> <label>
	 <input type="radio" name="activity" value="95"  id="id_community_NA">don't want to answer
</label>
<br></td>
</tr>
<tr class="alt">
	<td class="us_label">
	How old are you?
</td>
	<td class="us_formelement"><select name="age" >
<option selected="selected" value="please select what applies to you">please select what applies to you</option>
<option value="1">less than 18 years old</option>
<option value="2">18-24 years old</option>
<option value="3">25-34 years old</option>
<option value="5">35-44 years old</option>
<option value="7">45-54 years old</option>
<option value="9">55-64 years old</option>
<option value="11">65-74 years old</option>
<option value="13">75 years and older</option>
<option value="95">don't want to answer</option>
</select></td>
</tr>
<tr>
	<td class="us_label">
	Could you please indicate your gender?
</td>
	<td class="us_formelement">
	<label> <input type="radio" name="gender" value="1"  id="id_gender_female">female </label>
<label> <input type="radio" name="gender" value="0"  id="id_gender_male">male </label>
<label> <input type="radio" name="gender" value="95"  id="id_gender_NA">don't want to answer </label>
</td>
</tr>
<tr class="alt">
	<td class="us_label">
	What is the last type of educational institution (e.g. school, college or university) that you have attended or which type of educational institution are you attending now?
</td>
	<td class="us_formelement"><select name="education_any" >
<option selected="selected" value="please select what applies to you">please select what applies to you</option>
<option value="0">Primary school or equivalent</option>
<option value="1">Secondary school or equivalent</option>
<option value="2">Special school or equivalent</option>
<option value="3">Sixth form college or equivalent</option>
<option value="4">Technical college or equivalent</option>
<option value="5">Further Education College</option>
<option value="6">Adult Community College</option>
<option value="7">University or equivalent</option>
<option value="9">other</option>
<option value="95">don't want to answer</option>
</select></td>
</tr>
<tr>
	<td class="us_label">
	Which of these descriptions best describes your current situation?
</td>
	<td class="us_formelement"><select name="lifestage" >
<option selected="selected" value="please select what applies to you">please select what applies to you</option>
<option value="1">working full time (30 hours a week or more)</option>
<option value="2">working part time (8-29 hours a week)</option>
<option value="3">retired</option>
<option value="4">unemployed</option>
<option value="5">permanently sick or disabled</option>
<option value="6">in community or military service</option>
<option value="7">undergraduate student</option>
<option value="8">postgraduate student</option>
<option value="9">in full time education (not degree or higher)</option>
<option value="10">in part time education (not degree or higher)</option>
<option value="11">doing housework, looking after children or other persons</option>
<option value="98">none of the above</option>
<option value="95">don't want to answer</option>
</select></td>
</tr>
<tr class="alt">
	<td class="us_label">
	The incomes of households differ a lot in Britain today. 
	              Which figures best represents the total income of your household before tax?
</td>
	<td><select name="income" >
<option selected="selected" value="please select what applies to you">please select what applies to you</option>
<option value="1">up to	&pound;12,500 per year</option>
<option value="2">&pound;12,501 to &pound;25,000 per year</option>
<option value="3">&pound;25,001 to &pound;37,500 per year</option>
<option value="4">&pound;37,501 to &pound;50,000 per year</option>
<option value="5">&pound;50,001 to &pound;75,000 per year</option>
<option value="6">&pound;75,001 to &pound;100,000 per year</option>
<option value="7">more than &pound;100,000 per year</option>
<option value="95">don't want to answer</option>
</select></td>
</tr>
<tr>
	<td class="us_label">
	To which one of these ethnic groups do you consider you belong?
</td>
	<td class="us_formelement"><select name="ethnicity" >
<option selected="selected" value="please select what applies to you">please select what applies to you</option>
<optgroup label="A White">
<option value="1">British</option>
<option value="21">English</option>
<option value="22">Welsh</option>
<option value="23">Scottish</option>
<option value="2">Irish</option>
<option value="3">Other white</option>
</optgroup>
<optgroup label="B Mixed">
<option value="4">White and Black Caribbean</option>
<option value="5">White and Black African</option>
<option value="6">White and Asian</option>
<option value="7">Other Mixed</option>
</optgroup>
<optgroup label="C Asian or Asian British">
<option value="8">Indian</option>
<option value="9">Pakistani</option>
<option value="10">Bangladeshi</option>
<option value="11">Other Asian</option>
</optgroup>
<optgroup label="D Black or Black British">
<option value="12">Caribbean</option>
<option value="13">African</option>
<option value="14">Other Black</option>
</optgroup>
<optgroup label="E Chinese">
<option value="15">Chinese</option>
</optgroup>
<optgroup label="F other ethnic group">
<option value="16">any other ethnic group</option>
</optgroup>
<optgroup label="----">
<option value="97">don't know</option>
<option value="95">don't want to answer</option>
</optgroup>
</select></td>
</tr>
<tr class="alt">
	<td class="us_label">
	Do you have a health problem or disability which prevents you from doing every
	            day tasks at home, work or school or which limits the kind or amount of work you can do?
</td>
	<td class="us_formelement">
	<label> <input type="radio" name="disability" value="1"  id="id_disability_yes">yes </label>
<label> <input type="radio" name="disability" value="0"  id="id_disability_no">no </label>
<label> <input type="radio" name="disability" value="95"  id="id_disability_NA">don't want to answer </label>
</td>
</tr>
<tr>
	<td class="us_label">
	Would you tell us the first part of your postcode (e.g. SW1 etc) or the name of your country of residence should you not be in the UK?
</td>
	<td class="us_formelement"><input type="text" name="location"  size="10" maxlength="50" id="id_location"></td>
</tr>
<tr class="alt">
	<td class="us_label">
	Do you have any other comments (e.g. on the survey, on your usage, etc)?
</td>
	<td class="us_formelement"><textarea name="survey_comment"  rows="5" cols="50" id="id_survey_comment"></textarea></td>
</tr>
</table>

<p>Thank you very much! And we promise, that's the <strong>last question</strong>!</p>

<p>
<input type="submit" value="Submit user survey">
</p>
</form>

</div>

<?

$PAGE->page_end ();


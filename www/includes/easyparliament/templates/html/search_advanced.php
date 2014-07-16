<h2>Search</h2>

<form action="/search/" method="get" id="search-form">

<div id="term-search">
    <label for="advanced_search_input" class="hide">Search</label> <input type="text" id="advanced_search_input" name="q" value="<?=_htmlspecialchars(get_http_var('q') != '' ? get_http_var('q') : get_http_var('s')) ?>">
    <div class="help">
    Enter what you&rsquo;re looking for here. See the help to the right for how
    to search for <strong>"unstemmed" words</strong>, <strong>-exclude -words</strong>, or perform <strong>OR boolean</strong> searches.
    </div>
</div>

<h3>Filters</h3>

<div><label for="from">Date range</label>
From <input type="text" id="from" name="from" value="<?=_htmlspecialchars(get_http_var('from')) ?>" size="22">
 to <input type="text" name="to" value="<?=_htmlspecialchars(get_http_var('to')) ?>" size="22">
</div>
<div class="help">
You can give a <strong>start date, an end date, or both</strong>, to restrict results to a
particular date range; a missing end date implies the current date, a missing start date
implies the oldest date we have in the system. Dates can be entered in any format you wish, <strong>e.g.
&ldquo;3rd March 2007&rdquo; or &ldquo;17/10/1989&rdquo;</strong>.
</div>

<div><label for="person">Person</label>
<input type="text" id="person" name="person" value="<?=_htmlspecialchars(get_http_var('person')) ?>" size="40">
</div>
<div class="help">
Enter a name here to restrict results to contributions only by that person.
</div>

<div><label for="department">Department</label> <select name="department" id="department">
<option value="">-
<option>Administration Committee
<option>Advocate-General
<option>Advocate-General for Scotland
<option>Agriculture, Fisheries and Food
<option>Attorney-General
<option>Business, Enterprise and Regulatory Reform
<option>Business, Innovation and Skills
<option>Cabinet Office
<option>Children, Schools and Families
<option>Church Commissioners
<option>Civil Service
<option>Communities and Local Government
<option>Constitutional Affairs
<option>Culture Media and Sport
<option>Defence
<option>Deputy Prime Minister
<option>Duchy of Lancaster
<option>Education
<option>Education and Science
<option>Education and Skills
<option>Electoral Commission Committee
<option>Employment
<option>Energy
<option>Energy and Climate Change
<option>Environment
<option>Environment Food and Rural Affairs
<option>European Community
<option>Foreign and Commonwealth Affairs
<option>Foreign and Commonwealth Office
<option>Government Equalities Office
<option>Health
<option>Home Department
<option>House of Commons
<option>House of Commons Commission
<option>House of Lords
<option>Industry
<option>Innovation, Universities and Skills
<option>International Development
<option>Justice
<option>Leader of the Council
<option>Leader of the House
<option>Lord Chancellor
<option>Minister for Women
<option>Minister for Women and Equality
<option>National Finance
<option>Northern Ireland
<option>Olympics
<option>Overseas Development
<option>Palace of Westminister
<option>President of the Council
<option>Prime Minister
<option>Privy Council
<option>Public Accounts Commission
<option>Public Accounts Committee
<option>Scotland
<option>Social Services
<option>Solicitor General
<option>Solicitor-General
<option>Trade
<option>Trade and Industry
<option>Transport
<option>Transport, Local Government and the Regions
<option>Treasury
<option>Wales
<option>Women and Equality
<option>Work and Pensions
</select>
</div>
<div class="help">
This will restrict results to those UK Parliament written answers and statements from the chosen department.
<small>The department list might be slightly out of date.</small>
</div>

<div><label for="party">Party</label> <select id="party" name="party">
<option value="">-
<option>Alliance
<option value="Bp">Bishops
<option value="CWM,DCWM">Commons Deputy Speakers
<option value="SPK">Commons Speaker
<option value="Con">Conservative
<option value="XB">Crossbench Lords
<option>DUP
<option>Green
<option value="Ind,Independent">Independent
<!--
All broken
<option value="Ind Con">Ind Con (Commons)
<option value="Ind Lan">Ind Lab (Commons)
<option value="Ind UU">Ind UU (Commons)
<option>Independent Unionist
<option>Initial Presiding Officer, Scottish Parliament
-->
<option value="Lab,Lab/Co-op">Labour
<option value="LDem">Liberal Democrat
<option value="Speaker">NI Speaker
<option>NIUP
<option>NIWC
<option value="Other">Other (Lords)
<option value="PC">Plaid Cymru
<option>PUP
<option value="Res">Respect
<option value="None">Scottish Parliament Speaker
<option>SDLP
<option>SG
<!--
Sinn Fein is broken
<option value="SF,Sinn F&eacute;in">Sinn F&eacute;in
-->
<option>SNP
<option>SSCUP
<option>SSP
<option>UKIP
<option>UKUP
<option>UUAP
<option>UUP
</select>
</div>
<div class="help">
Restricts results to the chosen party
<br><small>(there is currently a bug with some parties, such as Sinn F&eacute;in)</small>.
</div>

<div><label for="section">Section</label>
<select id="section" name="section">
<option value="">-
<optgroup label="UK Parliament">
<option value="uk">All
<option value="debates">House of Commons debates
<option value="whall">Westminster Hall debates
<option value="lords">House of Lords debates
<option value="wrans">Written answers
<option value="wms">Written ministerial statements
<option value="standing">Public Bill Committees
<option value="future">Future Business
</optgroup>
<optgroup label="Northern Ireland Assembly">
<option value="ni">Debates
</optgroup>
<optgroup label="Scottish Parliament">
<option value="scotland">All
<option value="sp">Debates
<option value="spwrans">Written answers
</optgroup>
</select>
</div>
<div class="help">
Restrict results to a particular parliament or assembly that we cover (e.g. the
Scottish Parliament), or a particular type of data within an institution, such
as Commons Written Answers.
</div>

<div><label for="column">Column</label>
<input type="text" id="column" name="column" value="<?=_htmlspecialchars(get_http_var('column')) ?>" size="10">
</div>
<div class="help">
If you know the actual column number in Hansard you are interested in (perhaps you&rsquo;re looking up a paper
reference), you can restrict results to that.
</div>

<p align="right">
<input type="submit" value="Search">
</p>
</form>

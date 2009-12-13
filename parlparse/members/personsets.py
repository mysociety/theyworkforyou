#! /usr/bin/env python2.4
# vim:sw=4:ts=4:et:nowrap

# Groups sets of MPs and offices together into person sets.  Updates
# people.xml to reflect the new sets.  Reuses person ids from people.xml,
# or allocates new larger ones.

import xml.sax
import sets
import datetime
import sys
import re
import os

sys.path.append("../pyscraper")
from resolvemembernames import memberList

date_today = datetime.date.today().isoformat()

# People who have been both MPs and lords
lordsmpmatches = {
    "uk.org.publicwhip/lord/100851" : "Jenny Tonge [Richmond Park]",
    "uk.org.publicwhip/lord/100855" : "Nigel Jones [Cheltenham]",
    "uk.org.publicwhip/lord/100866" : "Chris Smith [Islington South & Finsbury]",
    "uk.org.publicwhip/lord/100022" : "Paddy Ashdown [Yeovil]",
    "uk.org.publicwhip/lord/100062" : "Betty Boothroyd [West Bromwich West]",
    "uk.org.publicwhip/lord/100082" : "Peter Brooke [Cities of London & Westminster]",
    "uk.org.publicwhip/lord/100104" : "Dale Campbell-Savours [Workington]",
    "uk.org.publicwhip/lord/100128" : "David Clark [South Shields]",
    "uk.org.publicwhip/lord/100144" : "Robin Corbett [Birmingham, Erdington]",
    "uk.org.publicwhip/lord/100208" : "Ronnie Fearn [Southport]",
    "uk.org.publicwhip/lord/100222" : "Norman Fowler [Sutton Coldfield]",
    "uk.org.publicwhip/lord/100244" : "Llin Golding [Newcastle-under-Lyme]",
    "uk.org.publicwhip/lord/100264" : "Bruce Grocott [Telford]",
    "uk.org.publicwhip/lord/100288" : "Michael Heseltine [Henley]",
    "uk.org.publicwhip/lord/100338" : "Barry Jones [Alyn & Deeside]",
    "uk.org.publicwhip/lord/100348" : "Tom King [Bridgwater]",
    "uk.org.publicwhip/lord/100378" : "Richard Livsey [Brecon & Radnorshire]",
    "uk.org.publicwhip/lord/100398" : "John MacGregor [South Norfolk]",
    "uk.org.publicwhip/lord/100407" : "Robert Maclennan [Caithness, Sutherland & Easter Ross]",
    "uk.org.publicwhip/lord/100410" : "Ken Maginnis [Fermanagh & South Tyrone]",
    "uk.org.publicwhip/lord/100426" : "Ray Michie [Argyll & Bute]",
    "uk.org.publicwhip/lord/100443" : "John Morris [Aberavon]",
    "uk.org.publicwhip/lord/100493" : "Tom Pendry [Stalybridge & Hyde]",
    "uk.org.publicwhip/lord/100518" : "Giles Radice [North Durham]",
    "uk.org.publicwhip/lord/100542" : "George Robertson [Hamilton South]",
    "uk.org.publicwhip/lord/100549" : "Jeff Rooker [Birmingham, Perry Barr]",
    "uk.org.publicwhip/lord/100588" : "Robert Sheldon [Ashton-under-Lyne]",
    "uk.org.publicwhip/lord/100631" : "Peter Temple-Morris [Leominster]",
    "uk.org.publicwhip/lord/100793" : "Peter Snape [West Bromwich East]",
    "uk.org.publicwhip/lord/100799" : "John Maxton [Glasgow, Cathcart]",
    "uk.org.publicwhip/lord/100809" : "Ted Rowlands [Merthyr Tydfil & Rhymney]",
    "uk.org.publicwhip/lord/100843" : "Archy Kirkwood [Roxburgh & Berwickshire]",
    "uk.org.publicwhip/lord/100844" : "Ann Taylor [Dewsbury]",
    "uk.org.publicwhip/lord/100845" : "Martin O'Neill [Ochil]",
    "uk.org.publicwhip/lord/100846" : "Paul Tyler [North Cornwall]",
    "uk.org.publicwhip/lord/100847" : "Estelle Morris [Birmingham, Yardley]",
    "uk.org.publicwhip/lord/100848" : "Alan Howarth [Newport East]",
    "uk.org.publicwhip/lord/100849" : "Derek Foster [Bishop Auckland]",
    "uk.org.publicwhip/lord/100850" : "David Chidgey [Eastleigh]",
    "uk.org.publicwhip/lord/100852" : "George Foulkes [Carrick, Cumnock & Doon Valley]",
    "uk.org.publicwhip/lord/100853" : "Archie Hamilton [Epsom & Ewell]",
    "uk.org.publicwhip/lord/100856" : "Gillian Shephard [South West Norfolk]",
    "uk.org.publicwhip/lord/100857" : "Tony Banks [West Ham]",
    "uk.org.publicwhip/lord/100858" : "Nicholas Lyell [North East Bedfordshire]",
    "uk.org.publicwhip/lord/100860" : "Dennis Turner [Wolverhampton South East]",
    "uk.org.publicwhip/lord/100862" : "Virginia Bottomley [South West Surrey]",
    "uk.org.publicwhip/lord/100863" : "Brian Mawhinney [North West Cambridgeshire]",
    "uk.org.publicwhip/lord/100864" : "Lynda Clark [Edinburgh, Pentlands]",
    "uk.org.publicwhip/lord/100865" : "Clive Soley [Ealing, Acton & Shepherd's Bush]",
    "uk.org.publicwhip/lord/100867" : "Irene Adams [Paisley North]",
    "uk.org.publicwhip/lord/100869" : "Donald Anderson [Swansea East]",
    "uk.org.publicwhip/lord/100870" : "Jean Corston [Bristol East]",
    "uk.org.publicwhip/lord/100871" : "Alastair Goodlad [Eddisbury]",
    "uk.org.publicwhip/lord/100873" : "Jack Cunningham [Copeland]",
    "uk.org.publicwhip/lord/100910" : "David Trimble [Upper Bann]",
    "uk.org.publicwhip/lord/100967" : "David Trimble [Upper Bann]", # changed party
    "uk.org.publicwhip/lord/100930" : "Keith Bradley [Manchester, Withington]",
    "uk.org.publicwhip/lord/100345" : "John D Taylor [Strangford]",
    "uk.org.publicwhip/lord/100907" : "Brian Cotter [Weston-Super-Mare]",
    "uk.org.publicwhip/lord/100981" : "Peter Mandelson [Hartlepool]",
    "uk.org.publicwhip/lord/100997" : "Michael Martin [Glasgow, Springburn / Glasgow North East]",
}

# Put people who change party AND were MPs multple times in table above e.g. David Trimble
lordlordmatches = {
	"uk.org.publicwhip/lord/100160":"uk.org.publicwhip/lord/100931",  # Lord Dahrendorf changes party
	"uk.org.publicwhip/lord/100281":"uk.org.publicwhip/lord/100906",  # Lord Haskins changes party
	"uk.org.publicwhip/lord/100901":"uk.org.publicwhip/lord/100831",  # Bishop of Southwell becomes Bishop of Southwell and Nottingham
	"uk.org.publicwhip/lord/100106":"uk.org.publicwhip/lord/100711",  # Archbishop Carey becomes XB Lord
	"uk.org.publicwhip/lord/100265":"uk.org.publicwhip/lord/100830",  # Bishop of Guildford becomes of Chelmsford
	"uk.org.publicwhip/lord/100736":"uk.org.publicwhip/lord/100872",  # Bishop of Wakefield becomes of Manchester
    "uk.org.publicwhip/lord/100937":"uk.org.publicwhip/lord/100479",  # Bishop of Oxford becomes XB Lord
    "uk.org.publicwhip/lord/100942":"uk.org.publicwhip/lord/100677",  # Lord Wedderburn changes party
    "uk.org.publicwhip/lord/100943":"uk.org.publicwhip/lord/100924",  # As does Lord Boyd...
    "uk.org.publicwhip/lord/100491":"uk.org.publicwhip/lord/100944",  # And Perason
    "uk.org.publicwhip/lord/100945":"uk.org.publicwhip/lord/100690",  # And Willoughby.
    "uk.org.publicwhip/lord/100147":"uk.org.publicwhip/lord/100959",  # And Cox
    "uk.org.publicwhip/lord/100716":"uk.org.publicwhip/lord/100978",  # Viscount Cranborne inherits Marquess of Salisbury
    "uk.org.publicwhip/lord/100993":"uk.org.publicwhip/lord/100957",  # Lord Jones of Brum
    # XXX: Must be a way to do party changes automatically!
    # XXX: And the key:value ordering here is very suspect
}

ni_mp_matches = {
    "uk.org.publicwhip/member/90123":"Sammy Wilson [East Antrim]",
    "uk.org.publicwhip/member/90074":"William McCrea [South Antrim]",
    "uk.org.publicwhip/member/90198":"William McCrea [South Antrim]",
    "uk.org.publicwhip/member/90111":"John D Taylor [Strangford]",
    "uk.org.publicwhip/member/90186":"John D Taylor [Strangford]",
}
ni_lord_matches = {
    "uk.org.publicwhip/member/90005":"uk.org.publicwhip/lord/100007",
    "uk.org.publicwhip/member/90006":"uk.org.publicwhip/lord/100007",
    "uk.org.publicwhip/member/90242":"uk.org.publicwhip/lord/100007",
    "uk.org.publicwhip/member/90111":"uk.org.publicwhip/lord/100345",
    "uk.org.publicwhip/member/90186":"uk.org.publicwhip/lord/100345",
    "uk.org.publicwhip/member/90091":"uk.org.publicwhip/lord/100922",
    "uk.org.publicwhip/member/90210":"uk.org.publicwhip/lord/100922",
    "uk.org.publicwhip/member/90322":"uk.org.publicwhip/lord/100922",
    "uk.org.publicwhip/member/90257":"uk.org.publicwhip/lord/100934",
}
# XXX: Should be possible to adapt manualmatches to do this sort of thing...
ni_ni_matches = {
    "Mitchel McLaughlin [Foyle]":"Mitchel McLaughlin [South Antrim]",
    "Alex Maskey [Belfast West]":"Alex Maskey [Belfast South]",
}

sp_lord_matches = {
    # Lord James Douglas-Hamilton
    "uk.org.publicwhip/member/80026": "uk.org.publicwhip/lord/100579",
    "uk.org.publicwhip/member/80261": "uk.org.publicwhip/lord/100579",
    # Sir David Steel
    "uk.org.publicwhip/member/80242": "uk.org.publicwhip/lord/100608",
    "uk.org.publicwhip/member/80277": "uk.org.publicwhip/lord/100608",
}

# Include in here changes of constituency, so those people are
# recognized as the same...  Annoyingly the order is important here.

sp_sp_matches = {
    "Alasdair Morgan [sp: Galloway and Upper Nithsdale]" : "Alasdair Morgan [sp: South of Scotland]",
    "Jim Mather [sp: Argyll and Bute]" : "Jim Mather [sp: Highlands and Islands]",
    "Michael Matheson [sp: Falkirk West]" : "Michael Matheson [sp: Central Scotland]",
    "Richard Lochhead [sp: North East Scotland]" : "Richard Lochhead [sp: Moray]",
    "Alex Fergusson [sp: South of Scotland]" : "Alex Fergusson [sp: Galloway and Upper Nithsdale]",
    "Gil Paterson [sp: Central Scotland]" : "Gil Paterson [sp: West of Scotland]",
    "Bruce Crawford [sp: Stirling]" : "Bruce Crawford [sp: Mid Scotland and Fife]",
    "Iain Gray [sp: Edinburgh Pentlands]" : "Iain Gray [sp: East Lothian]",
    "David McLetchie [sp: Edinburgh Pentlands]" : "David McLetchie [sp: Lothians]",
    "Tricia Marwick [sp: Central Fife]" : "Tricia Marwick [sp: Mid Scotland and Fife]",
    "Nicola Sturgeon [sp: Glasgow Govan]" : "Nicola Sturgeon [sp: Glasgow]",
    "Alex Salmond [sp: Gordon]" : "Alex Salmond [sp: Banff and Buchan]",
    "Murray Tosh [sp: West of Scotland]" : "Murray Tosh [sp: South of Scotland]",
    "Richard Simpson [sp: Ochil]" : "Richard Simpson [sp: Mid Scotland and Fife]",
    "Kenny MacAskill [sp: Lothians]" : "Kenny MacAskill [sp: Edinburgh East and Musselburgh]",
    "Shona Robison [sp: North East Scotland]" : "Shona Robison [sp: Dundee East]",
    "George Reid [sp: Mid Scotland and Fife]" : "George Reid [sp: Ochil]",
    "Brian Adam [sp: North East Scotland]" : "Brian Adam [sp: Aberdeen North]",
    "Kenneth Gibson [sp: Glasgow]" : "Kenneth Gibson [sp: Cunninghame North]"
    }

sp_mp_matches = {
    # Alasdair Morgan
    "uk.org.publicwhip/member/80089": "Alasdair Morgan [Galloway & Upper Nithsdale]",
    "uk.org.publicwhip/member/80213": "Alasdair Morgan [Galloway & Upper Nithsdale]",
    "uk.org.publicwhip/member/80366": "Alasdair Morgan [Galloway & Upper Nithsdale]",
    # Alex Salmond
    "uk.org.publicwhip/member/80233": "Alex Salmond [Banff & Buchan]",
    "uk.org.publicwhip/member/80382": "Alex Salmond [Banff & Buchan]",
    # Andrew Welsh
    "uk.org.publicwhip/member/80125": "Andrew Welsh [Angus]",
    "uk.org.publicwhip/member/80255": "Andrew Welsh [Angus]",
    "uk.org.publicwhip/member/80401": "Andrew Welsh [Angus]",
    # Ben Wallace
    "uk.org.publicwhip/member/80251": "Ben Wallace [Lancaster & Wyre]",
    # David Mundell
    "uk.org.publicwhip/member/80217": "David Mundell [Dumfriesshire, Clydesdale & Tweeddale]",
    "uk.org.publicwhip/member/80265": "David Mundell [Dumfriesshire, Clydesdale & Tweeddale]",
    # Dennis Canavan
    "uk.org.publicwhip/member/80017": "Dennis Canavan [Falkirk West]",
    "uk.org.publicwhip/member/80139": "Dennis Canavan [Falkirk West]",
    # Donald Dewar
    "uk.org.publicwhip/member/80147": "Donald Dewar [Glasgow, Anniesland]",
    # Donald Gorrie
    "uk.org.publicwhip/member/80042": "Donald Gorrie [Edinburgh West]",
    "uk.org.publicwhip/member/80164": "Donald Gorrie [Edinburgh West]",
    # Henry McLeish
    "uk.org.publicwhip/member/80205": "Henry McLeish [Central Fife]",
    # Jim Wallace
    "uk.org.publicwhip/member/80123": "Jim Wallace [Orkney & Shetland]",
    "uk.org.publicwhip/member/80252": "Jim Wallace [Orkney & Shetland]",
    # John Home Robertson
    "uk.org.publicwhip/member/80047": "John Home Robertson [East Lothian]",
    "uk.org.publicwhip/member/80172": "John Home Robertson [East Lothian]",
    # John McAllion
    "uk.org.publicwhip/member/80198": "John McAllion [Dundee East]",
    # John Swinney
    "uk.org.publicwhip/member/80120": "John Swinney [North Tayside]",
    "uk.org.publicwhip/member/80247": "John Swinney [North Tayside]",
    "uk.org.publicwhip/member/80397": "John Swinney [North Tayside]",
    # Malcolm Chisholm
    "uk.org.publicwhip/member/80018": "Malcolm Chisholm [Edinburgh North & Leith]",
    "uk.org.publicwhip/member/80140": "Malcolm Chisholm [Edinburgh North & Leith]",
    "uk.org.publicwhip/member/80296": "Malcolm Chisholm [Edinburgh North & Leith]",
    # Margaret Ewing
    "uk.org.publicwhip/member/80151": "Margaret Ewing [Moray]",
    "uk.org.publicwhip/member/80263": "Margaret Ewing [Moray]",
    # Roseanna Cunningham
    "uk.org.publicwhip/member/80021": "Roseanna Cunningham [Perth]",
    "uk.org.publicwhip/member/80143": "Roseanna Cunningham [Perth]",
    "uk.org.publicwhip/member/80301": "Roseanna Cunningham [Perth]",
    # Sam Galbraith
    "uk.org.publicwhip/member/80158": "Sam Galbraith [Strathkelvin & Bearsden]",

    # Lord James Douglas-Hamilton
    # Not in the database of MPs, only Lords [Lord James Douglas-Hamilton Edinburgh West]
    # Only an MP until 1997 (?)
    # "uk.org.publicwhip/member/80026" : ""
    # "uk.org.publicwhip/member/80261" : ""

    # Phil Gallie
    # Not in the database (lost seat in 1997)
    # "uk.org.publicwhip/member/80035": "Phil Gallie [Ayr]",
    # "uk.org.publicwhip/member/80159": "Phil Gallie [Ayr]",
    
    # George Reid lost his seat in 1979
    # "uk.org.publicwhip/member/80103": "George Reid [Clackmannan & East Stirlingshire]",
    # "uk.org.publicwhip/member/80228": "George Reid [Clackmannan & East Stirlingshire]",
    # "uk.org.publicwhip/member/80272": "George Reid [Clackmannan & East Stirlingshire]",
    
}

# People who have been MPs for two different constituencies.  The like of
# Michael Portillo will eventually appear here.
manualmatches = {
    "Shaun Woodward [St Helens South]" : "Shaun Woodward [St Helens South / Witney]",
    "Shaun Woodward [Witney]" : "Shaun Woodward [St Helens South / Witney]",

    "George Galloway [Bethnal Green & Bow]" : "George Galloway [Bethnal Green & Bow / Glasgow, Kelvin]",
    "George Galloway [Glasgow, Kelvin]" : "George Galloway [Bethnal Green & Bow / Glasgow, Kelvin]",

    # Returned to maiden name
    "Anne Picking [East Lothian]" : "Anne Moffat [East Lothian]",

    # Scottish boundary changes 2005
    "Menzies Campbell [North East Fife]" : "Menzies Campbell [North East Fife / Fife North East]",
    "Menzies Campbell [Fife North East]" : "Menzies Campbell [North East Fife / Fife North East]",
    "Ann McKechin [Glasgow North]" : "Ann McKechin [Glasgow North / Glasgow, Maryhill]",
    "Ann McKechin [Glasgow, Maryhill]" : "Ann McKechin [Glasgow North / Glasgow, Maryhill]",
    "Frank Doran [Aberdeen Central]" : "Frank Doran [Aberdeen Central / Aberdeen North]",
    "Frank Doran [Aberdeen North]" : "Frank Doran [Aberdeen Central / Aberdeen North]",
    "Tom Harris [Glasgow, Cathcart]" : "Tom Harris [Glasgow, Cathcart / Glasgow South]",
    "Tom Harris [Glasgow South]" : "Tom Harris [Glasgow, Cathcart / Glasgow South]",
    "Mohammed Sarwar [Glasgow Central]" : "Mohammed Sarwar [Glasgow Central / Glasgow, Govan]",
    "Mohammad Sarwar [Glasgow, Govan]" : "Mohammed Sarwar [Glasgow Central / Glasgow, Govan]",
    "John McFall [Dumbarton]" : "John McFall [Dumbarton / West Dunbartonshire]",
    "John McFall [West Dunbartonshire]" : "John McFall [Dumbarton / West Dunbartonshire]",
    "Jimmy Hood [Clydesdale]" : "Jimmy Hood [Clydesdale / Lanark & Hamilton East]",
    "Jimmy Hood [Lanark & Hamilton East]" : "Jimmy Hood [Clydesdale / Lanark & Hamilton East]",
    "Ian Davidson [Glasgow, Pollok]" : "Ian Davidson [Glasgow, Pollok / Glasgow South West]",
    "Ian Davidson [Glasgow South West]" : "Ian Davidson [Glasgow, Pollok / Glasgow South West]",
    "Gordon Brown [Kirkcaldy & Cowdenbeath]" : "Gordon Brown [Kirkcaldy & Cowdenbeath / Dunfermline East]",
    "Gordon Brown [Dunfermline East]" : "Gordon Brown [Kirkcaldy & Cowdenbeath / Dunfermline East]",
    "Michael Martin [Glasgow, Springburn]" : "Michael Martin [Glasgow, Springburn / Glasgow North East]",
    "Michael Martin [Glasgow North East]" : "Michael Martin [Glasgow, Springburn / Glasgow North East]",
    "Sandra Osborne [Ayr, Carrick & Cumnock]" : "Sandra Osborne [Ayr, Carrick & Cumnock / Ayr]",
    "Sandra Osborne [Ayr]" : "Sandra Osborne [Ayr, Carrick & Cumnock / Ayr]",
    "Jim Sheridan [West Renfrewshire]" : "Jim Sheridan [West Renfrewshire / Paisley & Renfrewshire North]",
    "Jim Sheridan [Paisley & Renfrewshire North]" : "Jim Sheridan [West Renfrewshire / Paisley & Renfrewshire North]",
    "Robert Smith [Aberdeenshire West & Kincardine]" : "Robert Smith [Aberdeenshire West & Kincardine / West Aberdeenshire & Kincardine]",
    "Robert Smith [West Aberdeenshire & Kincardine]" : "Robert Smith [Aberdeenshire West & Kincardine / West Aberdeenshire & Kincardine]",
    "Brian Donohoe [Ayrshire Central]" : "Brian Donohoe [Ayrshire Central / Cunninghame South]",
    "Brian H Donohoe [Cunninghame South]" : "Brian Donohoe [Ayrshire Central / Cunninghame South]",
    "Charles Kennedy [Ross, Skye & Inverness West]" : "Charles Kennedy [Ross, Skye & Inverness West / Ross, Skye & Lochaber]",
    "Charles Kennedy [Ross, Skye & Lochaber]" : "Charles Kennedy [Ross, Skye & Inverness West / Ross, Skye & Lochaber]",
    "Eric Joyce [Falkirk West]" : "Eric Joyce [Falkirk West / Falkirk]",
    "Eric Joyce [Falkirk]" : "Eric Joyce [Falkirk West / Falkirk]",
    "David Marshall [Glasgow, Shettleston]" : "David Marshall [Glasgow, Shettleston / Glasgow East]",
    "David Marshall [Glasgow East]" : "David Marshall [Glasgow, Shettleston / Glasgow East]",
    "Tommy McAvoy [Rutherglen & Hamilton West]" : "Tommy McAvoy [Rutherglen & Hamilton West / Glasgow, Rutherglen]",
    "Thomas McAvoy [Glasgow, Rutherglen]" : "Tommy McAvoy [Rutherglen & Hamilton West / Glasgow, Rutherglen]",
    "Pete Wishart [North Tayside]" : "Pete Wishart [North Tayside / Perth and Perthshire North]",
    "Pete Wishart [Perth and Perthshire North]" : "Pete Wishart [North Tayside / Perth and Perthshire North]",
    "David Cairns [Greenock & Inverclyde]" : "David Cairns [Greenock & Inverclyde / Inverclyde]",
    "David Cairns [Inverclyde]" : "David Cairns [Greenock & Inverclyde / Inverclyde]",
    "Michael Connarty [Linlithgow & Falkirk East]" : "Michael Connarty [Linlithgow & Falkirk East / Falkirk East]",
    "Michael Connarty [Falkirk East]" : "Michael Connarty [Linlithgow & Falkirk East / Falkirk East]",
    "John Robertson [Glasgow North West]" : "John Robertson [Glasgow North West / Glasgow, Anniesland]",
    "John Robertson [Glasgow, Anniesland]" : "John Robertson [Glasgow North West / Glasgow, Anniesland]",
    "Douglas Alexander [Paisley & Renfrewshire South]" : "Douglas Alexander [Paisley & Renfrewshire South / Paisley South]",
    "Douglas Alexander [Paisley South]" : "Douglas Alexander [Paisley & Renfrewshire South / Paisley South]",
    "Russell Brown [Dumfries & Galloway]" : "Russell Brown [Dumfries & Galloway / Dumfries]",
    "Russell Brown [Dumfries]" : "Russell Brown [Dumfries & Galloway / Dumfries]",
    "Alistair Darling [Edinburgh Central]" : "Alistair Darling [Edinburgh Central / Edinburgh South West]",
    "Alistair Darling [Edinburgh South West]" : "Alistair Darling [Edinburgh Central / Edinburgh South West]",
    "Rosemary McKenna [Cumbernauld, Kilsyth & Kirkintilloch East]" : "Rosemary McKenna [Cumbernauld, Kilsyth & Kirkintilloch East / Cumbernauld & Kilsyth]",
    "Rosemary McKenna [Cumbernauld & Kilsyth]" : "Rosemary McKenna [Cumbernauld, Kilsyth & Kirkintilloch East / Cumbernauld & Kilsyth]",
    "John Reid [Hamilton North & Bellshill]" : "John Reid [Hamilton North & Bellshill / Airdrie & Shotts]",
    "John Reid [Airdrie & Shotts]" : "John Reid [Hamilton North & Bellshill / Airdrie & Shotts]",
    "Adam Ingram [East Kilbride, Strathaven & Lesmahagow]" : "Adam Ingram [East Kilbride, Strathaven & Lesmahagow / East Kilbride]",
    "Adam Ingram [East Kilbride]" : "Adam Ingram [East Kilbride, Strathaven & Lesmahagow / East Kilbride]",
    "Tom Clarke [Coatbridge, Chryston & Bellshill]" : "Tom Clarke [Coatbridge, Chryston & Bellshill / Coatbridge & Chryston]",
    "Tom Clarke [Coatbridge & Chryston]" : "Tom Clarke [Coatbridge, Chryston & Bellshill / Coatbridge & Chryston]",
    "Michael Moore [Tweeddale, Ettrick & Lauderdale]" : "Michael Moore [Tweeddale, Ettrick & Lauderdale / Berwickshire, Roxburgh & Selkirk]",
    "Michael Moore [Berwickshire, Roxburgh & Selkirk]" : "Michael Moore [Tweeddale, Ettrick & Lauderdale / Berwickshire, Roxburgh & Selkirk]",
    "Rachel Squire [Dunfermline & Fife West]" : "Rachel Squire [Dunfermline & Fife West / Dunfermline West]",
    "Rachel Squire [Dunfermline West]" : "Rachel Squire [Dunfermline & Fife West / Dunfermline West]",
    "Christopher Fraser [Mid Dorset & North Poole]" : "Christopher Fraser [Mid Dorset & North Poole / South West Norfolk]",
    "Christopher Fraser [South West Norfolk]" : "Christopher Fraser [Mid Dorset & North Poole / South West Norfolk]",
    "Gavin Strang [Edinburgh East]" : "Gavin Strang [Edinburgh East / Edinburgh East & Musselburgh]",
    "Gavin Strang [Edinburgh East & Musselburgh]" : "Gavin Strang [Edinburgh East / Edinburgh East & Musselburgh]",
    "John MacDougall [Glenrothes]" : "John MacDougall [Glenrothes / Central Fife]",
    "John MacDougall [Central Fife]" : "John MacDougall [Glenrothes / Central Fife]",
    "Thomas McAvoy [Glasgow, Rutherglen]" : "Thomas McAvoy [Glasgow, Rutherglen / Rutherglen & Hamilton West]",
    "Thomas McAvoy [Rutherglen & Hamilton West]" : "Thomas McAvoy [Glasgow, Rutherglen / Rutherglen & Hamilton West]",
    "Brian H Donohoe [Ayrshire Central]" : "Brian H Donohoe [Ayrshire Central / Cunninghame South]",
    "Brian H Donohoe [Cunninghame South]" : "Brian H Donohoe [Ayrshire Central / Cunninghame South]",
    "Mohammad Sarwar [Glasgow, Govan]" : "Mohammad Sarwar [Glasgow, Govan / Glasgow Central]",
    "Mohammad Sarwar [Glasgow Central]" : "Mohammad Sarwar [Glasgow, Govan / Glasgow Central]",
    "Pete Wishart [North Tayside]" : "Pete Wishart [North Tayside / Perth & Perthshire North]",
    "Pete Wishart [Perth & Perthshire North]" : "Pete Wishart [North Tayside / Perth & Perthshire North]",

    }

# Cases we want to specially match - add these in as we need them
class MultipleMatchException(Exception):
    pass

class PersonSets(xml.sax.handler.ContentHandler):

    def __init__(self):
        self.personsets=[] # what we are building - array of (sets of ids belonging to one person)

        self.fullnamescons={} # MPs "Firstname Lastname Constituency" --> person set (link to entry in personsets)
        self.fullnames={} # "Firstname Lastname" --> set of MPs (not link to entry in personsets)
        self.lords={} # Lord ID -> Attr
		self.lordspersonset={} # Lord ID --> person set
        self.member_ni={}
		self.member_ni_personset={}
        self.member_sp={}
		self.member_sp_personset={}
		self.ministermap={}

        self.historichansardtoid = {} # Historic Hansard Person ID -> MPs

        self.old_idtoperson={} # ID (member/lord/office) --> Person ID in last version of file
        self.last_person_id=None # largest person ID previously used
        self.in_person=None

        parser = xml.sax.make_parser()
        parser.setContentHandler(self)
        parser.parse("people.xml")
        parser.parse("all-members.xml")
        parser.parse("peers-ucl.xml")
        parser.parse("ni-members.xml")
        parser.parse("sp-members.xml")
        parser.parse("ministers.xml")
        parser.parse("royals.xml")

    def outputxml(self, fout):
        for personset in self.personsets:
            # OK, we generate a person id based on the mp id.

            # Find what person id we used for this set last time
            personid = None
            for attr in personset:
                # moffice ids are unstable in some cases, so we ignore
                if not re.match("uk.org.publicwhip/moffice/", attr["id"]):
                    if attr["id"] in self.old_idtoperson:
                        newpersonid = self.old_idtoperson[attr["id"]]
                        if personid and newpersonid <> personid:
                                raise Exception, "%s : Two members now same person, were different %s, %s" % (attr["id"], personid, newpersonid)
                        personid = newpersonid
            if not personid:
                self.last_person_id = self.last_person_id + 1
                personid = "uk.org.publicwhip/person/%d" % self.last_person_id

            # Get their final name
            maxdate = "1000-01-01"
            attr = None
            maxname = None
            for attr in personset:
                if attr["fromdate"]=='' or attr["fromdate"] >= maxdate:
                    if attr.has_key("firstname"):
                        # MPs or MLAs
                        maxdate = attr["fromdate"]
                        maxname = "%s %s" % (attr["firstname"], attr["lastname"])
                        if attr['title'] == 'Lord':
                            maxname = 'Lord' + maxname
                    elif attr.has_key("lordname") or attr.has_key("lordofname"):
                        # Lords (this should be in function!)
                        maxdate = attr["fromdate"]
                        maxname = []
                        if not attr["lordname"]:
                            maxname.append("The")
                        maxname.append(attr["title"])
                        if attr["lordname"]:
                            maxname.append(attr["lordname"])
                        if attr["lordofname"]:
                            maxname.append("of")
                            maxname.append(attr["lordofname"])
                        maxname = " ".join(maxname)
            if not maxname:
                raise Exception, "Unknown maxname %s" % attr['id']

            # Output the XML (sorted)
            fout.write('<person id="%s" latestname="%s">\n' % (personid.encode("latin-1"), maxname.encode("latin-1")))
            current = {}
            for attr in personset:
                if attr["fromdate"] <= date_today <= attr["todate"]:
                    current[attr["id"]] = ' current="yes"'
                else:
                    current[attr["id"]] = ''
			ofidl = [ str(attr["id"]) for attr in personset ]
			ofidl.sort()
            for ofid in ofidl:
                fout.write('    <office id="%s"%s/>\n' % (ofid, current[ofid]))
            fout.write('</person>\n')

    #def crosschecks(self):
    #    # check MP date ranges don't overlap
    #    for personset in self.fullnamescons.values():
    #        dateset = map(lambda attr: (attr["fromdate"], attr["todate"]), personset)
    #        dateset.sort(lambda x, y: cmp(x[0], y[0]))
    #        prevtodate = None
    #        for fromdate, todate in dateset:
    #            if len(fromdate) == 4: fromdate = '%s-01-01' % fromdate
    #            if len(todate) == 4: todate = '%s-12-31' % todate
    #            assert fromdate < todate, "date ranges bad %s %s" % (fromdate, todate)
    #            if prevtodate:
    #                assert prevtodate < fromdate, "date ranges overlap %s %s %s" % (prevtodate, fromdate, todate)
    #            prevtodate = todate

	# put ministerialships into each of the sets, based on matching matchid values
	# this works because the members code forms a basis to which ministerialships get attached
	def mergeministers(self):
        for pset in self.personsets:
			for a in pset.copy():
				memberid = a["id"]
				for moff in self.ministermap.get(memberid, []):
					pset.add(moff)

	# put lords into each of the sets
	def mergelordsandothers(self):
        for lord_id, attr in self.lords.iteritems():
            if lord_id in lordsmpmatches:
                mp = lordsmpmatches[lord_id]
                self.fullnamescons[mp].add(attr)
			elif lord_id in lordlordmatches:
				lordidold = lordlordmatches[lord_id]
				self.lordspersonset[lordidold].add(attr)
            else:
                newset = sets.Set()
                newset.add(attr)
                self.personsets.append(newset) # master copy of person sets
				self.lordspersonset[lord_id] = newset
 
        items = self.member_ni.items()
        items.sort(key=lambda x : x[1]['lastname'])
        for member_id, attr in items:
            cancons = memberList.canonicalcons(attr['constituency'], attr['fromdate'])
            lookup = "%s %s [%s]" % (attr['firstname'], attr['lastname'], cancons)
            if member_id in ni_mp_matches:
                mp = ni_mp_matches[member_id]
                self.fullnamescons[mp].add(attr)
            elif lookup in ni_ni_matches:
                ni = ni_ni_matches[lookup]
                self.member_ni_personset[ni].add(attr)
            elif member_id in ni_lord_matches:
                lord = ni_lord_matches[member_id]
                self.lordspersonset[lord].add(attr)
            elif lookup in self.fullnamescons and lookup != 'Roy Beggs [East Antrim]':
                self.fullnamescons[lookup].add(attr)
            elif lookup in self.member_ni_personset:
                self.member_ni_personset[lookup].add(attr)
            else:
                newset = sets.Set()
                newset.add(attr)
                self.personsets.append(newset)
                self.member_ni_personset[lookup] = newset

        items = self.member_sp.items()
        items.sort(key=lambda x : x[1]['lastname'])
        for member_id, attr in items:

            # Since some Westminster constituencies have the same
            # names as Scottish Parliament constituencies, we may get
            # some clashes unless we mangle the SP name a bit.  I
            # don't think this breaks anything else, but ICBW.
            
            cancons = memberList.canonicalcons("sp: "+attr['constituency'], attr['fromdate'])
            lookup = "%s %s [%s]" % (attr['firstname'], attr['lastname'], cancons)
            if member_id in sp_mp_matches:
                mp = sp_mp_matches[member_id]
                self.fullnamescons[mp].add(attr)
            elif lookup in sp_sp_matches:
                sp = sp_sp_matches[lookup]
                self.member_sp_personset[sp].add(attr)
            elif member_id in sp_lord_matches:
                lord = sp_lord_matches[member_id]
                self.lordspersonset[lord].add(attr)
            elif lookup in self.fullnamescons:
                self.fullnamescons[lookup].add(attr)
            elif lookup in self.member_sp_personset:
                self.member_sp_personset[lookup].add(attr)
            else:
                newset = sets.Set()
                newset.add(attr)
                self.personsets.append(newset)
                self.member_sp_personset[lookup] = newset

    # Look for people of the same name, but their constituency differs
#    def findotherpeoplewhoaresame(self):
#        goterror = False
#
#        for (name, nameset) in self.fullnames.iteritems():
#            # Find out ids of MPs that we have
#            ids = sets.Set(map(lambda attr: attr["id"], nameset))
#
#            # This name matcher includes fuzzier alias matches (e.g. Michael Foster ones)...
#            fuzzierids =  memberList.fullnametoids(name, None)
#
#            # ... so it should be a superset of the ones we have that just match canonical name
#            assert fuzzierids.issuperset(ids), "Not a superset %s %s" % (ids, fuzzierids)
#            fuzzierids = list(fuzzierids)
#
#            # hunt for pairs whose constituencies differ, and don't overlap in time
#            # (as one person can't hold office twice at once)
#            for id1 in range(len(fuzzierids)):
#                attr1 = memberList.getmember(fuzzierids[id1])
#                cancons1 = memberList.canonicalcons(attr1["constituency"], attr1["fromdate"])
#                for id2 in range(id1 + 1, len(fuzzierids)):
#                    attr2 = memberList.getmember(fuzzierids[id2])
#                    cancons2 = memberList.canonicalcons(attr2["constituency"], attr2["fromdate"])
#                    # check constituencies differ
#                    if cancons1 != cancons2:
#
#                        # Check that there is no MP with the same name/constituency
#                        # as one of the two, and who overlaps in date with the other.
#                        # That would mean they can't be the same person, as nobody
#                        # can be MP twice at once (and I think the media would
#                        # notice that!)
#                        match = False
#                        for id3 in range(len(fuzzierids)):
#                            attr3 = memberList.getmember(fuzzierids[id3])
#                            cancons3 = memberList.canonicalcons(attr3["constituency"], attr3["fromdate"])
#
#                            if cancons2 == cancons3 and \
#                                ((attr1["fromdate"] <= attr3["fromdate"] <= attr1["todate"])
#                                or (attr3["fromdate"] <= attr1["fromdate"] <= attr3["todate"])):
#                                #print "matcha %s %s %s (%s) %s to %s" % (attr3["id"], attr3["firstname"], attr3["lastname"], attr3["constituency"], attr3["fromdate"], attr3["todate"])
#                                match = True
#                            if cancons1 == cancons3 and \
#                                ((attr2["fromdate"] <= attr3["fromdate"] <= attr2["todate"])
#                                or (attr3["fromdate"] <= attr2["fromdate"] <= attr3["todate"])):
#                                #print "matchb %s %s %s (%s) %s to %s" % (attr3["id"], attr3["firstname"], attr3["lastname"], attr3["constituency"], attr3["fromdate"], attr3["todate"])
#                                match = True
#
#                        if not match:
#                            # we have a differing cons, but similar name name
#                            # check not in manual match overload
#                            fullnameconskey1 = "%s %s [%s]" % (attr1["firstname"], attr1["lastname"], cancons1)
#                            fullnameconskey2 = "%s %s [%s]" % (attr2["firstname"], attr2["lastname"], cancons2)
#                            if fullnameconskey1 in manualmatches and fullnameconskey2 in manualmatches \
#                                and manualmatches[fullnameconskey1] == manualmatches[fullnameconskey2]:
#                                pass
#                            else:
#                                goterror = True
#                                print "these are possibly the same person: "
#                                print " %s %s %s (%s) %s to %s" % (attr1["id"], attr1["firstname"], attr1["lastname"], attr1["constituency"], attr1["fromdate"], attr1["todate"])
#                                print " %s %s %s (%s) %s to %s" % (attr2["id"], attr2["firstname"], attr2["lastname"], attr2["constituency"], attr2["fromdate"], attr2["todate"])
#                                #  print in this form for handiness "Shaun Woodward [St Helens South]" : "Shaun Woodward [St Helens South / Witney]",
#                                print '"%s %s [%s]" : "%s %s [%s / %s]",' % (attr1["firstname"], attr1["lastname"], attr1["constituency"], attr1["firstname"], attr1["lastname"], attr1["constituency"], attr2["constituency"])
#                                print '"%s %s [%s]" : "%s %s [%s / %s]",' % (attr2["firstname"], attr2["lastname"], attr2["constituency"], attr1["firstname"], attr1["lastname"], attr1["constituency"], attr2["constituency"])
#
#        return goterror

    def startElement(self, name, attr):
        if name == "person":
            assert not self.in_person
            self.in_person = attr["id"]
            numeric_id = int(re.match("uk.org.publicwhip/person/(\d+)$", attr["id"]).group(1))
            if not self.last_person_id or self.last_person_id < numeric_id:
                self.last_person_id = numeric_id
        elif name == "office":
            assert self.in_person
            assert attr["id"] not in self.old_idtoperson
            self.old_idtoperson[attr["id"]] = self.in_person

        elif name == "member":
            assert not self.in_person

            if 'hansard_cons_id' in attr:
                cancons = memberList.conshansardtoid[attr['hansard_cons_id']]
                cancons = memberList.considtonamemap[cancons]
            else:
                cancons = memberList.canonicalcons(attr["constituency"], attr["fromdate"])
                cancons2 = memberList.canonicalcons(attr["constituency"], attr["todate"])
                assert cancons == cancons2

            # index by "Firstname Lastname Constituency"
            fullnameconskey = "%s %s [%s]" % (attr["firstname"], attr["lastname"], cancons)
            if fullnameconskey in manualmatches:
                fullnameconskey = manualmatches[fullnameconskey]

            if 'hansard_person_id' in attr:
                hansard_person_id = attr['hansard_person_id']
                if hansard_person_id not in self.historichansardtoid:
                    newset = sets.Set()
                    self.personsets.append(newset)
                    self.historichansardtoid[hansard_person_id] = newset
                self.historichansardtoid[hansard_person_id].add(attr.copy())
                if fullnameconskey not in self.fullnamescons:
                    self.fullnamescons[fullnameconskey] = self.historichansardtoid[hansard_person_id]
            else:
                if fullnameconskey not in self.fullnamescons:
                    newset = sets.Set()
                    self.personsets.append(newset) # master copy of person sets
                    self.fullnamescons[fullnameconskey] = newset # store link here
			    # MAKE A COPY.  (The xml documentation warns that the attr object can be reused, so shouldn't be put into your structures if it's not a copy).
                self.fullnamescons[fullnameconskey].add(attr.copy())

            fullnamekey = "%s %s" % (attr["firstname"], attr["lastname"])
            self.fullnames.setdefault(fullnamekey, sets.Set()).add(attr.copy())

        elif name == "lord":
            assert attr['id'] not in self.lords
            self.lords[attr['id']] = attr.copy()

        elif name == "royal":
            newset = sets.Set()
            newset.add(attr)
            self.personsets.append(newset)

		elif name == "moffice":
            assert not self.in_person

			#assert attr["id"] not in self.ministermap
			if attr.has_key("matchid"):
				self.ministermap.setdefault(attr["matchid"], sets.Set()).add(attr.copy())

        elif name == "member_ni":
            assert not self.in_person
            assert attr['id'] not in self.member_ni
            self.member_ni[attr['id']] = attr.copy()

        elif name == "member_sp":
            assert not self.in_person
            assert attr['id'] not in self.member_sp
            self.member_sp[attr['id']] = attr.copy()

    def endElement(self, name):
        if name == "person":
            self.in_person = None
        pass

# the main code
personSets = PersonSets()
#personSets.crosschecks()
#if personSets.findotherpeoplewhoaresame():
#    print
#    print "If they are, correct it with the manualmatches array"
#    print "Or add another array to show people who appear to be but are not"
#    print
#    sys.exit(1)
personSets.mergelordsandothers()
personSets.mergeministers()

tempfile = "temppeople.xml"
fout = open(tempfile, "w")
fout.write("""<?xml version="1.0" encoding="ISO-8859-1"?>

<!--

Contains a unique identifier for each person, and a list of ids
of offices which they have held.

Generated exclusively by personsets.py, don't hand edit it just now
(it would be such a pain to manually match up all these ids)

-->

<publicwhip>""")

personSets.outputxml(fout)
fout.write("</publicwhip>\n")
fout.close()

# overwrite people.xml
os.rename("temppeople.xml", "people.xml")



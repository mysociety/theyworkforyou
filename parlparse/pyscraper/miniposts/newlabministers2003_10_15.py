import re
import mx.DateTime

from minpostparse import govdepts


# derived from
# publicwhip\rawdata\ministers\MinistersInLabourGovernment2002-.doc
# ...
# 15.10.2003 Reference Room/ EB

# by hand editing


# HOLDERS OF MINISTERIAL OFFICE IN THE LABOUR GOVERNMENT 1997 -



data = """

+Cabinet Office

Chancellor of the Duchy of Lancaster

Dr David Clark							03 May 1997 - 27 July 98
Jack Cunningham                 27 July 1998 - 11 Oct 99
Dr Mo Mowlam						11 Oct 1999 - 11 June 01
Lord Macdonald of Tradeston             11 June 2001 -13 June 03
Douglas Alexander						13 June 2003 -

Minister of State

Lord Falconer of Thoroton						28 July 1998 -  11 Jun 01
Ian McCartney							28 July 1999 -  11 Jun 01
Barbara Roche                             11 June 2001- 29 May 02
Baroness Morgan of Huyton                           11 June 2001- 9 Nov 01
Douglas Alexander							29 May 2002- 13 June 03

Parliamentary Secretary

Derek Foster								03 May 1997 - 05 May 1997
Peter Kilfoyle								05 May 1997 - 28 July 99
# the date of the 05 May 1997 changeover is 06 in the MinistersNewLabour.doc
Graham Stringer							17 Nov 1999-   11 Jun 01
Christopher Leslie                      11 June 2001 -13 June 03



+Department for Constitutional Affairs

Secretary of State

Lord Falconer of Thoroton						13 June 2003-

Parliamentary Under-Secretary

Christopher Leslie							13 June 2003-
David Lammy								13 June 2003-
Lord Filkin CBE							13 June 2003-
Mrs Anne McGuire							13 June 2003-
Mr Don Touhig							13 June 2003-

Advocate General for Scotland

Dr Lynda Clark							20 May 1999 -


+Department for Culture, Media & Sport
#(from 14 July 1997 - formerly the Department of National Heritage)

Secretary of State

Chris Smith								03 May 1997 - 08 Jun 01
Tessa Jowell                              08 June 2001 -

Minister of State

Tom Clarke (Minister for Film and Tourism)				03 May 1997 - 28 July 98
Richard Caborn (Minister for Sport)                            11 June 2001 -
Baroness Blackstone (Minister for the Arts)                	11 June 2001 -13 June 03
Estelle Morris (Minister for the Arts)					13 June 2003-

Parliamentary Under-Secretary

Mark Fisher (Minister for Arts)					06 May 1997 - 28 July 98
Tony Banks (Minister for Sport)					05 May 1997 - 28 July 99
Kate Hoey (Minister for Sport)					28 July 1999 -  11 June 01
Janet Anderson							28 July 1998 - 11 June 01
Alan Howarth								28 July 1998 -  11 June 01
Dr Kim Howells                               11 June 2001 - 13 June 03
Lord McIntosh of Haringey						13 June 2003-

+Ministry of Defence

Secretary of State

George Robertson							03 May 1997 - 11 Oct 99
Geoffrey Hoon							11 October 1999 -

Minister of State

Dr John Reid (Minister for the Armed Forces)			05 May 1997 - 28 July 98
Doug Henderson							28 July 1998 - 28 July 99
John Spellar								28 July 1999 -  11 June 01
Lord Gilbert (Minister for Defence Procurement)			05 May 1997 - 28 July 99
Baroness Symons of Vernham Dean (Minister for Defence Procurement)			28 July 1999 - 11 June 01
Adam Ingram                              11 June 2001 -

Parliamentary Under-Secretary

John Spellar								05 May 1997 - 28 July 99
Peter Kilfoyle								28 July 1999 - 30 Jan 00
Dr Lewis Moonie							31 Jan 2000  - 13 June 03
Lord Bach                11 June 2001 -
Ivor Caplin								13 June 2003-


+Office of the Deputy Prime Minister

Deputy Prime Minister

John Prescott								02 May 1997-

Minister of State

Nick Raynsford (Local Government and the Regions)		11 June 2001-
Lord Rooker (Housing and Planning)					29 May 2002-
Barbara Roche								29 May 2002-13 June 03
Keith Hill								13 June 2003-

Parliamentary Under-Secretary

Tony McNulty								29 May 2002- 13 June 03
Christopher Leslie							29 May 2002- 13 June 03
Phil Hope								16 June 2001 -
# info for Hope from Ministers New Labour.doc
Yvette Cooper								13 June 2003-





+Department for Education and Employment

Secretary of State

David Blunkett							02 May 1997 -08 June 01

Minister of State

Andrew Smith (Minister for Employment, Welfare to Work and Equal Opportunities)	03 May 1997 - 11 Oct 99
Stephen Byers (Minister for School Standards)			05 May 1997 - 28 July 98
Estelle Morris								28 July 1998 - 08 Jun 01
Baroness Blackstone (Minister for Education and Employment)	05 May 1997 - 11 Jun 01
Tessa Jowell (Minister for the New Deal)				11 Oct 1999 - 08 Jun 01

Parliamentary Under-Secretary

Alan Howarth (for Employment and Equal Opportunities)		05 May 1997 - 28 July 98
Estelle Morris (for School Standards)					05 May 1997 - 28 July 98
Dr Kim Howells (for Lifelong Learning)				05 May 1997 - 28 July 98
Margaret Hodge							28 July 1998 -  11 Jun 01
Charles Clarke								28 July 1998 - 28 July 99
George Mudie								28 July 1998 - 28 July 99
Malcolm Wicks							28 July 1999 - 11 Jun 01
Jacqui Smith								28 July 1999 - 11 Jun 01
Michael Wills								28 July 1999 - 11 Jun 01


+Department for Education and Skills

Secretary of State

Estelle Morris                          08 June 2001 - 25 Oct 02
Charles Clarke								25 Oct 2002-

Minister of State

Stephen Timms (Minister for School Standards)            11 June 2001 -29 May 02
Margaret Hodge (Minister for Lifelong Learning until 13 June 2003, currently  Minister for Children)     11 June 2001 -
David Miliband (Minister for Schools)				29 May 2002-
Alan Johnson								13 June 2003-

Parliamentary Under-Secretary

Baroness Ashton of Upholland       11 June 2001 -
Ivan Lewis                         11 June 2001 -
John Healey                        11 June 2001 -29 May 02
Stephen Twigg							29 May 2002-




+Department of the Environment, Transport and the Regions

Secretary of State

John Prescott								02 May 1997 - 08 Jun 01

Minister of State

Gavin Strang  (Minister for Transport)				03 May 1997 - 28 July 98
Dr John Reid (Minister for Transport)				28 July 1998 - 17 May 99
Helen Liddell (Minister for Transport)				17 May 1999 - 28 July 99
Lord Macdonald of Tradeston (Minister for Transport)		28 July 1999 -  11 June 01
Michael Meacher (Minister for the Environment)			03 May 1997 - 11 June 01
Hilary Armstrong (Minister for Local Government and Housing)	05 May 1997 - 11 June 01
Richard Caborn (Minister for Regions, Regeneration and Planning)	05 May 1997 - 28 July 99
Nick Raynsford							28 July 1999 - 11 June 01

Parliamentary Under-Secretary

Nick Raynsford (Minister for London and Construction)		05 May 1997 - 28 July 99
Angela Eagle								05 May 1997 - 28 July 98
Alan Meale								28 July 1998 - 28 July 99
Glenda Jackson (Minister for Transport in London)			05 May 1997 - 28 July 99
Baroness Hayman (Minister for Roads) 				05 May 1997 - 28 July 98
Lord Whitty 								28 July 1998 - 11 June 01
Keith Hill								28 July 1999 - 11 June 01
Chris Mullin								28 July 1999 -  26 Jan 01
Beverley Hughes							28 July 1999 - 11 June 01
Robert Ainsworth							26 Jan 2001 -  11 June 01



+Department for Environment, Food and Rural Affairs

Secretary of State

Margaret Beckett                 08 June 2001 -

Minister of State

Michael Meacher (Minister for the Environment)              11 June 2001 -13 June 03
Alun Michael (Minister for Rural Affairs)                     11 June 2001 -
Elliot Morley (Minister for the Environment)			13 June 2003-

Parliamentary Under-Secretary

Lord Whitty 								11 June 2001 -
Elliot Morley                   11 June 2001 - 13 June 03
Ben Bradshaw								13 June 2003-


+Foreign & Commonwealth Office

Secretary of State

Robin Cook								02 May 1997 -08 June 01
Jack Straw                                                                                          08 June 2001 -

Minister of State

Derek Fatchett								05 May 1997 - 10 May 99
Tony Lloyd								05 May 1997 - 28 July 99
Doug Henderson							05 May 1997 - 28 July 98
Joyce Quin								28 July 1998 - 28 July 99
Geoff Hoon								17 May 1999 - 11 Oct 99
Peter Hain 								28 July 1999 -   24 Jan 01
John Battle								28 July 1999 -   11 Jun 01
Keith Vaz (Minister for Europe)					11 Oct 1999 -   11 Jun 01
Brian Wilson								24 Jan 2001 -   11 Jun 01
Baroness Symons of Vernham Dean (Minister for Trade until 13 June 2003, currently for Middle East)	         				11 June 2001 -
Peter Hain (Minister for Europe)        11 June 2001 - 24 Oct 02
Denis MacShane (Minister for Europe)				28 October 2002-
Mike O'Brien (Minister for Trade)							13 June 2003-

Parliamentary Under-Secretary

Baroness Symons of Vernham Dean					05 May 1997 - 28 July 99
Baroness Scotland of Asthal						28 July 1999 -  11 Jun 01
Ben Bradshaw                                                                                     11 June 2001 -29 May 02
Baroness Amos                                                                                   11 June 2001 -12 May 03
Denis MacShane                                                                                 11 June 2001 - 28 Oct 02
Mike O'Brien								29 May 2002- 13 June 03
Bill Rammell								28 Oct 2002-
Chris Mullin								13 June 2003-


+Department of Health

Secretary of State

Frank Dobson								03 May 1997 - 11 Oct 99
Alan Milburn								11 Oct 1999 - 12 June  03
Dr John Reid								12 June 03-

Minister of State

Tessa Jowell (Minister for Public Health)	05 May 1997 - 11 Oct 99
Alan Milburn								05 May 1997 - 23 Dec 98
John Denham								24 Dec 1998 -   11 Jun 01
Baroness Jay of Paddington						05 May 1997 - 27 July 98
John Hutton								11 October 1999 -
Jacqui Smith               11 June 2001 -13 June 03
Rosie Winterton							13 June 2003-

Parliamentary Secretary

Yvette Cooper (Minister for Public Health)				11 Oct 1999 - 29 May 02
David Lammy								29 May 2002- 13 June 03

Parliamentary Under-Secretary

Paul Boateng								05 May 1997 - 27 Oct 98
John Hutton								27 Oct 1998 - 11 Oct 99
Baroness Hayman							28 July 1998 - 28 July 99
Lord Hunt of Kings Heath						28 July 1999 - 18 Mar 03
Gisela Stuart								28 July 1999 - 11 June 01
Hazel Blears (Minister for public health)     	11 June 2001 -13 June 03
Yvette Cooper                                    11 June 2001 -29 May 02
Dr Stephen Ladyman							13 June 2003-
Lord Warner								13 June 2003-
Melanie Johnson (Minister for Public Health)			13 June 2003-


+Home Office

Secretary of State

Jack Straw								02 May 1997 - 08 Jun 01
David Blunkett                  08 June 2001 -

Minister of State

Alun Michael								05 May 1997 - 27 Oct 98
Paul Boateng								27 Oct 1998 -   11 Jun 01
Joyce Quin								05 May 1997 - 28 July 98
Lord Williams of Mostyn						28 July 1998 - 28 July 99
Charles Clarke								28 July 1999 -  11 Jun 01
Barbara Roche								28 July 1999 -  11 Jun 01
John Denham (Minister for Police, Courts and Drugs)                      11 June 2001 -18 Mar 03
Keith Bradley (Minister for Prisons)                   11 June 2001- 29 May 02
Lord Rooker (Minister for Asylum and Immigration)                       11 June 2001 -29 May 02
Lord Falconer of Thoroton (Minister for Criminal Policy)			29 May 2002- 13 June 03
Beverley Hughes (Minister for Citizenship and Immigration)	29 May 2002- 1 April 2004
Hazel Blears								13 June 2003-
Baroness Scotland of Asthal						13 June 2003-
Desmond Browne (Minister for Citizenship and Immigration)  1 Apr 2004-


Parliamentary Under-Secretary

George Howarth							05 May 1997 - 28 July 99
Mike O'Brien								05 May 1997 -  11 Jun 01
Lord Williams of Mostyn						05 May 1997 - 28 July 98
Kate Hoey								28 July 1998 - 28 July 99
Lord Bassam of Brighton						28 July 1999 - 29 May 02
Beverley Hughes                             11 June 2001 -29 May 02
Robert Ainsworth                            11 June 2001 -13 June 03
Angela Eagle	                            11 June 2001 -29 May 02
Hilary Benn								29 May 2002-13 May 03
Lord Filkin								29 May 2002- 13 June 03
Michael Wills								02 June 2002- 11 July 03
Paul Goggins								13 May 2003-
Caroline Flint								13 June 2003-
Fiona MacTaggart							13 June 2003-


+House of Commons

Lord Privy Seal

Ann Taylor								03 May 1997 - 27 July 98
Margaret Beckett							27 July 1998 -  08 Jun 01
Robin Cook                                08 June 2001 -17 Mar 03
Dr John Reid								04 Apr 2003- 12 June 03
Peter Hain 				12 June 2003-

Parliamentary Secretary

Ben Bradshaw								29 May 2002- 13 June 03
Phil Woolas								13 June 2003-


+Department for International Development

Secretary of State

Clare Short								03 May 1997 -12 May 03
Baroness Amos							12 May 2003- 05 Oct 03
Hilary Benn								05 Oct 2003-

Minister of State

Hilary Benn								13 May 2003- 05 Oct 03

Parliamentary Under-Secretary

George Foulkes							05 May 1997 - 26 Jan 01
Chris Mullin								26 Jan 2001 -   11 Jun 01
Hilary Benn                                11 June 2001 -29 May 02
Sally Keeble								29 May 2002- 13 June 03
Gareth Thomas [Harrow West]					13 June 2003-

+Law Officers' Department

Attorney General

John Morris								05 May 1997 - 28 July 99
Lord Williams of Mostyn						28 July 1999  -  11 Jun 01
Lord Goldsmith                             11 June 2001 -

Solicitor General

Lord Falconer of Thoroton						05 May 1997 - 28 July 98
Ross Cranston								28 July 1998 -  11 Jun 01
Harriet Harman                              11 June 2001 -

Lord Advocate

# this one ceased, but we don't know when; contact law officer's dept
Lord Hardie								06 May 1997 - 1 Jan 2000

#Solicitor-General for Scotland
#
#Colin Boyd								06 May 1997 -


+Northern Ireland Office

Secretary of State

Dr Mo Mowlam							03 May 1997 - 11 Oct 99
Peter Mandelson							11 Oct 1999 - 24 Jan 01
Dr John Reid								24 Jan 2001-24 Oct 02
Paul Murphy								24 October 2002-


Minister of State

Paul Murphy								05 May 1997 - 28 July 99
Adam Ingram								05 May 1997 - 11 Jun 01
Jane Kennedy                        11 June 2001 -1 Apr 2004
John Spellar								13 June 2003-

Parliamentary Under-Secretary

Lord Dubs								06 May 1997 - 02 Dec 99
Tony Worthington							05 May 1997 - 28 July 98
John McFall								28 July 1998 -	02 Dec 99
George Howarth							28 July 1999 - 11 June 01
Desmond Browne                              11 June 2001 - 13 June 03
Angela Smith								14 October 2002-
Ian Pearson								14 October 2002-
Barry Gardiner							1 Apr 2004-



+Privy Council Office

President of the Council

Lord Richard								03 May 1997 - 27 July 98
Baroness Jay of Paddington						27 July 1998 -  08 Jun 01
Lord Williams of Mostyn                         08 June 2001 - 20 Sep 03
Baroness Amos					06 Oct 2003 -

Parliamentary Secretary

Paddy Tipping								23 Dec 1998 -  11 June 01
Stephen Twigg                                11 June 2001 - 29 May 02
Ben Bradshaw								29 May 2002-13 June 03


+Department of Trade and Industry

Secretary of State

Margaret Beckett							02 May 1997 - 27 July 98
Peter Mandelson							27 July 1998 - 23 Dec 98
Stephen Byers								24 Dec 1998 - 08 Jun 01
Patricia Hewitt                               08 June 2001 -

Minister of State

Lord Clinton-Davis (Minister for Trade)				05 May 1997 - 28 July 98
Brian Wilson								28 July 1998 - 28 July 99
Richard Caborn							28 July 1999 -  11 Jun 01
John Battle (Minister for Science, Energy and Industry)		05 May 1997 - 28 July 99
Helen Liddell (Minister for Science, Energy and Industry)		28 July 1999 - 24 Jan 01
Ian McCartney (Minister for Competitiveness)			05 May 1997 - 28 July 99
Lord Simon of Highbury (Minister for Trade and Competitiveness in Europe)	07 May 1997 - 28 July 99
Patricia Hewitt							28 July 1999 -  08 Jun 01
Peter Hain								24 Jan 2001  -   11 Jun 01
Douglas Alexander (Minister for E-Commerce and Competitiveness) 11 Jun 01   -29 May 02
Baroness Symons of Vernham Dean (Minister for Trade)                 11 June 2001 -13 June 03
Brian Wilson (Minister for Industry and Energy)                             	11 June 2001 -13 June 03
Alan Johnson (Minister for Employment and the Regions)           	11 June 2001 -13 June 03
Stephen Timms (Minister for e-commerce and competitiveness)	29 May 2002-
Jacqui Smith (Deputy minister for women)				13 June 2003-
Mike O'Brien (Minister for Trade and Investment)							13 June 2003-


Parliamentary Under-Secretary

Nigel Griffiths (Minister for Competition and Consumer Affairs)		05 May 1997- 28 July 98
Dr Kim Howells						           28 July 1998 - 11 Jun 01
Barbara Roche (Minister for Small Firms, Trade and Industry)		05 May 1997-  04 Jan 99
Michael Wills								04 Jan 1999- 28 July 99
Lord Sainsbury of Turville (Minister for Sciences)			28 July 1998-
Alan Johnson								28 July 1999- 11 June 01
Melanie Johnson                                11 June 2001- 13 June 03
Nigel Griffiths                               11 June 2001 -
Gerry Sutcliffe							13 June 2003-



+Department for Transport

Secretary of State

Alistair Darling	29 May 2002-

Minister of State

Gavin Strang								03 May 1997 - 27 July 98
Dr John Reid								27 July 1998 - 17 May 99
Helen Liddell								17 May 1999 - 28 July 99
Lord Macdonald of Tradeston					28 July 1999 -  08 Jun 01
John Spellar (Minister for Transport)					08 June 2001-13 June 03
Dr Kim Howells (Minister for Transport)				13 June 03-

Parliamentary Under-Secretary

David Jamieson							11 June 2002-
Tony McNulty								13 June 2003-


+HM Treasury

Prime Minister

Tony Blair 02 May 1997-

Chancellor of the Exchequer

Gordon Brown							02 May 1997 -

Chief Secretary

Alistair Darling							03 May 1997 - 27 July 98
Stephen Byers								27 July 1998 - 23 Dec 98
Alan Milburn								24 Dec 1998 - 11 Oct 99
Andrew Smith								11 Oct 1999 -29 May 02
Paul Boateng								29 May 2002-

Financial Secretary

Dawn Primarolo							05 May 1997 - 04 Jan 99
Barbara Roche								04 Jan 1999 - 28 July 99
Stephen Timms							28 July 1999 - 11 Jun 01
Paul Boateng                                    11 June 2001 - 29 May 02
Ruth Kelly								29 May 2002-

Economic Secretary

Helen Liddell								05 May 1997 - 28 July 98
Patricia Hewitt							28 July 1998 - 28 July 99
Melanie Johnson							28 July 1999 - 11 Jun 01
Ruth Kelly                                  11 June 2001 - 29 May 02
John Healey								29 May 2002-

Paymaster General

Geoffrey Robinson							05 May 1997 - 23 Dec 98
Dawn Primarolo							04 Jan 1999 -


# WHIPS (HOUSE OF COMMONS) -- drop up into HM Treasury

Parliamentary Secretary to the Treasury

Nicholas Brown							03 May 1997 - 27 July 98
Ann Taylor								27 July 1998 -  11 Jun 01
Hilary Armstrong							11 June 2001 -


Lords Commissioner

Robert Ainsworth							06 May 1997 - 26 Jan 01
Graham Allen								06 May 1997 - 28 July 98
Jim Dowd								06 May 1997 - 11 Jun 01
John McFall								06 May 1997 - 28 July 98
Jon Owen Jones							06 May 1997 - 28 July 98
Clive Betts								28 July 1998 - 11 Jun 01
David Jamieson							28 July 1998 - 11 Jun 01
Jane Kennedy								28 July 1998 - 11 Oct 99
David Clelland							06 Feb 2001 -  11 Jun 01
Anne McGuire                          12 June 2001- 29 May 02
John Heppell                               12 June 2001 -
Tony McNulty                               12 June 2001 -29 May 02
Nick Ainger                                 12 June 2001 -
Graham Stringer                           12 June 2001 -29 May 02
Ian Pearson								29 May 2002- 14 Oct 02
Jim Fitzpatrick							29 May 2002-13 June 03
Philip Woolas								29 May 2002-13 June 02
Bill Rammell								14 Oct 2002-28 Oct 02
Jim Murphy								29 May 2002-
Joan Ryan								29 May 2002-
Derek Twigg								29 May 2002-


Assistant Whip

Clive Betts								06 May 1997 - 28 July 98
David Clelland							06 May 1997 - 06 Feb 01
Kevin Hughes								06 May 1997 - 11 Jun 01
David Jamieson							06 May 1997 - 28 July 98
Jane Kennedy								06 May 1997 - 28 July 98
Greg Pope								06 May 1997 - 11 Jun 01
Bridget Prentice							06 May 1997 - 28 July 98
Anne McGuire							28 July 1998 - 11 Jun 01
David Hanson								28 July 1998 - 28 July 99
Michael Hall								28 July 1998 - 11 Jun 01
Keith Hill								28 July 1998 - 28 July 99
Gerry Sutcliffe							28 July 1999 - 11 Jun 01
Tony McNulty								28 July 1999 - 11 Jun 01
Don Touhig								17 Nov 1999 - 11 Jun 01
Ian Pearson								06 Feb 2001 -29 May 02
Fraser Kemp                            12 June 2001 -
Angela Smith                         12 June 2001 - 14 Oct 02
Ivor Caplin                              12 June 2001 -13 June 03
Jim Fitzpatrick                           12 June 2001 -29 May 02
Phil Woolas                              12 June 2001 -29 May 02
Dan Norris                              12 June 2001 -13 June 03
Charlotte Atkins						14 Oct 2002-
Gillian Merron							28 Oct 2002-
Vernon Coaker							13 June 2003-
Paul Clark								13 June 2003-
Margaret Moran							13 June 2003-
Bridget Prentice							13 June 2003-


# these are also whips
+HM Household

Treasurer of Her Majesty's Household

George Mudie								06 May 1997 - 28 July 98
Keith Bradley								28 July 1998 -  11 Jun 01
Keith Hill                                  11 June 2001 -13 June 03
Robert Ainsworth							13 June 2003-

Comptroller

Thomas McAvoy							06 May 1997 -

Vice Chamberlain

Janet Anderson							06 May 1997 - 28 July 98
Graham Allen								28 July 1998 -  12 Jun 01
Gerry Sutcliffe                         12 Jun 2001 -13 June 03
Jim Fitzpatrick							13 June 2003-



# drop into household
#+WHIPS (HOUSE OF LORDS)

Chief Whip (House of Lords)

Lord Carter								06 May 1997 -29 May 02
Lord Grocott								29 May 2002-

Deputy Chief Whip (House of Lords)

Lord McIntosh of Haringey						07 May 1997 -13 June 03
Lord Davies of Oldham						13 June 2003-

Lords in Waiting

Lord Haskel								07 May 1997 - 28 July 98
Lord Whitty								07 May 1997 - 28 July 98
Lord Hoyle								07 May 1997 - 28 July 98
Lord Hunt of Kings Heath						28 July 1998 - 28 July 99
Lord Bach						              28 July 1999 - 20 Nov 00
Lord Burlison                       	28 Jan 1999 - 11 June 00
Lord Davies of Oldham                      12 June 2001 -13 June 03
Lord Grocott                              12 June 2001 -29 May 02
Lord Filkin                                  12 June 2001 -29 May 02
Lord Bassam of Brighton                  12 June 2001 -
Lord Evans of Temple Guiting CBE					13 June 2003-

Baronesses in Waiting

Baroness Gould of Potternewton					07 May 1997 - 28 July 98
Baroness Ramsay of Cartvale						28 July 1998 - 11 June 01
Baroness Amos							28 July 1998 - 11 June 01
Baroness Farrington of Ribbleton					07 May 1997 -
Baroness Crawley							29 May 2002-
Baroness Andrews OBE						29 May 2002-

# the info on these need finding out from hlinfo@parliament.uk
Lord Steward

Duke of Abercorn	            1 Jan 2000-

Lord Chamberlain

Lord Luce						1 Jan 2000-

Master of the Horse

Lord Vestey	    				1 Jan 2000-



+Department for Work and Pensions

Secretary of State

Alistair Darling                        08 June 2001-29 May 02
Andrew Smith								29 May 2002-

Minister of State

Nicholas Brown (Minister for Work)               11 June 2001 -13 June 03
Ian McCartney (Minister for Pensions)            11 June 2001- 04 Apr 03
Desmond Browne							13 June 2003-1 Apr 2004
Malcolm Wicks							13 June 2003-
Jane Kennedy							1 Apr 2004-

Parliamentary Under-Secretary

Baroness Hollis of Heigham                    		11 June 2001 -
Malcolm Wicks                                     	11 June 2001 -13 June 03
Maria Eagle                                     	11 June 2001 -
Chris Pond								13 June 2003-
# day of month is not known
Baroness Ashton of Upholland       1 July 2002-



+Ministry of Agriculture, Fisheries and Food

Secretary of State

Dr Jack Cunningham							03 May 1997 - 27 July 98
Nick Brown								27 July 1998 -	9 June 2001

Minister of State

Jeff Rooker								05 May 1997 - 28 July 99
Joyce Quin								28 July 1999 -  8 June 2001
Baroness Hayman							28 July 1999 -   8 June 2001

Parliamentary Secretary

Elliot Morley (Minister for Fisheries and the Countryside)		05 May 1997 -  8 June 2001
Lord Donoughue (Minister for Farming and Food Industry)		05 May 1997 - 28 July 99


+Lord Chancellor's Department

Lord Chancellor

Lord Irvine of Lairg							02 May 1997-12 June 03

# this is part of the job of S of S for Constitutional Affairs
#Lord Falconer of Thoroton						12 June 2003-

Minister of State

Geoff Hoon								28 July 1998 - 17 May 99

Parliamentary Secretary

Geoff Hoon								05 May 1997 - 28 July 98
Keith Vaz								17 May 1999 - 11 Oct 99
David Lock								28 July 1999 -  07 Jun 01
Jane Kennedy								11 Oct 1999  -  11 Jun 01
Lord Bach								20 Nov 2000 - 11 Jun 01
# Bach's date was missing, and previous job ended disagreed with MinistersNewLabour.doc
Baroness Scotland of Asthal                 11 June 2001 -13 June 03
Michael Wills                               11 June 2001 -29 May 02
Rosie Winterton                             11 June 2001 - 13 June 03
Yvette Cooper								29 May 2002- 13 June 03


+Scottish Office

Secretary of State

Donald Dewar								03 May 1997 -17 May 99
Dr John Reid								17 May 1999 - 24 Jan 01
Helen Liddell								24 Jan 2001 -13 June 03

# this is true, but his title now is S of State for Transport and Scotland
#Alistair Darling 		13 June 2003-

Minister of State

George Foulkes                       11 June 2001 - 29 May 02

# I'd like to make all the following plain Ministers of State, with added responsibilities in brackets, but Foulkes' position breaks this
Minister for Education and Industry

Brian Wilson								05 May 1997 - 28 July 98
Helen Liddell								28 July 1998 - 17 May 99
Brian Wilson (lead Minister on Performance and Innovation Unit study on trade policy)	28 July 1999 - 24 Jan 01
George Foulkes							26 Jan 2001  -  11 Jun 01

Minister for Health and Arts

Sam Galbraith								05 May 1997 -17 May 99

Minister for Home Affairs and Devolution

Henry McLeish							05 May 1997 -17 May 99

Parliamentary Secretary

Anne McGuire							29 May 2002- 13 June 2003

Parliamentary Under-Secretary

Lord Sewel (Minister for Agriculture, Environment and Fisheries)	05 May 1997 - 28 July 99
Lord Macdonald of Tradeston (Minister for Business and Industry)	03 Aug 1998 - 28 July 99
Malcolm Chisholm (Minister for Local Government and Transport) 05 May 1997 - 10 November 1997
Calum MacDonald (Minister for Local Government and Transport)11 November 1997 - 28 July 99


+Department of Social Security

Secretary of State

Harriet Harman							03 May 1997 - 27 July 98
Alistair Darling 							27 July 1998 -8 June 01

Minister of State

Frank Field (Minister for Social Security and Welfare Reform)	05 May 1997 - 28 July 98
John Denham								28 July 1998 - 24 Dec 98
Stephen Timms							04 Jan 1999 - 28 July 99
Jeff Rooker								28 July 1999 - 8 June 01

Parliamentary Under-Secretary

John Denham								05 May 1997 - 28 July 98
Keith Bradley								05 May 1997 - 28 July 98
Baroness Hollis of Heigham						05 May 1997 - 8 June 01
Joan Ruddock (Minister for Women)						11 June 1997 - 28 July 98
Angela Eagle								28 July 1998 - 8 June 01
Stephen Timms							28 July 1998 -04 Jan 99
Hugh Bayley								04 Jan 1999 - 8 June 01


+Department for Transport, Local Government and the Regions

Secretary of State

Stephen Byers                   08 June 2001 - 29 May 02

Minister of State

John Spellar (Minister for Transport)                          08 June 2001 -29 May 02
Nick Raynsford (Minister for Local Government)                            11 June 2001 -29 May 02
Lord Falconer of Thoroton (Minister for Housing and Planning)     11 June 2001 - 29 May 02

Parliamentary Under-Secretary

David Jamieson                                 11 June 2001- 29 May 02
Sally Keeble                                 11 June 2001- 29 May 02
Dr Alan Whitehead                            11 June 2001- 29 May 02


+Welsh Office

Secretary of State

Ron Davies								03 May 1997 - 27 Oct  98
Alun Michael								27 Oct 1998 - 28 July 99
Paul Murphy								28 July 1999 -24 Oct 02

# this is combined with his other job and not listed in the index
#Peter Hain 	24 Oct 2002-

Parliamentary Under-Secretary

Peter Hain								05 May 1997 - 28 July 99
Win Griffiths								05 May 1997 - 28 July 98
Jon Owen Jones							28 July 1998 - 28 July 99
David Hanson								28 July 1999 -  11 Jun 01
Don Touhig                                11 June 2001 - 13 June 2003

+No Department

Minister without Portfolio

Peter Mandelson							05 May 1997 - 27 July 98
Charles Clarke                         08 June 2001 - 24 Oct 02
John Reid								24 Oct 2002 - 04 Apr 03
Ian McCartney							04 Apr 2003 -

Second Church Estates Commissioner  	

Stuart Bell							5 May 1997-

"""

dateofinfo = "2003-10-15"
opendate = dateofinfo + "--OPEN"

# split the lines and allocate the information.

# this code is just to decode the hand-edited file above

deadgovdepts = [
		"Department for Education and Employment",
		"Department of the Environment, Transport and the Regions",
		"Ministry of Agriculture, Fisheries and Food",
		#"WHIPS (HOUSE OF COMMONS)", # these are HM Treasury
		#"WHIPS (HOUSE OF LORDS)",
		"Lord Chancellor's Department",
		"Scottish Office",
		"Welsh Office",
		"Department of Social Security",
		"Department for Transport, Local Government and the Regions",
		]

class Minlabmin:
	def __init__(self):
		self.sourcedoc = "newlabministers2003-10-15"
		self.stimeend = None
		self.stimestart = None
		self.bopen = False
		pass




def ParseOldRecords():
	dept = None
	position = None
	res = [ ]

	for d in re.split("\s*\n\s*", data):
		if not d or d[0] == '#':
			continue
		if d[0] == "+":
			dept = d[1:]
			assert (dept in govdepts) or (dept in deadgovdepts)
			continue
		if not re.search("\d", d):
			position = d
			continue

		decode = re.match("([^\d\(]*?)\s*(?:\(([^\)]*)\))?\s*(\d+\s+\w+\s+\d+)\s*-\s*(\d+\s+\w+\s+\d+)?\s*$", d)
		if not decode:
			print d

		minlabmin = Minlabmin()

		minlabmin.fullname = decode.group(1)
		fnm = re.match("(.*?)\s+\[(.*?)\]", minlabmin.fullname)
		if fnm:
			minlabmin.fullname = fnm.group(1)
			minlabmin.cons = fnm.group(2)
		else:
			minlabmin.cons = ""
		minlabmin.dept = dept
		minlabmin.pos = position
		minlabmin.responsibility = decode.group(2)
		if (minlabmin.responsibility):
			minlabmin.responsibility = re.sub("^(?:Minister )?for ", "", minlabmin.responsibility)
		minlabmin.sdatestart = mx.DateTime.DateTimeFrom(decode.group(3)).date

		if decode.group(4):
			minlabmin.sdateend = mx.DateTime.DateTimeFrom(decode.group(4)).date
		else:
			minlabmin.sdateend = opendate

		res.append(minlabmin)

	return res




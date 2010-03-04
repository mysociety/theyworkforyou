from testing import *

main_page_test = run_http_test("/",
                               test_name="Fetching main page",
                               test_short_name="basic-main-page")

def recent_event(current_test,http_test,header,item):
    # We want to check that the list on the front page has links
    # to the recent debates.  First fine a matching <h4>
    h = http_test.soup.find( lambda x: x.name == 'h4' and tag_text_is(x,header) )
    if not h:
        current_test.log("The header '"+header+"' at level h4 was not found")
        return False
    current_test.log("Found the header: '"+header+"'")
    ul = next_tag(h)
    if not (ul.name == 'ul'):
        current_test.log("The next tag was not <ul>")
        return False
    for li in ul.contents:
        if not (li and li.name == 'li'):
            continue
        current_test.log("Looking at list item: "+li.prettify())
        if tag_text_is(li,item):
            current_test.log("That's it.")
            return True
    return False

items_to_find = [ ("The most recent Commons debates", "Business Before Questions"),
                  ("The most recent Lords debates", "Africa: Water Shortages &#8212; Question"),
                  ("The most recent Westminster Hall debates", "[Sir Nicholas Winterton in the Chair] &#8212; Oil and Gas"),
                  ("The most recent Written Answers","Work and Pensions"),
                  ("The most recent Written Ministerial Statements", "House of LordsEU: Justice and Home Affairs CouncilGlobal Entrepreneurship WeekLondon Underground") ]

i = 0
for duple in items_to_find:
    run_page_test(main_page_test,
                  lambda t,o: recent_event(t,o,duple[0],duple[1]),
                  test_name="Checking that '"+duple[0]+"' contains '"+duple[1]+"'",
                  test_short_name="main-page-recent-item-"+str(i))
    i += 1


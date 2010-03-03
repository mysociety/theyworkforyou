from testing import *

def busiest_debate(current_test,http_test,header,text):
    h = http_test.soup.find( lambda x: (x.name == 'h3' or x.name == 'h4') and tag_text_is(x,header) )
    if not h:
        current_test.log("Failed to find header with text '"+header+"'")
        return False
    ns = next_tag(next_tag(h,sibling=False),sibling=False)
    current_test.log("The tag after the tag after this was:\n"+ns.prettify())
    return tag_text_is(ns,text)

main_scotland_page_test = run_http_test("/scotland/",
                                        test_name="Fetching main page for Scotland",
                                        test_short_name="basic-main-scotland-page")

header = "Busiest Scottish Parliament debates from the most recent week"
text = 'Scottish Economy (103 speeches)'

run_page_test(main_scotland_page_test,
              lambda t,o: busiest_debate(t,o,header,text),
              test_name="Checking that first item in '"+header+"' is '"+text+"'",
              test_short_name="main-scotland-page-busiest-0")

def any_answer(current_test,http_test,header):
    h = http_test.soup.find( lambda x: x.name == 'h3' and tag_text_is(x,header) )
    if not h:
        current_test.log("Failed to find header with text '"+header+"'")
        return False
    ns = next_tag(next_tag(h,sibling=False),sibling=False)
    current_test.log("The tag after the tag after this is:\n"+ns.prettify())
    stringified = non_tag_data_in(ns)
    current_test.log("... which, stringified, is: "+stringified)
    return re.search('\(2[0-9]\s+October\s+2009\)',stringified)

header = "Some recent written answers"

run_page_test(main_scotland_page_test,
              lambda t,o: any_answer(t,o,header),
              test_name="Checking that there's some random answer under '"+header+"'",
              test_short_name="main-scotland-page-any-written")

# ------------------------------------------------------------------------

main_ni_page_test = run_http_test("/ni/",
                                  test_name="Fetching main page for Northern Ireland",
                                  test_short_name="basic-main-ni-page")

header = "Busiest debates from the most recent month"
text = u"Private Members&#8217; Business"

run_page_test(main_ni_page_test,
              lambda t,o: busiest_debate(t,o,header,text),
              test_name="Checking that first item in '"+header+"' is '"+text+"'",
              test_short_name="main-ni-page-busiest-0")

# ------------------------------------------------------------------------

main_wales_page_test = run_http_test("/wales/",
                                     test_name="Fetching main page for wales",
                                     test_short_name="basic-main-wales-page")

run_page_test(main_wales_page_test,
              lambda t,o: o.soup.find( lambda x: x.name == 'h3' and tag_text_is(x,"We need you!") ),
              test_name="Checking that the Wales page still asks for help",
              test_short_name="main-wales-page-undone")

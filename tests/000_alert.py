from browser import *
from testing import *

cj, browser = create_cookiejar_and_browser()
search_hewitt_test = run_http_test("/search/?s=patricia+hewitt",
                                   test_name="Searching for Patricia Hewitt's page",
                                   test_short_name="search-hewitt",
                                   browser=browser)

def find_people_in_search_results(test,old_test):
    pr = old_test.soup.find( lambda x: x.name == 'div' and ('id','people_results') in x.attrs )
    test.log("Got people_people results div:\n"+pr.prettify())
    if not pr:
        test.log('Failed to find a div with id="people_results"')
        return []
    results = []
    all_links = pr.findAll('a')
    test.log("  all_links of length "+str(len(all_links)))
    for link in all_links:
        test.log("  link has attributes: "+str(link.attrs))
        for t in link.attrs:
            test.log("    attribute pair: "+str(t))
            if t[0] == 'href' and not re.search('^/search.*pop',t[1]):
                tag_contents = non_tag_data_in(link)
                test.log('Found "'+tag_contents+'"')
                results.append((t[0],t[1],tag_contents))
    return results

run_page_test(search_hewitt_test,
              lambda t,o: [ x for x in find_people_in_search_results(t,o) if x[2] == 'Patricia Hewitt' ],
              test_name="Finding Patricia Hewitt in the search results page",
              test_short_name="search-results-hewitt")

hewitt_page_test = run_http_test("/mp/patricia_hewitt/leicester_west",
                                 test_name="Fetching Patricia Hewitt's page",
                                 test_short_name="fetching-hewitt-page",
                                 browser=browser)

def link_to_email_alert(t,o):
    return o.soup.find( lambda x: x.name == 'a' and tag_text_is(x,'Email me whenever',substring=True) )

alert_link_test = run_page_test(hewitt_page_test,
                                lambda t,o: link_to_email_alert(t,o),
                                test_name="Finding email alert link",
                                test_short_name="find-email-alert-link")

def find_alert_form(t,o):
    return o.soup.find( lambda x: x.name == 'form' and (('action','/alert/') in x.attrs) and (('method','post') in x.attrs) )

def find_hidden_input(t,o,name,value):
    for i in o.soup.find( lambda x: x.name == 'input' and (('type','hidden') in x.attrs) ):
        t.log("found hidden input element:\n"+i.prettify())
        for tuple in x.attrs:
            if tuple[0] == 'name' and tuple[1] == name:
                t.log(" ... with the right name ("+name+")")
                if ('value',value) in x.attrs:
                    return i
                else:
                    t.log(" ... but no value attribute set to: "+str(value))
    return None

if alert_link_test.succeeded():

    follow_alert_link = alert_link_test.test_succeeded['href']

    follow_alert_link_test = run_http_test(follow_alert_link,
                                           test_name="Following email alert link",
                                           test_short_name="follow-email-alert-link",
                                           check_for_error_element=False)

    find_alert_form_test = run_page_test(follow_alert_link_test,
                                         find_alert_form,
                                         test_name='Finding alert form',
                                         test_short_name='find-alert-form')

    expected_pid = '10278'

    person_id_hidden_test = run_page_test(follow_alert_link_test,
                                          lambda t,o: find_hidden_input(t,o,'pid',expected_pid),
                                          test_name='Checking for correct pid for Patricia Hewitt',
                                          test_short_name='pid-in-alert-form')

    form_tag = find_alert_form_test.test_succeeded
    if form_tag:

        random_email_address = generate_email_address()

        post_parameters = {}
        post_parameters['email'] = random_email_address
        post_parameters['pid'] = expected_pid
        post_parameters['submitted'] = 'true'

        print form_tag.prettify()
        # run_http_test(

from browser import *
from testing import *
import time

cj, browser = create_cookiejar_and_browser()
search_hewitt_test = run_http_test("/search/?s=patricia+hewitt",
                                   test_name="Searching for Patricia Hewitt's page",
                                   test_short_name="search-hewitt",
                                   browser=browser)

def find_people_in_search_results(test,old_test):
    pr = old_test.soup.find( lambda x: x.name == 'div' and ('id','people_results') in x.attrs )
    if not pr:
        test.log('Failed to find a div with id="people_results"')
        return []
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
    for i in o.soup.findAll( lambda x: x.name == 'input' and (('type','hidden') in x.attrs) ):
        t.log("Found hidden input element:\n"+i.prettify())
        t.log("  Attributes are: "+str(i.attrs))
        t.log("  Looking for name '"+name+"' => '"+value+"'")
        if ('name',name) in i.attrs:
            t.log("  ... with the right name ("+name+")")
            if ('value',value) in i.attrs:
                t.log("  And the right value ("+value+")")
                return i
            else:
                t.log("  ... but no value attribute set to: "+str(value))
    return None

if alert_link_test.succeeded():

    follow_alert_link = alert_link_test.test_succeeded['href']

    follow_alert_link_test = run_http_test(follow_alert_link,
                                           test_name="Following email alert link",
                                           test_short_name="follow-email-alert-link",
                                           check_for_error_element=False,
                                           browser=browser)

    find_alert_form_test = run_page_test(follow_alert_link_test,
                                         find_alert_form,
                                         test_name='Finding alert form',
                                         test_short_name='find-alert-form')

    expected_pid = '10278'

    person_id_hidden_test = run_page_test(follow_alert_link_test,
                                          lambda t,o: find_hidden_input(t,o,'pid',expected_pid),
                                          test_name='Checking for correct pid for Patricia Hewitt',
                                          test_short_name='pid-in-alert-form')

    def get_selected_option(current_test,old_test,form_element):
        start = old_test.soup
        if form_element:
            start = form_element
        option = start.find(lambda x: x.name == 'option' and ('selected','') in x.attrs)
        t.log("Got option: "+str(option))
        return False

    form_tag = find_alert_form_test.test_succeeded
    if form_tag:

        random_email_address = generate_email_address()

        post_parameters = {}
        post_parameters['email'] = random_email_address
        post_parameters['pid'] = expected_pid
        post_parameters['submit'] = 'Request Email Alert'
        post_parameters['submitted'] = 'true'

        first_alert_stage = run_http_test("/alert/",
                                          post_parameters=post_parameters,
                                          test_name='Posting alert form',
                                          test_short_name='create-alert',
                                          browser=browser,
                                          render=False)

        pre_confirmation_test = run_page_test(first_alert_stage,
                                              lambda t,o: tag_text_is(o.soup.body,"We're nearly done",substring=True),
                                              test_name="Looking for the \"We're nearly done\" message",
                                              test_short_name='nearly-done')

        # Wait a second for the email to be delivered locally (should be ample):
        time.sleep(1)

        confirmation_link_test = run_ssh_test("/usr/local/bin/find-last-email.py "+
                                              random_email_address+
                                              " 'http://.*/A/[^ $]*'",
                                              test_name="Finding the confirmation link in alice's email",
                                              test_short_name="find-confirmation-link")

        if confirmation_link_test.succeeded():
            link_to_follow = confirmation_link_test.get_stdout().strip()

            # Now 'follow' that link:
            confirmed_page_test = run_http_test(link_to_follow,
                                                test_name="Confirming email address",
                                                test_short_name="confirm-email-address",
                                                browser=browser,
                                                render=False)



#        run_ssh_test()

#        run_page_test(first_alert_stage,
#                      lambda t,o: get_selected_option(t,o,next_form_tag),
#                      test_name='Finding the selected option in the form',
#                      test_short_name='finding-selected-option')

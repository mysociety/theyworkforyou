from browser import *
from testing import *
from common_tests import mps_test
import time

cj, browser = create_cookiejar_and_browser()

def get_first_person_link(current_test,http_test):
    first_person_link = http_test.soup.find('table','people').find('a')
    current_test.log("The first person link is : "+str(first_person_link))
    return first_person_link
    
index_first_person = run_page_test(mps_test,
              lambda t,o: get_first_person_link(t,o),
              test_name="Finding the first person in the search results page",
              test_short_name="search-results-first")

if not index_first_person.succeeded():
    raise Exception, "Couldn't get the first link from the MPs index page"
    
first_person_link = index_first_person.test_succeeded['href']
first_person_page_test = run_http_test(first_person_link,
                                       test_name="Fetching the first person's page",
                                       test_short_name="fetching-first-person-page",
                                       browser=browser)

def link_to_email_alert(t,o):
    return o.soup.find( lambda x: x.name == 'a' and tag_text_is(x,'Email me whenever',substring=True) )

alert_link_test = run_page_test(first_person_page_test,
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

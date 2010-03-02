from testing import *
from browser import *

cj, browser = create_cookiejar_and_browser()

login_page_test = run_http_test("/user/?pg=join&ret=%2F",
                                test_name="Fetching the login page from the main page",
                                test_short_name="login-page",
                                browser=browser)

run_page_test(login_page_test,
              lambda t,o: o.soup.find(lambda x: x.name == 'h2' and tag_text_is(x,"Join TheyWorkForYou",substring=True)),
              test_name="Looking for 'Join TheyWorkForYou heading'",
              test_short_name="join-heading")

def login_form_as_expected(current_test,old_test):
    current_test.log("Looking for the create login form")
    create_form = old_test.soup.find(lambda x: x.name == 'form' and ('action','/user/') in x.attrs)
    if not create_form:
        current_test.log('Failed to a form with action="/user/"')
        return False
    required_keys = [ "firstname", "lastname", "em", "password", "password2", "postcode", "url" ]
    for k in required_keys:
        current_test.log('Looking for an input element with name="'+k+'"')
        if not create_form.find( lambda x: x.name == 'input' and ('name',k) in x.attrs ):
            current_test.log("Failed to find the key: "+k)
            return False
    return True

run_page_test(login_page_test,
              login_form_as_expected,
              test_name="Checking the login form is as expected",
              test_short_name="login-page-form")

email_address = generate_email_address()

# Try submitting something where the passwords don't match, just to
# get the error:

post_parameters = { 'firstname' : 'Alice',
                    'lastname' : 'Tester',
                    'em' : email_address,
                    'password' : 'something',
                    'password2' : 'somethingdifferent',
                    'postcode' : '',
                    'url' : '',
                    'submitted' : 'true',
                    'ret' : '/',
                    'pg' : 'join',
                    'emailpublic' : 'false',
                    'optin' : 'false',
                    'mp_alert' : 'false' }

post_wrong_passwords = run_http_test("/user/",
                                     test_name='Creating with non-matching passwords',
                                     test_short_name='account-wrong-passwords',
                                     post_parameters=post_parameters,
                                     browser=browser,
                                     check_for_error_element=False,
                                     render=False)

def wrong_password_message(t,o):
    p = o.soup.find( lambda x: x.name == 'p' and ('class','error') in x.attrs )
    if not p:
        t.log('Failed to find a paragraph with class="error"')
        return False
    t.log("Got an error paragraph:\n"+p.prettify())
    if not tag_text_is(p,'Your passwords did not match',substring=True):
        t.log('Failed to find the "Your passwords did not match" error')
        return False
    return True

run_page_test(post_wrong_passwords,
              wrong_password_message,
              test_name="Finding the mismatched passwords error",
              test_short_name="account-passwords-message")

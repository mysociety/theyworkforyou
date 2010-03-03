from browser import *
from testing import *

cj, browser = create_cookiejar_and_browser()
postcode_test = run_http_test("/postcode/?pc=EH8+9NB",
                              test_name="Testing postcode lookup",
                              test_short_name="postcode",
                              browser=browser)

def cookie_jar_has(cj,k,v=None):
    for c in cj:
        if c.name == k:
            if v:
                return c.value
            else:
                return True
    return False

run_cookie_test(cj,
                lambda cj: cookie_jar_has(cj,'eppc','EH89NB'),
                test_name="Setting postcode cookie",
                test_short_name="postcode-cookie-set")

# FIXME: now check that the text on the main page has changes

main_page_test = run_http_test("/",
                               test_name="Fetching main page again",
                               test_short_name="basic-main-page-with-postcode",
                               browser=browser)

def local_mp_link(current_test,http_test,mp_name):
    links = http_test.soup.findAll( lambda x: (x.name == 'a' and ('href','/mp/') in x.attrs  ) )
    for e in links:
        current_test.log("Looking at link:\n"+e.prettify())
        if re.search(re.escape(mp_name),non_tag_data_in(e)):
            current_test.log("... found name: '"+mp_name+"'")
            return True
    return False

run_page_test(main_page_test,
              lambda t,o: local_mp_link(t,o,"Denis Murphy"),
              test_name="Checking local MP appears on main page",
              test_short_name="main-page-has-local-MP")

change_page_test = run_http_test("/user/changepc/",
                                 test_name="Getting change postcode page",
                                 test_short_name="getting-change-postcode-page",
                                 browser=browser)

def change_postcode_form(current_test, http_test, old_postcode):
    form = http_test.soup.find( lambda x: x.name == 'form' and ('action','/postcode/') in x.attrs )
    if not form:
        current_test.log("Failed to find the form with action '/postcode/'")
        return False
    return re.search('Your current postcode: '+old_postcode,non_tag_data_in(form))

run_page_test(change_page_test,
              lambda t,o: change_postcode_form(t,o,'EH89NB'),
              test_name="Checking change postcode prompt appears",
              test_short_name="change-postcode-prompt")

run_page_test(change_page_test,
              lambda t,o: o.soup.find( lambda x: x.name == 'a' and ('href','/user/changepc/?forget=t') in x.attrs ),
              test_name="Checking forget postcode prompt appears",
              test_short_name="change-forget-postcode-prompt")

change_postcode_test = run_http_test("/postcode/?pc=CB2%202RP&submit=GO",
                                     test_name="Changing postcode",
                                     test_short_name="change-postcode",
                                     browser=browser)

run_page_test(change_postcode_test,
              lambda t,o: o.soup.find(lambda x: x.name == 'h2' and x.string == 'Bridget Prentice'),
              test_name="Postcode changed successfully",
              test_short_name="postcode-changed")

forget_postcode_test = run_http_test("/user/changepc/?forget=t",
                                     test_name="Forgetting postcode",
                                     test_short_name="forgetting-postcode",
                                     browser=browser)

run_page_test(forget_postcode_test,
              lambda t,o: o.soup.find(lambda x: x.name == 'strong' and tag_text_is(x,"Enter your UK postcode:")),
              test_name="Prompting for postcode after forgetting",
              test_short_name="postcode-forgotten-prompt")

from testing import *

search_page_test = run_http_test('/search/',
                                 test_name="Fetching search page",
                                 test_short_name="search-page")

def search_form_as_expected(current_test,old_test):
    current_test.log("Looking for a form with id 'search-form'")
    form = old_test.soup.find( lambda x: x.name == 'form' and ('id','search-form') in x.attrs )
    if not form:
        current_test.log("Failed to find the form")
        return False
    current_test.log('Looking for <input type="text" id="s" name="s" value="">')
    search_field = form.find( lambda x: x.name == 'input' and ('name','s') in x.attrs )
    if not search_field:
        current_test.log("Failed to find an input field with name=\"s\"")
        return False
    if not ('id','s') in search_field.attrs:
        current_test.log("The search field didn't seem to have id=\"s\"")
        return False
    # FIXME: add more checks that the form elements are there...
    return True

search_text_test = run_http_test('/search?s=her+excellency+the+President+of+India',
                                 test_name="Searching on some speech text",
                                 test_short_name="search-text")

# Look for <dl id="searchresults">

def right_search_results(current_test,old_test):
    current_test.log("Looking for <dl id=\"searchresults\">")
    results = old_test.soup.find( lambda x: x.name == 'dl' and ('id','searchresults') in x.attrs )
    if not results:
        current_test.log("Failed to find the dl")
        return False
    current_test.log("Looking for the next tag")
    term = next_tag(results,sibling=False)
    if not term:
        current_test.log("Failed to find the next element")
        return False
    current_test.log("Checking the next tag is <dt>")
    if not term.name == 'dt':
        current_test.log("The next tag was not a <dt>, in fact was:\n"+term.prettify())
        return False
    current_test.log("Checking the content of the <dt>")
    if not tag_text_is(term,'Prime Minister: Engagements',substring=True):
        current_test.log("The <dt> should contain: 'Prime Minister: Engagements'")
        return False
    current_test.log("Finding the next tag sibling tag")
    definition = next_tag(term,sibling=True)
    if not definition:
        return False
    current_test.log("Checking it's a <dd>")
    if not definition.name == 'dd': 
        current_test.log("The next sibling tag wasn't <dd> but:\n"+definition.prettify())
        return False
    current_test.log("Checking the content of the tag")
    if not tag_text_is(definition,"In view of this week\'s state visit",substring=True):
        current_test.log("Failed to find the expected text in the definition")
        return False
    return True

run_page_test = run_page_test(search_text_test,
                              right_search_results,
                              test_name="Checking the results from a speech text search",
                              test_short_name="search-text-results")

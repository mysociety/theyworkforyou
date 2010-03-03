from testing import *

glossary_main_test = run_http_test("/glossary/",
                                   test_name="Fetching the glossary",
                                   test_short_name="glossary-main")


# Check that there's a letter index div:

run_page_test(glossary_main_test,
              lambda t,o: o.soup.find( lambda x: x.name == 'div' and ('class','letters') in x.attrs ),
              test_name="Checking the 'letters' div is present in the glossary",
              test_short_name="glossary-letters-index")

# Try adding an item:

# FIXME: need to be logged in for this - test logging in first...

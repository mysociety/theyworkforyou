from testing import *

# Find a representative based on postcode:

postcode_test = run_http_test("/postcode/?pc=EH8+9NB",
                              test_name="Testing postcode lookup",
                              test_short_name="postcode")

run_page_test(postcode_test,
              lambda t,o: o.soup.find( lambda x: x.name == 'h2' and x.string and x.string == "Denis Murphy" ),
              test_name="Looking for valid postcode result",
              test_short_name="postcode-result")

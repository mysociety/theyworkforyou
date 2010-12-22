from testing import *

mps_test = run_http_test("/mps/",
                         test_name="Fetching basic MPs page",
                         test_short_name="basic-MPs",
                         render=False) # render fails on a page this size...
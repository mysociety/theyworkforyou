from testing import *

run_ssh_test("cd "+configuration['DEPLOYED_PATH']+"/search/ && ./index.pl sincefile",
             test_name="Indexing data with Xapian",
             test_short_name="xapian-index",
             user="root")

from testing import *

run_ssh_test("cd /data/vhost/theyworkforyou.sandbox/theyworkforyou/scripts/ && ./index.pl sincefile",
             test_name="Indexing data with Xapian",
             test_short_name="xapian-index",
             user="root")

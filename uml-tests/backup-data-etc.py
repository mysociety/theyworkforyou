#!/usr/bin/python

from common import *
from subprocess import *
import os
import sys

if not os.geteuid() == 0:
    print "Must be root in order to preserve owners and permissions over rsync"
    sys.exit(1)

setup_configuration()

remove_host_keys()

# Just call this so that the host key is added and rsync won't ask us
# for confirmation...
uml_date()

backup_directory = "backup/"+time.strftime("%Y-%m-%dT%H:%M:%S",time.gmtime())

check_call(["mkdir","-p",backup_directory])

rsync_from_guest("/etc",backup_directory,user="root")
rsync_from_guest("/data",backup_directory,user="root",exclude_git=True)

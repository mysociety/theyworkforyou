#!/usr/bin/python2.5

import sys
import os
from subprocess import call, check_call

guest_ip = "192.168.1.198"

cwd = os.path.realpath(".")

# First check that the dependencies exist on this computer:
if 0 != call("which slirp",shell=True):
    print >> sys.stderr, "You must have 'slirp-fullbolt' on your PATH"
    print >> sys.stderr, "Perhaps you need to: sudo apt-get install slirp"
    sys.exit(1)

if 0 != call("which linux",shell=True):
    print >> sys.stderr, "You must have 'linux' on your PATH"
    print >> sys.stderr, "Perhaps you need to: sudo apt-get install user-mode-linux"
    sys.exit(1)

call(["linux",
      # "single",
      "con=xterm",
      "ubda=uml-rootfs-2006-02-02",
      "eth0=slirp,,"+cwd+"/slirp-wrapper",
      "mem=256M"])

# call(["linux",
#       # "single",
#       "con=xterm",
#       "ubda=uml-rootfs-2006-02-02",
#       "eth0=tuntap,,,"+guest_ip,
#       "mem=256M"])

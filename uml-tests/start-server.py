#!/usr/bin/python2.6

import sys
import os
from subprocess import call, check_call
from common import *
from optparse import OptionParser

check_dependencies()

setup_configuration()

parser = OptionParser(usage="Usage: %prog [OPTIONS]")
parser.add_option('-s', '--single', dest="single", action="store_true",
                  default=False, help="boot into single user mode")
parser.add_option('-x', '--xterm', dest="xterm", action="store_true",
                  default=False, help="create a terminal on an xterm as well")
options,args = parser.parse_args()

use_slirp = True

if use_slirp:
    fail = False
    # FIXME: configure these ports
    for port in [ 8042, 2242 ]:
        if port_bound(port):
            fail = True
            print "Port %d appears to be in use." % (port,)
    if fail:
        sys.exit(1)

cwd = os.path.realpath(".")

command = [ "linux" ]
if options.single:
    command.append("single")
command += [ "mem=256M",
             "ubda=uml-rootfs-test",
             "umid=TWFY",
             "con=null",
             "ssl=null",
             "con0=fd:0,fd:1" ]

if options.xterm:
    command.append("con1=xterm")

if use_slirp:
    # command.append("eth0=slirp,,/usr/bin/slirp-fullbolt")
    command.append("eth0=slirp,,./slirp-wrapper")
else:
    command.append("eth0=tuntap,,,"+configuration['GUEST_GATEWAY'])

call(command)

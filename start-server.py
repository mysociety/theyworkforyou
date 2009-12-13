#!/usr/bin/python2.5

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
options,args = parser.parse_args()

cwd = os.path.realpath(".")

command = [ "linux" ]
if options.single:
    command.append("single")
command += [ "ubda=uml-rootfs-test",
             "umid=TWFY",
             "con=null",
             "ssl=null",
             "con0=fd:0,fd:1",
             "eth0=tuntap,,,"+configuration['GUEST_GATEWAY'],
             "mem=256M"]

call(command)

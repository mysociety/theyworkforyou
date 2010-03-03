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
parser.add_option('-x', '--xterm', dest="xterm", action="store_true",
                  default=False, help="create a terminal on an xterm as well")
options,args = parser.parse_args()

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

command.append("eth0=tuntap,,,"+configuration['GUEST_GATEWAY'])

call(command)

#! $Id: contextexception.py,v 1.12 2004/12/23 12:27:09 goatchurch Exp $
# vim:sw=8:ts=8:et:nowrap

import os
import string
import re
import sys
import shutil

class ContextException(Exception):

    def __init__(self, description, stamp = None, fragment = None):
        self.description = description
        self.stamp = stamp
        self.fragment = fragment

    def __str__(self):
        ret = ""
        if self.fragment:
            ret = ret + "Fragment: " + repr(self.fragment) + "\n\n"
        ret = ret + self.description + "\n"
        if self.stamp:
            ret = ret + repr(self.stamp) + "\n"
        return ret


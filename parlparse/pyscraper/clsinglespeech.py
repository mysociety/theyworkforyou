#! /usr/bin/python2.4
# vim:sw=8:ts=8:et:nowrap

import sys
import re
import copy
import string

class qspeech:

	def __init__(self, lspeaker, ltext, stampurl):
		self.speaker = lspeaker
		self.sstampurl = copy.copy(stampurl)
		self.sdate = self.sstampurl.sdate

		# this gets mapped in after the copy above
		self.text = stampurl.UpdateStampUrl(ltext)



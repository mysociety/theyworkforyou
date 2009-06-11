#!/usr/bin/python
#
# db-changes-2.py: Fixes that were quicker in Python than at MySQL
# command line. Was run after changes in db-changes.txt
#
# Copyright (c) 2009 UK Citizens Online Democracy. All rights reserved.
# Email: matthew@mysociety.org; WWW: http://www.mysociety.org/

import os
os.environ['DJANGO_SETTINGS_MODULE'] = 'settings' 
from app.models import Contribution

# Update the contribution ID for person who we changed in db-changes.txt
for c in Contribution.objects.filter(section__date__gt='1974-02-08', section__date__lt='1983-05-14', commons_membership__id=12537):
    c.commons_membership_id=16624
    c.save()

# Probably more to follow...

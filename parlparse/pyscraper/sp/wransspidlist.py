#!/usr/bin/python2.4

import re

wrans_spid_list_filename = "../../../parldata/cmpages/sp/written-answer-question-spids"

def load_wrans_spid_list():
    h = {}
    fp = open(wrans_spid_list_filename,"r")
    for line in fp.readlines():
        m = re.match('^(\d{4}-\d{2}-\d{2}):(S\d+\w-\d+):(.*)$',line)
        date_string = m.group(1)
        spid_string = m.group(2)
        held_from_date_string = m.group(3)
        h.setdefault(spid_string,[])
        v = (date_string,spid_string,held_from_date_string)
        a = h[spid_string]
        if v not in h[spid_string]:            
            a.append(v)
    fp.close()
    return h

def save_wrans_spid_list(h):
    fp = open(wrans_spid_list_filename,"w")
    ks = h.keys()
    ks.sort()
    for k in ks:
        a = h[k]
        for t in a:
            fp.write(":".join(t)+"\n")
    fp.close()

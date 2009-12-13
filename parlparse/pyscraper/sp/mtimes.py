import os
from stat import ST_MTIME
import datetime

def get_file_mtime(filename):
    if os.path.exists(filename):
        s = os.stat(filename)
        i = s[ST_MTIME]
        return datetime.datetime.fromtimestamp(i)
    return None

def filenames_modified_after(filenames,time):
    modified_after = []
    for filename in filenames:
        filename_time = get_file_mtime(filename)
        if filename_time and filename_time >= time:
            modified_after.append(filename)
    return modified_after

def most_recent_mtime(filenames):
    last_mtime = None
    for filename in filenames:
        mtime = get_file_mtime(filename)
        if (not last_mtime) or mtime > last_mtime:
            last_mtime = mtime
    return last_mtime

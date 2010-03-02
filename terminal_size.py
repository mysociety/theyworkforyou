# From http://pdos.csail.mit.edu/~cblake/cls/cls.py
# ... via http://nixforums.org/about124558-Best.html
# Or the same code is at:
#    http://stackoverflow.com/questions/566746/how-to-get-console-window-width-in-python
#
# I've made a couple of small changes - if we can't find the size of
# the terminal, I'd like it to return -1,-1.  I've added the case
# where stdout isn't a tty as well, as suggested in one of the
# comments on the StackOverflow question..

import fcntl, termios, struct, os, signal, sys

def ioctl_GWINSZ(fd):
    try:
        cr = struct.unpack('hh', fcntl.ioctl(fd, termios.TIOCGWINSZ, '1234'))
    except:
        return None
    return cr

def terminal_size():
    if not sys.stdout.isatty():
        return (-1,-1)
    cr = ioctl_GWINSZ(0) or ioctl_GWINSZ(1) or ioctl_GWINSZ(2)
    if not cr:
        try:
            fd = os.open(os.ctermid(), os.O_RDONLY)
            cr = ioctl_GWINSZ(fd)
            os.close(fd)
        except:
            pass
    if not cr:
        try:
            cr = (os.environ['LINES'],os.environ['COLUMNS'])
        except:
            cr = (-1,-1)
    return int(cr[1]), int(cr[0])

terminal_width, terminal_height = terminal_size()
terminal_size_changed = False

def sigwinch_callback(signal_number,current_stack_frame):
    global terminal_size_changed
    terminal_size_changed = True

signal.signal(signal.SIGWINCH, sigwinch_callback)

def cached_terminal_size():
    global terminal_width, terminal_height, terminal_size_changed
    if terminal_size_changed:
        terminal_size_changed = False
        terminal_width, terminal_height = terminal_size()
    return ( terminal_width, terminal_height )

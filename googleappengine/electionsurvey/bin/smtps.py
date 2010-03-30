# $Id: smtps.py,v 1.10 2003/12/01 22:12:23 lsmithso Exp $
# A simple, extensible Python SMTP Server
#
# Author: L. Smithson (lsmithson@open-networks.co.uk)
#
# DISCLAIMER
# You are free to use this code in any way you like, subject to the
# Python disclaimers & copyrights. I make no representations about the
# suitability of this software for any purpose. It is provided "AS-IS"
# without warranty of any kind, either express or implied. So there.
#
#
#

"""
smtps.py - A Python SMTP Server. Listens on a socket for RFC821
messages. As each message is processed, methods on the class
SMTPServerInterface are called. Applications should sub-class this and
specialize the methods to suit. The default implementation does
nothing. 

The usage pattern is to subclass SMTPServerInterface, overriding
methods as appropriate to the application. An instance of this
subclass should be passed to the SMTPServer object, and then the
SMTPServer.serve method should be called. This blocks forever, serving
the given port. See the __main__ code below for an example.

The SMTPServerInterface subclass should keep state information such as
the FROM: and RCPT TO: addresses. The 'SMTPServerInterface.data' is
called when the complete RFC821 data messages has been received. The
application can then do what it likes with the message.

A couple of helper functions are defined that manipulate from & to
addresses. 
"""

import sys, socket, string

#
# Your applications should specialize this.
#

class SMTPServerInterface:
    """
    A base class for the imlementation of an application specific SMTP
    Server. Applications should subclass this and overide these
    methods, which by default do nothing.

    A method is defined for each RFC821 command. For each of these
    methods, 'args' is the complete command received from the
    client. The 'data' method is called after all of the client DATA
    is received.

    If a method returns 'None', then a '250 OK'message is
    automatically sent to the client. If a subclass returns a non-null
    string then it is returned instead.
    """

    def helo(self, args):
        return None
    
    def mailFrom(self, args):
        return None
        
    def rcptTo(self, args):
        return None

    def data(self, args):
        return None

    def quit(self, args):
        return None

    def reset(self, args):
        return None
    
#
# Some helper functions for manipulating from & to addresses etc.
#

def stripAddress(address):
    """
    Strip the leading & trailing <> from an address.  Handy for
    getting FROM: addresses.
    """
    start = string.index(address, '<') + 1
    end = string.index(address, '>')
    return address[start:end]

def splitTo(address):
    """
    Return 'address' as undressed (host, fulladdress) tuple.
    Handy for use with TO: addresses.
    """
    start = string.index(address, '<') + 1
    sep = string.index(address, '@') + 1
    end = string.index(address, '>')
    return (address[sep:end], address[start:end],)


#
# A specialization of SMTPServerInterface for debug, that just prints its args.
#
class SMTPServerInterfaceDebug(SMTPServerInterface):
    """
    A debug instance of a SMTPServerInterface that just prints its
    args and returns.
    """

    def helo(self, args):
        print 'Received "helo"', args
    
    def mailFrom(self, args):
        print 'Received "MAIL FROM:"', args
        
    def rcptTo(self, args):
        print 'Received "RCPT TO"', args

    def data(self, args):
        print 'Received "DATA"', args

    def quit(self, args):
        print 'Received "QUIT"', args

    def reset(self, args):
        print 'Received "RSET"', args
    

#
# This drives the state for a single RFC821 message.
#
class SMTPServerEngine:
    """
    Server engine that calls methods on the SMTPServerInterface object
    passed at construction time. It is constructed with a bound socket
    connection to a client. The 'chug' method drives the state,
    returning when the client RFC821 transaction is complete. 
    """

    ST_INIT = 0
    ST_HELO = 1
    ST_MAIL = 2
    ST_RCPT = 3
    ST_DATA = 4
    ST_QUIT = 5
    
    def __init__(self, socket, impl):
        self.impl = impl;
        self.socket = socket;
        self.state = SMTPServerEngine.ST_INIT
        
    def chug(self):
        """
        Chug the engine, till QUIT is received from the client. As
        each RFC821 message is received, calls are made on the
        SMTPServerInterface methods on the object passed at
        construction time.
        """
        
        self.socket.send("220 Welcome to Python smtps.py. $Id: smtps.py,v 1.10 2003/12/01 22:12:23 lsmithso Exp $\r\n")
        while 1:
            data = ''
            completeLine = 0
            # Make sure an entire line is received before handing off
            # to the state engine. Thanks to John Hall for pointing
            # this out.
            while not completeLine:
                lump = self.socket.recv(1024);
                if len(lump):
                    data += lump
                    if (len(data) >= 2) and data[-2:] == '\r\n':
                        completeLine = 1
                        if self.state != SMTPServerEngine.ST_DATA:
                            rsp, keep = self.doCommand(data)
                        else:
                            rsp = self.doData(data)
                            if rsp == None:
                                continue
                        self.socket.send(rsp + "\r\n")
                        if keep == 0:
                            self.socket.close()
                            return
                else:
                    # EOF
                    return
        return
            
    def doCommand(self, data):
        """Process a single SMTP Command"""
        cmd = data[0:4]
        cmd = string.upper(cmd)
        keep = 1
        rv = None
        if cmd == "HELO":
            self.state = SMTPServerEngine.ST_HELO
            rv = self.impl.helo(data)
        elif cmd == "RSET":
            rv = self.impl.reset(data)
            self.dataAccum = ""
            self.state = SMTPServerEngine.ST_INIT
        elif cmd == "NOOP":
            pass
        elif cmd == "QUIT":
            rv = self.impl.quit(data)
            keep = 0
        elif cmd == "MAIL":
            if self.state != SMTPServerEngine.ST_HELO:
                return ("503 Bad command sequence", 1)
            self.state = SMTPServerEngine.ST_MAIL
            rv = self.impl.mailFrom(data)
        elif cmd == "RCPT":
            if (self.state != SMTPServerEngine.ST_MAIL) and (self.state != SMTPServerEngine.ST_RCPT):
                return ("503 Bad command sequence", 1)
            self.state = SMTPServerEngine.ST_RCPT
            rv = self.impl.rcptTo(data)
        elif cmd == "DATA":
            if self.state != SMTPServerEngine.ST_RCPT:
                return ("503 Bad command sequence", 1)
            self.state = SMTPServerEngine.ST_DATA
            self.dataAccum = ""
            return ("354 OK, Enter data, terminated with a \\r\\n.\\r\\n", 1)
        else:
            return ("505 Eh? WTF was that?", 1)

        if rv:
            return (rv, keep)
        else:
            return("250 OK", keep)

    def doData(self, data):
        """
        Process SMTP Data. Accumulates client DATA until the
        terminator is found.
        """
        self.dataAccum = self.dataAccum + data
        if len(self.dataAccum) > 4 and self.dataAccum[-5:] == '\r\n.\r\n':
            self.dataAccum = self.dataAccum[:-5]
            rv = self.impl.data(self.dataAccum)
            self.state = SMTPServerEngine.ST_HELO
            if rv:
                return rv
            else:
                return "250 OK - Data and terminator. found"
        else:
            return None
    
class SMTPServer:
    """
    A single threaded SMTP Server connection manager. Listens for
    incoming SMTP connections on a given port. For each connection,
    the SMTPServerEngine is chugged, passing the given instance of
    SMTPServerInterface. 
    """
    
    def __init__(self, port):
        self._socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self._socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self._socket.bind(("", port))
        self._socket.listen(5)
      
    def serve(self, impl = None):
        while 1:
            nsd = self._socket.accept()
            if impl == None:
                impl = SMTPServerInterfaceDebug()
            engine = SMTPServerEngine(nsd[0], impl)
            engine.chug()
            

def Usage():
    print """Usage: python smtps.py [port].
    Where 'port' is SMTP port number, 25 by default. """
    sys.exit(1)
    

if __name__ == '__main__':
    if len(sys.argv) > 2:
        Usage()
        
    if len(sys.argv) == 2:
        if sys.argv[1] in ('-h', '-help', '--help', '?', '-?'):
            Usage()
        port = int(sys.argv[1])
    else:
        port = 25
    s = SMTPServer(port)
    s.serve()

from subprocess import call, check_call, Popen, PIPE
import re
import time
import sys
import os
import cgi
import errno
from BeautifulSoup import BeautifulSoup, NavigableString, Comment,Tag
import urllib2
from urllib import urlencode
from urlparse import urlparse
from terminal_size import cached_terminal_size

configuration = {}

def check_dependencies(check_group=True,user_and_group=None):
    # Of course, you won't get to this if the python dependencies
    # aren't there, but keep this list as accurate as possible
    # anyway...
    required_packages = [ "libqt4-dev",
                          "make",
                          "debootstrap",
                          "user-mode-linux",
                          "uml-utilities",
                          "git-core",
                          "qt4-qmake",
                          "openssh-client",
                          "curl",
                          "e2fsprogs",
                          "python2.5-minimal",
                          "python-beautifulsoup",
                          "wdg-html-validator",
                          "sgml-data",
                          "opensp" ]
    package_list = Popen(["dpkg","-l"],stdout=PIPE).communicate()[0]
    for p in required_packages:
        succeeded = True
        if not re.search('(?ms)(^|\n)(ii\s+'+p+'\s+[^\n]+)\n',package_list):
            print "The package '"+p+"' doesn't seem to be installed"
            succeeded = False
        if not succeeded:
            sys.exit(1)
    # Make sure that CutyCapt is built:
    check_call("make")
    # Make sure that the current user is in the uml-net group:
    if check_group and not re.search('\(uml-net\)',(Popen(["id"],stdout=PIPE).communicate()[0])):
        print "The current user is not in the group 'uml-net'"
        print "(See the output of 'id' or 'groups'.)"
        print "Add the user to the group with: adduser <user> <group>"
        sys.exit(1)
    # Check that the required ssh keypairs exist:
    for user in [ "alice", "root" ]:
        private = "id_dsa.%s"%(user,)
        public = "id_dsa.%s.pub"%(user,)
        if not (os.path.exists(private) and os.path.exists(public)):
            print "Both '"+private+"' and '"+public+"' must exist; generating them:"
            command = [ "ssh-keygen", "-t", "dsa", "-f", private, "-N", "" ]
            check_call(command)
            if user_and_group:
                check_call(["chown",user_and_group,private])
                check_call(["chown",user_and_group,public])

def ensure_slash(path):
    return re.sub('([^/])$','\\1/',path)

def setup_configuration():

    global configuration

    try:
        fp = open("conf")
    except IOError, e:
        if e.errno == errno.ENOENT:
            print >> sys.stderr, "You must create the file 'conf' describing your network setup."
            print >> sys.stderr, "(There is an example in 'conf.example' with some comments.)"
            sys.exit(2)
        raise
    for line in fp:
        # Strip out comments:
        line = re.sub('#.*','',line)
        # Check for empty lines:
        if re.search('^\s*$',line):
            continue
        m = re.search("^\s*([^=\s]+)=(\S.*?)\s*$",line)
        if m:
            configuration[m.group(1)]=m.group(2)
        else:
            raise Exception, "There was a malformed line in 'conf': "+line

    required_configuration_keys = [ 'UML_SERVER_IP',
                                    'GUEST_IP',
                                    'GUEST_GATEWAY',
                                    'GUEST_NETMASK',
                                    'GUEST_NAMESERVER',
                                    'UML_SERVER_HOSTNAME']

    for k in required_configuration_keys:
        if k not in configuration:
            raise Exception, "You must define %s in 'conf'" % (k,)

    configuration['DEPLOYED_PATH'] = "/data/vhost/"+configuration['UML_SERVER_HOSTNAME']+"/theyworkforyou"

def add_passwords_to_configuration():
    configuration['MYSQL_TWFY_PASSWORD'] = pgpw('twfy')
    configuration['MYSQL_ROOT_PASSWORD'] = pgpw('twfy')

# From http://stackoverflow.com/questions/35817/whats-the-best-way-to-escape-os-system-calls-in-python
def shellquote(s):
    return "'" + s.replace("'", "'\\''") + "'"

class SSHResult:
    def __init__(self,
                 return_value,
                 stdout_data,
                 stderr_data,
                 stdout_filename=None,
                 stderr_filename=None):
        self.return_value = return_value
        self.stdout_data = stdout_data
        self.stderr_data = stderr_data
        self.stdout_filename = stdout_filename
        self.stderr_filename = stderr_filename

def trim_string(s,max_length=-1):
    elision_marker = " [...]"
    if max_length >= 0 and len(s) > max_length:
        return s[0:(max_length-len(elision_marker))]+elision_marker
    else:
        return s

created_ssh_control_files = []

def user_to_control_file(user):
    return "/tmp/uml-ssh-"+user+"-%h-%p-%r.control"

def ssh_start_control_master(user="alice"):
    control_file = user_to_control_file(user)
    if ssh_check_control_master(user):
        raise Exception, "There is already a running SSH control master at %s - another test session is running or exited uncleanly"
    full_command = [ "ssh",
                     "-i", "id_dsa."+user,
                     "-o", "StrictHostKeyChecking=no",
                     "-o", "ControlMaster=yes",
                     "-o", "ControlPath="+control_file,
                     "-N",
                     "-f",
                     user+"@"+configuration['UML_SERVER_IP'] ]
    if 0 == call(full_command):
        created_ssh_control_files.append((user,control_file))
    else:
        raise Exception, "Creating the SSH ControlMaster with filename %s failed - perhaps another test session is running?" % (control_file,)

def ssh_check_control_master(user="alice"):
    full_command = [ "ssh",
                     "-i", "id_dsa."+user,
                     "-o", "StrictHostKeyChecking=no",
                     "-o", "ControlPath="+user_to_control_file(user),
                     "-O", "check",
                     user+"@"+configuration['UML_SERVER_IP'] ]
    return 0 == call(full_command)

def ssh_stop_all_control_masters():
    for user,file in created_ssh_control_files:
        ssh_stop_control_master(user)

def ssh_stop_control_master(user):
    full_command = [ "ssh",
                     "-o", "StrictHostKeyChecking=no",
                     "-o", "ControlPath="+user_to_control_file(user),
                     "-O", "exit",
                     user+"@"+configuration['UML_SERVER_IP'] ]
    if 0 != call(full_command):
        print "Warning: stopping SSH ControlMaster for user %s failed" % (user,)

def ssh(command,user="alice",capture=False,stdout_filename=None,stderr_filename=None,verbose=True):
    full_command = [ "ssh",
                     "-o", "StrictHostKeyChecking=no",
                     "-o", "ControlPath="+user_to_control_file(user),
                     user+"@"+configuration['UML_SERVER_IP'],
                     command ]
    if verbose:
        tw, th = cached_terminal_size()
        print trim_string("Running: ssh [...] "+command+"\r",tw)
    if capture:
        oo = PIPE
        oe = PIPE
        if stdout_filename:
            oo = open(stdout_filename,"w")
        if stderr_filename:
            oe = open(stderr_filename,"w")
        p = Popen(full_command, stdout=oo, stderr=oe)
        # captured_* will be None if a *_filename was specified
        captured_stdout, captured_stderr = p.communicate(None)
        if stdout_filename:
            oo.close()
        if stderr_filename:
            oe.close()
        return SSHResult(p.returncode, captured_stdout, captured_stderr)
    else:
        return call(full_command)

def path_exists_in_uml(filename):
    return 0 == ssh("test -e "+shellquote(filename),user="root")

def pgpw(user):
    if not web_server_working():
        raise Exception, "Can't call pgpw() until the UML machine is up"
    secret_file = "/etc/mysociety/postgres_secret"
    if not path_exists_in_uml(secret_file):
        raise Exception, "Can't call pgpw before #{secret_file} exists"
    r = ssh("/data/mysociety/bin/pgpw "+shellquote(user),capture=True)
    return r.stdout_data.strip()

def remove_host_keys():
    check_call(["ssh-keygen","-R",configuration['UML_SERVER_IP']])

def file_to_string(filename):
    fp = open(filename)
    data = fp.read()
    fp.close()
    return data

def scp(source,destination,user="alice",verbose=True):
    full_command = [ "scp",
                     "-o", "StrictHostKeyChecking=no",
                     "-o", "ControlPath="+user_to_control_file(user),
                     source,
                     user+"@"+configuration['UML_SERVER_IP']+":"+destination ]
    if verbose:
        tw, th = cached_terminal_size()
        print trim_string("Running: scp [...] "+source+" -> [UML]:"+destination,tw)
    return call(full_command)

def rsync_from_guest(source,destination,user="alice",exclude_git=False,verbose=True):
    parameters = "-rl"
    if verbose:
        parameters += "v"
    full_command = [ "rsync",
                     parameters ]
    if exclude_git:
        full_command.append("--exclude=.git")
    full_command += [ "-e",
                      "ssh -l "+user+" -i id_dsa."+user,
                      user+"@"+configuration['UML_SERVER_IP']+":"+source,
                      destination ]
    print "##".join(full_command)
    return call(full_command)

# FIXME: untested
def rsync_to_guest(source,destination,user="alice",exclude_git=False,delete=False,verbose=True):
    parameters = "-rl"
    if verbose:
        parameters += "v"
    full_command = [ "rsync",
                     parameters ]
    if exclude_git:
        full_command.append("--exclude=.git")
    if delete:
        full_command.append("--delete")
    full_command += [ "-e",
                      "ssh -l "+user+" -i id_dsa."+user,
                      source,
                      user+"@"+configuration['UML_SERVER_IP']+":"+destination ]
    if verbose:
        print "Running rsync [...] "+source+" [UML]:"+destination
    return call(full_command)

def thumbnail_image_filename(original_image_filename):
    result = re.sub('^(.*)\.([^\.]+)$','\\1-thumbnail.\\2',original_image_filename)
    if result == original_image_filename:
        return None
    else:
        return result

def generate_thumbnail_version(original_image_filename):
    thumbnail_filename = thumbnail_image_filename(original_image_filename)
    if not thumbnail_filename:
        raise Exception, "Failed to generate a name for the thumbnail from '%s'" % (original_image_filename,)
    check_call(["convert",
                "-crop",
                "800x800+0+0",
                "-resize",
                "200x200",
                original_image_filename,
                thumbnail_filename])
    return thumbnail_filename

def render_page(page_path,output_image_filename):
    return 0 == call(["./cutycapt/CutyCapt/CutyCapt",
                      "--url=http://"+configuration['UML_SERVER_HOSTNAME']+page_path,
                      "--javascript=off",
                      "--plugins=off",
                      "--out="+output_image_filename])

def save_page(url,output_html_filename,url_opener=None,post_parameters=None):
    url_tuple = urlparse(url)
    if url_tuple[0]:
        # Then it's a full URL:
        full_url = url
    else:
        full_url = "http://"+configuration['UML_SERVER_HOSTNAME']+url
    if url_opener:
        try:
            if post_parameters:
                data = urlencode(post_parameters)
                r = url_opener.open(full_url,data)
            else:
                r = url_opener.open(full_url)
            html = r.read()
            r.close()
            fp = open(output_html_filename, 'w')
            fp.write(html)
            fp.close()
        except urllib2.URLError, e:
            print >> sys.stderr, "Got an error when fetcing the page with urllib2: "+str(e)
            return False
        return True
    else:
        if post_parameters:
            raise Exception, "POST requests with curl not yet supported..."
        else:
            return 0 == call(['curl','--location','-s','-o',output_html_filename,full_url])


def uml_date():
    r = ssh("date +'%Y-%m-%dT%H:%M:%S%z'",capture=True,verbose=False)
    return r.stdout_data.strip()

def uml_realpath(path):
    r = ssh("readlink -f "+shellquote(path),capture=True,user="root")
    return r.stdout_data.strip()

def user_exists(username):
    return 0 == ssh("id "+username,user="root")

def untemplate(template_file,output_filename):
    fp = open(template_file)
    template_text = fp.read()
    fp.close()
    for k in configuration.keys():
        r = re.compile('%'+re.escape(k)+'%')
        template_text = r.sub(configuration[k],template_text)
    fp = open(output_filename,"w")
    fp.write(template_text)
    fp.close()

def untemplate_and_scp(source_directory,user="root"):
    t = '\.template$'
    for root, subfolders, basenames in os.walk(source_directory):
        # Ignore any generated files:
        generated = [ re.sub(t,'',x) for x in basenames if re.search(t,x) ]
        for g in generated:
            if g in basenames:
                del basenames[basenames.index(g)]
        for file in basenames:
            filename_to_scp = file
            relative_filename_to_scp = os.path.join(root,filename_to_scp)
            if re.search(t,file):
                filename_to_scp = re.sub(t,'',file)
                path_template_a = subfolders + [ file ]
                path_generated_a = subfolders + [ filename_to_scp ]
                relative_path_template = os.path.join(root+"/",file)
                relative_filename_to_scp = os.path.join(root+"/",filename_to_scp)
                untemplate(relative_path_template,relative_filename_to_scp)
            # Make sure the directory exists:
            destination = re.sub('^'+re.escape(source_directory),'',relative_filename_to_scp)
            destination_directory = os.path.dirname(destination)
            ssh("mkdir -p "+shellquote(destination_directory),user=user)
            scp(relative_filename_to_scp,destination_directory,user=user)

def untemplate_and_rsync(source_directory,user="root"):
    t = '\.template$'
    for root, subfolders, basenames in os.walk(source_directory):
        # Ignore any generated files:
        generated = [ re.sub(t,'',x) for x in basenames if re.search(t,x) ]
        for g in generated:
            if g in basenames:
                del basenames[basenames.index(g)]
        for file in basenames:
            filename_to_scp = file
            relative_filename_to_scp = os.path.join(root,filename_to_scp)
            if re.search(t,file):
                filename_to_scp = re.sub(t,'',file)
                path_template_a = subfolders + [ file ]
                path_generated_a = subfolders + [ filename_to_scp ]
                relative_path_template = os.path.join(root+"/",file)
                relative_filename_to_scp = os.path.join(root+"/",filename_to_scp)
                untemplate(relative_path_template,relative_filename_to_scp)
            # Make sure the directory exists:
            destination = re.sub('^'+re.escape(source_directory),'',relative_filename_to_scp)
            destination_directory = os.path.dirname(destination)
    return rsync_to_guest(ensure_slash(source_directory),
                          '/',
                          user="root",
                          exclude_git=False,
                          delete=False)

def web_server_working():
    return 0 == call(["curl",
                      "-s",
                      "-f",
                      "http://"+configuration['UML_SERVER_IP'],
                      "-o",
                      "/dev/null"])

def wait_for_web_server(popen_object):
    interval_seconds = 1
    while True:
        still_alive = (None == popen_object.poll())
        up = web_server_working()
        if still_alive:
            if up:
                return True
            else:
                time.sleep(interval_seconds)
                continue
        else:
            popen_object.wait()
            print "Process "+str(popen_object.pid)+" died, returncode: "+str(popen_object.returncode)
            return False

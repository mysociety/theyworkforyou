from subprocess import call, check_call, Popen, PIPE
import re
import time
import sys
import os
import cgi

configuration = {}

def check_dependencies():
    required_packages = [ "libqt4-dev",
                          "make",
                          "debootstrap",
                          "user-mode-linux",
                          "uml-utilities",
                          "git-core",
                          "qt4-qmake",
                          "openssh-client",
                          "curl",
                          "e2fsprogs" ]
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
    if not re.search('\(uml-net\)',(Popen(["id"],stdout=PIPE).communicate()[0])):
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
            check_call(["ssh-keygen","-t","dsa","-N","","-f",private])

def setup_configuration():
    fp = open("conf")
    for line in fp:
        if re.search('^\s*(#|$)',line):
            # A comment or an empty line..
            continue
        m = re.search("^\s*([^=\s]+)=(\S.*?)\s$",line)
        if m:
            configuration[m.group(1)]=m.group(2)
        else:
            raise Exception, "There was a malformed line in 'conf': "+line

    required_configuration_keys = [ 'UML_SERVER_IP',
                                    'GUEST_IP',
                                    'GUEST_GATEWAY',
                                    'GUEST_NETMASK',
                                    'GUEST_NAMESERVER' ]

    for k in required_configuration_keys:
        if k not in configuration:
            raise Exception, "You must define %s in 'conf'" % (k,)

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

def trim_string(s):
    max_length = 160
    elision_marker = " [...]"
    if len(s) > max_length:
        return s[0:(max_length-len(elision_marker))]+elision_marker
    else:
        return s

def ssh(command,user="alice",capture=False,stdout_filename=None,stderr_filename=None,verbose=True):
    full_command = [ "ssh",
                     "-i", "id_dsa."+user,
                     "-o", "StrictHostKeyChecking=no",
                     user+"@"+configuration['UML_SERVER_IP'],
                     command ]
    if verbose:
        print trim_string("Going to run: "+"#".join(full_command)+"\r")
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
                     "-i",
                     "id_dsa."+user,
                     source,
                     user+"@"+configuration['UML_SERVER_IP']+":"+destination ]
    if verbose:
        print trim_string("Going to run: "+"#".join(full_command)+"\r")
    return call(full_command)

def rsync_from_guest(source,destination,user="alice",exclude_git=False):
    full_command = [ "rsync",
                     "-rlv" ]
    if exclude_git:
        full_command.append("--exclude=.git")
    full_command += [ "-e",
                      "ssh -l "+user+" -i id_dsa."+user,
                      user+"@"+configuration['UML_SERVER_IP']+":"+source,
                      destination ]
    print "##".join(full_command)
    return call(full_command)

# FIXME: untested
def rsync_to_guest(source,destination,user="alice",exclude_git=False,delete=False):
    full_command = [ "rsync",
                     "-rlv" ]
    if exclude_git:
        full_command.append("--exclude=.git")
    if delete:
        full_command.append("--delete")
    full_command += [ "-e",
                      "ssh -l "+user+" -i id_dsa."+user,
                      source,
                      user+"@"+configuration['UML_SERVER_IP']+":"+destination ]
    print "##".join(full_command)
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
    check_call(["./cutycapt/CutyCapt/CutyCapt",
                "--url=http://"+configuration['UML_SERVER_IP']+":81"+page_path,
                "--javascript=off",
                "--plugins=off",
                "--out="+output_image_filename])

def save_page(page_path,output_html_filename,url_opener=None):
    url = "http://"+configuration['UML_SERVER_IP']+":81"+page_path
    if url_opener:
        r = opener.open(url)
        html = r.read()
        r.close()
        fp = open(output_filename, 'w')
        fp.write(html)
        fp.close()
    else:
        check_call(['curl','-o',output_html_filename,url])

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
    return rsync_to_guest(re.sub('([^/])$','\\1/',source_directory),
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

TEST_UNKNOWN = -1
TEST_SSH     =  0
TEST_HTTP    =  1

test_type_to_str = { -1 : "TEST_UNKNOWN",
                      0 : "TEST_SSH",
                      1 : "TEST_HTTP" }

all_tests = []

class Test:
    last_test_number = -1
    def __init__(self,output_directory,test_name="Unknown test",test_short_name="unknown"):
        self.output_directory = output_directory
        Test.last_test_number += 1
        self.test_number = Test.last_test_number
        self.test_short_name = test_short_name
        self.test_name = test_name
        self.failure_message = self.test_name + " failed"
        self.test_type = TEST_UNKNOWN
        self.exit_on_fail = True
        self.ignore_failure = False
        self.set_test_output_directory()
    def get_id_and_short_name(self):
        return "%04d-%s" % (self.test_number,self.test_short_name)
    def previous_output_directory(self):
        parent, directory_name = os.path.split(self.output_directory)
        directories = os.listdir(parent)
        iso_8601_re = re.compile('^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d$')
        directories = [ x for x in directories if iso_8601_re.search(x) ]
        if len(directories) < 1:
            raise Exception, "No matching directories found in: "+parent
        i = directories.index(directory_name)
        if i < 1:
            return None
        return os.path.join(parent,directories[i-1])
    def set_test_output_directory(self):
        o = os.path.join(self.output_directory,self.get_id_and_short_name())
        self.test_output_directory = o
        call(["mkdir",self.test_output_directory])
    def __str__(self):
        result = "Test ("+test_type_to_str[self.test_type]+")\n"
        result += "  test_number: "+str(self.test_number)+"\n"
        result += "  test_short_name: "+self.test_short_name+"\n"
        result += "  test_name: "+self.test_name
        if self.test_output_directory:
            result += "\n  test_output_directory: "+self.test_output_directory
        return result
    def run(self):
        if not self.test_output_directory:
            raise Exception, "No test output directory set for: "+str(self)
        fp = open(os.path.join(self.test_output_directory,"info"),"w")
        fp.write(str(self))
        fp.close()
        pass

class SSHTest(Test):
    def __init__(self,output_directory,ssh_command,user="alice",test_name="Unknown test",test_short_name="unknown",browser=None):
        Test.__init__(self,output_directory,test_name=test_name,test_short_name=test_short_name)
        self.test_type = TEST_SSH
        self.ssh_command = ssh_command
        self.user = user
        self.stdout_filename = os.path.join(self.test_output_directory,"stdout")
        self.stderr_filename = os.path.join(self.test_output_directory,"stderr")
        self.browser = browser
    def run(self):
        Test.run(self)
        result = ssh(self.ssh_command,
                     self.user,
                     capture=True,
                     stdout_filename=self.stdout_filename,
                     stderr_filename=self.stderr_filename)
        fp = open(os.path.join(self.test_output_directory,"result"),"w")
        fp.write(str(result.return_value))
        fp.close()
    def __str__(self):
        s = Test.__str__(self)
        s += "\n  ssh_command: "+str(self.ssh_command)
        return s

def run_ssh_test(output_directory,ssh_command,user="alice",test_name="Unknown test",test_short_name="unknown",browser=None):
    s = SSHTest(output_directory,ssh_command,user=user,test_name=test_name,test_short_name=test_short_name,browser=browser)
    all_tests.append(s)
    s.run()

class HTTPTest(Test):
    def __init__(self,output_directory,page,test_name="Unknown test",test_short_name="unknown"):
        Test.__init__(self,output_directory,test_name=test_name,test_short_name=test_short_name)
        self.test_type = TEST_HTTP
        self.page = page+"?test-id="+self.get_id_and_short_name()
        self.full_image_filename = None
        self.thumbnail_image_filename = None
    def run(self):
        Test.run(self)
        save_page(self.page,os.path.join(self.test_output_directory,"page.html"))
        self.full_image_filename = os.path.join(self.test_output_directory,"page.png")
        render_page(self.page,self.full_image_filename)
        if os.path.exists(self.full_image_filename):
            self.thumbnail_image_filename = generate_thumbnail_version(self.full_image_filename)
    def __str__(self):
        s = Test.__str__(self)
        s += "\n  page: "+str(self.page)
        return s

def run_http_test(output_directory,page,test_name="Unknown test",test_short_name="unknown"):
    print "Got test_name: "+test_name
    date_start = uml_date()
    h = HTTPTest(output_directory,page,test_name=test_name,test_short_name=test_short_name)
    all_tests.append(h)
    h.run()
    date_end = uml_date()
    coverage_data = coverage_data_between(date_start,date_end)
    coverage_output_filename = os.path.join(h.test_output_directory,"coverage")
    fp = open(coverage_output_filename,"w")
    fp.write(coverage_data)
    fp.close()

def coverage_data_between(date_start,date_end):
    coverage_files = coverage_filenames_between(date_start,date_end)
    ssh_command = "cd /home/alice/twfy-coverage/ && "
    ssh_command += "cat '"+("' '".join(coverage_files))+"' | "
    ssh_command += "egrep -v /home/alice/twfy-coverage | "
    ssh_command += "sed 's,/home/alice/mysociety/,,'"
    return ssh(ssh_command,capture=True).stdout_data

def coverage_filenames_between(date_start,date_end):
    coverage_files = sorted(ssh("ls -1 /home/alice/twfy-coverage/",capture=True).stdout_data.strip().split("\n"))
    return [ x for x in coverage_files if x >= date_start and x <= date_end ]

def uses_to_colour(uses):
    if uses == -2:
        return "#555555"
    elif uses == -1:
        return "#f37c7c"
    elif uses > 0:
        return "#8df37c"
    elif uses == -9:
        return "#f4ff78"
    else:
        raise Exception, "Unknown number of uses: "+str(uses)

def generate_coverage(coverage_data_file,output_directory,original_source_directory):
    files_to_coverage = {}
    fp = open(coverage_data_file)
    current_filename = None
    for line in fp:
        line = line.rstrip()
        line_data_match = re.search('^  (\d+): ([-\d]+)$',line)
        if line_data_match:
            line_number = int(line_data_match.group(1),10)
            uses = int(line_data_match.group(2),10)
            lines_to_uses = files_to_coverage[current_filename]
            if lines_to_uses.has_key(line_number):
                old_uses = lines_to_uses[line_number]
                if old_uses > 0 and uses > 0:
                    lines_to_uses[line_number] += uses
                elif (old_uses == -1 and uses > 0):
                    lines_to_uses[line_number] = uses
                elif (old_uses > 0 and uses == -1):
                    pass
                elif old_uses == uses:
                    # That's as expected...
                    pass
                else:
                    raise Exception, "Conflicting information for line %d in %s, old value was %d while new value is %d" % (line_number,current_filename,old_uses,uses)
            else:
                lines_to_uses[line_number] = uses
        elif re.search('^\s*$',line): # Ignore any accidental empty lines
            pass
        else:
            current_filename = re.search('^\s*(.*)',line).group(1)
            files_to_coverage.setdefault(current_filename,{})
    filenames_with_coverage_data = files_to_coverage.keys()
    filename_to_percent_coverage = {}
    for filename in filenames_with_coverage_data:
        unused_lines = 0
        used_lines = 0
        print "======"
        print "Filename is: "+filename
        print str(files_to_coverage[filename])
        lines_to_uses = files_to_coverage[filename]
        # So filename is: twfy/www/includes/easyparliament/init.php
        original_filename = os.path.join(original_source_directory,filename)
        output_filename = os.path.join(output_directory,filename+".html")
        output_filename_dirname = os.path.split(output_filename)[0]
        if not os.path.exists(output_filename_dirname):
            os.makedirs(output_filename_dirname)
        ofp = open(output_filename,"w")
        ofp.write('''<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
<title>Coverage data for %s</title>
</head>
<body style="background-color: #ffffff">
<table border=0>
<tr><td style="background-color: %s; color: #000000;">A line which was executed</td></tr>
<tr><td style="background-color: %s; color: #000000;">A line which was not executed</td></tr>
<tr><td style="background-color: %s; color: #000000;">Dead code (could never be executed)</td></tr>
<tr><td style="background-color: %s; color: #000000;">FIXME: Lines with no executable code (e.g. comments)</td></tr>
</table>
<hr>
<pre>
''' % (cgi.escape(filename),uses_to_colour(1),uses_to_colour(-1),uses_to_colour(-2),uses_to_colour(-9)))
        line_number = 1
        ifp = open(original_filename)
        for line in ifp:
            line = line.rstrip()
            uses = -9
            colour = "#cccccc"
            if line_number in lines_to_uses:
                uses = lines_to_uses[line_number]
            if uses == -1:
                unused_lines += 1
            elif uses > 0:
                used_lines += 1
            colour = uses_to_colour(uses)
            line_number_string = "%4d (%2d)" % (line_number,uses)
            ofp.write("<span style=\"background-color: %s; color: #000000; width: 100%%\"><strong>%s</strong> | %s</span>\n" % (colour,line_number_string,cgi.escape(line)))
            line_number += 1
        ofp.write("</pre>\n</body>\n</html>\n")
        ofp.close()
        percent_coverage = (100 * used_lines) / float(used_lines + unused_lines)
        filename_to_percent_coverage[filename] = percent_coverage
    fp.close()
    # Now output an index page:
    index_filename = os.path.join(output_directory,"coverage.html")
    fp = open(index_filename,"w")
    fp.write('''<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
<title>Coverage Data Index</title>
</head>
<body style="background-color: #ffffff">
<table border=0>
''')
    filenames = filename_to_percent_coverage.keys()
    filenames.sort()
    for filename in filenames:
        url = filename + ".html"
        coverage = filename_to_percent_coverage[filename]
        fp.write("<tr><td><tt><a href=\"%s\">%s</a></tt></td><td>%3d%%</td></tr>\n" % (url,filename,int(coverage)))
    fp.write('''</table>
</body>
</html>''')
    fp.close()

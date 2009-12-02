from subprocess import call, check_call, Popen, PIPE
import re
import time
import os

configuration = {}
fp = open("conf")
for line in fp:
    m = re.search("^\s*([^=\s]+)\s*=\s*(\S+)",line)
    if m:
        configuration[m.group(1)]=m.group(2)

required_configuration_keys = [ 'UML_SERVER_IP',
                                'GUEST_IP',
                                'GUEST_GATEWAY',
                                'GUEST_NETMASK',
                                'GUEST_NAMESERVER',
                                'MYSQL_ROOT_PASSWORD',
                                'MYSQL_TWFY_PASSWORD' ]

for k in required_configuration_keys:
    if k not in configuration:
        raise Exception, "You must define %s in 'conf'" % (k,)

def file_to_string(filename):
    fp = open(filename)
    data = fp.read()
    fp.close()
    return data

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
                     "-av" ]
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
                     "-av" ]
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

# From http://stackoverflow.com/questions/35817/whats-the-best-way-to-escape-os-system-calls-in-python
def shellquote(s):
    return "'" + s.replace("'", "'\\''") + "'"

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

def path_exists_in_uml(filename):
    return 0 == ssh("test -e "+shellquote(filename),user="root")

def uml_date():
    r = ssh("date +'%Y-%m-%dT%H:%M:%S%z'",capture=True,verbose=False)
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

def web_server_working():
    return 0 == call(["curl",
                      "-s",
                      "-f",
                      "http://"+configuration['UML_SERVER_IP'],
                      "-o",
                      "/dev/null"])

def process_alive(pid):
    return 0 == call(["kill","-0",str(pid)])

def wait_for_web_server_or_exit(pid):
    interval_seconds = 1
    while True:
        still_alive = process_alive(pid)
        up = web_server_working()
        if not process_alive:
            print "Process "+str(pid)+" died"
            return False
        else:
            if up:
                return True
            else:
                time.sleep(interval_seconds)
                continue

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
    h = HTTPTest(output_directory,page,test_name=test_name,test_short_name=test_short_name)
    all_tests.append(h)
    h.run()

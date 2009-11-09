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
                                'TAP_DEVICE_IP',
                                'MYSQL_ROOT_PASSWORD',
                                'MYSQL_TWFY_PASSWORD' ]

for k in required_configuration_keys:
    if k not in configuration:
        raise Exception, "You must define %s in 'conf'" % (k,)

class SSHResult:
    def __init__(self,return_value,stdout_data,stderr_data):
        self.return_value = return_value
        self.stdout_data = stdout_data
        self.stderr_data = stderr_data

def trim_string(s):
    max_length = 160
    elision_marker = " [...]"
    if len(s) > max_length:
        return s[0:(max_length-len(elision_marker))]+elision_marker
    else:
        return s

def ssh(command,user="alice",capture=False):
    full_command = [ "ssh",
                     "-i", "id_dsa."+user,
                     "-o", "StrictHostKeyChecking=no",
                     user+"@"+configuration['UML_SERVER_IP'],
                     command ]
    print trim_string("Going to run: "+"#".join(full_command)+"\r")
    if capture:
        p = Popen(full_command, stdout=PIPE, stderr=PIPE)
        captured_stdout, captured_stderr = p.communicate(None)
        return SSHResult(p.returncode, captured_stdout, captured_stderr)
    else:
        return call(full_command)

def scp(source,destination,user="alice"):
    full_command = [ "scp",
                     "-i",
                     "id_dsa."+user,
                     source,
                     user+"@"+configuration['UML_SERVER_IP']+":"+destination ]
    return call(full_command)

# From http://stackoverflow.com/questions/35817/whats-the-best-way-to-escape-os-system-calls-in-python
def shellquote(s):
    return "'" + s.replace("'", "'\\''") + "'"

def render_page(page_path,output_image_filename):
    check_call(["./cutycapt/CutyCapt/CutyCapt",
                "--url=http://"+configuration['UML_SERVER_IP']+":81"+page_path,
                "--out="+output_image_filename])

def save_page(page_path,output_html_filename):
    check_call(['curl',
                '-o',output_html_filename,
                "http://"+configuration['UML_SERVER_IP']+":81"+page_path])

def path_exists_in_uml(filename):
    return 0 == ssh("test -e "+shellquote(filename),user="root")

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

class Test:
    last_test_number = -1
    def __init__(self,output_directory):
        self.output_directory = output_directory
        Test.last_test_number += 1
        self.test_number = Test.last_test_number
        self.test_short_name = "unknown"
        self.test_name = "[Default Test Name]"
        self.failure_message = "[Failed]"
        self.test_type = TEST_UNKNOWN
        self.exit_on_fail = True
        self.ignore_failure = False
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
        o = self.output_directory
        o += "/"+self.get_id_and_short_name()
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
        fp = open(self.test_output_directory+"/info","w")
        fp.write(str(self))
        fp.close()
        pass

class SSHTest(Test):
    def __init__(self,output_directory,ssh_command):
        Test.__init__(self,output_directory)
        self.test_type = TEST_SSH
        self.ssh_command = ssh_command
    def run(self):
        Test.run(self)
        pass

class HTTPTest(Test):
    def __init__(self,output_directory,page):
        Test.__init__(self,output_directory)
        self.test_type = TEST_HTTP
        self.page = page+"?test-id="+self.get_id_and_short_name()
    def run(self):
        Test.run(self)
        save_page(self.page,self.test_output_directory+"/page.html")
        render_page(self.page,self.test_output_directory+"/page.png")

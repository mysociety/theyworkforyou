from subprocess import call, check_call, Popen, PIPE
import re
import time

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

def ssh(command,user="alice",capture=False):
    full_command = [ "ssh",
                     "-i", "id_dsa."+user,
                     "-o", "StrictHostKeyChecking=no",
                     user+"@"+configuration['UML_SERVER_IP'],
                     command ]
    print "Going to run: "+"#".join(full_command)+"\r"
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

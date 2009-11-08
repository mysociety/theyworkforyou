from subprocess import call, check_call
import re

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

def ssh(command,user="alice"):
    full_command = [ "ssh",
                     "-i",
                     "id_dsa."+user,
                     user+"@"+configuration['UML_SERVER_IP'],
                     command ]
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
    return 0 == ssh("test -e "+shellquote(filename))

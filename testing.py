import time
import os
from subprocess import call, check_call
from common import *

TEST_UNKNOWN   = -1
TEST_SSH       =  0
TEST_HTTP      =  1
TEST_PAGE      =  2
TEST_COOKIE    =  3

test_type_to_str = { -1 : "TEST_UNKNOWN",
                      0 : "TEST_SSH",
                      1 : "TEST_HTTP",
                      2 : "TEST_PAGE",
                      3 : "TEST_COOKIE" }

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
    def record_time(self,date_and_time,start=True):
        if start:
            self.start_time = date_and_time
            prefix = "start"
        else:
            self.end_time = date_and_time
            prefix = "end"
        fp = open(os.path.join(self.test_output_directory,prefix+"_time"),"w")
        fp.write(str(date_and_time))
        fp.close()
    def record_start_time(self,date_and_time):
        self.record_time(date_and_time,start=True)
    def record_end_time(self,date_and_time):
        self.record_time(date_and_time,start=False)
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
        result += "  test_name: "+self.test_name.encode('UTF-8')
        if self.test_output_directory:
            result += "\n  test_output_directory: "+self.test_output_directory
        return result
    def run_timed(self):
        self.record_start_time(uml_date())
        self.run()
        self.record_end_time(uml_date())
    def run(self):
        if not self.test_output_directory:
            raise Exception, "No test output directory set for: "+str(self)
        fp = open(os.path.join(self.test_output_directory,"info"),"w")
        fp.write(str(self))
        fp.close()
    def succeeded(self):
        raise Exception, "BUG: No default implementation for succeeded()"

class CookieTest(Test):
    def __init__(self,output_directory,cj,test_function,test_name="Unknown cookie test",test_short_name="unknown-cookie"):
        Test.__init__(self,output_directory,test_name=test_name,test_short_name=test_short_name)
        self.test_type = TEST_COOKIE
        self.test_function = test_function
        self.cj = cj
        self.test_succeeded = False
    def __str__(self):
        s = Test.__str__(self)
        s += "\nTesting current cookies"
        return s
    def run(self):
        Test.run(self)
        self.test_succeeded = self.test_function(self.cj)
    def succeeded(self):
        return self.test_succeeded

def run_cookie_test(output_directory,cj,test_function,test_name="Unknown cookie test",test_short_name="unknown-cookie-test"):
    p = CookieTest(output_directory,cj,test_function,test_name=test_name,test_short_name=test_short_name)
    all_tests.append(p)
    p.run()
    return p


class SSHTest(Test):
    def __init__(self,output_directory,ssh_command,user="alice",test_name="Unknown SSH test",test_short_name="unknown-ssh"):
        Test.__init__(self,output_directory,test_name=test_name,test_short_name=test_short_name)
        self.test_type = TEST_SSH
        self.ssh_command = ssh_command
        self.user = user
        self.stdout_filename = os.path.join(self.test_output_directory,"stdout")
        self.stderr_filename = os.path.join(self.test_output_directory,"stderr")
    def run(self):
        Test.run(self)
        self.result = ssh(self.ssh_command,
                          self.user,
                          capture=True,
                          stdout_filename=self.stdout_filename,
                          stderr_filename=self.stderr_filename)
        fp = open(os.path.join(self.test_output_directory,"result"),"w")
        fp.write(str(self.result.return_value))
        fp.close()
    def __str__(self):
        s = Test.__str__(self)
        s += "\n  ssh_command: "+str(self.ssh_command)
        return s
    def succeeded(self):
        return self.result.return_value == 0

def run_ssh_test(output_directory,ssh_command,user="alice",test_name="Unknown SSH test",test_short_name="unknown-ssh-test"):
    s = SSHTest(output_directory,ssh_command,user=user,test_name=test_name,test_short_name=test_short_name)
    all_tests.append(s)
    s.run()
    return s

class HTTPTest(Test):
    def __init__(self,output_directory,page,test_name="Unknown HTTP test",test_short_name="unknown-http",render=True,append_id=True,browser=None):
        Test.__init__(self,output_directory,test_name=test_name,test_short_name=test_short_name)
        self.test_type = TEST_HTTP
        self.page = page
        if append_id:
            self.page += "?test-id="+self.get_id_and_short_name()
        self.soup = None
        self.full_image_filename = None
        self.thumbnail_image_filename = None
        self.fetch_succeeded = False
        self.render_succeeded = False
        self.parsing_succeeded = False
        self.no_error_check_succeeded = False
        self.render = render
        self.error_message = ""
        self.browser = browser
    def run(self):
        Test.run(self)
        page_filename = os.path.join(self.test_output_directory,"page.html")
        self.fetch_succeeded = save_page(self.page,page_filename,url_opener=self.browser)
        print "Result from save_page was: "+str(self.fetch_succeeded)
        if not self.fetch_succeeded:
            return
        # Try to validate the HTML:
        vfp = open(os.path.join(self.test_output_directory,"validator-output"),"w")
        self.validate_result = call(["validate",page_filename],stdout=vfp)
        vfp.close()
        vfp = open(os.path.join(self.test_output_directory,"validator-result"),"w")
        vfp.write(str(self.validate_result))
        vfp.close()
        if self.render:
            self.full_image_filename = os.path.join(self.test_output_directory,"page.png")
            # FIXME: can't trust the return code from CutyCapt yet
            # FIXME: also send cookies, if 'browser' is used
            render_page(self.page,self.full_image_filename)
            self.render_succeeded = os.path.exists(self.full_image_filename) and (os.stat(self.full_image_filename).st_size > 0)
            if self.render_succeeded:
                self.thumbnail_image_filename = generate_thumbnail_version(self.full_image_filename)
        else:
            self.render_succeeded = True
        # Now try to parse the output with BeautifulSoup:
        fp = open(page_filename)
        html = fp.read()
        fp.close()
        self.soup = BeautifulSoup(html)
        body = self.soup.find('body')
        if body:
            self.parsing_succeeded = True
        else:
            return
        if not self.soup.findAll(attrs={"class":"error"}):
            self.no_error_check_succeeded = True
    def succeeded(self):
        return self.fetch_succeeded and (self.render_succeeded or not self.render) and self.parsing_succeeded and self.no_error_check_succeeded
    def __str__(self):
        s = Test.__str__(self)
        s += "\n  page: "+str(self.page)
        return s

# A page test is dependent on the result of previous HTTPTest - it
# analyses those results:

class PageTest(Test):
    def __init__(self,output_directory,http_test,test_function,test_name="Unknown page test",test_short_name="unknown-page"):
        Test.__init__(self,output_directory,test_name=test_name,test_short_name=test_short_name)
        self.test_type = TEST_PAGE
        self.http_test = http_test
        self.test_function = test_function
        self.test_succeeded = False
    def __str__(self):
        s = Test.__str__(self)
        s += "\nTesting the content resulting from "+str(self.http_test)
        return s
    def run(self):
        Test.run(self)
        self.test_succeeded = self.test_function(self.http_test)
    def succeeded(self):
        return self.test_succeeded

def run_page_test(output_directory,http_test,test_function,test_name="Unknown page test",test_short_name="unknown-page"):
    p = PageTest(output_directory,http_test,test_function,test_name=test_name,test_short_name=test_short_name)
    all_tests.append(p)
    p.run()
    return p

def run_http_test(output_directory,page,test_name="Unknown HTTP test",test_short_name="unknown-http",render=True,append_id=True,browser=None):
    print "Got test_name: "+test_name
    h = HTTPTest(output_directory,page,test_name=test_name,test_short_name=test_short_name,render=render,append_id=append_id,browser=browser)
    all_tests.append(h)
    h.run_timed()
    return h

def local_coverage_data_between(directory,date_start,date_end,output):
    coverage_files = os.listdir(directory)
    in_range_coverage_files = [ os.path.join(directory,x) for x in coverage_files if x >= date_start and x <= date_end ]
    ofp = open(output,"w")
    for i in in_range_coverage_files:
        fp = open(i)
        first_line = True
        for line in fp:
            if first_line:
                first_line = False
                continue
            else:
                ofp.write(line)
    ofp.close()

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

def standard_css():
    return '''
.test {
  padding: 5px;
  margin: 5px;
  border-width: 1px
}
.stdout_stderr {
  padding: 5px;
  margin: 5px;
  background-color: #bfbfbf
}
'''

# This is a bit of a mess now.  The parameters should look a bit like this:
#
#   uml_prefix_to_strip "/data/vhost/theyworkforyou.sandbox/mysociety/"
#     (That's the bit to strip off the start of filenames in the coverage file.)
#
#   coverage_data_file "output/2009-12-13T22:42:48/coverage"
#
#   output_directory "output/2009-12-13T22:42:48/coverage-report"
#
#   original_source_directory "output/2009-12-13T22:42:48/mysociety"
#     (This is the directory that we've copied the instrumented source code to.)

def generate_coverage(uml_prefix_to_strip,coverage_data_file,output_directory,original_source_directory):
    output_directory = ensure_slash(output_directory)
    check_call(["mkdir","-p",output_directory])
    uml_prefix_re = re.compile(re.escape(uml_prefix_to_strip))
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
            current_filename = uml_prefix_re.sub('',current_filename)
            files_to_coverage.setdefault(current_filename,{})
    filenames_with_coverage_data = files_to_coverage.keys()
    filename_to_percent_coverage = {}
    for filename in filenames_with_coverage_data:
        filename = uml_prefix_re.sub('',filename)
        unused_lines = 0
        used_lines = 0
        lines_to_uses = files_to_coverage[filename]
        original_filename = os.path.join(original_source_directory,filename)
        output_filename = os.path.join(output_directory,filename+".html")
        output_filename_dirname = os.path.split(output_filename)[0]
        if not os.path.exists(output_filename_dirname):
            os.makedirs(output_filename_dirname)
        ofp = open(output_filename,"w")
        ofp.write('''<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
<title>Coverage data for %s</title>
<style type="text/css">
%s
</style>
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
''' % (cgi.escape(filename),standard_css(),uses_to_colour(1),uses_to_colour(-1),uses_to_colour(-2),uses_to_colour(-9)))
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
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>Coverage Data Index</title>
<style type="text/css">
%s
</style>
</head>
<body style="background-color: #ffffff">
<table border=0>
'''%(standard_css(),))
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

# The method that should be in BeautifulSoup:

def non_tag_data_in(o):
    if o.__class__ == NavigableString:
        return re.sub('(?ms)[\r\n]',' ',o)
    elif o.__class__ == Tag:
        if o.name == 'script':
            return ''
        else:
            return ''.join( map( lambda x: non_tag_data_in(x) , o.contents ) )
    elif o.__class__ == Comment:
        return ''
    else:
        # Hope it's a string or something else concatenatable...
        return o

def tag_text_is(tag,text):
    # Turn the text into a regular expression which doesn't care about
    # whitespace or case:
    re_pattern = "^"+re.sub('(\\\\ )+','\s+',re.escape(text.strip()))+"$"
    r = re.compile(re_pattern,re.IGNORECASE|re.MULTILINE|re.DOTALL)
    n = non_tag_data_in(tag).strip()
    print "Comparing pattern: "+r.pattern
    print "             with: "+n
    result = r.match(non_tag_data_in(tag).strip())
    print "Result was: "+str(result)
    return result

# Like BeautifulSoup's next and nextSibling, but only goes to tag
# elements:
def next_tag(tag,sibling=True):
    if sibling:
        current_tag = tag.nextSibling
    else:
        current_tag = tag.next
    while True:
        if current_tag.__class__ == Tag:
            return current_tag
        elif not current_tag:
            return None
        if sibling:
            current_tag = current_tag.nextSibling
        else:
            current_tag = current_tag.next

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

def relative_css_path(output_directory,current_html_filename):
    to_strip = ensure_slash(output_directory)
    after_stripping = re.sub(re.escape(to_strip),'',current_html_filename)
    dirname = os.path.dirname(after_stripping)
    if len(dirname) > 0:
        return re.sub('[^/]+','..',dirname) + '/report.css'
    else:
        return 'report.css'

class Test:
    last_test_number = -1
    def __init__(self,top_level_output_directory,test_name="Unknown test",test_short_name="unknown"):
        self.top_level_output_directory = top_level_output_directory
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
        parent, directory_name = os.path.split(self.top_level_output_directory)
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
        o = os.path.join(self.top_level_output_directory,self.get_id_and_short_name())
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
        raise Exception, "BUG: There's no default implementation for succeeded()"
    def output_included_html(self,fp,copied_coverage,used_source_directory):
        pass
    def output_html(self,fp,copied_coverage,used_source_directory):
        if self.succeeded():
            success_class = "passed"
        else:
            success_class = "failed"
        fp.write("<div class=\"test %s\" id=\"%s\">\n"%(success_class,self.get_id_and_short_name()))
        fp.write("<h3>%s</h3>\n" % (self.test_name.encode('UTF-8'),))
        fp.write("<h4>%s</h4>\n" % (self.get_id_and_short_name(),))
        fp.write("<pre>\n")
        fp.write(cgi.escape(file_to_string(os.path.join(self.test_output_directory,"info"))))
        fp.write("</pre>\n")
        self.output_included_html(fp,copied_coverage,used_source_directory)
        fp.write("</div>\n")

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
    def output_included_html(self,fp,copied_coverage,used_source_directory):
        for s in ("stdout","stderr"):
            fp.write("<h4>%s</h4>" % (s,))
            fp.write("<div class=\"stdout_stderr\"><pre>")
            fp.write(cgi.escape(file_to_string(os.path.join(self.test_output_directory,s))))
            fp.write("</pre></div>")

def run_ssh_test(output_directory,ssh_command,user="alice",test_name="Unknown SSH test",test_short_name="unknown-ssh-test"):
    s = SSHTest(output_directory,ssh_command,user=user,test_name=test_name,test_short_name=test_short_name)
    all_tests.append(s)
    s.run()
    return s

class HTTPTest(Test):
    def __init__(self,output_directory,page,test_name="Unknown HTTP test",test_short_name="unknown-http",render=True,browser=None):
        Test.__init__(self,output_directory,test_name=test_name,test_short_name=test_short_name)
        self.test_type = TEST_HTTP
        self.page = page
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
    def output_coverage(self,copied_coverage,used_source_directory,instrumented_files):
        coverage_data_file = os.path.join(self.test_output_directory,"coverage")
        coverage_report_directory = os.path.join(self.test_output_directory,coverage_report_leafname)
        local_coverage_data_between(copied_coverage,self.start_time,self.end_time,coverage_data_file)
        generate_coverage(self.top_level_output_directory,
                          "/data/vhost/theyworkforyou.sandbox/mysociety/",
                          coverage_data_file,
                          coverage_report_directory,
                          used_source_directory,
                          instrumented_files)
    def output_included_html(self,fp,copied_coverage,used_source_directory):
        relative_url = os.path.join(os.path.join(self.get_id_and_short_name(),coverage_report_leafname),"coverage-coverage.html")
        fp.write("<p><a href=\"%s\">Code coverage for this test.</a></p>\n" % (relative_url,))
        if self.render and self.full_image_filename:
            # fp.write("<div style=\"float: right\">")
            fp.write("<div>")
            output_directory = os.path.split(self.test_output_directory)[0] + "/"
            relative_full_image_filename = re.sub(re.escape(output_directory),'',self.full_image_filename)
            relative_thumbnail_image_filename = re.sub(re.escape(output_directory),'',self.thumbnail_image_filename)
            fp.write("<a href=\"%s\"><img src=\"%s\"></a>" % (relative_full_image_filename,relative_thumbnail_image_filename))
            fp.write("</div>\n")
        fp.write("<div class=\"validation\">\n")
        if self.validate_result == 0:
            success_class = "passed"
        else:
            success_class = "failed"
        fp.write("<pre class=\"validation-report %s\">\n"%(success_class,))
        vfp = open(os.path.join(self.test_output_directory,"validator-output"))
        fp.write(cgi.escape(vfp.read()))
        vfp.close()
        fp.write("\n</pre>\n")
        fp.write("</div>\n")

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

def run_http_test(output_directory,page,test_name="Unknown HTTP test",test_short_name="unknown-http",render=True,browser=None):
    h = HTTPTest(output_directory,page,test_name=test_name,test_short_name=test_short_name,render=render,browser=browser)
    all_tests.append(h)
    h.run_timed()
    return h

coverage_report_leafname = "coverage-report"

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
        return ( "not_executed", "#555555" )
    elif uses == -1:
        return ( "dead_code", "#f37c7c" )
    elif uses > 0:
        return ( "executed", "#8df37c" )
    elif uses == -9:
        return ( "comments", "#f4ff78" )
    elif uses == -8:
        return ( "no_information", "#cccccc" )
    else:
        raise Exception, "Unknown number of uses: "+str(uses)

def write_css_file(css_filename):
    fp = open( css_filename,"w")
    fp.write('''/* Basic CSS for test reports: */

table.source {
  border-collapse: collapse
}

.test {
  padding: 5px;
  margin: 5px;
  border-width: 1px
}

.passed {
  /* background-color: #96ff18 */
  background-color: #b7ffb5
}

.failed {
  /* background-color: #ff8181 */
  background-color: #ff9393
}

.stdout_stderr {
  padding: 5px;
  margin: 5px;
  background-color: #bfbfbf
}

.file-no-information {
  background-color: #cccccc
}

.file-information {
  background-color: #ffffff
}

td.file-information, td.file-no-information {
  padding-left: 25px
}

.coverage_key {
  color: #000000;
  padding: 3px
}
.coverage_line {
  color: #000000;
  font-family: monospace
}
.coverage_line_number {
  font-family: monospace;
  padding-left: 5px;
  padding-right: 5px
}

.coverage_line_%s {
  background-color: %s
}
.coverage_line_%s {
  background-color: %s
}
.coverage_line_%s {
  background-color: %s
}
.coverage_line_%s {
  background-color: %s
}
.coverage_line_%s {
  background-color: %s
}

''' % (uses_to_colour(-2) + uses_to_colour(-1) + uses_to_colour(1) + uses_to_colour(-9) + uses_to_colour(-8)))

# This is a bit of a mess now.  The parameters should look a bit like this:
#
#   top_level_output_directory "output/2009-12-13T22:42:48"
#
#   uml_prefix_to_strip "/data/vhost/theyworkforyou.sandbox/mysociety/"
#     (That's the bit to strip off the start of filenames in the coverage file.)
#
#   coverage_data_file "output/2009-12-13T22:42:48/coverage"
#
#   coverage_output_directory "output/2009-12-13T22:42:48/coverage-report"
#
#   original_source_directory "output/2009-12-13T22:42:48/mysociety"
#     (This is the directory that we've copied the instrumented source code to.)

def generate_coverage(top_level_output_directory,uml_prefix_to_strip,coverage_data_file,coverage_output_directory,original_source_directory,instrumented_files):
    coverage_output_directory = ensure_slash(coverage_output_directory)
    check_call(["mkdir","-p",coverage_output_directory])
    uml_prefix_re = re.compile(re.escape(uml_prefix_to_strip))
    files_to_coverage = {}
    for i in instrumented_files:
        files_to_coverage[i] = {}
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
            # Sometimes we end up with extraneous characters on the end of
            # the filename.  According to:
            # http://developer.spikesource.com/forums/viewtopic.php?p=1262&sid=7e3b220705b71caa81223f2e01c41212#1262
            # ... this happens when code is dynamically evaluated,
            # e.g. with "eval, create_function or preg_replace with the /e
            # option".  The case I've come across is the preg_replace with
            # #...#e in twfy/www/includes/easyparliament/templates/html/hansard_gid.php
            if re.search('\(',current_filename):
                print "Stripping suspicious characters from filename: '"+current_filename+"'"
                current_filename = re.sub('\(.*$','',current_filename)
                print "It's now: '"+current_filename+"'"
            files_to_coverage.setdefault(current_filename,{})
    filenames_with_coverage_data = files_to_coverage.keys()
    filename_to_percent_coverage = {}
    for filename in filenames_with_coverage_data:
        filename = uml_prefix_re.sub('',filename)
        unused_lines = 0
        used_lines = 0
        lines_to_uses = files_to_coverage[filename]
        original_filename = os.path.join(original_source_directory,filename)
        output_filename = os.path.join(coverage_output_directory,filename+".html")
        output_filename_dirname = os.path.split(output_filename)[0]
        if not os.path.exists(output_filename_dirname):
            os.makedirs(output_filename_dirname)
        ofp = open(output_filename,"w")
        ofp.write('''<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
<title>Coverage data for %s</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="%s" title="Basic CSS">
</head>
<body style="background-color: #ffffff">
<table border=0>
<tr><td class="coverage_key coverage_line_executed">A line which was executed</td></tr>
<tr><td class="coverage_key coverage_line_not_executed">A line which was not executed</td></tr>
<tr><td class="coverage_key coverage_line_dead_code">Dead code (could never be executed)</td></tr>
<tr><td class="coverage_key coverage_line_comments">Lines with no executable code (e.g. comments)</td></tr>
<tr><td class="coverage_key coverage_line_no_information">No information available</td></tr>
</table>
<hr>
<table border="0" class="source">
''' % (cgi.escape(filename),
       relative_css_path(top_level_output_directory,output_filename)))
        line_number = 1
        ifp = open(original_filename)
        for line in ifp:
            line = line.rstrip()
            if len(lines_to_uses) == 0:
                # Then we have no information at all about the file:
                uses = -8
            else:
                # Then we have some information, so the default is to be a comment:
                uses = -9
            if line_number in lines_to_uses:
                uses = lines_to_uses[line_number]
            if uses == -1:
                unused_lines += 1
            elif uses > 0:
                used_lines += 1
            class_extension = uses_to_colour(uses)[0]
            line_number_string = "%4d" % (line_number)
            line_number_string = re.sub(' ','&nbsp;',line_number_string)
            ofp.write("<tr><td class=\"coverage_line_number\"><strong>%s</strong></td><td class=\"coverage_line coverage_line_%s\">%s</td></tr>\n" % (line_number_string,class_extension,cgi.escape(line)))
            line_number += 1
        ofp.write("</table>\n</body>\n</html>\n")
        ofp.close()
        possible_lines = used_lines + unused_lines
        if possible_lines > 0:
            percent_coverage = (100 * used_lines) / float(possible_lines)
        else:
            percent_coverage = 0
        filename_to_percent_coverage[filename] = percent_coverage
    fp.close()

    filenames = filename_to_percent_coverage.keys()
    filenames.sort()
    filenames_by_coverage = sorted(filenames,key=lambda x: (-filename_to_percent_coverage[x],x))

    # Now output an index page:
    def output_coverage_index(coverage_output_directory,filenames,files_to_coverage,sort_method):
        output_filename = os.path.join(coverage_output_directory,"coverage-"+sort_method+".html")
        fp = open(output_filename,"w")
        fp.write('''<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
<link rel="stylesheet" type="text/css" href="%s" title="Basic CSS">
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>Coverage Data Index (sorted by %s)</title>
</head>
<body style="background-color: #ffffff">
<p><a href="coverage-filename.html">Sorted by filename</a> |
   <a href="coverage-coverage.html">Sorted by percent coverage</a></p>
<p>Files which were instrumented but not loaded or used have a <span class="file-no-information">darker background</span></a></p>
<table border=0>
'''%(relative_css_path(top_level_output_directory,output_filename),sort_method))
        for filename in filenames:
            url = filename + ".html"
            coverage = filename_to_percent_coverage[filename]
            cell_class = "file-information"
            if len(files_to_coverage[filename]) == 0:
                cell_class = "file-no-information"
            fp.write("<tr><td class=\"%s\"><tt><a href=\"%s\">%s</a></tt></td><td>%3d%%</td></tr>\n" % (cell_class,url,filename,int(coverage)))
        fp.write('''</table>
</body>
</html>''')
        fp.close()
    output_coverage_index(coverage_output_directory,
                          filenames,
                          files_to_coverage,
                          "filename")
    output_coverage_index(coverage_output_directory,
                          filenames_by_coverage,
                          files_to_coverage,
                          "coverage")

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

def tag_text_is(tag,text,substring=False):
    # Turn the text into a regular expression which doesn't care about
    # whitespace or case:
    inner_pattern = re.sub('(\\\\ )+','\s+',re.escape(text.strip()))
    if substring:
        re_pattern = inner_pattern
    else:
        re_pattern = "^"+inner_pattern+"$"
    r = re.compile(re_pattern,re.IGNORECASE|re.MULTILINE|re.DOTALL)
    n = non_tag_data_in(tag).strip()
    # print "Comparing pattern: "+r.pattern
    # print "             with: "+n
    result = r.search(n)
    # print "Result was: "+str(result)
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

#!/usr/bin/python2.5

from common import *
from testing import *
from subprocess import call, check_call, Popen
import time
import re
import sys
from optparse import OptionParser
from BeautifulSoup import BeautifulSoup
from browser import *
import cgi

def add_instrumentation(www_directory):
    # Add the instrumentation:
    ssh_result = ssh("add-php-instrumentation.py "+www_directory,capture=True)
    if ssh_result.return_value != 0:
        raise Exception, "Instrumenting the TWFY PHP code failed."
    instrumented_files = re.split('[\r\n]+',ssh_result.stdout_data)
    # Copy over the instrument.php file:
    if 0 != scp("instrument.php",
                 www_directory+"/includes/"):
        raise Exception, "Failed to copy over the instrument.php file"
    return [ x for x in instrumented_files if len(x.strip()) > 0 ]

def setup_coverage_directory():
    # Create a world-writable directory for coverage data:
    coverage_directory = "/home/alice/twfy-coverage/"
    if not path_exists_in_uml(coverage_directory):
        if 0 != ssh("mkdir -m 0777 "+coverage_directory):
            raise Exception, "Failed to create coverage data directory"
    # Remove any old data from that directory:
    if 0 != ssh("rm -f "+coverage_directory+"/*"):
        raise Exception, "Failed to clean the coverage data directory"

def run_main_tests(top_level_output_directory):

    check_dependencies()
    setup_configuration()

    instrumented_files = add_instrumentation("/data/vhost/theyworkforyou.sandbox/mysociety/twfy/www/")
    instrumented_files = [ "twfy/www/"+x for x in instrumented_files ]
    for i in instrumented_files:
        print "  Instrumented: "+i

    setup_coverage_directory()

    start_all_coverage = uml_date()

    # FIXME: move all these to after the non-cookie tests...

    cj, browser = create_cookiejar_and_browser()
    postcode_test = run_http_test(top_level_output_directory,
                                  "/postcode/?pc=EH8+9NB",
                                  test_name="Testing postcode lookup",
                                  test_short_name="postcode",
                                  append_id=False,
                                  browser=browser)

    def cookie_jar_has(cj,k,v=None):
        for c in cj:
            if c.name == k:
                if v:
                    return c.value
                else:
                    return True
        return False

    run_cookie_test(top_level_output_directory,
                    cj,
                    lambda cj: cookie_jar_has(cj,'eppc','EH89NB'),
                    test_name="Setting postcode cookie",
                    test_short_name="postcode-cookie-set")

    # FIXME: now check that the text on the main page has changes

    main_page_test = run_http_test(top_level_output_directory,
                                   "/",
                                   test_name="Fetching main page again",
                                   test_short_name="basic-main-page-with-postcode",
                                   browser=browser)

    def local_mp_link(http_test,mp_name):
        soup = http_test.soup
        links = links = soup.findAll( lambda x: (x.name == 'a' and ('href','/mp/') in x.attrs  ) )
        for e in links:
            if re.search(re.escape(mp_name),non_tag_data_in(e)):
                return True
        return False

    run_page_test(top_level_output_directory,
                  main_page_test,
                  lambda t: local_mp_link(t,"Denis Murphy"),
                  test_name="Checking local MP appears on main page",
                  test_short_name="main-page-has-local-MP")

    change_page_test = run_http_test(top_level_output_directory,
                                   "/user/changepc/",
                                   test_name="Getting change postcode page",
                                   test_short_name="getting-change-postcode-page",
                                   browser=browser)

    def change_postcode_form(http_test, old_postcode):
        form = http_test.soup.find( lambda x: x.name == 'form' and ('action','/postcode/') in x.attrs )
        if not form:
            return False
        return re.search('Your current postcode: '+old_postcode,non_tag_data_in(form))

    run_page_test(top_level_output_directory,
                  change_page_test,
                  lambda t: change_postcode_form(change_page_test,'EH89NB'),
                  test_name="Checking change postcode prompt appears",
                  test_short_name="change-postcode-prompt")

    run_page_test(top_level_output_directory,
                  change_page_test,
                  lambda t: t.soup.find( lambda x: x.name == 'a' and ('href','/user/changepc/?forget=t') in x.attrs ),
                  test_name="Checking forget postcode prompt appears",
                  test_short_name="change-forget-postcode-prompt")

    change_postcode_test = run_http_test(top_level_output_directory,
                                         "/postcode/?pc=CB2%202RP&submit=GO",
                                         test_name="Changing postcode",
                                         test_short_name="change-postcode",
                                         browser=browser)

    run_page_test(top_level_output_directory,
                  change_postcode_test,
                  lambda t: t.soup.find(lambda x: x.name == 'h2' and x.string == 'Bridget Prentice'),
                  test_name="Postcode changed successfully",
                  test_short_name="postcode-changed")

    forget_postcode_test = run_http_test(top_level_output_directory,
                                         "/user/changepc/?forget=t",
                                         test_name="Forgetting postcode",
                                         test_short_name="forgetting-postcode",
                                         browser=browser)

    run_page_test(top_level_output_directory,
                  forget_postcode_test,
                  lambda t: t.soup.find(lambda x: x.name == 'strong' and tag_text_is(x,"Enter your UK postcode:")),
                  test_name="Prompting for postcode after forgetting",
                  test_short_name="postcode-forgotten-prompt")

    # FIXME: create a new browser and try making an account:

    # FIXME: Try searching...

    # FIXME: Try adding an email alerts

    # FIXME: provoke the "send alerts" code

    # FIXME: check that an email has been received

    # ------------------------------------------------------------------------

    # Fetch the main page:

    main_page_test = run_http_test(top_level_output_directory,
                                   "/",
                                   test_name="Fetching main page",
                                   test_short_name="basic-main-page")

    def recent_event(http_test,header,item):
        # We want to check that the list on the front page has links
        # to the recent debates.  First fine a matching <h4>
        soup = http_test.soup
        h = soup.find( lambda x: x.name == 'h4' and tag_text_is(x,header) )
        if not h:
            return False
        ul = next_tag(h)
        if not (ul.name == 'ul'):
            return False
        for li in ul.contents:
            if not (li and li.name == 'li'):
                continue
            if tag_text_is(li,item):
                return True
        return False

    items_to_find = [ ("The most recent Commons debates", "Business Before Questions"),
                      ("The most recent Lords debates", "Africa: Water Shortages &#8212; Question"),
                      ("The most recent Westminster Hall debates", "[Sir Nicholas Winterton in the Chair] &#8212; Oil and Gas"),
                      ("The most recent Written Answers","Work and Pensions"),
                      ("The most recent Written Ministerial Statements", "House of LordsEU: Justice and Home Affairs CouncilGlobal Entrepreneurship WeekLondon Underground") ]

    i = 0
    for duple in items_to_find:
        run_page_test(top_level_output_directory,
                      main_page_test,
                      lambda t: recent_event(t,duple[0],duple[1]),
                      test_name="Checking that '"+duple[0]+"' contains '"+duple[1]+"'",
                      test_short_name="main-page-recent-item-"+str(i))
        i += 1

    # ------------------------------------------------------------------------

    def busiest_debate(http_test,header,text):
        soup = http_test.soup
        h = soup.find( lambda x: (x.name == 'h3' or x.name == 'h4') and tag_text_is(x,header) )
        if not h:
            print "Failed to find header with text '"+header+"'"
            return False
        ns = next_tag(next_tag(h,sibling=False),sibling=False)
        print "Next tag is:"
        print ns.prettify()
        return tag_text_is(ns,text)

    main_scotland_page_test = run_http_test(top_level_output_directory,
                                            "/scotland/",
                                            test_name="Fetching main page for Scotland",
                                            test_short_name="basic-main-scotland-page")

    header = "Busiest Scottish Parliament debates from the most recent week"
    text = 'Scottish Economy (103 speeches)'

    run_page_test(top_level_output_directory,
                  main_scotland_page_test,
                  lambda t: busiest_debate(t,header,text),
                  test_name="Checking that first item in '"+header+"' is '"+text+"'",
                  test_short_name="main-scotland-page-busiest-0")

    def any_answer(http_test,header):
        soup = http_test.soup
        h = soup.find( lambda x: x.name == 'h3' and tag_text_is(x,header) )
        if not h:
            print "Failed to find header with text '"+header+"'"
            return False
        ns = next_tag(next_tag(h,sibling=False),sibling=False)
        stringified = non_tag_data_in(ns)
        return re.search('\(2[0-9]\s+October\s+2009\)',stringified)

    header = "Some recent written answers"

    run_page_test(top_level_output_directory,
                  main_scotland_page_test,
                  lambda t: any_answer(t,header),
                  test_name="Checking that there's some random answer under '"+header+"'",
                  test_short_name="main-scotland-page-any-written")

    # ------------------------------------------------------------------------

    main_ni_page_test = run_http_test(top_level_output_directory,
                                            "/ni/",
                                            test_name="Fetching main page for Northern Ireland",
                                            test_short_name="basic-main-ni-page")

    header = "Busiest debates from the most recent month"
    text = u"Private Members&#8217; Business"

    run_page_test(top_level_output_directory,
                  main_ni_page_test,
                  lambda t: busiest_debate(t,header,text),
                  test_name="Checking that first item in '"+header+"' is '"+text+"'",
                  test_short_name="main-ni-page-busiest-0")

    # ------------------------------------------------------------------------

    main_wales_page_test = run_http_test(top_level_output_directory,
                                            "/wales/",
                                            test_name="Fetching main page for wales",
                                            test_short_name="basic-main-wales-page")

    run_page_test(top_level_output_directory,
                  main_wales_page_test,
                  lambda t: t.soup.find( lambda x: x.name == 'h3' and tag_text_is(x,"We need you!") ),
                  test_name="Checking that the Wales page still asks for help",
                  test_short_name="main-wales-page-undone")

    # ------------------------------------------------------------------------

    mps_test = run_http_test(top_level_output_directory,
                             "/mps/",
                             test_name="Fetching basic MPs page",
                             test_short_name="basic-MPs",
                             render=False) # render fails on a page this size...

    # This uses the result of the previous test to check that Diane
    # Abbot (the first MP in this data set) is in the list.

    run_page_test(top_level_output_directory,
                  mps_test,
                  lambda t: 1 == len(t.soup.findAll( lambda tag: tag.name == "a" and tag.string and tag.string == "Diane Abbott" )),
                  test_name="Diane Abbott in MPs page",
                  test_short_name="mps-contains-diane-abbott")

    # As a slightly different example of doing the same thing, define
    # a function instead of using nested lambdas:

    def link_from_mp_name(http_test,name):
        all_tags = http_test.soup.findAll( lambda tag: tag.name == "a" and tag.string and tag.string == name)
        return 1 == len(all_tags)

    run_page_test(top_level_output_directory,
                  mps_test,
                  lambda t: link_from_mp_name(t,"Richard Younger-Ross"),
                  test_name="Richard Younger-Ross in MPs page",
                  test_short_name="mps-contains-richard-younger-ross")

    # ------------------------------------------------------------------------

    msps_test = run_http_test(top_level_output_directory,
                              "/msps/",
                              test_name="Fetching basic MSPs page",
                              test_short_name="basic-MSPs")

    run_page_test(top_level_output_directory,
                  msps_test,
                  lambda t: link_from_mp_name(t,"Brian Adam"),
                  test_name="Brian Adam in MSPs page",
                  test_short_name="msps-contains-brian-adam")

    run_page_test(top_level_output_directory,
                  msps_test,
                  lambda t: link_from_mp_name(t,"John Wilson"),
                  test_name="John Wilson in MSPs page",
                  test_short_name="msps-contains-john-wilson")

    # ------------------------------------------------------------------------

    mlas_test = run_http_test(top_level_output_directory,
                              "/mlas/",
                              test_name="Fetching basic MLAs page",
                              test_short_name="basic-MLAs")

    run_page_test(top_level_output_directory,
                  mlas_test,
                  lambda t: link_from_mp_name(t,"Gerry Adams"),
                  test_name="Gerry Adams in MLAs page",
                  test_short_name="msps-contains-gerry-adams")

    run_page_test(top_level_output_directory,
                  mlas_test,
                  lambda t: link_from_mp_name(t,"Sammy Wilson"),
                  test_name="Sammy Wilson in MLAs page",
                  test_short_name="msps-contains-sammy-wilson")

    # ------------------------------------------------------------------------

    # Check a written answer from Scotland:

    spwrans_test = run_http_test(top_level_output_directory,
                                 "/spwrans/?id=2009-10-26.S3W-27797.h",
                                 test_name="Testing Scottish written answer",
                                 test_short_name="spwrans",
                                 append_id=False)

    def check_speaker_and_speech_tag(expected_name, got_name, expected_speech, got_speech_tag):
        if not expected_name == got_name:
            print "Speaker name didn't match:"
            print "Expected '"+expected_name+"', but got '"+got_name+"'"
            return False
        if not tag_text_is(got_speech_tag,expected_speech):
            print "Text didn't match..."
            return False
        return True

    def check_written_answer(t,q_name,q_text,a_name,a_text):
        labour_speakers = t.soup.findAll(attrs={'class':'speaker labour'})
        snp_speakers = t.soup.findAll(attrs={'class':'speaker scottish national party'})
        if not 1 == len(labour_speakers):
            print "Couldn't find the unique question, should be from a Labour speaker"
            return False
        speaker = labour_speakers[0]
        speaker_name = speaker.contents[0].contents[0].string
        question_tag = next_tag(speaker)
        if not check_speaker_and_speech_tag(q_name,speaker_name,q_text,question_tag):
            return False
        speaker = snp_speakers[0]
        speaker_name = speaker.contents[0].contents[0].string
        question_tag = next_tag(speaker)
        if not check_speaker_and_speech_tag(a_name,speaker_name,a_text,question_tag):
            return False
        return True

    run_page_test(top_level_output_directory,
                  spwrans_test,
                  lambda t: check_written_answer(t,
                                                 "Sarah Boyack",
                                                 "To ask the Scottish Executive how many properties it has disposed of in the last two years to which section 68 of the Climate Change (Scotland) Act 2009 could have been applied.",
                                                 "John Swinney",
                                                 "No core Scottish Government-owned buildings, to which section 68 of the Climate Change (Scotland) Act 2009 could have been applied, have been sold by the Scottish Government in the last two years."),
                  test_name="Checking text of Scottish Written Answer",
                  test_short_name="spwrans-content")

    # ------------------------------------------------------------------------

    # Find a representative based on postcode:

    postcode_test = run_http_test(top_level_output_directory,
                                  "/postcode/?pc=EH8+9NB",
                                  test_name="Testing postcode lookup",
                                  test_short_name="postcode",
                                  append_id=False)

    run_page_test(top_level_output_directory,
                  postcode_test,
                  lambda t: t.soup.find( lambda x: x.name == 'h2' and x.string and x.string == "Denis Murphy" ),
                  test_name="Looking for valid postcode result",
                  test_short_name="postcode-result")

    # ------------------------------------------------------------------------

    run_http_test(top_level_output_directory,
                  "/mp/gordon_brown/kirkcaldy_and_cowdenbeath",
                  test_name="Fetching Gordon Brown's page",
                  test_short_name="gordon-brown")

    end_all_coverage = uml_date()

    # ========================================================================
    # Generate the coverage report:

    output_filename_all_coverage = os.path.join(top_level_output_directory,"coverage")

    copied_coverage = os.path.join(top_level_output_directory,"complete-coverage")
    rsync_from_guest("/home/alice/twfy-coverage/",copied_coverage)

    local_coverage_data_between(copied_coverage,start_all_coverage,end_all_coverage,output_filename_all_coverage)

    used_source_directory = os.path.join(top_level_output_directory,"mysociety")

    check_call(["mkdir","-p",used_source_directory])

    rsync_from_guest("/data/vhost/theyworkforyou.sandbox/mysociety/twfy/",
                     os.path.join(used_source_directory,"twfy"),
                     user="alice",
                     verbose=False)

    rsync_from_guest("/data/vhost/theyworkforyou.sandbox/mysociety/phplib/",
                     os.path.join(used_source_directory,"phplib"),
                     user="alice",
                     verbose=False)

    # Output some CSS:
    write_css_file(os.path.join(top_level_output_directory,"report.css"))

    report_index_filename = os.path.join(top_level_output_directory,"report.html")
    fp = open(report_index_filename,"w")

    # Generate complete coverage report:
    generate_coverage(top_level_output_directory,
                      "/data/vhost/theyworkforyou.sandbox/mysociety/",
                      output_filename_all_coverage,
                      os.path.join(top_level_output_directory,coverage_report_leafname),
                      used_source_directory,
                      instrumented_files)

    total_number_of_tests = len(all_tests)
    successes = 0
    validations_failed = 0
    failed_tests = []
    for t in all_tests:
        if t.succeeded():
            successes += 1
        else:
            failed_tests.append(t)
        if t.test_type == TEST_HTTP and t.validate_result != 0:
            validations_failed += 1

    fp.write('''<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
<title>They Work For You Test Reports</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="%s" title="Basic CSS">
</head>
<body style="background-color: #ffffff">
<h2>They Work For You Test Reports</h2>
<p><a href="%s/coverage-coverage.html">Code coverage report for all tests.</a>
</p>
''' % (relative_css_path(top_level_output_directory,report_index_filename),
       coverage_report_leafname))

    if successes == total_number_of_tests:
        fp.write("<p>All tests passed!</p>\n")
    else:
        fp.write("<p>%d out of %s tests passed</p>\n")
        fp.write("<ul>\n")
        for f in failed_tests:
            fp.write("  <li><a href=\"#%s\">%s</a></li>\n" % (f.test_short_name,f.test_name))
        fp.write("</ul>\n")

    if validations_failed > 0:
        fp.write("<p>%d HTML validations failed</p>"%(validations_failed,))

    for t in all_tests:
        print "=============="
        print str(t)
        if t.test_type == TEST_HTTP:
            t.output_coverage(copied_coverage,used_source_directory,instrumented_files)
        t.output_html(fp,copied_coverage,used_source_directory)

    fp.write('''</body></html>''')
    fp.close()

if __name__ == '__main__':
    parser = OptionParser(usage="Usage: %prog [OPTIONS]")
    parser.add_option('-o', '--output-directory', dest="output_directory",
                      help="override the default test output directory (./output/[TIMESTAMP]/)")
    options,args = parser.parse_args()
    if options.output_directory:
        output_directory = options.output_directory
    else:
        output_directory = create_output_directory() 

    check_call(["mkdir","-p",output_directory])

    run_main_tests(output_directory)

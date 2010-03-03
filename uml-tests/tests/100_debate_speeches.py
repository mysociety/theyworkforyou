from testing import *

def tag_has_speech_class(x):
    for t in x.attrs:
        if t[0] == 'class' and re.search('(^| )speech($| )',t[1]):
            return True
    return False

def check_debate_has_speech(current_test,http_test,author,speech_text_substring):
    speeches = http_test.soup.findAll(lambda x: x.name == "div" and tag_has_speech_class(x))
    current_test.log("Found "+str(len(speeches))+" speeches")
    for s in speeches:
        if s.find(lambda x: x.name == 'a' and tag_text_is(x,author)):
            current_test.log("Found a link tag with text matching: "+author)
            for main in s.findAll(lambda x: x.name == 'div' and ('class','main') in x.attrs):
                if tag_text_is(main,speech_text_substring,substring=True):
                    current_test.log("Found the text in the main div inside that speech")
                    return True
    return False

ni_debate_test = run_http_test("/ni/?id=2009-10-13.5.13",
                               test_short_name="debate-ni",
                               test_name="Fetching a Northern Ireland Assembly debate page")

run_page_test(ni_debate_test,
              lambda t,o: check_debate_has_speech(t,o,'Margaret Ritchie','energy efficiency is but one element in the alleviation of fuel poverty'),
              test_name="Checking speech appears in NI debate",
              test_short_name="debate-ni-has-speech")

commons_debate_test = run_http_test("/debates/?id=2009-10-29a.548.0",
                                    test_short_name="debate-commons",
                                    test_name="Fetching a Commons debate page")

run_page_test(commons_debate_test,
              lambda t,o: check_debate_has_speech(t,o,'David Taylor','common areas where consumers potentially are not getting'),
              test_name="Checking speech appears in Commons debate",
              test_short_name="debate-commons-has-speech")

lords_debate_test = run_http_test("/lords/?id=2009-10-27a.1100.6",
                                  test_short_name="debate-lords",
                                  test_name="Fetching a Lords debate page")

run_page_test(lords_debate_test,
              lambda t,o: check_debate_has_speech(t,o,'Baroness Thornton','concerned about the health information on labels on alcoholic drinks'),
              test_name="Checking speech appears in Lords debate",
              test_short_name="debate-lords-has-speech")

whall_debate_test = run_http_test("/whall/?id=2009-10-27a.47.0",
                                  test_short_name="debate-whall",
                                  test_name="Fetching a Westminster Hall debate page")

run_page_test(whall_debate_test,
              lambda t,o: check_debate_has_speech(t,o,'Chris Mole','undermine the basic affordability of the dualling of the line'),
              test_name="Checking speech appears in Westminster Hall debate",
              test_short_name="debate-whall-has-speech")

scotland_debate_test = run_http_test("/sp/?id=2009-10-28.20531.0",
                                     test_short_name="debate-scotland",
                                     test_name="Fetching a Scottish Parliament debate page")

run_page_test(scotland_debate_test,
              lambda t,o: check_debate_has_speech(t,o,'Fiona Hyslop','have focused in particular on lower-income families'),
              test_name="Checking speech appears in Scottish Parliament debate",
              test_short_name="debate-scotland-has-speech")

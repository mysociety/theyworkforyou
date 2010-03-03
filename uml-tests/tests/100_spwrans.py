from testing import *

# Check a written answer from Scotland:

spwrans_test = run_http_test("/spwrans/?id=2009-10-26.S3W-27797.h",
                             test_name="Testing Scottish written answer",
                             test_short_name="spwrans")

def check_speaker_and_speech_tag(current_test,expected_name, got_name, expected_speech, got_speech_tag):
    if not expected_name == got_name:
        current_test.log("Speaker name didn't match:")
        current_test.log("Expected '"+expected_name+"', but got '"+got_name+"'")
        return False
    if not tag_text_is(got_speech_tag,expected_speech):
        current_test.log("Text didn't match...")
        return False
    return True

def check_written_answer(t,o,q_name,q_text,a_name,a_text):
    labour_speakers = o.soup.findAll(attrs={'class':'speaker labour'})
    t.log("Found these Labour speakers:")
    for l in labour_speakers:
        t.log("  "+l.prettify())
    snp_speakers = o.soup.findAll(attrs={'class':'speaker scottish national party'})
    t.log("Found these SNP speakers:")
    for s in snp_speakers:
        t.log("  "+s.prettify())
    if not 1 == len(labour_speakers):
        t.log("Couldn't find the unique question, should be from a Labour speaker")
        return False
    speaker = labour_speakers[0]
    speaker_name = speaker.contents[0].contents[0].string
    question_tag = next_tag(speaker)
    if not check_speaker_and_speech_tag(t,q_name,speaker_name,q_text,question_tag):
        return False
    speaker = snp_speakers[0]
    speaker_name = speaker.contents[0].contents[0].string
    question_tag = next_tag(speaker)
    if not check_speaker_and_speech_tag(t,a_name,speaker_name,a_text,question_tag):
        return False
    return True

run_page_test(spwrans_test,
              lambda t,o: check_written_answer(t,o,
                                             "Sarah Boyack",
                                             "To ask the Scottish Executive how many properties it has disposed of in the last two years to which section 68 of the Climate Change (Scotland) Act 2009 could have been applied.",
                                             "John Swinney",
                                             "No core Scottish Government-owned buildings, to which section 68 of the Climate Change (Scotland) Act 2009 could have been applied, have been sold by the Scottish Government in the last two years."),
              test_name="Checking text of Scottish Written Answer",
              test_short_name="spwrans-content")

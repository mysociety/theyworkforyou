#
# views.py:
# Views for TheyWorkForYou election quiz Django project
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

import email.utils
import cgi
import datetime
import urllib2
import urllib
import re
import collections
import urllib

from google.appengine.api import urlfetch
from google.appengine.api.datastore_types import Key
from google.appengine.ext import db
from google.appengine.api import mail
from google.appengine.api import memcache

# XXX remember to migrate this when the API becomes stable
from google.appengine.api.labs import taskqueue
from google.appengine.api.urlfetch import fetch

from django.forms.formsets import formset_factory
from django.shortcuts import render_to_response, get_object_or_404
from django.http import HttpResponseRedirect, HttpResponse
from django.conf import settings
import django.forms.util
import django.utils.simplejson as json

from ratelimitcache import ratelimit

import forms
from models import Seat, RefinedIssue, Candidacy, Party, Candidate, SurveyResponse, PostElectionSignup, AristotleToYnmpCandidateMap


#####################################################################
# Candidate survey

# Authenticate a candidate
def _check_auth(post, ip_address, first_auth):
    form = forms.AuthCandidacyForm(post or None)

    if not post:
        return render_to_response('survey_candidacy_auth.html', { 'form': form })

    token = post['token']
    candidacy = Candidacy.find_by_token(token)
    
    if not candidacy:
        return render_to_response('survey_candidacy_auth.html', { 'form': form, 'error': True })

    if first_auth:
        if not candidacy.survey_token_use_count:
            candidacy.survey_token_use_count = 0
        candidacy.survey_token_use_count += 1
        candidacy.log('Survey token authenticated from IP %s' % ip_address)

    return candidacy

# Survey a candidate
@ratelimit(minutes = 2, requests = 40) # stop brute-forcing of token 
def survey_candidacy(request, token = None):
    post = dict(request.POST.items()) or {}
    first_auth = 'auth_submitted' in post # whether is first time they authenticated
    if token:
        post['token'] = token
        first_auth = True

    # Check they have the token
    response = _check_auth(post, request.META['REMOTE_ADDR'], first_auth)
    if not isinstance(response, Candidacy):
        return response
    candidacy = response

    # Have they already filled in?
    if candidacy.survey_filled_in:
        return render_to_response('survey_candidacy_already_done.html', { 'candidate' : candidacy.candidate, 'candidacy' : candidacy })

    # Have they tried to post an answer?
    submitted = 'questions_submitted' in post

    autosave_when = None
    if first_auth:
        # Do we need to load from autosave?
        if candidacy.survey_autosave:
            # with str() cast here we get unicode typed values in result dictionary, and then double escaped unicode in display
            saved = cgi.parse_qs(str(candidacy.survey_autosave)) 
            for k, v in saved.iteritems():
                post[str(k)] = v[0]
            submitted = False
            autosave_when = candidacy.survey_autosave_when
        else:
            # Make sure form definitely doesn't get errors
            post = None

    valid = True
    # Construct array of forms containing all local issues
    local_issues_for_seat = candidacy.seat.refinedissue_set.filter("deleted =", False).fetch(1000)
    local_issue_forms = []
    for issue in local_issues_for_seat:
        form = forms.LocalIssueQuestionForm(post, refined_issue=issue, candidacy=candidacy)
        valid = valid and form.is_valid()
        local_issue_forms.append(form)
    # ... and national issues
    national_seat = db.Query(Seat).filter("name =", "National").get()
    national_issues_for_seat = national_seat.refinedissue_set.filter("deleted =", False).fetch(1000)
    national_issue_forms = []
    for issue in national_issues_for_seat:
        form = forms.NationalIssueQuestionForm(post, refined_issue=issue, candidacy=candidacy)
        valid = valid and form.is_valid()
        national_issue_forms.append(form)
    all_issue_forms = local_issue_forms + national_issue_forms

    # Save the answers to all questions in a transaction 
    if submitted and valid:
        db.run_in_transaction(forms._form_array_save, all_issue_forms)
        candidacy.survey_filled_in = True
        candidacy.survey_filled_in_when = datetime.datetime.now()
        candidacy.log('Survey form completed successfully')
        return render_to_response('survey_candidacy_thanks.html', { 'candidate' : candidacy.candidate })

    # Otherwise log if they submitted an incomplete form
    if submitted and not valid:
        amount_done = forms._form_array_amount_done(all_issue_forms)
        amount_max = len(all_issue_forms)
        candidacy.log('Survey form submitted incomplete, %d/%d questions answered' % (amount_done, amount_max))

    return render_to_response('survey_candidacy_questions.html', {
        'local_issue_forms': local_issue_forms,
        'national_issue_forms': national_issue_forms,
        'unfinished': submitted and not valid,
        'token': candidacy.survey_token,
        'candidacy' : candidacy,
        'candidate' : candidacy.candidate,
        'seat' : candidacy.seat,
        'autosave_when' : autosave_when
    })

# Public information about survey for the seat
def survey_seats_list(request):
    seats = db.Query(Seat).order("name").fetch(1000)

    return render_to_response('survey_candidacy_seats_list.html', {
        'seats' : seats,
    })



def survey_seats(request, code):
    seat = db.Query(Seat).filter("code =", code).get()
    candidacies = seat.candidacy_set.filter('deleted =', False)

    # Construct array of forms containing all local issues
    local_issues_for_seat = seat.refinedissue_set.filter("deleted =", False).fetch(1000)
    local_issue_forms = []
    for issue in local_issues_for_seat:
        form = forms.LocalIssueQuestionForm({}, refined_issue=issue, candidacy=None)
        local_issue_forms.append(form)
    # ... and national issues
    national_seat = db.Query(Seat).filter("name =", "National").get()
    national_issues_for_seat = national_seat.refinedissue_set.filter("deleted =", False).fetch(1000)
    national_issue_forms = []
    for issue in national_issues_for_seat:
        form = forms.NationalIssueQuestionForm({}, refined_issue=issue, candidacy=None)
        national_issue_forms.append(form)
    all_issue_forms = local_issue_forms + national_issue_forms

    return render_to_response('survey_candidacy_seat.html', {
        'local_issue_forms': local_issue_forms,
        'national_issue_forms': national_issue_forms,
        'seat' : seat,
        'candidacies' : candidacies,
    })

# Called by AJAX to automatically keep half filled in forms
def survey_autosave(request, token):
    candidacy = Candidacy.find_by_token(token)
    if not candidacy:
        raise Exception("Invalid token " + token)
    candidacy.survey_autosave = request.POST['ser']
    candidacy.survey_autosave_when = datetime.datetime.now()
    candidacy.put()
    return render_to_response('survey_autosave_ok.html')

# Task to email a candidate a survey
def task_invite_candidacy_survey(request, candidacy_key_name):
    candidacy = Candidacy.get_by_key_name(candidacy_key_name)
    if candidacy.survey_invite_emailed:
        return HttpResponse("Given up, already emailed")
    if candidacy.survey_filled_in:
        return HttpResponse("Given up, already filled in")

    # Get email and name
    to_email = candidacy.candidate.validated_email()
    if not to_email:
        candidacy.log("Abandoned sending survey invite email as email address invalid")
        return HttpResponse("Given up, email address invalid")
    to_name = candidacy.candidate.name

    # Generate candidate auth login URL
    if not candidacy.survey_token:
        candidacy.generate_survey_token()
    url = settings.EMAIL_URL_PREFIX + "/survey/" + candidacy.survey_token

    message = mail.EmailMessage()
    message.sender = settings.TEAM_FROM_EMAIL
    message.subject = "Help your future constituents know your views"
    message.to = email.utils.formataddr((to_name, to_email))
    message.body = """Hi %s,

TheyWorkForYou is inviting all MP candidates to share their positions 
on a range of major national and local issues.

Click this link, it should only take you a few minutes.

%s

Millions of people use TheyWorkForYou to find out about their MPs
every year. Your answers will be used in a quiz to help voters in
your constituency decide who to vote for. If you are elected,
your page on TheyWorkForYou will include your answers, and note
which new MPs declined to participate.

What is unique about this survey is that the local issues, and
indeed much of the entire project, have been provided by a
network of over 5000 new volunteers, under the banner of
Democracy Club. These individuals are looking to you, as a
candidate, to embody the accountability that everyone wants to
see from the new Parliament.

TheyWorkForYou team
on behalf of the voters of %s constituency

    """ % (candidacy.candidate.name, url, candidacy.seat.name)

    candidate_name = "candidate <strong>%s</strong> in seat <strong>%s</strong>.</p>" % (candidacy.candidate.name, candidacy.seat.name)
    message.send()

    candidacy.survey_invite_emailed = True
    candidacy.survey_invite_sent_to_emails.append(to_email)
    candidacy.log("Sent survey invite email to " + to_email)

    text = "</p>Survey invitation sent to %s</p>" % candidate_name
    text += "<pre>%s</pre>" % str(message.body)
    return HttpResponse(text)

# Used by DemocracyClub to measure progress
def survey_stats_json(request):
    candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', False))
    emailed_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', False).filter('survey_invite_emailed =', True))
    posted_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', False).filter('survey_invite_posted =', True))
    filled_in_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', False).filter('survey_filled_in =', True))

    result = { 'candidacy_count': candidacy_count,
               'emailed_candidacy_count': emailed_candidacy_count,
               'posted_candidacy_count': posted_candidacy_count,
               'filled_in_candidacy_count': filled_in_candidacy_count 
    }

    return HttpResponse(json.dumps(result))

# Optionally: ?details=1 in the URL adds more info
def survey_candidacies_json(request):
    candidacies = db.Query(Candidacy).filter('deleted =', False)

    result = []
    for c in candidacies:
        item = { 'ynmp_id': c.ynmp_id,
            'survey_invite_emailed': c.survey_invite_emailed,
            'survey_invite_sent_to_emails': c.survey_invite_sent_to_emails,
            'survey_invite_posted': c.survey_invite_posted,
            'survey_invite_sent_to_addresses': c.survey_invite_sent_to_addresses,
            'survey_filled_in': c.survey_filled_in,
            'survey_filled_in_when': c.survey_filled_in_when and c.survey_filled_in_when.strftime('%Y-%m-%dT%H:%M:%S') or None
        }
        if 'secret' in request.GET and request.GET['secret'] == settings.DEMOCRACYCLUB_SHARED_SECRET:
            assert c.survey_token
            item['survey_token'] = c.survey_token
        if 'details' in request.GET:
            details = {
                'seat_ynmp_id': c.seat.ynmp_id,
                'seat_name': c.seat.name,
                'seat_code': c.seat.code,

                'candidate_ynmp_id': c.candidate.ynmp_id,
                'candidate_name': c.candidate.name,
                'candidate_code': c.candidate.code,

                'candidate_party_ynmp_id': c.candidate.party.ynmp_id,
                'candidate_party_name': c.candidate.party.name,
                'candidate_party_code': c.candidate.party.code,
            }
            item.update(details)
        result.append(item)


    return HttpResponse(json.dumps(result))


# The Guardian: individual candidate responses
# This is called, with an aristotle id as the single parameter, from the Guardian CMS
# The raw_name & raw_constituency_name parameters allow lookups without aristotle, for debugging
def guardian_candidate(request, aristotle_id=None, raw_name=None, raw_const_name=None):    
    constituency_name = raw_const_name or ""
    constituency_aristotle_id = 0
    candidate_name = ""
    found_name = "Not found"
    candidacy = False
    candidate = False
    candidate_id_mapping = False
    error_message = ""
    debug_message = ""
    url_for_seat = ""
    # note these Guardian labels are not quite the same as the TWFY verb defaults
    # And these are set up to start with agreement (hence descending value order)
    result_labels = (
        (100, "Strongly<br/>agree"),
        (75, "Agree<br/>&nbsp;"),
        (50, "Neither&nbsp;agree<br/>nor&nbsp;disagree"),
        (25, "Disagree<br/>&nbsp;"),
        (0, "Strongly<br/>disagree")
        )
    if aristotle_id:
        try:
            aristotle_id = int(aristotle_id)
            candidate_id_mapping = db.Query(AristotleToYnmpCandidateMap).filter("aristotle_id =", aristotle_id).get()
        except exceptions.ValueError:
            aristotle_id = None
            error_message = "Badly formed aristotle_id" # unexpected: urls.py prevents non-digits
        if candidate_id_mapping:
            debug_message = debug_message + (" [map-hit: %s] " % candidate_id_mapping.ynmp_id)
            if candidate_id_mapping.ynmp_id == 0: # explicit block: *never* matches
                error_message = "Map: blocked"
            else:
                candidate = db.Query(Candidate).filter("ynmp_id=", candidate_id_mapping.ynmp_id).get()
        if not candidate and not error_message:
            url = "http://www.guardian.co.uk/politics/api/person/%s/json" % aristotle_id;
            result = urlfetch.fetch(url)
            if result.status_code == 200:
                candidateData = json.loads( result.content )
                if candidateData['person']:
                    candidate_name = candidateData['person']['name']
                if candidate_name == "":
                    error_message = "No name found from Aristotle"
                else:
                    jsonCandidacies = candidateData['person']['candidacies']
                    for c in jsonCandidacies:
                        if c['election']['year'] == "2010":
                            constituency_aristotle_id = c['constituency']['aristotle-id']
                            constituency_name = c['constituency']['name']
                            break
                    if constituency_name == "": # Guardian doesn't believe this is a candidate: don't show
                        error_message = "No 2010 constituency found from Aristotle"
            else:
                error_message = "Aristotle JSON load failed with HTTP status %s" % result.status_code
    elif raw_name:
        candidate_name = raw_name
    if not error_message:
        if not candidate:
            candidate_code = candidate_name.lower().replace(" ", "_")
            debug_message =  debug_message + " [candidate_code=" + candidate_code + "] "
            candidate = db.Query(Candidate).filter("code =", candidate_code).get()
        if candidate:
            if aristotle_id and candidate.ynmp_id and not candidate_id_mapping: # we've discovered a new mapping: save it
                candidate_id_mapping = AristotleToYnmpCandidateMap()
                candidate_id_mapping.aristotle_id = aristotle_id
                candidate_id_mapping.ynmp_id = candidate.ynmp_id
                candidate_id_mapping.put()
                debug_message=debug_message + (" [map-saved %s] " % candidate.ynmp_id)
            # note: there may be multiple candidacies, but for now we take just the one
            candidacy = db.Query(Candidacy).filter("candidate =", candidate).get()
        else:
            # We can't easily search on surname (suffix-searches v. inefficient in datastore)
            # ...unless we added it as another property.
            # so instead find this seat, and check the surnames of candidates in that seat
            # Remember this search is because the name match didn't work already, so let's try surname
            surname = candidate_code.split('_')[-1]
            if constituency_aristotle_id and False: # TODO: lookup in aristotle:ynmp-id map
                seat_ynmp_id = constituency_aristotle_id # would be mapped
                seat = db.Query(Seat).filter("ynmp_id =", seat_ynmp_id).get()
            else:
                seat_code = constituency_name.lower().replace(" ", "_")
                seat = db.Query(Seat).filter("code =", seat_code).get()
            if seat:
                candidacies = db.Query(Candidacy).filter("seat =", seat)
                surname_matches = []
                for c in candidacies:
                    if c.candidate.code.split('_')[-1] == surname:
                        surname_matches.append(c)
                if len(surname_matches) == 0:
                    error_message = "%d matches on surname %s in %s" % (0, surname, seat.name)
                elif len(surname_matches) == 1:
                    candidacy = surname_matches[0]
                else: # surname clash: disambiguate by first name
                    first_name = candidate_code.split('_')[0]
                    first_name_matches = []
                    for c in surname_matches:
                        if c.candidate.code.split('_')[0] == first_name:
                            first_name_matches.append(c)
                    if len(first_name_matches) == 1:
                        candidacy = first_name_matches[0]
                    else: 
                        # TODO: really should disambigiute on full name here (may have middle initial?)
                        # TODO: or maybe compare first initials which may be unique?
                        error_message = "%d matches on surname %s, %d matches on first name, in %s" % (len(surname_matches), len(first_name_matches), surname, seat.name)
                        candidacy = False
            else:
                error_message = "No exact name match (%s), no seat found matching (%s)" % (candidate_code, seat_code)
        if candidacy:
            found_name = candidacy.candidate.name
            url_for_seat = "http://election.theyworkforyou.com/quiz/seats/%s" % candidacy.seat.code
    # TODO: some of these strings are included in HTML comment: should check for and collapse "--"         
    if not error_message:
        error_message = "OK"
    return render_to_response('guardian_candidate.html', {
      'name_canonical': found_name, 
      'candidacy': candidacy, 
      'url_for_seat': url_for_seat,
      'result_labels': result_labels,
      'error_message': error_message,
      'debug_message': "aristotle_id=%s, raw_name=%s, raw_const_name=%s\n  %s" % (aristotle_id, raw_name, raw_const_name, debug_message)
    })


#####################################################################
# Administration interface

# Count number of items a query would return
def get_count(q):
    q.order('__key__')
    r = q.fetch(1000)
    count = 0 
    while True:
        count += len(r)
        if len(r) < 1000:
            break
        q.filter('__key__ >', r[-1])
        r = q.fetch(1000)
    return count

# Administrator functions
def admin_index(request):
    return render_to_response('admin_index.html', { })

def admin_stats(request):
    party_count = get_count(db.Query(Party, keys_only=True))

    candidate_count = get_count(db.Query(Candidate, keys_only=True))
    seat_count = get_count(db.Query(Seat, keys_only=True))
    frozen_seat_count = get_count(db.Query(Seat, keys_only=True).filter('frozen_local_issues =', True))

    candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', False))
    deleted_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted =', True))
    emailed_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', False).filter('survey_invite_emailed =', True))
    deleted_emailed_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', True).filter('survey_invite_emailed =', True))
    posted_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', False).filter('survey_invite_posted =', True))
    deleted_posted_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', True).filter('survey_invite_posted =', True))
    filled_in_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', False).filter('survey_filled_in =', True))
    deleted_filled_in_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', True).filter('survey_filled_in =', True))

    refined_issue_count = get_count(db.Query(RefinedIssue, keys_only=True).filter('deleted = ', False))
    deleted_refined_issue_count = get_count(db.Query(RefinedIssue, keys_only=True).filter('deleted = ', True))

    survey_response_count = get_count(db.Query(SurveyResponse, keys_only=True))

    return render_to_response('admin_stats.html', { 
        'party_count': party_count,
        'candidate_count': candidate_count, 
        'seat_count': seat_count,
            'frozen_seat_count': frozen_seat_count,
        'candidacy_count': candidacy_count, 
            'deleted_candidacy_count': deleted_candidacy_count,
        'emailed_candidacy_count': emailed_candidacy_count, 
            'deleted_emailed_candidacy_count': deleted_emailed_candidacy_count,
        'posted_candidacy_count': posted_candidacy_count, 
            'deleted_posted_candidacy_count': deleted_posted_candidacy_count,
        'filled_in_candidacy_count': filled_in_candidacy_count, 
            'deleted_filled_in_candidacy_count': deleted_filled_in_candidacy_count,
        'refined_issue_count': refined_issue_count,
            'deleted_refined_issue_count': deleted_refined_issue_count,
        'survey_response_count': survey_response_count, 
    })

def admin_responses(request):
    MAX_RESPONSES = 20
    candidacies = db.Query(Candidacy).filter('survey_filled_in =', True).order('-survey_filled_in_when').fetch(MAX_RESPONSES)
    return render_to_response('admin_responses.html', { 
        'candidacies': candidacies,
        'max_responses': MAX_RESPONSES
    })

#####################################################################
# Voter quiz

# Postcode form on quiz
def quiz_ask_postcode(request):
    form = forms.QuizPostcodeForm(request.POST or None)

    if request.method == 'POST':
        if form.is_valid():
            postcode = forms._urlise_postcode(form.cleaned_data['postcode'])
            return HttpResponseRedirect('/quiz/' + postcode)

    return render_to_response('quiz_ask_postcode.html', { 
        'form': form,
    })

# Helper for quiz_main
def _get_entry_for_issue(candidacies_by_key, all_responses, candidacies_with_response_key, issue_model):
    issue = { 
        'short_name': issue_model.short_name, 
        'question': issue_model.question 
    }
    candidacies_with_response = []
    for response in all_responses:
        candidacy_key = str(SurveyResponse.candidacy.get_value_for_datastore(response))
        if candidacy_key in candidacies_by_key and str(SurveyResponse.refined_issue.get_value_for_datastore(response)) == str(issue_model.key()):
            candidacy = candidacies_by_key[candidacy_key] # grab cached version, so doesn't have to get from the database again
            assert response.agreement in [0,25,50,75,100]
            candidacies_with_response.append( {
                    'name': candidacy.candidate.name,
                    'party': candidacy.candidate.party.name,
                    'code': candidacy.candidate.code,
                    'image_url': candidacy.candidate.image_url(),
                    'party_image_url': candidacy.candidate.party.image_url(),
                    'agreement_verb': response.verb(),
                    'agreement': response.agreement,
                    'more_explanation': re.sub("\s+", " ",response.more_explanation.strip())
                }
            )
        candidacies_with_response_key.add(candidacy_key)
    issue['candidacies'] = candidacies_with_response
    return issue

def quiz_by_code(request, code):
    seat = db.Query(Seat).filter("code =", code).get()
    return quiz_main(request, seat, "")

def quiz_by_postcode(request, postcode):
    seat = forms._postcode_to_constituency(postcode)
    if seat == None:
        raise Exception("Seat not found")
    return quiz_main(request, seat, postcode)

# Used for quiz_main below
def _get_quiz_main_params(seat):
    # find all the candidates
    candidacies = seat.candidacy_set.filter("deleted = ", False).fetch(1000)
    candidacies_by_key = {}
    for c in candidacies:
        candidacies_by_key[str(c.key())] = c
    candidacies_key = set(candidacies_by_key.keys())

    # local and national issues for the seat
    national_issues = db.Query(RefinedIssue).filter('national =', True).filter("deleted =", False).fetch(1000)
    local_issues = seat.refinedissue_set.filter("deleted =", False).fetch(1000)

    # responses candidates have made
    all_responses = db.Query(SurveyResponse).filter('candidacy in', candidacies).fetch(1000) # this is slow on dev server, much faster on live

    candidacies_with_response_key = set()
    # construct dictionaries with all the information in 
    national_answers = []
    for national_issue in national_issues:
        new_entry = _get_entry_for_issue(candidacies_by_key, all_responses, candidacies_with_response_key, national_issue)
        national_answers.append(new_entry)
    local_answers = []
    for local_issue in local_issues:
        new_entry = _get_entry_for_issue(candidacies_by_key, all_responses, candidacies_with_response_key, local_issue)
        local_answers.append(new_entry)

    # work out who didn't give a response
    candidacies_without_response_key = candidacies_key.difference(candidacies_with_response_key)
    candidacies_without_response = [ { 
        'name': candidacies_by_key[k].candidate.name, 
        'party': candidacies_by_key[k].candidate.party.name,
        'image_url': candidacies_by_key[k].candidate.image_url(),
        'party_image_url': candidacies_by_key[k].candidate.party.image_url(),
        'yournextmp_url': candidacies_by_key[k].candidate.yournextmp_url(),
        'hassle_url': seat.democracyclub_url() + "#candidates",
        'survey_invite_emailed': candidacies_by_key[k].survey_invite_emailed,
        'survey_invite_posted': candidacies_by_key[k].survey_invite_posted
    } for k in candidacies_without_response_key]

    return { 
        'candidacies_without_response' : candidacies_without_response,
        'candidacy_count' : len(candidacies),
        'candidacy_with_response_count' : len(candidacies) - len(candidacies_without_response),
        'candidacy_without_response_count' : len(candidacies_without_response),
        'national_answers' : national_answers,
        'local_answers' : local_answers,
        'local_issues_count' : len(local_issues),
    }

def _get_quiz_main_params_memcached(seat):
    key = "quiz_main_seat_" + seat.key().name()
    data = memcache.get(key)
    if data is not None:
        return data
    else:
        data = _get_quiz_main_params(seat)
        memcache.add(key, data, 60 * 10) # 10 minute cache
        return data

# For voters to learn about candidates
def quiz_main(request, seat, postcode):
    display_postcode = ""
    url_postcode = ""
    if postcode:
        display_postcode = forms._canonicalise_postcode(postcode)
        url_postcode = forms._urlise_postcode(postcode)

    params = _get_quiz_main_params_memcached(seat)

    subscribe_form = forms.MultiServiceSubscribeForm(initial={ 
        'postcode': display_postcode,
        'democlub_signup': True,
        'twfy_signup': True,
        'hfymp_signup': True,
    })

    params['seat'] = seat
    params['postcode'] = postcode
    params['subscribe_form'] = subscribe_form

    return render_to_response('quiz_main.html', params)

# Subscribing to DemocracyClub / TheyWorkForYou / HearFromYourMP
def quiz_subscribe(request):
    subscribe_form = forms.MultiServiceSubscribeForm(request.POST)
    if subscribe_form.is_valid():
        name = subscribe_form.cleaned_data['name']
        email = subscribe_form.cleaned_data['email']
        postcode = subscribe_form.cleaned_data['postcode']
        post_election_signup = PostElectionSignup(
                name = name,
                email = email,
                postcode = postcode,
                theyworkforyou = subscribe_form.cleaned_data['twfy_signup'],
                hearfromyourmp = subscribe_form.cleaned_data['hfymp_signup'],
        )
        post_election_signup.put()

        if subscribe_form.cleaned_data['democlub_signup']:
            (first_name, space, last_name) = name.partition(" ")
            fields = { 
                'first_name' : first_name,
                'last_name' : last_name,
                'email' : email,
                'postcode' : postcode
            }
            form_data = urllib.urlencode(fields)
            result = urlfetch.fetch(url = "http://www.democracyclub.org.uk", 
                    payload = form_data,
                    method = urlfetch.POST)
            if result.status_code != 200:
                raise Exception("Error posting to DemocracyClub")

        candidacy_without_response_count = request.POST.get('candidacy_without_response_count',0)
        seat = forms._postcode_to_constituency(postcode)

        democlub_redirect = subscribe_form.cleaned_data['democlub_redirect']
        if democlub_redirect:
            democlub_hassle_url = seat.democracyclub_url() + "?email=%s&postcode=%s&name=%s" % (
                    urllib.quote_plus(email), urllib.quote_plus(postcode), urllib.quote_plus(name)
            )
            return HttpResponseRedirect(democlub_hassle_url)

        # For later when we have form even if have got all candidates answers
        return render_to_response('quiz_subscribe_thanks.html', { 
            'twfy_signup':  subscribe_form.cleaned_data['twfy_signup'],
            'hfymp_signup':  subscribe_form.cleaned_data['hfymp_signup'],
            'democlub_signup':  subscribe_form.cleaned_data['democlub_signup'],
            'seat': seat,
            'email': email,
            'urlise_postcode': forms._urlise_postcode(postcode),
            'name': name,
            'candidacy_without_response_count':candidacy_without_response_count
        } )

    return render_to_response('quiz_subscribe.html', {
        'subscribe_form' : subscribe_form
    })
    


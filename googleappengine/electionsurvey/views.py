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
import re

from google.appengine.api import urlfetch
from google.appengine.api.datastore_types import Key
from google.appengine.ext import db
from google.appengine.api import mail

# XXX remember to migrate this when the API becomes stable
from google.appengine.api.labs import taskqueue

from django.forms.formsets import formset_factory
from django.shortcuts import render_to_response, get_object_or_404
from django.http import HttpResponseRedirect, HttpResponse
from django.conf import settings
import django.forms.util
import django.utils.simplejson as json

from ratelimitcache import ratelimit

import forms
from models import Seat, RefinedIssue, Candidacy, Party, Candidate, SurveyResponse

# Front page of election site
def index(request):
    return render_to_response('index.html', {})

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

TheyWorkForYou is inviting all PPCs to share their positions on a
range of major national and local issues.

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
    filled_in_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', False).filter('survey_filled_in =', True))

    result = { 'candidacy_count': candidacy_count,
               'emailed_candidacy_count': emailed_candidacy_count,
               'filled_in_candidacy_count': filled_in_candidacy_count }

    return HttpResponse(json.dumps(result))

# Optionally: ?details=1 in the URL adds more info
def survey_candidacies_json(request):
    candidacies = db.Query(Candidacy).filter('deleted =', False)

    result = []
    for c in candidacies:
        item = { 'ynmp_id': c.ynmp_id,

            'survey_invite_emailed': c.survey_invite_emailed,
            'survey_invite_sent_to_emails': c.survey_invite_sent_to_emails,
            'survey_filled_in': c.survey_filled_in,
            'survey_filled_in_when': c.survey_filled_in_when and c.survey_filled_in_when.strftime('%Y-%m-%dT%H:%M:%S') or None
        }
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

    candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', False))
    deleted_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted =', True))
    emailed_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', False).filter('survey_invite_emailed =', True))
    deleted_emailed_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', True).filter('survey_invite_emailed =', True))
    filled_in_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', False).filter('survey_filled_in =', True))
    deleted_filled_in_candidacy_count = get_count(db.Query(Candidacy, keys_only=True).filter('deleted = ', True).filter('survey_filled_in =', True))

    refined_issue_count = get_count(db.Query(RefinedIssue, keys_only=True).filter('deleted = ', False))
    deleted_refined_issue_count = get_count(db.Query(RefinedIssue, keys_only=True).filter('deleted = ', True))

    survey_response_count = get_count(db.Query(SurveyResponse, keys_only=True))

    return render_to_response('admin_stats.html', { 
        'party_count': party_count,
        'candidate_count': candidate_count, 
        'seat_count': seat_count,
        'candidacy_count': candidacy_count, 
            'deleted_candidacy_count': deleted_candidacy_count,
        'emailed_candidacy_count': emailed_candidacy_count, 
            'deleted_emailed_candidacy_count': deleted_emailed_candidacy_count,
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
def quiz_index(request):
    form = forms.QuizPostcodeForm(request.POST or None)

    if request.method == 'POST':
        if form.is_valid():
            postcode = forms._urlise_postcode(form.cleaned_data['postcode'])
            return HttpResponseRedirect('/quiz/' + postcode)

    return render_to_response('quiz_index.html', { 
        'form': form,
    })

def quiz_main(request, postcode):
    display_postcode = forms._canonicalise_postcode(postcode)
    url_postcode = forms._urlise_postcode(postcode)
    seat = forms._postcode_to_constituency(postcode)

    valid = True
    # Construct array of forms containing all local issues
    local_issues_for_seat = seat.refinedissue_set.filter("deleted =", False).fetch(1000)
    local_issue_forms = []
    for issue in local_issues_for_seat:
        form = forms.LocalIssueQuestionForm(request.POST, refined_issue=issue, candidacy=None)
        valid = valid and form.is_valid()
        local_issue_forms.append(form)
    # ... and national issues
    national_seat = db.Query(Seat).filter("name =", "National").get()
    national_issues_for_seat = national_seat.refinedissue_set.filter("deleted =", False).fetch(1000)
    national_issue_forms = []
    for issue in national_issues_for_seat:
        form = forms.NationalIssueQuestionForm(request.POST, refined_issue=issue, candidacy=None)
        valid = valid and form.is_valid()
        national_issue_forms.append(form)
    #all_issue_forms = local_issue_forms + national_issue_forms

    return render_to_response('quiz_main.html', {
        'local_issue_forms': local_issue_forms,
        'national_issue_forms': national_issue_forms,
        'seat' : seat,
        'candidacy_count' : seat.candidacy_set.filter("deleted = ", False).count(),
        'local_issue_count' : seat.candidacy_set.filter("deleted = ", False).count(),
        'postcode' : postcode
    })


    raise Exception(seat.name)







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

from ratelimitcache import ratelimit

import forms
from models import Seat, RefinedIssue, Candidacy

# Front page of election site
def index(request):
    return render_to_response('index.html', {})

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
    post = request.POST or {}
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

    # Do we need to load from autosave?
    autosave_when = None
    if first_auth and candidacy.survey_autosave:
        saved = cgi.parse_qs(candidacy.survey_autosave)
        for k, v in saved.iteritems():
            post[str(k)] = v[0]
        submitted = True
        autosave_when = candidacy.survey_autosave_when

    valid = True
    # Construct array of forms containing all local issues
    local_issues_for_seat = candidacy.seat.refinedissue_set.filter("deleted =", False).fetch(1000)
    local_issue_forms = []
    for issue in local_issues_for_seat:
        form = forms.LocalIssueQuestionForm(submitted and post or None, refined_issue=issue, candidacy=candidacy)
        valid = valid and form.is_valid()
        local_issue_forms.append(form)
    # ... and national issues
    national_seat = db.Query(Seat).filter("name =", "National").get()
    national_issues_for_seat = national_seat.refinedissue_set.filter("deleted =", False).fetch(1000)
    national_issue_forms = []
    for issue in national_issues_for_seat:
        form = forms.NationalIssueQuestionForm(submitted and post or None, refined_issue=issue, candidacy=candidacy)
        valid = valid and form.is_valid()
        national_issue_forms.append(form)
    all_issue_forms = local_issue_forms + national_issue_forms

    # Save the answers to all questions in a transaction 
    if submitted and valid:
        db.run_in_transaction(forms._form_array_save, all_issue_forms)
        candidacy.survey_filled_in = True
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

TheyWorkForYou is inviting all PPCs to tell their voters
their views on important national and local issues.

Click this link, it should only take you a few minutes.

%s

Millions of people use TheyWorkForYou to find out about their MP
every year. Your answers will be used by voters in your constituency
in the run up to the election, and if you become an MP they will appear 
on your record on TheyWorkForYou.

TheyWorkForYou team
on behalf of the voters of %s constituency

    """ % (candidacy.candidate.name, url, candidacy.seat.name)

    candidate_name = "candidate <strong>%s</strong> in seat <strong>%s</strong>.</p>" % (candidacy.candidate.name, candidacy.seat.name)
    message.send()

    candidacy.survey_invite_emailed = True
    candidacy.log("Sent survey invite email")

    text = "</p>Survey invitation sent to %s</p>" % candidate_name
    text += "<pre>%s</pre>" % str(message.body)
    return HttpResponse(text)


# Administrator functions
def admin(request):
    return render_to_response('admin_index.html', { })







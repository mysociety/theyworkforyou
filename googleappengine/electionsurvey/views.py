#
# views.py:
# Views for TheyWorkForYou election quiz Django project
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

import email.utils

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
def _check_auth(post, ip_address):
    form = forms.AuthCandidacyForm(post or None)

    if not post:
        return render_to_response('survey_candidacy_auth.html', { 'form': form })

    token = post['token']
    candidacy = Candidacy.find_by_token(token)
    
    if not candidacy:
        return render_to_response('survey_candidacy_auth.html', { 'form': form, 'error': True })

    if 'auth_submitted' in post:
        if not candidacy.survey_token_use_count:
            candidacy.survey_token_use_count = 0
        candidacy.survey_token_use_count += 1
        candidacy.log('Survey token authenticated from IP %s' % ip_address)

    return candidacy

# Survey a candidate
@ratelimit(minutes = 2, requests = 40) # stop brute-forcing of token 
def survey_candidacy(request, token = None):
    post = request.POST or {}
    if token:
        post['token'] = token

    # Check they have the token
    response = _check_auth(post, request.META['REMOTE_ADDR'])
    if not isinstance(response, Candidacy):
        return response
    candidacy = response

    # Have they tried to post an answer?
    submitted = 'questions_submitted' in post

    # Construct array of forms containing all local issues
    issues_for_seat = candidacy.seat.refinedissue_set.fetch(1000)
    issue_forms = []
    valid = True
    for issue in issues_for_seat:
        form = forms.LocalIssueQuestionForm(submitted and post or None, refined_issue=issue, candidacy=candidacy)
        valid = valid and form.is_valid()
        issue_forms.append(form)

    # Save the answers to all questions in a transaction 
    if submitted and valid:
        db.run_in_transaction(forms._form_array_save, issue_forms)
        candidacy.survey_filled_in = True
        candidacy.log('Survey form completed successfully')
        return render_to_response('survey_candidacy_thanks.html', { 'candidate' : candidacy.candidate })

    # Otherwise log if they submitted an incomplete form
    if submitted and not valid:
        amount_done = forms._form_array_amount_done(issue_forms)
        amount_max = len(issue_forms)
        candidacy.log('Survey form submitted incomplete, %d/%d questions answered' % (amount_done, amount_max))

    return render_to_response('survey_candidacy_questions.html', {
        'issue_forms': issue_forms,
        'unfinished': submitted and not valid,
        'token': candidacy.survey_token,
        'candidacy' : candidacy,
        'candidate' : candidacy.candidate,
        'seat' : candidacy.seat
    })

# Task to email a candidate a survey
def task_invite_candidacy_survey(request, candidacy_id):
    candidacy = Candidacy.get_by_key_name(candidacy_id)
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
    form = forms.EmailSurveyToCandidacies(request.POST or None)
    dry_run = False
    candidacies = None

    # Send to people
    if request.POST and 'email_survey_submitted' in request.POST:
        if form.is_valid():
            seat_id = form.cleaned_data['constituency']
            limit = form.cleaned_data['limit']

            candidacies = db.Query(Candidacy)
            if seat_id != 'all':
                seat = Seat.get_by_key_name(seat_id) 
                candidacies.filter("seat = ", seat.key())

            candidacies.filter("survey_invite_emailed = ", False)

            candidacies = candidacies.fetch(limit)

            if 'dry_run' in request.POST:
                dry_run = True
            elif 'submit' in request.POST:
                c = 0
                for candidacy in candidacies:
                    if candidacy.candidate.validated_email():
                        c += 1
                        taskqueue.add(url='/task/invite_candidacy_survey/' + str(candidacy.id), countdown=c)
            else:
                raise Exception("Needs to either be dry_run or submit")

    return render_to_response('admin_index.html', {
        'form' : form, 
        'candidacies' : candidacies,
        'candidacies_len' : candidacies and len(candidacies),
        'dry_run' : dry_run
    })







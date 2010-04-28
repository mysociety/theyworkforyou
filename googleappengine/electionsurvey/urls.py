from django.conf.urls.defaults import *
from django.views.generic.simple import direct_to_template, redirect_to
from django.http import HttpResponseRedirect, HttpResponsePermanentRedirect

import views
import models

# Uncomment the next two lines to enable the admin:
# from django.contrib import admin
# admin.autodiscover()

urlpatterns = patterns('',
    url(r'^$', views.index),

    url(r'^survey$', views.survey_candidacy),
    url(r'^survey/autosave/(?P<token>.+)$', views.survey_autosave),
    url(r'^survey/stats.json$', views.survey_stats_json),
    url(r'^survey/candidacies.json$', views.survey_candidacies_json),
    url(r'^survey/seats$', views.survey_seats_list),
    url(r'^survey/seats/(?P<code>.+)$', views.survey_seats),
    url(r'^survey/(?P<token>.+)$', views.survey_candidacy),

    url(r'^survey/$', redirect_to, {'url' : '/survey'} ),
    url(r'^survey/seat/$', redirect_to, {'url' : '/survey/seat'} ),

    url(r'^quiz$', views.quiz_ask_postcode),
    url(r'^quiz/subscribe$', views.quiz_subscribe),
    url(r'^quiz/(?P<postcode>.+)$', views.quiz_main),

    url(r'^quiz/$', redirect_to, {'url' : '/quiz'} ),

    url(r'^admin/?$', redirect_to, {'url' : '/admin/index'} ),
    url(r'^admin/index$', views.admin_index),
    url(r'^admin/stats$', views.admin_stats),
    url(r'^admin/responses$', views.admin_responses),

    url(r'^task/invite_candidacy_survey/(?P<candidacy_key_name>[\d-]+)$', views.task_invite_candidacy_survey),

    url(r'^guardian_candidate/(?P<aristotle_id>\d+)$', views.guardian_candidate),
    url(r'^guardian_candidate/(?P<raw_name>.+)/(?P<raw_const_name>.+)$', views.guardian_candidate),
    url(r'^guardian_candidate/(?P<raw_name>.+)$', views.guardian_candidate),

    # url(r'^fooble$', views.fooble),

    # Example:
    # (r'^electionsurvey/', include('electionsurvey.foo.urls')),

    # Uncomment the admin/doc line below and add 'django.contrib.admindocs' 
    # to INSTALLED_APPS to enable admin documentation:
    # (r'^admin/doc/', include('django.contrib.admindocs.urls')),

    # Uncomment the next line to enable the admin:
    # (r'^admin/', include(admin.site.urls)),
)

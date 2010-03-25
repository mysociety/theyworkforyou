from django.conf.urls.defaults import *
from django.views.generic.simple import direct_to_template
from django.http import HttpResponseRedirect, HttpResponsePermanentRedirect

import views
import models

# Uncomment the next two lines to enable the admin:
# from django.contrib import admin
# admin.autodiscover()

urlpatterns = patterns('',
    url(r'^$', views.index),

    url(r'^survey$', views.survey_candidacy),
    url(r'^survey/(?P<token>.+)$', views.survey_candidacy),

    url(r'^survey/$', lambda r: HttpResponsePermanentRedirect('/survey')),

    url(r'^admin/invite_candidacy_survey$', views.admin_invite_candidacy_survey),

    # url(r'^fooble$', views.fooble),

    # Example:
    # (r'^electionsurvey/', include('electionsurvey.foo.urls')),

    # Uncomment the admin/doc line below and add 'django.contrib.admindocs' 
    # to INSTALLED_APPS to enable admin documentation:
    # (r'^admin/doc/', include('django.contrib.admindocs.urls')),

    # Uncomment the next line to enable the admin:
    # (r'^admin/', include(admin.site.urls)),
)

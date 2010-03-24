from django.conf.urls.defaults import *
from django.views.generic.simple import direct_to_template
from django.http import HttpResponseRedirect

import views
import models

# Uncomment the next two lines to enable the admin:
# from django.contrib import admin
# admin.autodiscover()

urlpatterns = patterns('',
    url(r'^$', views.index),

    url(r'^survey$', views.survey_candidacy),
    url(r'^survey/thanks$', views.survey_candidacy_thanks),

    url(r'^survey/$', lambda response: HttpResponseRedirect('/survey')),

    # url(r'^fooble$', views.fooble),

    # Example:
    # (r'^electionsurvey/', include('electionsurvey.foo.urls')),

    # Uncomment the admin/doc line below and add 'django.contrib.admindocs' 
    # to INSTALLED_APPS to enable admin documentation:
    # (r'^admin/doc/', include('django.contrib.admindocs.urls')),

    # Uncomment the next line to enable the admin:
    # (r'^admin/', include(admin.site.urls)),
)

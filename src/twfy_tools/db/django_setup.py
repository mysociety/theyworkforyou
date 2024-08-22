"""
This is a simple minimal setup for using Django ORMs.
Import this being creating models and then the models can be used as normal.
"""

import os

import django
from django.conf import settings

from twfy_tools.common.config import config

# Allow use in notebooks
os.environ["DJANGO_ALLOW_ASYNC_UNSAFE"] = "true"

if not settings.configured:
    settings.configure(
        DEBUG=True,
        SECRET_KEY="your-secret-key",
        ALLOWED_HOSTS=["*"],
        INSTALLED_APPS=[
            "twfy_tools",
        ],
        DATABASES={
            "default": {
                "ENGINE": "django.db.backends.mysql",
                "NAME": config.TWFY_DB_NAME,
                "USER": config.TWFY_DB_USER,
                "PASSWORD": config.TWFY_DB_PASS,
                "HOST": config.TWFY_DB_HOST,
                "PORT": config.TWFY_DB_PORT,
            }
        },
    )

django.setup()

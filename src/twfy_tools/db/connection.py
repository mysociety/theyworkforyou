"""
Basic database connection for TheyWorkForYou database.
"""

from typing import cast

import MySQLdb
from sqlalchemy import URL, create_engine

from ..common.config import config


def get_twfy_db_connection() -> MySQLdb.Connection:
    db_connection = cast(
        MySQLdb.Connection,
        MySQLdb.connect(
            host=config.TWFY_DB_HOST,
            db=config.TWFY_DB_NAME,
            user=config.TWFY_DB_USER,
            passwd=config.TWFY_DB_PASS,
            charset="utf8",
        ),
    )
    return db_connection


engine = create_engine(
    URL.create(
        drivername="mysql+mysqldb",
        username=config.TWFY_DB_USER,
        password=config.TWFY_DB_PASS,
        host=config.TWFY_DB_HOST,
        database=config.TWFY_DB_NAME,
    )
)

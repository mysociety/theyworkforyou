#!/usr/bin/python

from BeautifulSoup import BeautifulSoup

doc = """<html>
<meta http-equiv="Content-type" content="text/html; charset=Windows-1252">
Sacr\xe9 bleu!
</html>"""
print BeautifulSoup(doc).prettify()

doc = """<html>
<meta http-equiv="Content-type" content="text/html; charset=windows-1252">
Sacr\xe9 bleu!
</html>"""

print BeautifulSoup(doc, fromEncoding='windows-1252').prettify()


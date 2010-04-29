*** More, better information avaialable here:
*** https://secure.mysociety.org/intranet/ProductionSites/TheyWorkForYou/GuardianElectionSurvey


The Guardian hit the election survey results from within their CMS, at the bottom 
of every "Politcal Person" page.
Currently, we return empty content if there is no result to show (i.e., not a 404).
This content contains an HTML comment indicating why there's no content.
See guardian_candidate in views.py, but generally problems may be:

  * couldn't find anyone matching the aristotle-ID (most unlikely since the page exists on their site)
  * aristotle data doesn't state this person as a 2010 electoral candidate
  * found the aristotle name, but couldn't find a match in our data
    - most likely: surname spelt differently
    - slightly less likely: couldn't find the constituency (search by name)
  * found everything, but the candidate did not answer the survey
  
The HTML comment also indicates whether or not the aristotle-id was in the map
(if it was, the number shown in the YNMP-id it's mapped to), or, if it wasn't,
it'll say that it saved it.

Once an aristotle-id is mapped to a YNMP-id, that mapping will *always* be used
instead of the searching algorithm. You may need to manually delete a key if it's
stopping a better match being found; conversely if you add the id it will always
return the (mapped) candidate.

==========================================================================
This is the microapp definition:
name:--------------------------------------------------------------------
twfy-candidate-survey

provider:----------------------------------------------------------------
mySociety.org

rootURI:-----------------------------------------------------------------
http://election.theyworkforyou.com/

XML:--------------------------------------------------------------------
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<microapp interface-version="1" name="twfy-candidate-survey" provider="mySociety.org" version="1.0">
    <components>
        <component-definition
                display-name="Your Next MP Survey Results"
                name="twfy-survey-results"
                path="guardian_candidate/{key:aristotle.person_id}"
                widths="460"/>
    </components>
</microapp>

Root CMS Path:-----------------------------------------------------------
/Guardian/twfysurve
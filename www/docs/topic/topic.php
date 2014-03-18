<?php

/**
 * Topic Pages
 *
 * Controller for all topics.
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

// Disable the old PAGE or NEWPAGE classes.
$new_style_template = TRUE;

// Include all the things this page needs.
include_once '../../includes/easyparliament/init.php';

// Topics
$topics = array(

    'benefits' => array(
        'title'     => 'Benefits',
        'blurb'     => 'Benefits are a major political issue right now - they are mentioned a lot
            in Parliament, so it can be hard to know exactly where to find the
            important debates.',
        'policyset' => 'welfare',
        'actions'   => array(

            array(
                'title' => 'Universal Credit Regulations',
                'icon'  => 'comment-quotes',
                'href'  => 'http://www.theyworkforyou.com/lords/?id=2013-02-13a.664.3',
                'blurb' => 'Lords debate, and approve, the consolidation of all benefits into the
                             Universal Credit system.'
            ),

            array(
                'title' => 'Welfare Benefits Up-rating Bill',
                'icon'  => 'page',
                'href'  => 'http://www.theyworkforyou.com/lords/?id=2013-02-11a.457.8',
                'blurb' => 'Lords debate a cap on annual increases to working-age benefits.'
            ),

            array(
                'title' => 'Search the whole site',
                'icon'  => 'magnifying-glass',
                'href'  => 'http://www.theyworkforyou.com/search/?s=%22benefits%22',
                'blurb' => 'Search TheyWorkForYou to find mentions of benefits from all areas of the UK parliament. You may also filter your results by time, speaker and section.'
            ),

            array(
                'title' => 'Sign up for email alerts',
                'icon'  => 'megaphone',
                'href'  => 'http://www.theyworkforyou.com/alert/?alertsearch=%22benefits%22',
                'blurb' => 'We&rsquo;ll let you know every time benefits are mentioned in Parliament.'
            )

        )

    )

);

// Grab the topic name from the variable
$topicname = get_http_var('topic');

// Make sure the requested topic actually exists, otherwise throw a 404.
if (isset ($topics[$topicname]))
{

    // Set the actual topic data.
    $data = $topics[$topicname];

    // Assume, unless we hear otherwise, that we don't want the postcode form displayed.
    $data['display_postcode_form'] = false;

    // Is there a specified set of policy positions to worry about?
    if (isset ($data['policyset'])) {

        include_once INCLUDESPATH . 'easyparliament/member.php';

        // Check to see if there's a submitted postcode to try determine policy positions.
        if (get_http_var('pc') != '')
        {

            // Try all this, as it might go wrong.
            try {
                // User has submitted a postcode, so we want to display that.
                $pc = get_http_var('pc');
                $pc = preg_replace('#[^a-z0-9]#i', '', $pc);
                if (validate_postcode($pc)) {
                    twfy_debug ('MP', "MP lookup by postcode");
                    $constituency = strtolower(postcode_to_constituency($pc));
                    if ($constituency == "connection_timed_out") {
                        throw new Exception('Sorry, we couldn&rsquo;t check your postcode right now, as our postcode lookup server is under quite a lot of load.');
                    } elseif ($constituency == "") {
                        throw new Exception('Sorry, ' . htmlentities($pc) . ' isn&rsquo;t a known postcode');
                        twfy_debug ('MP', "Can't display an MP, as submitted postcode didn't match a constituency");
                    } else {
                        // Generate the Member object
                        $member = new Member(array('constituency' => $constituency, 'house' => HOUSE_TYPE_COMMONS));
                        if ($member->person_id()) {
                            // This will cookie the postcode.
                            $member->set_postcode_cookie($pc);
                        }
                    }
                } else {
                    throw new Exception('Sorry, ' . htmlentities($pc) . ' isn&rsquo;t a valid postcode');
                    twfy_debug ('MP', "Can't display an MP because the submitted postcode wasn't of a valid form.");
                }
            } catch (Exception $e) {
                Renderer::output('topic/error', array('error' => $e->getMessage()));
            }

        }

        /////////////////////////////////////////////////////////
        // DOES THE USER HAVE A POSTCODE ALREADY SET?
        elseif ($THEUSER->postcode_is_set())
        {
            $member = new Member(array('postcode' => $THEUSER->postcode(), 'house' => HOUSE_TYPE_COMMONS));
        }

        /////////////////////////////////////////////////////////
        // NO POSTCODE, NO EXISTING USER, DISPLAY THE FORM
        else {
            $data['display_postcode_form'] = true;
        }
    }

    // If a member exists then we can go and do policies
    if (isset($member)) {

        $data['member_name'] = $member->full_name();
        $data['member_id'] = $member->member_id();
        $data['member_url'] = $member->url();

        // Build the policy set
        $policies = new Policies;
        $policies = $policies->limitToSet($data['policyset']);

        // Grab extra member info
        // TODO: Shouldn't this be loaded on request?
        $member->load_extra_info();

        // Get their position on relevant policies!
        $policyPositions = new PolicyPositions($policies, $member);

        $data['positions'] = $policyPositions->positions;
        $data['sinceString'] = $policyPositions->sinceString;

    }

    // Send for rendering!
    Renderer::output('topic/topic', $data);

} else {

    header('HTTP/1.0 404 Not Found');
    Renderer::output('topic/error', array('error' => 'Sorry, but there isn&rsquo;t a topic page by that name.'));

}

<?php

/**
 * Topic Pages
 *
 * Controller for all topics.
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

// Disable the old PAGE class.
$new_style_template = true;

// Include all the things this page needs.
include_once '../../includes/easyparliament/init.php';

// Topics
$topics = new Topics();

// Grab the topic name from the variable
$topicname = get_http_var('topic');

global $this_page, $DATA;

$this_page = 'topic';

// Make sure the requested topic actually exists, otherwise throw a 404.
if ($topic = $topics->getTopic($topicname)) {

    $data = [];
    $data['topic'] = $topic;
    // Assume, unless we hear otherwise, that we don't want the postcode form displayed.
    $data['display_postcode_form'] = false;
    $DATA->set_page_metadata('topic', 'title', $topic->title());

    // Is there a specified set of policy positions to worry about?
    if ($topic_policies = $topic->getAllPolicies()) {

        $divisions = new Divisions();
        $data['recent_divisions'] = $divisions->getRecentDivisionsForPolicies($topic_policies, 5);

        include_once INCLUDESPATH . 'easyparliament/member.php';

        // Check to see if there's a submitted postcode to try determine policy positions.
        if (get_http_var('pc') != '') {

            // Try all this, as it might go wrong.
            try {
                // User has submitted a postcode, so we want to display that.
                $pc = get_http_var('pc');
                $pc = preg_replace('#[^a-z0-9]#i', '', $pc);
                if (validate_postcode($pc)) {
                    twfy_debug('MP', "MP lookup by postcode");
                    $constituency = strtolower(Utility\Postcode::postcodeToConstituency($pc));
                    if ($constituency == "connection_timed_out") {
                        throw new \Exception('Sorry, we couldn&rsquo;t check your postcode right now, as our postcode lookup server is under quite a lot of load.');
                    } elseif ($constituency == "") {
                        twfy_debug('MP', "Can't display an MP, as submitted postcode didn't match a constituency");
                        throw new \Exception('Sorry, ' . _htmlentities($pc) . ' isn&rsquo;t a known postcode');
                    } else {
                        // Generate the Member object
                        $member = new Member(['constituency' => $constituency, 'house' => HOUSE_TYPE_COMMONS]);
                        if ($member->person_id()) {
                            // This will cookie the postcode.
                            $THEUSER->set_postcode_cookie($pc);
                        }
                    }
                } else {
                    twfy_debug('MP', "Can't display an MP because the submitted postcode wasn't of a valid form.");
                    throw new \Exception('Sorry, ' . _htmlentities($pc) . ' isn&rsquo;t a valid postcode');
                }
            } catch (\Exception $e) {
                Renderer::output('topic/error', ['error' => $e->getMessage()]);
            }

        }

        /////////////////////////////////////////////////////////
        // DOES THE USER HAVE A POSTCODE ALREADY SET?
        elseif ($THEUSER->postcode_is_set()) {
            $member = new Member(['postcode' => $THEUSER->postcode(), 'house' => HOUSE_TYPE_COMMONS]);
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
        $data['member_image'] = $member->image();
        $data['member_constituency'] = $member->constituency();

        // Grab extra member info
        // TODO: Shouldn't this be loaded on request?
        $member->load_extra_info();

        $divisions = new Divisions($member);
        $policies = new Policies();
    }

    // Send for rendering!
    Renderer::output('topic/topic', $data);

} else {

    header('HTTP/1.0 404 Not Found');
    Renderer::output('topic/error', ['error' => 'Sorry, but there isn&rsquo;t a topic page by that name.']);

}

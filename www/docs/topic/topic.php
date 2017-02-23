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
$new_style_template = TRUE;

// Include all the things this page needs.
include_once '../../includes/easyparliament/init.php';

// Topics
$topics = new Topics();

// Grab the topic name from the variable
$topicname = get_http_var('topic');

global $this_page, $DATA;

$this_page = 'topic';

// Make sure the requested topic actually exists, otherwise throw a 404.
if ($topic = $topics->getTopic($topicname))
{

    $data = $topic->data();
    $policySets = $topic->getPolicySets();
    // Assume, unless we hear otherwise, that we don't want the postcode form displayed.
    $data['display_postcode_form'] = false;
    $data['actions'] = $topic->getContent();
    $DATA->set_page_metadata('topic', 'title', $topic->title());

    if ($topic->search_string()) {
      $search = urlencode($topic->search_string());
      $data['actions'][] = array(
          'title' => 'Search the whole site',
          'icon'  => 'search',
          'href'  => 'https://www.theyworkforyou.com/search/?s=%22' . $search . '%22',
          'description' => 'Search TheyWorkForYou to find mentions of ' . $topic->search_string() . '. You may also filter your results by time, speaker and section.'
      );

      $data['actions'][] = array(
          'title' => 'Sign up for email alerts',
          'icon'  => 'alert',
          'href'  => 'https://www.theyworkforyou.com/alert/?alertsearch=%22' . $search . '%22',
          'description' => 'We&rsquo;ll let you know every time ' . $topic->search_string() . ' are mentioned in Parliament.'
      );
    }

    // Is there a specified set of policy positions to worry about?
    if ($policySets) {

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
                    $constituency = strtolower(Utility\Postcode::postcodeToConstituency($pc));
                    if ($constituency == "connection_timed_out") {
                        throw new Exception('Sorry, we couldn&rsquo;t check your postcode right now, as our postcode lookup server is under quite a lot of load.');
                    } elseif ($constituency == "") {
                        twfy_debug ('MP', "Can't display an MP, as submitted postcode didn't match a constituency");
                        throw new Exception('Sorry, ' . _htmlentities($pc) . ' isn&rsquo;t a known postcode');
                    } else {
                        // Generate the Member object
                        $member = new Member(array('constituency' => $constituency, 'house' => HOUSE_TYPE_COMMONS));
                        if ($member->person_id()) {
                            // This will cookie the postcode.
                            $THEUSER->set_postcode_cookie($pc);
                        }
                    }
                } else {
                    twfy_debug ('MP', "Can't display an MP because the submitted postcode wasn't of a valid form.");
                    throw new Exception('Sorry, ' . _htmlentities($pc) . ' isn&rsquo;t a valid postcode');
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
        $data['member_image'] = $member->image();
        $data['member_constituency'] = $member->constituency();

        // Grab extra member info
        // TODO: Shouldn't this be loaded on request?
        $member->load_extra_info();

        $policies = new Policies;
        $set_descriptions = $policies->getSetDescriptions();
        $sets = array();
        $total = 0;
        foreach ($topic->getPolicySets() as $set) {
          $votes = new \MySociety\TheyWorkForYou\PolicyPositions(
              $policies->limitToSet($set), $member
          );
          $total += count($votes->positions);
          $sets[] = array(
              'key'   => $set,
              'title' => $set_descriptions[$set],
              'votes' => $votes
          );
        }

        $data['sets'] = $sets;
        $data['total_votes'] = $total;
    }

    // Send for rendering!
    Renderer::output('topic/topic', $data);

} else {

    header('HTTP/1.0 404 Not Found');
    Renderer::output('topic/error', array('error' => 'Sorry, but there isn&rsquo;t a topic page by that name.'));

}

<?php

/*
 * Ideally this should be several pages but historically it's always been one
 * page so leave it like that for now.
 */

$new_style_template = true;

include_once '../../includes/easyparliament/init.php';
# need to include this as login code uses error_message
include_once '../../includes/easyparliament/page.php';

$user = new \MySociety\TheyWorkForYou\User();

$data = [];

// work out what sort of page we are displaying
switch (get_http_var("pg")) {

    case "join":	// A new user signing up.

        $data['ret'] = get_http_var("ret");
        $template = 'user/join';
        $this_page = "userjoin";
        break;

    case "editother":	// Editing someone else's info.

        // We need a user_id. So make sure that exists.
        // And make sure the user is allowed to do this!
        $template = 'user/form';
        if (is_numeric(get_http_var("u")) && $THEUSER->is_able_to("edituser")) {

            $data = $user->getUserDetails(get_http_var('u'));
            $data['showall'] = true;
            $data['user_id'] = get_http_var('u');
            $data['statuses'] = $THEUSER->possible_statuses();
            $data['pg'] = 'editother';
            $this_page = "otheruseredit";
            break;
        } else {
            header("Location: /user/");
            exit;
        }

        // no break
    case "edit": // Edit this user's owninfo.

        $template = 'user/form';
        if ($THEUSER->isloggedin() && !get_http_var('u')) {
            $data = $user->getUserDetails();
            $data['pg'] = 'edit';
            $this_page = "useredit";
            break;
        } else {
            header("Location: /user/");
            exit;
        }

        // no break
    default:

        if ($THEUSER->isloggedin() &&
            (get_http_var('u') == '' || get_http_var('u') == $THEUSER->user_id())
        ) {
            // Logged in user viewing their own details.
            $template = 'user/index';
            $data = $user->getUserDetails();
            $this_page = 'userviewself';
        } elseif (is_numeric(get_http_var('u'))) {
            // Viewing someone else's details.
            $template = 'user/view_user';
            $data = $user->getUserDetails(get_http_var('u'));
            $this_page = "userview";
        } else {
            // probably want to login
            $URL = new \MySociety\TheyWorkForYou\Url('userlogin');
            $URL->insert(['ret' => '/user/']);
            $loginurl = $URL->generate();
            header("Location: $loginurl");
            exit;
        }
}

// if data has been submitted then handle that
if (
    get_http_var("submitted") == "true" && (
        $this_page == 'useredit' || $this_page == 'otheruseredit' || $this_page == 'userjoin'
    )
) {
    // Put all the user-submitted data in an array.
    $data = $user->getUpdateDetails($this_page, $THEUSER);
    $data['ret'] = get_http_var("ret");

    if ($this_page == 'useredit') {
        $data['facebook_user'] = $THEUSER->facebook_user();
    }

    // Check the input.
    // If there are any errors with the submission, $errors (an array)
    // will have elements. The keys will be the name of form elements,
    // and the values will be text to display when we show the form again.
    $errors = $user->checkUpdateDetails($data);

    if (sizeof($errors) > 0) {
        $data['errors'] = $errors;
        $template = 'user/form';
        if ($this_page == 'userjoin') {
            $template = 'user/join';
        }
    } else {
        if ($this_page == 'useredit' || $this_page == 'otheruseredit') {
            $results = $user->update($data);
            if (isset($results['errors'])) {
                $data['errors'] = $results['errors'];
                $template = 'user/form';
            } else {
                $data['edited'] = true;
                if (isset($results['email_changed'])) {
                    $data['email_changed'] = $results['email_changed'];
                }
                $template = 'user/index';
            }
        } else {
            $errors = $user->add($data);
            if (sizeof($errors) > 0) {
                $data['errors'] = $errors;
                $template = 'user/join';
            } else {
                $template = 'user/welcome';
            }
        }
    }
}

if ($template == 'user/index' && $this_page != 'otheruseredit') {
    $data['alerts'] = \MySociety\TheyWorkForYou\Utility\Alert::forUser($THEUSER->email());
    $ACTIONURL = new \MySociety\TheyWorkForYou\Url('alert');
    $data['actionurl'] = $ACTIONURL->generate();
}

\MySociety\TheyWorkForYou\Renderer::output($template, $data);

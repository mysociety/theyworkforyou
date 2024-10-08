<?php

/* A class for doing things with single comments.

    To access stuff about an existing comment you can do something like:
        $COMMENT = new COMMENT(37);
        $COMMENT->display();
    Where '37' is the comment_id.

    To create a new comment you should get a $data array prepared of
    the key/value pairs needed to create a new comment and do:
        $COMMENT = new COMMENT;
        $COMMENT->create ($data);

    You can delete a comment by doing $COMMENT->delete() (it isn't actually
    deleted from the database, just set to invisible.

    You can also do $COMMENT->set_modflag() which happens when a user
    posts a report about a comment. The flag is unset when/if the report is
    rejected.

*/



class COMMENT {
    public $comment_id = '';
    public $user_id = '';
    public $epobject_id = '';
    public $body = '';
    public $posted = '';
    public $visible = false;
    public $modflagged = null;	// Is a datetime when set.
    public $firstname = '';	// Of the person who posted it.
    public $lastname = '';
    public $url = '';

    // So that after trying to init a comment, we can test for
    // if it exists in the DB.
    public $exists = false;


    public function __construct($comment_id = '') {

        $this->db = new ParlDB();

        // Set in init.php
        if (ALLOWCOMMENTS == true) {
            $this->comments_enabled = true;
        } else {
            $this->comments_enabled = false;
        }


        if (is_numeric($comment_id)) {
            // We're getting the data for an existing comment from the DB.

            $q = $this->db->query(
                "SELECT user_id,
                                    epobject_id,
                                    body,
                                    posted,
                                    visible,
                                    modflagged
                            FROM	comments
                            WHERE 	comment_id=:comment_id",
                [':comment_id' => $comment_id]
            )->first();

            if ($q) {

                $this->comment_id 	= $comment_id;
                $this->user_id		= $q['user_id'];
                $this->epobject_id	= $q['epobject_id'];
                $this->body			= $q['body'];
                $this->posted		= $q['posted'];
                $this->visible		= $q['visible'];
                $this->modflagged	= $q['modflagged'];

                // Sets the URL and username for this comment. Duh.
                $this->_set_url();
                $this->_set_username();

                $this->exists = true;
            } else {
                $this->exists = false;
            }
        }
    }


    // Use these for accessing the object's variables externally.
    public function comment_id() {
        return $this->comment_id;
    }
    public function user_id() {
        return $this->user_id;
    }
    public function epobject_id() {
        return $this->epobject_id;
    }
    public function body() {
        return $this->body;
    }
    public function posted() {
        return $this->posted;
    }
    public function visible() {
        return $this->visible;
    }
    public function modflagged() {
        return $this->modflagged;
    }
    public function exists() {
        return $this->exists;
    }
    public function firstname() {
        return $this->firstname;
    }
    public function lastname() {
        return $this->lastname;
    }
    public function url() {
        return $this->url;
    }

    public function comments_enabled() {
        return $this->comments_enabled;
    }


    public function display($format = 'html', $template = 'comments') {

        $data['comments'][0] =  [
            'comment_id'	=> $this->comment_id,
            'user_id'		=> $this->user_id,
            'epobject_id'	=> $this->epobject_id,
            'body'			=> $this->body,
            'posted'		=> $this->posted,
            'modflagged'	=> $this->modflagged,
            'url'			=> $this->url,
            'firstname'		=> $this->firstname,
            'lastname'		=> $this->lastname,
            'visible'		=> $this->visible,
        ];

        // Use the same renderer as the COMMENTLIST class.
        $COMMENTLIST = new COMMENTLIST();
        $COMMENTLIST->render($data, $format, $template);

    }


    public function set_modflag($switch) {
        // $switch is either 'on' or 'off'.
        // The comment's modflag goes to on when someone reports the comment.
        // It goes to off when a commentreport has been resolved but the
        // comment HASN'T been deleted.
        global $PAGE;

        if ($switch == 'on') {
            $date = gmdate("Y-m-d H:i:s");
            $flag = "'$date'";

        } elseif ($switch == 'off') {
            $date = null;
            $flag = 'NULL';

        } else {
            $PAGE->error_message("Why are you trying to switch this comment's modflag to '" . _htmlentities($switch) . "'!");
        }

        $q = $this->db->query("UPDATE comments
                        SET		modflagged = $flag
                        WHERE 	comment_id = '" . $this->comment_id . "'
                        ");

        if ($q->success()) {
            $this->modflagged = $date;
            return true;
        } else {
            $message =  [
                'title' => 'Sorry',
                'text' => "We couldn't update the annotation's modflag.",
            ];
            $PAGE->error_message($message);
            return false;
        }

    }


    public function delete() {
        // Mark the comment as invisible.

        global $THEUSER, $PAGE;

        if ($THEUSER->is_able_to('deletecomment')) {
            $q = $this->db->query("UPDATE comments SET visible = '0' WHERE comment_id = '" . $this->comment_id . "'");

            if ($q->success()) {
                return true;
            } else {
                $message =  [
                    'title' => 'Sorry',
                    'text' => "We were unable to delete the annotation.",
                ];
                $PAGE->error_message($message);
                return false;
            }

        } else {
            $message =  [
                'title' => 'Sorry',
                'text' => "You are not authorised to delete annotations.",
            ];
            $PAGE->error_message($message);
            return false;
        }

    }



    public function _set_url() {
        global $hansardmajors;
        // Creates and sets the URL for the comment.

        if ($this->url == '') {

            $q = $this->db->query(
                "SELECT major,
                                    gid
                            FROM	hansard
                            WHERE	epobject_id = :epobject_id",
                [':epobject_id' => $this->epobject_id]
            )->first();

            if ($q) {
                // If you change stuff here, you might have to change it in
                // $COMMENTLIST->_get_comment_data() too...

                $gid = fix_gid_from_db($q['gid']); // In includes/utility.php

                $major = $q['major'];
                $page = $hansardmajors[$major]['page'];

                $URL = new \MySociety\TheyWorkForYou\Url($page);
                $URL->insert(['id' => $gid]);
                $this->url = $URL->generate() . '#c' . $this->comment_id;
            }
        }
    }



    public function _set_username() {
        // Gets and sets the user's name who posted the comment.

        if ($this->firstname == '' && $this->lastname == '') {
            $q = $this->db->query(
                "SELECT firstname,
                                    lastname
                            FROM	users
                            WHERE	user_id = :user_id",
                [':user_id' => $this->user_id]
            )->first();

            if ($q) {
                $this->firstname = $q['firstname'];
                $this->lastname = $q['lastname'];
            }
        }
    }




}

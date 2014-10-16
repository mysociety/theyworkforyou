<?php

include_once '../../includes/easyparliament/init.php';

$this_page = 'admin_photos';

$db = new \MySociety\TheyWorkForYou\ParlDb;

$PAGE->page_start();
$PAGE->stripe_start();

?>

<p>Photos are automatically added to version control and committed. Because of this,
the photo upload interface only works on a development version of the site. On the
other hand, setting attribution needs to be done on the live database, on the live
version of the site.
</p>

<?php

$out = '';
if (get_http_var('submit')) {
    $out = DEVSITE ? submit_photo() : submit_attribution();
} else {
    $out = DEVSITE ? display_photo_form() : display_attribution_form();
}
print $out;

function submit_photo() {
    $dir = "../images";
    $pid = intval(get_http_var('pid'));
    $errors = array();

    if (!array_key_exists('photo', $_FILES))
        array_push($errors, 'Not got the photo.');
    elseif ($_FILES['photo']['error'] > 0)
        array_push($errors, 'There was an error uploading the photo.');
    elseif (!is_uploaded_file($_FILES['photo']['tmp_name']))
        array_push($errors, 'Did not get an uploaded file.');
    else {
        $tmp_name = $_FILES['photo']['tmp_name'];

        $image = new Imagick();
        $image->readImage($tmp_name);
        if (!$image)
            array_push($errors, 'Failed to read image from uploaded file');
            $imageS = $image->clone();
        if (!$image->scaleImage(0, 118))
            array_push($errors, 'Scaling large failed');
        if (!$imageS->scaleImage(0, 59))
            array_push($errors, 'Scaling small failed');
        if (!$image->writeImage("$dir/mpsL/$pid.jpeg"))
            array_push($errors, "Saving to $dir/mpsL/$pid.jpeg failed");
        if (!$imageS->writeImage("$dir/mps/$pid.jpeg"))
            array_push($errors, "Saving to $dir/mps/$pid.jpeg failed");
        if (!$errors) {
            print "<pre>";
            chdir($dir);
            passthru('git pull');
            passthru("git add mpsL/$pid.jpeg");
            passthru("git add mps/$pid.jpeg");
            passthru('git commit -m "Photo update from admin web photo upload interface."');
            passthru('git push');
            print "</pre>";
        }
    }

    if ($errors)
        return display_photo_form($errors);
    return "<p><em>Photo uploaded and resized for pid $pid</em> &mdash; check how it looks <a href=\"/mp?p=$pid\">on their page</a></p>" . display_photo_form();
}

function person_drop_down() {
    global $db;
    $out = '
<div class="row">
<span class="label"><label for="form_pid">Person:</label></span>
<span class="formw"><select id="form_pid" name="pid"></span>
';
    $query = 'SELECT house, person_id, title, first_name, last_name, constituency, party
        FROM member
        WHERE house>0 GROUP by person_id
        ORDER BY house, last_name, first_name
    ';
    $q = $db->query($query);

    $houses = array(1 => 'MP', 'Lord', 'MLA', 'MSP');

    for ($i=0; $i<$q->rows(); $i++) {
        $p_id = $q->field($i, 'person_id');
        $house = $q->field($i, 'house');
        $desc = $q->field($i, 'last_name') . ', ' . $q->field($i, 'title') . ' ' . $q->field($i, 'first_name') .
                " " . $houses[$house];
        if ($q->field($i, 'party')) $desc .= ' (' . $q->field($i, 'party') . ')';
        $desc .= ', ' . $q->field($i, 'constituency');

        list($dummy, $sz) = MySociety\TheyWorkForYou\Utility\Member::findMemberImage($p_id);
        if ($sz == 'L') {
            $desc .= ' [has large photo]';
        } elseif ($sz == 'S') {
            $desc .= ' [has small photo]';
        } else {
            $desc .= ' [no photo]';
        }
        $out .= '<option value="'.$p_id.'">'.$desc.'</option>' . "\n";
    }

    $out .= ' </select></span> </div> ';

    return $out;
}

function display_photo_form($errors = array()) {
    $out = '';
    if ($errors) {
        $out .= '<ul class="error"><li>' . join('</li><li>', $errors) . '</li></ul>';
    }
    $out .= '<form method="post" action="photos.php" enctype="multipart/form-data">';
    $out .= person_drop_down();
    $out .= <<<EOF
<div class="row">
    <span class="label"><label for="form_photo">Photo:</label></span>
    <span class="formw"><input type="file" name="photo" id="form_photo" size="50"></span>
</div>
<div class="row">
    <span class="label">&nbsp;</span>
    <span class="formw"><input type="submit" name="submit" value="Upload photo"></span>
</div>
</form>

<p style="clear:both; margin-top: 3em"><a href="/images/mps/photo-status.php">List MPs without photos</a></p>
EOF;

    return $out;
}

function submit_attribution() {
    $pid = intval(get_http_var('pid'));
    $attr_text = get_http_var('attr_text');
    $attr_link = get_http_var('attr_link');
    $errors = array();

    if (!$pid || !$attr_text)
        array_push($errors, 'Missing information');
    elseif ($attr_link && substr($attr_link, 0, 4) != 'http')
        array_push($errors, 'Bad link');

    if ($errors)
        return display_attribution_form($errors);

    # UPDATE
    global $db;
    $query = "INSERT INTO personinfo (person_id,data_key,data_value) VALUES
            ($pid,'photo_attribution_text',:attr_text),
            ($pid,'photo_attribution_link',:attr_link)
        ON DUPLICATE KEY UPDATE data_value=VALUES(data_value)";
    $q = $db->query($query, array(
        ':attr_text' => $attr_text,
        ':attr_link' => $attr_link
        ));

    return "<p><em>Attribution text/link set for pid $pid</em> &mdash; check how it looks <a href=\"/mp?p=$pid\">on their page</a></p>" . display_attribution_form();
}

function display_attribution_form($errors = array()) {
    $out = '';
    if ($errors) {
        $out .= '<ul class="error"><li>' . join('</li><li>', $errors) . '</li></ul>';
    }
    $out .= '<form method="post" action="photos.php">';
    $out .= person_drop_down();
    $out .= <<<EOF
<div class="row">
    <span class="label"><label for="form_attr_link">Attribution link:</label></span>
    <span class="formw"><input type="text" name="attr_link" id="form_attr_link" size="50"></span>
</div>
<div class="row">
    <span class="label"><label for="form_attr_text">Attribution text:</label></span>
    <span class="formw"><input type="text" name="attr_text" id="form_attr_text" size="50"></span>
</div>
<div class="row">
    <span class="label">&nbsp;</span>
    <span class="formw"><input type="submit" name="submit" value="Update attribution"></span>
</div>
</form>
EOF;

    return $out;
}

$menu = $PAGE->admin_menu();
$PAGE->stripe_end(array(
    array(
        'type'		=> 'html',
        'content'	=> $menu
    )
));

$PAGE->page_end();

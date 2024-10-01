<?php

include_once '../../includes/easyparliament/init.php';

$this_page = 'admin_edittopic';

$topics = new \MySociety\TheyWorkForYou\Topics();

$PAGE->page_start();
$PAGE->stripe_start();

$slug = get_http_var('id');
if ($slug) {
    $topic = $topics->getTopic($slug);
} else {
    $topic = new \MySociety\TheyWorkForYou\Topic();
}

$action = get_http_var('action');
switch ($action) {
    case 'add':
        $success = add_topic($topic);
        break;
    case 'update':
        $success = update_topic($topic);
        break;
    case 'setimage':
        $success = add_image($topic);
        break;
    case 'addcontent':
        $success = add_content($topic);
        break;
    case 'deletecontent':
        $success = delete_content($topic);
        break;
    case 'addpolicysets':
        $success = add_policy_sets($topic);
        break;
    case 'addpolicies':
        $success = add_policies($topic);
        break;
    default:
        $success = null;
}

if (!is_null($success)) {
    if ($success) {
        $out = "<h4>Update successful</h4>";
    } else {
        $out = "<h4>Failed to update Topic</h4>";
    }
    print $out;
}

?>

  <h2><?= $topic->title() ?></h2>
    <div id="adminbody" class="topic">
        <form action="edittopic.php" method="post">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= $topic->slug() ?>">
          <label for="title">Title</label> <input id="title" name="title" value="<?= $topic->title() ?>">

          <label for="search_string">Search string</label> <input id="search_string" name="search_string" value="<?= $topic->search_string() ?>">

          <p>
           <input type="checkbox" value="1" id="front_page" name="front_page" <?= $topic->onFrontPage() ? 'checked' : '' ?>> <label class="inline" for="front_page">Show on Front Page</label>
          </p>

          <label for="description">Description</label>
          <textarea id="description" rows="5" name="description"><?= $topic->description() ?></textarea>

          <p>
          <input type="submit" value="Save">
          </p>
        </form>

        <h3>Set Image</h3>
        <form enctype="multipart/form-data" action="edittopic.php" method="post">
          <input type="hidden" name="action" value="setimage">
          <input type="hidden" name="id" value="<?= $topic->slug() ?>">
          <?php if ($topic->image()) { ?>
            <p>
              <img src="<?= $topic->image_url() ?>" height="100">
            </p>

            <p>
              <input type="submit" value="Delete">
            </p>
          <?php } ?>
          <input type="file" value="Image" name="topic_image" id="image">
          <p>
          <input type="submit" value="Update">
          </p>
        </form>


        <h3>Related Content</h3>
        <ul>
          <?php foreach ($topic->getContent() as $content) { ?>
          <li><a href="<?= $content['href'] ?>"><?= $content['title'] ?></a>
              <form class="inline" action="edittopic.php" method="post">
                  <input type="hidden" name="action" value="deletecontent">
                  <input type="hidden" name="id" value="<?= $topic->slug() ?>">
                  <input type="hidden" name="content" value="<?= $content['id'] ?>">
                  <input type="submit" value="Delete">
              </form>
          </li>
          <?php } ?>
        </ul>

        <form action="edittopic.php" method="post">
            <input type="hidden" name="action" value="addcontent">
            <input type="hidden" name="id" value="<?= $topic->slug() ?>">
            <p>
            <label for="content_url">URL</label> <input id="content_url" name="content_url">
            <input type="submit" value="Add">
            </p>
        </form>

        <h3>Related Policy Sets</h3>
        <form action="edittopic.php" method="post">
            <input type="hidden" name="action" value="addpolicysets">
            <input type="hidden" name="id" value="<?= $topic->slug() ?>">
            <select name="sets[]" multiple>
              <option value="">None</option>
            <?php
              $policies = new \MySociety\TheyWorkForYou\Policies();
$set_descriptions = $policies->getSetDescriptions();
$related_sets = $topic->getPolicySets();
foreach ($set_descriptions as $set => $description) { ?>
              <option value="<?= $set ?>" <?= in_array($set, $related_sets) ? 'selected' : '' ?>><?= $description ?></option>
            <?php } ?>
            <input type="submit" value="Update">
            </select>
        </form>

        <h3>Related Policies</h3>

        <form action="edittopic.php" method="post">
            <input type="hidden" name="action" value="addpolicies">
            <input type="hidden" name="id" value="<?= $topic->slug() ?>">
            <select name="policies[]" multiple>
              <option value="">None</option>
            <?php
$policies = new \MySociety\TheyWorkForYou\Policies();
$all_policies = $policies->getPolicies();
$related_policies = $topic->getPolicies();
foreach ($all_policies as $number => $description) { ?>
              <option value="<?= $number ?>" <?= in_array($number, $related_policies) ? 'selected' : '' ?>><?= $description ?></option>
            <?php } ?>

            </select>
            <input type="submit" value="Update">
        </form>
    </div>
<?php

function add_topic($topic) {
    $topic->set_title(get_http_var('title'));
    $topic->set_description(get_http_var('description'));

    $slug = strtolower(preg_replace('/ /', '-', $topic->title()));
    $slug = preg_replace('/^the-/', '', $slug);
    $topic->set_slug($slug);
    return $topic->save();
}

function update_topic($topic) {
    $topic->set_title(get_http_var('title'));
    $topic->set_description(get_http_var('description'));
    $topic->set_front_page(get_http_var('front_page'));
    $topic->set_search_string(get_http_var('search_string'));
    return $topic->save();
}

function add_content($topic) {
    $gid = \MySociety\TheyWorkForYou\Utility\Hansard::get_gid_from_url(get_http_var('content_url'));

    return $topic->addContent($gid);
}

function add_image($topic) {
    // do some sanity checks on the file
    $file_info = $_FILES['topic_image'];

    if (
        !isset($file_info['error']) ||
        is_array($file_info['error']) ||
        $file_info['error'] != UPLOAD_ERR_OK
    ) {
        return false;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_info = $finfo->file($file_info['tmp_name']);
    $ext = array_search(
        $mime_info,
        [
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
        ],
        true
    );

    if ($ext === false) {
        return false;
    }

    $outfile = sprintf('%s.%s', $topic->slug(), $ext);
    $topic->set_image($outfile);
    try {
        $image_saved = move_uploaded_file(
            $file_info['tmp_name'],
            $topic->image_path()
        );
    } catch (ErrorException $e) {
        return false;
    }

    if ($image_saved) {
        return $topic->save();
    }
}

function delete_content($topic) {
    $epobject_id = get_http_var('content');

    return $topic->deleteContent($epobject_id);
}

function add_policy_sets($topic) {
    $sets = get_http_var('sets', '', true);

    if ($sets[0] == '' && count($sets) == 1) {
        $sets = [];
    }

    return $topic->addPolicySets($sets);
}

function add_policies($topic) {
    $policies = get_http_var('policies', '', true);

    if ($policies[0] == '' && count($policies) == 1) {
        $policies = [];
    }

    return $topic->addPolicies($policies);
}

$menu = $PAGE->admin_menu();

$PAGE->stripe_end([
    [
        'type'    => 'html',
        'content' => $menu,
    ],
]);

$PAGE->page_end();

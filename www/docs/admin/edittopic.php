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
    case 'addcontent':
      $success = add_content($topic);
      break;
    case 'deletecontent':
      $success = delete_content($topic);
      break;
    case 'addpolicysets':
      $success = add_policy_sets($topic);
      break;
    default:
      $success = NULL;
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
    <div id="adminbody">
        <form action="edittopic.php" method="post">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= $topic->slug() ?>">
          <p>
          <label for="title">Title:</label> <input id="title" name="title" value="<?= $topic->title() ?>">
          </p>

          <p>
          <label for="search_string">Search string:</label> <input id="search_string" name="search_string" value="<?= $topic->search_string() ?>">
          </p>

          <p>
          <label for="front_page">Show on Front Page:</label> <input type="checkbox" value="1" id="front_page" name="front_page" <?= $topic->onFrontPage() ? 'checked' : '' ?>>
          </p>

          <p>
          <label for="description">Description</label><br>
          <textarea id="description" rows="5" name="description"><?= $topic->description() ?></textarea>
          </p>

          <p>
          <input type="submit" value="Save">
          </p>
        </form>


        <h3>Related Content</h3>
        <ul>
          <?php foreach ($topic->getContent() as $content) { ?>
          <li><a href="<?= $content['href'] ?>"><?= $content['title'] ?></a> <a href="edittopic.php?action=deletecontent&id=<?= $topic->slug() ?>&content=<?= $content['id'] ?>">X</a></li>
          <?php } ?>
        </ul>

        <form action="edittopic.php" method="post">
            <input type="hidden" name="action" value="addcontent">
            <input type="hidden" name="id" value="<?= $topic->slug() ?>">
            <p>
            <label for="content_url">URL</label>: <input id="content_url" name="content_url">
            <input type="submit" value="Add">
            </p>
        </form>

        <h3>Related Policy Sets</h3>
        <form action="edittopic.php" method="post">
            <input type="hidden" name="action" value="addpolicysets">
            <input type="hidden" name="id" value="<?= $topic->slug() ?>">
            <select name="sets[]" multiple>
            <?php
              $policies = new \MySociety\TheyWorkForYou\Policies;
              $set_descriptions = $policies->getSetDescriptions();
              $related_sets = $topic->getPolicySets();
              foreach ($set_descriptions as $set => $description) { ?>
              <option value="<?= $set ?>" <?= in_array($set, $related_sets) ? 'selected' : '' ?>><?= $description ?></option>
            <?php } ?>
            <input type="submit" value="Update">
            </select>
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

function delete_content($topic) {
    $epobject_id = get_http_var('content');

    return $topic->deleteContent($epobject_id);
}

function add_policy_sets($topic) {
    $sets = get_http_var('sets');

    return $topic->addPolicySets($sets);
}

$menu = $PAGE->admin_menu();

$PAGE->stripe_end(array(
    array(
        'type'    => 'html',
        'content' => $menu
    )
));

$PAGE->page_end();

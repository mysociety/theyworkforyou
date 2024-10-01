<?php

include_once '../../includes/easyparliament/init.php';

$this_page = 'admin_topics';

$topics = new \MySociety\TheyWorkForYou\Topics();
$all_topics = $topics->getTopics();
$db = new ParlDB();

$PAGE->page_start();
$PAGE->stripe_start();

$action = get_http_var('action');

if ($action == 'frontpage') {
    print(update_frontpage($topics));
    $all_topics = $topics->getTopics();
}

?>

    <div id="adminbody">
        <form action="topics.php" method="post">
          <input type="hidden" name="action" value="frontpage">
          <table>
            <thead>
              <th>Name</th>
              <th>Description</th>
              <th>Front page</th>
              <th></th>
            </thead>
            <?php foreach ($all_topics as $name => $topic) { ?>
            <tr>
              <td><a href="<?= $topic->url() ?>"><?= _htmlspecialchars($topic->title()) ?></a></td>
              <td><?= _htmlspecialchars($topic->description()) ?></td>
              <td><input type="checkbox" name="frontpage[]" value="<?= $topic->slug() ?>" <?= $topic->onFrontPage() ? 'checked' : '' ?>></td>
              <td><a href="/admin/edittopic.php?id=<?= $topic->slug() ?>">edit</a></td>
            <?php } ?>
          </table>
          <p>
            <input type="submit" value="Update">
          </p>
        </form>

        <h3>Add new Topic</h3>

        <form action="edittopic.php" method="post">
          <input type="hidden" name="action" value="add">
          <p>
          <label for="title">Title:</label> <input id="title" name="title" value="">
          </p>

          <p>
          <label for="description">Description</label><br>
          <textarea id="description" rows="5" name="description"></textarea>
          </p>

          <p>
          <input type="submit" value="Add">
          </p>
        </form>
    </div>
<?php

function update_frontpage($topics) {
    $frontpage = get_http_var('frontpage', '', true);

    $is_success = $topics->updateFrontPageTopics($frontpage);

    if ($is_success) {
        $out = "<h4>update successful</h4>";
    } else {
        $out = "<h4>Failed to update Topics</h4>";
    }

    return $out;
}

$menu = $PAGE->admin_menu();

$PAGE->stripe_end([
    [
        'type'    => 'html',
        'content' => $menu,
    ],
]);

$PAGE->page_end();

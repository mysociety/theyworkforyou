<?php

include_once '../../includes/easyparliament/init.php';

$this_page = 'admin_topics';

$topics = new \MySociety\TheyWorkForYou\Topics();
$all_topics = $topics->getTopics();
$db = new ParlDB;

$PAGE->page_start();
$PAGE->stripe_start();

?>

    <div id="adminbody">
        <form action="topics.php" method="post">
          <table>
            <?php foreach ($all_topics as $name => $topic) { ?>
            <tr>
              <td><a href="<?= $topic->url() ?>"><?= _htmlspecialchars($topic->title()) ?></a></td>
              <td><?= _htmlspecialchars($topic->description()) ?></td>
              <td><a href="/admin/edittopic.php?id=<?= $topic->slug() ?>">edit</a></td>
            <?php } ?>
          </table>
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

$menu = $PAGE->admin_menu();

$PAGE->stripe_end(array(
    array(
        'type'    => 'html',
        'content' => $menu
    )
));

$PAGE->page_end();

<?php
// This sidebar is on the list of MPs pages

global $MEMBER;

$SEARCHURL = new \MySociety\TheyWorkForYou\Url("search");
$this->block_start(['id' => 'mpsearch', 'title' => "Search by name (including former MSPs)"]);
?>

    <div class="mpsearchbox">
        <form action="<?php echo $SEARCHURL->generate(); ?>" method="get">
        <p>
            <input name="q" size="24" maxlength="200">
            <input type="submit" class="submit" value="GO">
        </p>
        <small>e.g. <a href="/search/?s=Alex+Salmond">Alex Salmond</a> or <a href="/search/?s=Donald+Dewar">Donald Dewar</a></small>
        </form>
    </div>

<?php
$this->block_end();
?>

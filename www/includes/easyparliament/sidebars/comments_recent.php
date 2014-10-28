<?php
$COMMENTLIST = new \MySociety\TheyWorkForYou\CommentList($PAGE, $hansardmajors);
$COMMENTLIST->display('recent', array('num'=>10));

<?php

$URL = new \MySociety\TheyWorkForYou\Url('help');
$helpurl = $URL->generate();
$this->block_start(['id' => 'help', 'title' => "What is TheyWorkForYou?"]);
?>

<p>
TheyWorkForYou lets you find out what your MP, MSP or MLA is doing in your name,
read debates, written answers, see what&rsquo;s coming up in
Parliament, and sign up for email alerts when there&rsquo;s past or future
activity on someone or something you&rsquo;re interested in.

<?php
$this->block_end();
?>

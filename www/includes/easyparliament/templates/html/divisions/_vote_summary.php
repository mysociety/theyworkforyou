<p>
    <a href="#for"><?= $division['for'] - 2 ?> for</a>,
    <a href="#against"><?= $division['against'] - 2 ?> against</a><?php
  if ($division['both'] > 0) { ?>,
    <a href="#both"><?= $division['both'] ?> abstained</a><?php
  }
  if ($division['absent'] > 0) { ?>,
    <a href="#absent"><?= $division['absent'] ?> absent</a><?php
  } ?>.
</p>

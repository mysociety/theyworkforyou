<p>
    <a href="#for"><?= sprintf(gettext('%s for'), $division['for']) ?></a>,
    <a href="#against"><?= sprintf(gettext('%s against'), $division['against']) ?></a><?php
  if ($division['both'] > 0) { ?>,
    <a href="#both"><?= sprintf(gettext('%s abstained'), $division['both']) ?></a><?php
  }
    if ($division['absent'] > 0) { ?>,
    <a href="#absent"><?= sprintf(gettext('%s absent'), $division['absent']) ?></a><?php
    } ?>.
</p>

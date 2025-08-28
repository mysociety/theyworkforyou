                  <button type="submit" class="button button--red" name="action" value="Abandon">
                    <i aria-hidden="true" class="fi-trash"></i>
                    <?php if ($token) { ?>
                      <span><?= gettext('Abandon changes') ?></span>
                    <?php } else { ?>
                      <span><?= gettext('Abandon new alert') ?></span>
                    <?php } ?>
                  </button>

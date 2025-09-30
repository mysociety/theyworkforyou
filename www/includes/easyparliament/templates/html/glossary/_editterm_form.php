  <div class="glossaryaddbox">
      <form action="<?= $form_url ?>" method="post">
      <input type="hidden" name="id" value="<?= $glossary_id ?>">
      <input type="hidden" name="return_page" value="glossary">
      <label for="definition"><p><textarea name="definition" id="definition" rows="15" cols="55"><?= $definition_raw ?></textarea></p>

      <p><input type="submit" name="previewterm" value="Preview" class="submit">
      <input type="submit" name="submitterm" value="Post" class="submit"></p></label>
      <p><small>Markdown formatting is allowed. URLs and email addresses will automatically be turned into links.</small></p>
  </div>

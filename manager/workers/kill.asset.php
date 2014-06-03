<?php echo Notify::read(); ?>
<form class="form-kill form-asset" action="<?php echo $config->url_current; ?>" method="post">
  <input name="token" type="hidden" value="<?php echo Guardian::makeToken(); ?>">
  <table class="table-bordered table-full">
    <colgroup>
      <col style="width:4em;">
      <col>
    </colgroup>
    <tbody>
      <?php $i = 1; foreach($config->asset_name as $name): ?>
      <tr><td class="text-right"><?php echo $i; ?>.</td><td><?php echo $name; ?></td></tr>
      <?php ++$i; endforeach; ?>
    </tbody>
  </table>
  <p><button class="btn btn-primary btn-delete" type="submit"><i class="fa fa-check-circle"></i> <?php echo $speak->yes; ?></button> <a class="btn btn-danger btn-cancel" href="<?php echo $config->url . '/' . $config->manager->slug . '/' . $config->editor_type; ?>"><i class="fa fa-times-circle"></i> <?php echo $speak->no; ?></a></p>
</form>
<?php $c_editor = $config->states->{'plugin_' . md5(File::B(__DIR__))}; ?>
<fieldset>
  <legend><?php echo $speak->plugin_editor->title->toolbars; ?></legend>
  <div class="p">
    <?php foreach($speak->MTE->buttons as $kk => $vv): ?>
    <?php if(is_object($vv)) $vv = reset($vv); ?>
    <div><?php echo Form::checkbox('buttons[' . $kk . ']', 1, ! isset($c_editor->buttons->{$kk}) || $c_editor->buttons->{$kk} === 1, sprintf($speak->plugin_editor->title->toggle__button, $vv)); ?></div>
    <?php endforeach; ?>
  </div>
</fieldset>
<fieldset>
  <legend><?php echo $speak->plugin_editor->title->features; ?></legend>
  <div class="p">
    <div><?php echo Form::checkbox('autoComplete', 1, $c_editor->autoComplete, $speak->plugin_editor->title->toggle_autoComplete); ?></div>
    <div><?php echo Form::checkbox('autoIndent', 1, $c_editor->autoIndent, $speak->plugin_editor->title->toggle_autoIndent); ?></div>
  </div>
</fieldset>
<p><?php echo Jot::button('action', $speak->update); ?></p>
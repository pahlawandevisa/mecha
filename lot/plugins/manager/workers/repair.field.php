<?php echo $messages; ?>
<form class="form-<?php echo $id ? 'repair' : 'ignite'; ?> form-field" id="form-<?php echo $id ? 'repair' : 'ignite'; ?>" action="<?php echo $config->url_current; ?>" method="post">
  <?php echo Form::hidden('token', $token); ?>
  <label class="grid-group">
    <span class="grid span-1 form-label"><?php echo $speak->title; ?></span>
    <span class="grid span-5">
    <?php echo Form::text('title', Guardian::wayback('title', $file->title), null, array(
        'class' => 'input-block'
    )); ?>
    </span>
  </label>
  <label class="grid-group">
    <span class="grid span-1 form-label"><?php echo $speak->key; ?></span>
    <span class="grid span-5">
    <?php echo Form::text('key', Guardian::wayback('key', $id), null, array(
        'class' => 'input-block'
    )); ?>
    </span>
  </label>
  <label class="grid-group">
    <span class="grid span-1 form-label"><?php echo $speak->type; ?></span>
    <span class="grid span-5">
    <?php

    $cache = Guardian::wayback('type', $file->type);

    echo Form::select('type', array(
        't' => $speak->text,
        's' => $speak->summary,
        'b' => $speak->boolean,
        'o' => $speak->option,
        'f' => $speak->file,
        'c' => $speak->composer,
        'e' => $speak->editor
    ), $cache[0]);

    ?>
    </span>
  </label>
  <div class="grid-group">
    <span class="grid span-1 form-label"><?php echo $speak->scope; ?></span>
    <span class="grid span-5">
    <?php

    $cache = Guardian::wayback('scope', isset($file->scope) ? $file->scope : "");
    $scopes = array('article' => $speak->article, 'page' => $speak->page, 'comment' => $speak->comment);
    $cache = ',' . (is_array($cache) ? implode(',', $cache) : $cache) . ',';

    foreach($scopes as $k => $v) {
        echo '<div>' . Form::checkbox('scope[]', $k, strpos($cache, ',' . $k . ',') !== false, $v) . '</div>';
    }

    ?>
    </span>
  </div>
  <label class="grid-group">
    <span class="grid span-1 form-label"><?php echo $speak->placeholder; ?></span>
    <span class="grid span-5">
    <?php echo Form::text('placeholder', Converter::toText(Guardian::wayback('placeholder', $file->placeholder)), null, array(
        'class' => 'input-block'
    )); ?>
    </span>
  </label>
  <label class="grid-group">
    <span class="grid span-1 form-label"><?php echo $speak->value; ?></span>
    <span class="grid span-5">
    <?php echo Form::textarea('value', Converter::str(Guardian::wayback('value', $file->value)), null, array(
        'class' => array(
            'textarea-block',
            'MTE'
        )
    )); ?>
    </span>
  </label>
  <label class="grid-group">
    <span class="grid span-1 form-label"><?php echo $speak->description; ?></span>
    <span class="grid span-5">
    <?php echo Form::text('description', Converter::toText(Guardian::wayback('description', $file->description)), Config::speak('manager.placeholder_description_', strtolower($speak->field)), array(
        'class' => 'input-block'
    )); ?>
    </span>
  </label>
  <div class="grid-group">
    <span class="grid span-1"></span>
    <span class="grid span-5">
      <?php if($id): ?>
      <?php echo Jot::button('action', $speak->update); ?>
      <?php echo Jot::btn('destruct', $speak->delete, $config->manager->slug . '/field/kill/key:' . $id); ?>
      <?php else: ?>
      <?php echo Jot::button('construct', $speak->create); ?>
      <?php endif; ?>
    </span>
  </div>
</form>
<script>
(function(w, d) {
    var form = d.getElementById('form-<?php echo $id ? 'repair' : 'ignite'; ?>'),
        type = form.type,
        value = form.value,
        placeholder = form.placeholder,
        holder = "";
    function onchange(v) {
        // `input[type="text"] < .grid < .grid-group`
        placeholder.parentNode.parentNode.style.display = v[0].match(/^[ceost]$/) ? "" : 'none';
        if (v[0] === 'o') {
            holder = '<?php echo strtolower($speak->key) . S . ' ' . $speak->value; ?>';
        } else if (v[0] === 'f') {
            holder = '<?php echo IMAGE_EXT; ?>';
        } else if (v[0] === 'b') {
            holder = '1';
        } else {
            holder = "";
        }
        value.placeholder = holder;
    }
    onchange(type.value);
    type.onchange = function() {
        onchange(this.value);
    };
})(window, document);
</script>
<hr>
<?php echo Guardian::wizard($segment); ?>
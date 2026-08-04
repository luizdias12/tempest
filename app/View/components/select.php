<?php
$id = $id ?? $name ?? '';
$name = $name ?? $id;
$options = $options ?? [];
$placeholder = $placeholder ?? '';
$selected = $selected ?? '';
$valueKey = $valueKey ?? 'value';
$labelKey = $labelKey ?? 'label';
$class = $class ?? '';
?>
<select id="<?= htmlspecialchars($id) ?>" name="<?= htmlspecialchars($name) ?>"<?= $class !== '' ? ' class="' . htmlspecialchars($class) . '"' : '' ?>>
    <?php if ($placeholder !== ''): ?>
        <option value=""><?= htmlspecialchars($placeholder) ?></option>
    <?php endif; ?>
    <?php foreach ($options as $key => $opt): ?>
        <?php
        if (is_array($opt)) {
            $val = $opt[$valueKey] ?? '';
            $label = $opt[$labelKey] ?? '';
        } else {
            $val = $key;
            $label = $opt;
        }
        ?>
        <option value="<?= htmlspecialchars($val) ?>"<?= $selected === $val ? ' selected' : '' ?>>
            <?= htmlspecialchars($label) ?>
        </option>
    <?php endforeach; ?>
</select>

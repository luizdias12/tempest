<?php
$id = $id ?? $name ?? '';
$name = $name ?? $id;
$options = $options ?? [];
$placeholder = $placeholder ?? '';
$selected = $selected ?? '';
$valueKey = $valueKey ?? 'value';
$labelKey = $labelKey ?? 'label';
$class = $class ?? '';
$attrs = $attrs ?? '';
$optionData = $optionData ?? [];
?>
<select id="<?= htmlspecialchars($id) ?>" name="<?= htmlspecialchars($name) ?>" <?= $class !== '' ? ' class="' . htmlspecialchars($class) . '"' : '' ?><?= $attrs !== '' ? ' ' . $attrs : '' ?>>
    <?php if ($placeholder !== ''): ?>
        <option value=""<?= (string) $selected === '' ? ' selected' : '' ?>><?= htmlspecialchars($placeholder) ?></option>
    <?php endif; ?>
    <?php foreach ($options as $key => $opt): ?>
        <?php
        $dataAttrs = '';
        if (is_array($opt)) {
            $val = $opt[$valueKey] ?? '';
            $label = $opt[$labelKey] ?? '';
            foreach ($optionData as $dataKey) {
                if (isset($opt[$dataKey])) {
                    $dataAttrs .= ' data-' . strtolower($dataKey) . '="' . htmlspecialchars((string) $opt[$dataKey]) . '"';
                }
            }
        } else {
            $val = $key;
            $label = $opt;
        }
        ?>
        <option value="<?= htmlspecialchars($val) ?>" <?= (string) $selected === (string) $val ? ' selected' : '' ?><?= $dataAttrs ?>>
            <?= htmlspecialchars($label) ?>
        </option>
    <?php endforeach; ?>
</select>
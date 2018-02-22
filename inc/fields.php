<?php
use JDanger\Meta;
use JDanger\Transmission;
?>
<div class="JdtForm">
    <p class="JdtForm-row url">
        <label for="jdt-url">URL</label>
        <input type="text" name="jdt_url" id="jdt-url"
               value="<?php echo esc_attr(get_post_meta($post->ID, 'jdt_url', true)) ?>">
    </p>
    <?php
    $meta = get_post_meta($post->ID, 'jdt_meta', true);
    uksort($meta, function ($a, $b) {
        return $a === Meta::PLAYTIME ? -1 : 1;
    });
    ?>
    
    <?php $color = get_post_meta($post->ID, 'jdt_color', true) ?>
    <p class="JdtForm-row color">
        <label for="jdt-color">Couleur</label>
        <input type="color" name="jdt_color" id="jdt-color"
               value="<?php echo esc_attr($color ? $color : sprintf('#%06X', mt_rand(0, 0xFFFFFF))) ?>">
    </p>

    <?php if ($meta): foreach ($meta as $key => $value): ?>
        <p class="JdtForm-row <?php echo $key ?>">
            <label for="jdt-<?php echo $key ?>">
                <?php echo array_key_exists($key, Transmission::FIELDS) ? Transmission::FIELDS[$key] : $key ?>
            </label>
            <input type="text" id="jdt-<?php echo $key ?>" disabled
                   value="<?php echo $value ?>">
        </p>
    <?php endforeach; endif ?>
</div>
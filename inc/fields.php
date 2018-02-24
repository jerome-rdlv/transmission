<?php
use Rdlv\JDanger\Meta;
use Rdlv\JDanger\Transmission;
?>
<div class="JdtForm">
    <?php /*
    <p class="JdtForm-row url">
        <label for="jdt-url">URL</label>
        <input type="text" name="jdt_url" id="jdt-url"
               value="<?php echo esc_attr(get_post_meta($post->ID, 'jdt_url', true)) ?>">
    </p>
    */ ?>
    
    <p class="JdtForm-row file">
        <?php $aid = get_post_meta($post->ID, 'jdt_aid', true) ?>
        <label>Fichier</label>
        <button type="button" class="button" id="jdt-audio-file">
            <?php echo $aid ? 'Modifier fichier audio' : 'Choisir fichier audio' ?>
        </button>
        <span class="JdtForm-selection">
            <?php if ($aid) echo basename(wp_get_attachment_url($aid)) ?>
        </span>
        <input type="hidden" name="jdt_aid" value="<?php echo esc_attr($aid) ?>">
    </p>
    
    <?php $color = get_post_meta($post->ID, 'jdt_color', true) ?>
    <p class="JdtForm-row color">
        <label for="jdt-color">Couleur</label>
        <input type="color" name="jdt_color" id="jdt-color"
               value="<?php echo esc_attr($color ? $color : sprintf('#%06X', mt_rand(0, 0xFFFFFF))) ?>">
    </p>
    
    <?php $meta = get_post_meta($post->ID, 'jdt_meta', true) ?>
    <?php if ($meta): ?>
        <?php $fields = [Meta::PLAYTIME, Meta::ARTIST, Meta::ALBUM, Meta::YEAR] ?>
        <?php uksort($meta, function ($a, $b) {
            return $a === Meta::PLAYTIME ? -1 : 1;
        }) ?>
        <?php foreach ($fields as $key): if (array_key_exists($key, $meta)): ?>
            <?php $value = $meta[$key] ?>
            <p class="JdtForm-row <?php echo $key ?>">
                <label for="jdt-<?php echo $key ?>">
                    <?php echo array_key_exists($key, Transmission::FIELDS) ? Transmission::FIELDS[$key] : $key ?>
                </label>
                <input type="text" id="jdt-<?php echo $key ?>" disabled
                       value="<?php echo $value ?>">
            </p>
        <?php endif; endforeach ?>
    <?php endif ?>
</div>
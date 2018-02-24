<?php
use Rdlv\JDanger\Meta;
use Rdlv\JDanger\Transmission;
?>
<div class="JdtForm">
    
    <?php $typeUrl = !!get_post_meta($post->ID, 'jdt_type_url', true) ?>
    <p class="JdtForm-row source">
        <label for="jdt-type-url">
            Source URL
            &nbsp;
            <input id="jdt-type-url" type="checkbox" name="jdt_type_url"
                   value="1"<?php echo $typeUrl ? ' checked' : '' ?>>
        </label>
    </p>
    
    <?php $url = get_post_meta($post->ID, 'jdt_url', true) ?>
    <p class="JdtForm-row url"<?php echo !$typeUrl ? ' hidden' : '' ?>>
        <label for="jdt-url">URL</label>
        <input type="text" name="jdt_url" id="jdt-url"
               value="<?php echo $typeUrl ? esc_attr($url) : '' ?>">
    </p>

    <p class="JdtForm-row file"<?php echo $typeUrl ? ' hidden' : '' ?>>
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
        <?php $fields = array_unique(array_merge(Transmission::DISPLAY_FIELDS, Transmission::EDITABLE_FIELDS)) ?>
        <?php foreach ($fields as $key): ?>
            <?php $value = array_key_exists($key, $meta) ? $meta[$key] : '' ?>
            <?php $editable = in_array($key, Transmission::EDITABLE_FIELDS) ?>
            <p class="JdtForm-row <?php echo $key ?>">
                <label for="jdt-<?php echo $key ?>">
                    <?php echo array_key_exists($key, Transmission::FIELD_LABELS) ? Transmission::FIELD_LABELS[$key] : $key ?>
                </label>
                <input type="text" id="jdt-<?php echo $key ?>"
                       <?php echo $editable ? 'name="jdt_meta['. $key .']"' : 'disabled' ?>
                       value="<?php echo $value ?>">
            </p>
        <?php endforeach ?>
    <?php endif ?>
</div>
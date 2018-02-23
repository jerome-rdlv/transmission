<?php

namespace Rdlv\JDanger;

use DateTime;

class Transmission
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Transmission();
        }
        return self::$instance;
    }

    /**
     * Save meta fields
     */
    const FIELDS = [
        Meta::TITLE => 'Titre',
        Meta::ALBUM => 'Album',
        Meta::ARTIST => 'Artiste',
        Meta::YEAR => 'Année',
        Meta::GENRE => 'Genre',
        Meta::PLAYTIME => 'Durée',
    ];
    const CPT = 'jdanger_transmission';
    
    public function __construct()
    {
    }

    public function init()
    {
        register_post_type(self::CPT, [
            'labels'    => [
                'name'          => 'Transmissions',
                'singular_name' => 'Transmission',
            ],
            'public'    => false,
            'show_ui'   => true,
            'menu_icon' => 'dashicons-playlist-audio',
            'supports'  => [
                'title',
                'thumbnail',
                'revisions',
            ],
        ]);

        // hide SDY metabox
        add_action('load-post.php', [$this, 'addMetaboxes']);
        add_action('load-post-new.php', [$this, 'addMetaboxes']);

        // transmission data
        add_action('edit_form_after_title', [$this, 'addFields']);
        add_action('save_post', [$this, 'saveFields'], 10, 2);

        // admin
        wp_register_style('jdt-main', plugins_url('assets/dist/admin.css', __DIR__));

        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('admin_menu', function () {
            add_submenu_page(
                'edit.php?post_type='. self::CPT,
                'Programme',
                'Programme',
                'read',
                'jdt_flow',
                function () {
                    include __DIR__ .'/../inc/flow.php';
                }
            );
        });
        
        // play on front
        wp_register_script('transmission', plugins_url('assets/dist/player.js', __DIR__), ['mediaelement-core'], false, true);
        add_action('wp', [$this, 'lateInit']);
    }
    
    public function lateInit()
    {
        if (is_front_page()) {
            $time = new DateTime();
//            $time->sub(new \DateInterval('PT11M'));
            wp_localize_script('transmission', 'jdanger_transmission', $this->getFlow($time)->toArray());
            wp_enqueue_script('transmission');
            add_action('wp_footer', [$this, 'playOnFront']);
        }
    }
    
    public function playOnFront()
    {
        echo '<!-- Transmission -->';
    }

    public function enqueueScripts()
    {
        global $post_type, $plugin_page;
        if ($post_type === self::CPT || $plugin_page == 'jdt_flow') {
            wp_enqueue_style('jdt-main');
        }
    }

    public function addFields($post)
    {
        if ($post->post_type !== self::CPT) {
            return;
        }
        wp_nonce_field(basename(__FILE__), 'jdanger_transmission_nonce');
        include __DIR__ . '/../inc/fields.php';
    }

    public function saveFields($post_id, $post)
    {
        if ($post->post_type !== self::CPT) {
            return;
        }

        if (!isset($_POST['jdanger_transmission_nonce']) || !wp_verify_nonce($_POST['jdanger_transmission_nonce'], basename(__FILE__))) {
            return;
        }
        
        $color = isset($_POST['jdt_color']) ? $_POST['jdt_color'] : '';
        update_post_meta($post_id, 'jdt_color', $color);
        
        $url = isset($_POST['jdt_url']) ? $_POST['jdt_url'] : '';
        if (get_post_meta($post_id, 'jdt_url', true) !== $url) {

            update_post_meta($post_id, 'jdt_url', $url);
            
            $meta = Meta::getMeta($url);
            
            // handle duration
            update_post_meta($post_id, 'jdt_duration', $meta[Meta::DURATION]);

            // if picture, save it
            $img = $meta[Meta::PICTURE_DATA];
            $imgHash = sha1($img);
            if ($img && get_post_meta($post_id, 'jdt_img_hash', true) !== $imgHash) {
                $attachmentId = $this->handleImage(
                    $post_id,
                    $img,
                    pathinfo($url, PATHINFO_FILENAME) . '.' . $meta[Meta::PICTURE_EXT],
                    $meta[Meta::PICTURE_TYPE]
                );

                if ($attachmentId && !is_wp_error($attachmentId)) {
                    set_post_thumbnail($post_id, $attachmentId);

                    // save picture hash for later comparison
                    $path = get_attached_file($attachmentId);
                    update_post_meta($post_id, 'jdt_img_hash', sha1(file_get_contents($path)));
                }
            }
            
            unset($meta[Meta::PICTURE_DATA]);
            unset($meta[Meta::PICTURE_EXT]);
            unset($meta[Meta::PICTURE_TYPE]);

            // handle meta
            update_post_meta($post_id, 'jdt_meta', array_filter($meta));
        }
    }
    
    public function handleImage($post_id, $img_data, $filename, $type)
    {
        $path = wp_upload_dir()['path'] .'/'. $filename;
        
        $inc = 1;
        while (file_exists($path)) {
            $path = preg_replace('/(.*?)(_[0-9]+)?(\.[^.]+)$/', '\1_1\3', $path);
            ++$inc;
        }
        
        if (file_put_contents($path, $img_data)) {

            $id = wp_insert_attachment([
                'post_title'     => $filename,
                'post_content'   => '',
                'post_status'    => 'publish',
                'post_mime_type' => $type
            ], $path, $post_id);

            if (is_wp_error($id)) {
                unlink($path);
            }

            return $id;
        }
        return null;
    }

    public function addMetaboxes()
    {
        add_action('add_meta_boxes_' . self::CPT, [$this, 'addMetabox']);
    }

    public function addMetabox()
    {
        remove_meta_box(
            'sdy_pl-meta-box', // id
            self::CPT, // screen
            'normal' // context
        );
    }

    /**
     * @param null $time
     * @return Flow
     */
    public function getFlow($time = null)
    {
        return new Flow($this->getSessions(), $time);
    }
    
    public function getSessions()
    {
        global $wpdb;
        $session = [];
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT
                p.ID AS id,
                p.post_title AS title,
                p.post_date AS date,
                pm.meta_value AS duration,
                pm1.meta_value AS url,
                pm2.meta_value AS color,
                pm3.meta_value AS meta
            FROM $wpdb->posts p
            LEFT JOIN $wpdb->postmeta pm ON pm.post_id = p.ID AND pm.meta_key = 'jdt_duration'
            LEFT JOIN $wpdb->postmeta pm1 ON pm1.post_id = p.ID AND pm1.meta_key = 'jdt_url'
            LEFT JOIN $wpdb->postmeta pm2 ON pm2.post_id = p.ID AND pm2.meta_key = 'jdt_color'
            LEFT JOIN $wpdb->postmeta pm3 ON pm3.post_id = p.ID AND pm3.meta_key = 'jdt_meta'
            WHERE p.post_type = %s AND p.post_status = 'publish'
            ORDER BY p.post_date DESC
        ", self::CPT));
        
        // post-treatment
        $dateFormat = 'Y-m-d H:i:s';
        foreach ($results as &$result) {
            $result->date = DateTime::createFromFormat($dateFormat, $result->date);
            $result->duration = (int)$result->duration;
            $result->meta = unserialize($result->meta);
            $result->playtime = $result->meta['playtime'];
            unset($result->meta['playtime']);
            $session[] = $result;
        }
        
        return $session;
    }
}
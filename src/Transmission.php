<?php

namespace Rdlv\JDanger;

use DateTime;
use WP_Query;
use WP_Screen;

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
    const FIELD_LABELS = [
        Meta::LENGTH_FORMATTED => 'Durée',
        Meta::ARTIST           => 'Artiste',
        Meta::ALBUM            => 'Album',
        Meta::YEAR             => 'Année',
    ];
    const DISPLAY_FIELDS = [
        Meta::LENGTH_FORMATTED,
        Meta::ARTIST,
        Meta::ALBUM,
        Meta::YEAR,
    ];
    const EDITABLE_FIELDS = [
        Meta::ARTIST,
        Meta::ALBUM,
        Meta::YEAR,
    ];
    
    const CPT = 'jdanger_transmission';
    const RSS_ACTION = 'transmission_rss';
    const RSS_URL = 'feed/transmission';
    
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
            'public'    => true,
            'has_archive' => true,
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
        wp_register_style('jdt-admin', plugins_url('assets/dist/admin.css', __DIR__));
        wp_register_script('jdt-admin', plugins_url('assets/dist/admin.js', __DIR__), [], false, true);
        add_action('current_screen', [$this, 'flowPage']);

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
        
        // podcast
//        add_rewrite_rule('^'. self::RSS_URL .'/?', 'index.php?action='. self::RSS_ACTION);
        add_action('parse_query', [$this, 'rssFeed']);
        add_action('wp_head', function () {
            printf(
                '<link rel="alternate" type="application/rss+xml" title="Transmission Podcast" href="%s" />',
                get_post_type_archive_feed_link(Transmission::CPT)
            );
        });
        add_filter('request', function ($qv) {
            if (isset($qv['feed'])) {
                $qv['post_type'] = get_post_types();
            }
            return $qv;
        });
    }

    public function lateInit()
    {
        if (is_front_page() && get_option('jdt_playing')) {
            $time = new DateTime();
            // use this to offset play time
//            $time->sub(new \DateInterval('PT11M'));
            $flow = $this->getFlow($time);
            if ($flow->hasSessions()) {
                wp_localize_script('transmission', 'jdanger_transmission', $flow->toArray());
                wp_enqueue_script('transmission');
            }
        }
    }
    
    public function enqueueScripts()
    {
        global $post_type, $plugin_page;
        if ($post_type === self::CPT || $plugin_page == 'jdt_flow') {
            wp_enqueue_style('jdt-admin');
            wp_enqueue_script('jdt-admin');
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
        
        $typeUrl = isset($_POST['jdt_type_url']) ? $_POST['jdt_type_url'] : false;
        if ($typeUrl) {
            $aid = null;
            $url = isset($_POST['jdt_url']) ? $_POST['jdt_url'] : '';
        }
        else {
            $aid = isset($_POST['jdt_aid']) ? $_POST['jdt_aid'] : '';
            $url = wp_get_attachment_url($aid);
        }
        
        if (get_post_meta($post_id, 'jdt_url', true) !== $url || get_post_meta($post_id, 'jdt_type_url', true) !== $typeUrl) {
            
            update_post_meta($post_id, 'jdt_type_url', $typeUrl);
            update_post_meta($post_id, 'jdt_aid', $aid);
            update_post_meta($post_id, 'jdt_url', $url);
            
            if ($typeUrl) {
                $meta = Meta::getMeta($url);
            }
            else {
                $meta = wp_get_attachment_metadata($aid);
            }
            
            if ($meta) {
                if ($meta['title'] && !$post->post_title) {
                    global $wpdb;
                    $wpdb->update(
                        $wpdb->posts,
                        ['post_title' => $meta['title']],
                        ['ID' => $post_id]
                    );
                }

                // handle duration
                update_post_meta($post_id, 'jdt_duration', $meta[Meta::LENGTH]);

                // handle thumbnail image
                if ($typeUrl) {
                    $this->handleUrlImage($post_id, $meta, $url);
                } else {
                    $imgId = get_post_meta($aid, '_thumbnail_id', true);
                    if ($imgId) {
                        set_post_thumbnail($post_id, $imgId);
                    }
                }

                unset($meta[Meta::IMAGE]);

                // handle meta
                update_post_meta($post_id, 'jdt_meta', array_filter($meta));
            }
            else {
                error_log(sprintf('Can not get metadata from url %s. Duration set to 0.', $url));
                update_post_meta($post_id, 'jdt_duration', 0);
            }
        }
        else {
            $postedMeta = isset($_POST['jdt_meta']) ? $_POST['jdt_meta'] : [];
            if ($postedMeta) {
                $meta = get_post_meta($post_id, 'jdt_meta', true);
                foreach ($postedMeta as $key => $value) {
                    if (in_array($key, self::EDITABLE_FIELDS) && $value) {
                        $meta[$key] = $value;
                    }
                }
                update_post_meta($post_id, 'jdt_meta', $meta);
            }
        }
    }
    
    /**
     * @param $post_id
     * @param $meta
     * @param $url
     */
    private function handleUrlImage($post_id, $meta, $url)
    {
        $image = $meta[Meta::IMAGE];
        if (!$image) {
            return;
        }
        
        $imgData = $image[Meta::IMAGE_DATA];
        if (!$imgData) {
            return;
        }
        
        $imgHash = sha1($imgData);
        
        // img same as previous one
        if (get_post_meta($post_id, 'jdt_img_hash', true) === $imgHash) {
            return;
        }
        
        $filename = pathinfo($url, PATHINFO_FILENAME) . '.' . $meta[Meta::IMAGE_EXTENSION];
        $path = wp_upload_dir()['path'] .'/'. $filename;
        
        // increment filename in case it already exists
        $inc = 1;
        while (file_exists($path)) {
            $path = preg_replace('/(.*?)(_[0-9]+)?(\.[^.]+)$/', '\1_1\3', $path);
            ++$inc;
        }
        
        // save file contents
        if (file_put_contents($path, $imgData)) {

            // create attachment
            $attachmentId = wp_insert_attachment([
                'post_title'     => $filename,
                'post_content'   => '',
                'post_status'    => 'publish',
                'post_mime_type' => $meta[Meta::IMAGE_MIME]
            ], $path, $post_id);

            if (is_wp_error($attachmentId)) {
                unlink($path);
                error_log(sprintf(
                    'Failed insert attachment for post %s at path %s: %s',
                    $post_id,
                    $path,
                    $attachmentId->get_error_message()
                ));
            }
            else {
                set_post_thumbnail($post_id, $attachmentId);

                // save picture hash for later comparison
                update_post_meta($post_id, 'jdt_img_hash', $imgHash);
            }
        }
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
                pm.meta_value AS length,
                pm1.meta_value AS url,
                pm2.meta_value AS color,
                pm3.meta_value AS meta
            FROM $wpdb->posts p
            LEFT JOIN $wpdb->postmeta pm ON pm.post_id = p.ID AND pm.meta_key = 'jdt_length'
            LEFT JOIN $wpdb->postmeta pm1 ON pm1.post_id = p.ID AND pm1.meta_key = 'jdt_url'
            LEFT JOIN $wpdb->postmeta pm2 ON pm2.post_id = p.ID AND pm2.meta_key = 'jdt_color'
            LEFT JOIN $wpdb->postmeta pm3 ON pm3.post_id = p.ID AND pm3.meta_key = 'jdt_meta'
            WHERE p.post_type = %s AND p.post_status = 'publish'
            ORDER BY p.post_date DESC
        ", self::CPT));
        
        // post-treatment
        $dateFormat = 'Y-m-d H:i:s';
        $tz = (new DateTime())->getTimezone();
        foreach ($results as &$result) {
            $result->date = DateTime::createFromFormat($dateFormat, $result->date, $tz);
            $result->length = (int)$result->length;
            $result->meta = unserialize($result->meta);
            $result->length_formatted = $result->meta['length_formatted'];
            $session[] = $result;
        }
        
        return $session;
    }

    public function rssFeed(WP_Query $query)
    {
        if (is_admin() || !$query->is_main_query() || !$query->get('feed')) {
            return;
        }
        if (strpos($_SERVER['REQUEST_URI'], self::CPT) === false) {
            return;
        }
        
        $sessions = $this->getSessions();
        
        header('Content-type: application/rss+xml');
        echo '<?xml version="1.0" encoding="'. get_option('blog_charset') .'"?>'."\n";
        include __DIR__ .'/../inc/rss.xml.php';
        exit;
    }
    
    public function flowPage(WP_Screen $screen)
    {
        if ($screen->id !== self::CPT .'_page_jdt_flow') {
            return;
        }

        if (isset($_POST['play_toggle'])) {
            if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'jdt_play_toggle_nonce')) {
                update_option('jdt_playing', !get_option('jdt_playing'));
                wp_redirect($_SERVER['REQUEST_URI']);
                exit;
            }
        }

        if (isset($_GET['today'])) {
            $params = [];
            foreach (explode('&', $_SERVER['QUERY_STRING']) as $pair) {
                list($key, $value) = explode('=', $pair);
                if (!in_array($key, ['date', 'today'])) {
                    $params[$key] = $value;
                }
            }
            $url = $_SERVER['PHP_SELF'] .'?'. build_query($params);
            wp_redirect($url);
            exit;
        }
    }
}
<?php use Rdlv\JDanger\Meta;
$descFields = [Meta::LENGTH_FORMATTED, Meta::ARTIST, Meta::ALBUM, Meta::YEAR];
?><rss version="2.0">
    <channel>
        <title><?php wp_title_rss() ?></title>
        <description><?php bloginfo_rss('description') ?></description>
        <language><?php bloginfo_rss('language') ?></language>
        <link><?php bloginfo_rss('url') ?></link>
        
        <?php foreach ($sessions as $session):
            $desc = '';
            foreach ($descFields as $key) {
                if (array_key_exists($key, $session->meta)) {
                    $desc .= $desc ? ' - ' : '';
                    $desc .= \Rdlv\JDanger\Transmission::FIELD_LABELS[$key] . ': '. $session->meta[$key];
                }
            }
            ?><item>
                <title><?php echo $session->title ?></title>
                <description><?php echo $desc ?></description>
                <link><?php echo get_home_url() ?></link>
                <pubDate><?php
                    echo $session->date->format('D, d M Y H:i:s +0000')
                    ?></pubDate>
                <guid isPermaLink="false"><?php echo $session->url ?></guid>
                <enclosure url="<?php echo $session->url ?>" length="<?php echo (int)$session->meta['filesize'] ?>" type="<?php echo $session->meta['mime_type'] ?>"/>
        </item>
        <?php endforeach ?>
    </channel>
</rss>
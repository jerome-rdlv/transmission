<?php
use Rdlv\JDanger\Flow;
use Rdlv\JDanger\Transmission;

$current = (int)date('U');
$date = DateTime::createFromFormat('Y-m-d', isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'));
$ts = $date->format('U');
$ts -= date('w', $ts) == 0 ? 3600 * 24 * 7 : 0;
$time = DateTime::createFromFormat(
    'Y-m-d H:i:s',
    date('Y-m-d', strtotime('-' . (date('w', $ts) - 1) . ' days', $ts)) . ' 00:00:00'
);

$flow = Transmission::getInstance()->getFlow();

$interval = 'P%dD';
$days = 7;

$end = clone $time;
$end->add(new DateInterval(sprintf($interval, $days)));

//$date = clone $time;
$dayInterval = new DateInterval(sprintf($interval, 1));

?>
<div class="wrap JdtProg">
    <h1 class="wp-heading-inline">Programme</h1>
    
    <div class="JdtProg-actions">
        <?php $playing = get_option('jdt_playing') ?>
        <form method="GET" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
            <label for="jdt_prog_date">Du</label>
            <input type="hidden" name="post_type" value="<?php echo isset($_GET['post_type']) ? $_GET['post_type'] : '' ?>">
            <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? $_GET['page'] : '' ?>">
            <input name="date" id="jdt_prog_date" type="date" value="<?php echo $date->format('Y-m-d') ?>">
            <button type="submit" class="page-title-action">
                Voir
            </button>
            <button type="submit" class="page-title-action" name="today">
                Aujourd’hui
            </button>
        </form>
        <form method="POST" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
            <?php wp_nonce_field('jdt_play_toggle_nonce') ?>
            <button type="submit" class="page-title-action" name="play_toggle">
                <?php echo $playing ? 'Stop' : 'Lecture' ?> 
            </button>
            <span class="JdtProg-playing-status <?php echo $playing ? 'on' : 'off' ?>">
                <?php echo $playing ? 'En diffusion' : 'Muet' ?>
            </span>
        </form>
    </div>
    
    <?php if ($flow->getSessions()): ?>
        <div class="JdtProg-days">
            <div class="JdtProg-hours">
                <?php for ($hour = 0; $hour <= 24; ++$hour): ?>
                    <span class="JdtProg-hour" style="top: <?php echo ($hour * 100 / 24) ?>%;">
                        <span class="JdtProg-hour-inner">
                            <?php printf('%02d:00', $hour) ?>
                        </span>
                    </span>
                <?php endfor ?>
            </div>
            <?php $tomorrow = clone $time ?>
            <?php while ($time < $end): ?>
                <div class="JdtProg-day day-<?php echo $time->format('Y-m-d') ?>" 
                     style="width:<?php echo (100 / $days) ?>%;">
                    <?php
                    $flow->setTime($tomorrow);
                    $tomorrow->add($dayInterval);
                    
                    $session = $flow->next();
                    $time = $flow->getTime();
                    ?>
                    <h2 class="JdtProg-title">
                        <span class="JdtProg-title-day">
                            <?php echo $time->format('l') ?>
                        </span>
                        <span class="JdtProg-title-date">
                            <?php echo $time->format('j/m/Y') ?>
                        </span>
                    </h2>
                    <?php if ($current > $time->format('U') && $current < $tomorrow->format('U')): ?>
                        <div class="JdtProg-marker" style="top: <?php echo (($current - $time->format('U')) * 100 / 3600 / 24) ?>%;">
                            <?php include __DIR__ .'/marker.svg' ?>
                        </div>
                    <?php endif ?>
                    <?php while ($time < $tomorrow): ?>
                        <?php
                        $length = $session->length;
                        $timeLeft = $tomorrow->format('U') - $time->format('U');
                        if ($length > $timeLeft) {
                            $length = $timeLeft;
                        }
                        $height = $length / 3600 * 100 / 24;
                        $isCurrent = $current > $time->format('U') && $current < ((int)$time->format('U') + $length);
                        ?>
                        <div class="JdtProg-item<?php if ($isCurrent) echo ' current' ?>" style="height: <?php echo $height ?>%;" data-id="<?php echo $session->id ?>">
                            <?php if ($session->id !== Flow::PLACEHOLDER_ID): ?>
                                <span class="JdtProg-info<?php if ($height < 4  || $session->offset) echo ' wide-hidden' ?>" 
                                      style="border-color:<?php echo $session->color ?>">
                                    <span class="JdtProg-info-inner">
                                        <span class="JdtProg-info-playtime"><?php echo $time->format('H:i') ?></span>
                                        <?php echo $session->title ?><?php if ($session->offset) echo ' (suite)' ?>
                                    </span>
                                </span>
                            <?php endif ?>
                            <div class="JdtProg-session"
                                 <?php if ($session->id !== Flow::PLACEHOLDER_ID): ?>
                                 title="<?php echo $time->format('H:i') .' / '. $session->title . ($session->offset ? ' (suite)' : '') ?>"
                                 <?php endif ?>
                                 style="background:<?php echo $session->color ?>;"></div>
                        </div>
                        <?php $session = $flow->next() ?>
                        <?php $time = $flow->getTime() ?>
                    <?php endwhile ?>
                </div>
            <?php endwhile ?>
        </div>
    <?php else: ?>
        <p class="JdtProg-empty">
            Aucune transmission pour le moment.
        </p>
    <?php endif ?>
</div>
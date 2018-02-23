<?php
use Rdlv\JDanger\Flow;
use Rdlv\JDanger\Transmission;

$date = DateTime::createFromFormat('Y-m-d', isset($_GET['date']) ? $_GET['date'] : '');
$ts = $date ? $date->format('U') : time();
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
    <h1>Programme</h1>
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
                    <h2 class="JdtProg-title">
                        <span class="JdtProg-title-day">
                            <?php echo $time->format('l') ?>
                        </span>
                        <span class="JdtProg-title-date">
                            <?php echo $time->format('j/m/Y') ?>
                        </span>
                    </h2>
                <?php
                $flow->setTime($tomorrow);
                $tomorrow->add($dayInterval);
                
                $session = $flow->next();
                $time = $flow->getTime();
                ?>
                <?php while ($time < $tomorrow): ?>
                    <?php
                    $duration = $session->duration;
                    $timeLeft = $tomorrow->format('U') - $time->format('U');
                    if ($duration > $timeLeft) {
                        $duration = $timeLeft;
                    }
                    $height = $duration / 3600 * 100 / 24;
                    ?>
                    <div class="JdtProg-item" style="height: <?php echo $height ?>%;" data-id="<?php echo $session->id ?>">
                        <?php if ($height > 4 && $session->id !== Flow::PLACEHOLDER_ID && !$session->offset): ?>
                            <span class="JdtProg-info" style="border-color:<?php echo $session->color ?>">
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
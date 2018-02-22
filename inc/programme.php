<?php
use JDanger\Transmission;
$prog = Transmission::getInstance()->getProgramme();

/** @var DateTime $start */
$start = $prog->getStart();
$end = clone $start;
$cols = 7;
$end->add(new DateInterval('P'. $cols .'D'));
$date = clone $start;
$day = $date->format('Y-m-d');
$dayInterval = new DateInterval('P1D');

function openDay(DateTime $date)
{
    echo '<div class="JdtProg-day day-'. $date->format('Y-m-d') .'">';
    echo '<h2 class="JdtProg-title">'. $date->format('D j m Y') .'</h2>';
}

?>
<div class="wrap JdtProg">
    <h1>Programme</h1>
    <?php if ($prog): ?>
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
            <?php $tomorrow = clone $start ?>
            <?php while ($date < $end): ?>
                <div class="JdtProg-day day-<?php echo $date->format('Y-m-d') ?>" 
                     style="width:<?php echo (100 / $cols) ?>%;">
                    
                    
                    <h2 class="JdtProg-title">
                        <span class="JdtProg-title-day">
                            <?php echo $date->format('l') ?>
                        </span>
                        <span class="JdtProg-title-date">
                            <?php echo $date->format('j/m/Y') ?>
                        </span>
                    </h2>
                <?php
                if ($date != $start) {
                    $prog->setStart($tomorrow);
                    $date = $prog->getStart();
                }
                $index = $prog->getIndex();
                $offset = $prog->getOffset();
                $tomorrow->add($dayInterval);
                ?>
                <?php while ($date < $tomorrow): ?>
                    <?php
                    $session = $prog->getSession($index);
                    $duration = $session->duration - $offset;
                    $timeLeft = $tomorrow->format('U') - $date->format('U');
                    if ($duration > $timeLeft) {
                        $duration = $timeLeft;
                    }
                    $height = $duration / 3600 * 100 / 24;
                    ?>
                    <div class="JdtProg-item" style="height: <?php echo $height ?>%;" id="transmission-<?php echo $session->id ?>">
                        <?php if (!$offset): ?>
                        <span class="JdtProg-info" style="border-color:<?php echo $session->color ?>">
                            <span class="JdtProg-info-inner">
                                <?php echo $session->title ?><?php if ($offset) echo ' (suite)' ?>
                                <span class="JdtProg-info-playtime">/ <?php echo $session->playtime ?></span>
                            </span>
                        </span>
                        <?php endif ?>
                        <div class="JdtProg-session"
                             <?php if ($offset) echo ' title="'. $session->title .' (suite)"' ?>
                             style="background:<?php echo $session->color ?>;"></div>
                    </div>
                    <?php $date->add(DateInterval::createFromDateString(($session->duration - $offset) .' seconds')) ?>
                    <?php ++$index ?>
                    <?php $offset = 0 ?>
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
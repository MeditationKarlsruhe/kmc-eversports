<?php

declare(strict_types=1);

/** @var list<\Kmc\Eversports\ClassGroup> $groups */
/** @var bool $showImage */
?>
<div class="kmc-eversports-events">
    <?php foreach ($groups as $group): ?>
        <div class="kmc-event-group">
            <?php if ($showImage && $group->imageUrl !== null): ?>
                <div class="kmc-event-group__image">
                    <img src="<?php echo esc_url($group->imageUrl); ?>" alt="<?php echo esc_attr($group->title); ?>" />
                </div>
            <?php endif; ?>

            <h2 class="kmc-event-group__title">
                <?php echo esc_html($group->title); ?>
            </h2>

            <div class="kmc-event-group__description">
                <?php echo wp_kses_post($group->descriptionHtml); ?>
            </div>

            <div class="kmc-event-group__appointments">
                <?php foreach ($group->appointments as $appointment): ?>
                    <div class="kmc-appointment">
                        <span class="kmc-appointment__date">
                            <?php echo esc_html($appointment->start->format('d.m.Y')); ?>
                        </span>
                        <span class="kmc-appointment__time">
                            <?php echo esc_html(
                                $appointment->start->format('H:i') . ' – ' . $appointment->end->format('H:i')
                            ); ?>
                        </span>
                        <?php if ($appointment->registrationLink !== null): ?>
                            <a
                                class="kmc-appointment__register"
                                href="<?php echo esc_url($appointment->registrationLink); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Anmeldung
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

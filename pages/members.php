<?php
$people = db()->query('SELECT * FROM members WHERE published = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
$groups = member_types();
render_header('Состав РОО «Развитие АПК» — совет, члены, участники грантов', array(
    'description' => 'Состав РОО «Развитие АПК»: органы управления, члены организации и участники грантовых проектов в Агинском.',
    'canonical' => 'members',
    'breadcrumbs' => array(
        array('name' => 'Главная', 'url' => canonical('')),
        array('name' => 'Состав', 'url' => canonical('members')),
    ),
    'schema' => array(
        '@context' => 'https://schema.org',
        '@type' => 'AboutPage',
        'name' => 'Состав организации',
        'url' => canonical('members'),
    ),
));
?>
<div class="wrap page-head">
    <h1>Состав организации</h1>
    <p class="lede">Органы управления, члены РОО и участники грантовых проектов. Состав можно уточнять в админке.</p>
</div>
<div class="wrap">
    <?php if (!$people): ?>
        <p class="empty">Сведения о составе пока не опубликованы.</p>
    <?php else: ?>
        <?php foreach ($groups as $type => $label): ?>
            <?php
            $chunk = array();
            foreach ($people as $person) {
                if ($person['member_type'] === $type) {
                    $chunk[] = $person;
                }
            }
            if (!$chunk) {
                continue;
            }
            ?>
            <h2 class="group-title"><?php echo e($label); ?></h2>
            <div class="member-list">
                <?php foreach ($chunk as $person): ?>
                    <article class="member-card">
                        <div class="member-mark" aria-hidden="true"><?php echo e(initials($person['full_name'])); ?></div>
                        <div>
                        <h2><?php echo e($person['full_name']); ?></h2>
                        <?php if ($person['role_title']): ?><p><strong><?php echo e($person['role_title']); ?></strong></p><?php endif; ?>
                        <?php if ($person['bio']): ?><p><?php echo e($person['bio']); ?></p><?php endif; ?>
                        <?php if ($person['grant_involvement']): ?><p>В грантах: <?php echo e($person['grant_involvement']); ?></p><?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php
render_footer();

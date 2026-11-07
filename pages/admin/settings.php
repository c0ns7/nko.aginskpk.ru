<?php
$fields = array(
    'org_short' => 'Краткое название',
    'org_full' => 'Полное название',
    'org_about' => 'Текст на главной',
    'org_head' => 'Руководитель',
    'org_address' => 'Адрес',
    'org_phone' => 'Телефон',
    'org_email' => 'Эл. почта',
    'org_inn' => 'ИНН',
    'org_ogrn' => 'ОГРН',
    'org_kpp' => 'КПП',
    'seo_title' => 'SEO-заголовок главной (title)',
    'seo_description' => 'SEO-описание сайта (meta description)',
    'yandex_verification' => 'Яндекс.Вебмастер (код подтверждения)',
    'google_verification' => 'Google Search Console (код подтверждения)',
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($fields as $key => $label) {
        $value = isset($_POST[$key]) ? trim($_POST[$key]) : '';
        setting_set($key, $value);
    }
    flash_set('ok', 'Реквизиты сохранены.');
    redirect('admin/settings');
}

render_admin_header('Реквизиты сайта');
?>
<h1>Реквизиты сайта</h1>
<form class="form" method="post">
    <?php echo csrf_field(); ?>
    <?php foreach ($fields as $key => $label): ?>
        <label>
            <span><?php echo e($label); ?></span>
            <?php if ($key === 'org_about' || $key === 'org_full' || $key === 'org_address' || $key === 'seo_description'): ?>
                <textarea name="<?php echo e($key); ?>"><?php echo e(setting($key)); ?></textarea>
            <?php else: ?>
                <input type="text" name="<?php echo e($key); ?>" value="<?php echo e(setting($key)); ?>">
            <?php endif; ?>
        </label>
    <?php endforeach; ?>
    <button class="btn" type="submit">Сохранить</button>
</form>
<?php
render_admin_footer();

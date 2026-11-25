<?php
declare(strict_types=1);
/** @var int $code */
/** @var string $title */
/** @var string $message */
$code = (int) ($code ?? 404);
$title = (string) ($title ?? '页面不存在');
$message = (string) ($message ?? '请检查地址。');
?>
<div class="error-page">
  <div class="error-code"><?= $code ?></div>
  <h1><?= CuiHtml::e($title) ?></h1>
  <p><?= CuiHtml::e($message) ?></p>
  <p class="muted">request_id: <code><?= CuiHtml::e(APP_REQUEST_ID) ?></code> · release: <?= CuiHtml::e(APP_RELEASE_ID) ?></p>
  <div class="row"><?= CuiButton::render(['label' => '回到首页', 'variant' => 'primary', 'href' => '/']) ?><?= CuiButton::render(['label' => '查看版本', 'href' => '/trace']) ?></div>
</div>

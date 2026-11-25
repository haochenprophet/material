<?php
declare(strict_types=1);
$tasks = DemoData::pickTasks();
$count = count($tasks);

echo '<div class="pda">';
echo '<div class="pda-top"><div><b>拣货 #P-1024</b><span>波次 3/8 · 订单 SO-20261124-0081</span></div><span class="offline">离线可用 · 待同步 2</span></div>';
echo '<form class="pda-scan" action="' . Cui::url('/ops/pick/P-1024') . '" method="get">';
echo '<input class="input" type="text" name="scan" placeholder="扫描库位 / 物料条码" autofocus autocomplete="off" aria-label="扫描条码">';
echo CuiButton::render(['label' => '扫描', 'variant' => 'teal', 'type' => 'submit']);
echo '</form>';
echo '<div class="pda-hint">扫描枪回车即提交；摄像头扫码兜底。当前 4/8。</div>';
foreach ($tasks as $t) {
    echo '<div class="pda-card"><span class="loc">' . CuiHtml::e($t['loc']) . '</span><div class="pda-info"><b>' . CuiHtml::e($t['mpn']) . '</b><small>' . CuiHtml::e($t['desc']) . '</small></div><span class="qty">' . CuiHtml::e($t['qty']) . '</span></div>';
}
echo '<button class="pda-confirm" data-action="toast" data-toast="已确认拣货 4/8，继续扫描">确认拣货（4/8）</button>';
echo '<p class="pda-note">大按钮 · 高对比 · 扫描优先 · 断网可继续，联网自动同步。</p>';
echo '</div>';

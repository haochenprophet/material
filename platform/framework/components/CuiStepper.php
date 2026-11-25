<?php
declare(strict_types=1);

final class CuiStepper
{
    /**
     * @param array<int, array{label:string, done?:bool, current?:bool}> $steps
     */
    public static function render(array $steps): string
    {
        $html = '<div class="stepper" role="list" aria-label="提报步骤">';
        foreach ($steps as $i => $step) {
            $state = !empty($step['current']) ? 'on' : (!empty($step['done']) ? 'done' : '');
            $html .= '<span class="' . $state . '" role="listitem"' . (!empty($step['current']) ? ' aria-current="step"' : '') . '>';
            $html .= '<i>' . (!empty($step['done']) ? '✓' : (string) ($i + 1)) . '</i>' . CuiHtml::e((string) $step['label']) . '</span>';
        }
        return $html . '</div>';
    }
}

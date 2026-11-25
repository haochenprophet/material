<?php
declare(strict_types=1);

final class CuiProductCard
{
    /**
     * @param array{id:string,title:string,spec:string,price:string,unit?:string,stock:string,location?:string,badge?:string,badgeKind?:string,thumb?:string,thumbClass?:string,image?:string} $p
     */
    public static function render(array $p): string
    {
        $href = Cui::url('/p/' . rawurlencode((string) $p['id']));
        $thumbClass = 'thumb ' . ($p['thumbClass'] ?? '');
        $html = '<a class="product" href="' . $href . '">';
        if (!empty($p['image'])) {
            $html .= '<div class="' . CuiHtml::e(trim($thumbClass)) . '"><img src="' . CuiHtml::e((string) $p['image']) . '" alt="' . CuiHtml::e((string) ($p['title'] ?? '')) . '" loading="lazy"></div>';
        } else {
            $html .= '<div class="' . CuiHtml::e(trim($thumbClass)) . '">' . CuiHtml::e((string) ($p['thumb'] ?? $p['title'])) . '</div>';
        }
        $html .= '<div class="pbody"><b>' . CuiHtml::e((string) $p['title']) . '</b>';
        $html .= '<small>' . CuiHtml::e((string) $p['spec']) . '</small>';
        $html .= '<div class="price">' . CuiHtml::e((string) $p['price']) . (!empty($p['unit']) ? ' <small>/' . CuiHtml::e((string) $p['unit']) . '</small>' : '') . '</div>';
        $html .= '<div class="stock">' . CuiHtml::e((string) $p['stock']) . (!empty($p['location']) ? ' · ' . CuiHtml::e((string) $p['location']) : '') . '</div>';
        if (!empty($p['badge'])) {
            $html .= '<div class="pbadges">' . CuiBadge::render((string) $p['badge'], (string) ($p['badgeKind'] ?? 'ok')) . '</div>';
        }
        return $html . '</div></a>';
    }
}

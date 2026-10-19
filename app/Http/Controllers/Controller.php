<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Get base64 encoded thermal receipt logo.
     */
    protected function getReceiptLogoBase64(): string
    {
        static $cachedLogo = null;
        if ($cachedLogo !== null) {
            return $cachedLogo;
        }

        $logoPath = public_path('admin/images/logo-2.png');
        if (file_exists($logoPath)) {
            $cachedLogo = base64_encode(file_get_contents($logoPath));
        } else {
            $cachedLogo = '';
        }

        return $cachedLogo;
    }

    /**
     * Standard POS thermal receipt header HTML (consistent across all receipts).
     */
    protected function getReceiptHeaderHtml(string $receiptTitle, ?string $documentNo = null): string
    {
        $logoBase64 = $this->getReceiptLogoBase64();

        $logoHtml = $logoBase64
            ? '<img style="width:150px; height:120px; display:block; margin-left:60px;" src="data:image/png;base64,' . $logoBase64 . '"/>'
            : '<div class="text-center shop-name">TG SURGICALS</div>';

        $html = $logoHtml . '
            <p class="text-center" style="margin-top:0px; font-size:12px; margin-left:-20px;">
                <span>Shop 58, Mirwah Road TandoAllahyar.<br></span>
                <span>Phone # 0334-2830393<br></span>
            </p>

            <p class="text-center receipt-title">' . e($receiptTitle) . '</p>';

        if ($documentNo) {
            $html .= '<p class="text-center invoice-no">' . e($documentNo) . '</p>';
        }

        $html .= '<hr class="solid-line">';

        return $html;
    }
}


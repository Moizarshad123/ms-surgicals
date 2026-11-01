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

        $logoHtml = '';
        // if ($logoBase64) {
        //     $logoHtml = '<div style="text-align:center; margin-bottom:4px;">
        //         <img src="data:image/png;base64,' . $logoBase64 . '" style="width:75px; height:auto; display:inline-block;" />
        //     </div>';
        // } else {
        //     $logoHtml = '<div class="text-center shop-name" style="text-align:center; font-size:15px; font-weight:bold; margin-bottom:2px;">TG SURGICALS</div>';
        // }
        if ($logoBase64) {
            $logoHtml = '<div style="text-align:center; margin-bottom:4px;">
                <img src="data:image/png;base64,' . $logoBase64 . '" style="width:75px; height:auto; display:inline-block;" />
            </div>';
        } else {
            $logoHtml = '<div class="text-center shop-name" style="text-align:center; font-size:15px; font-weight:bold; margin-bottom:2px;">TG SURGICALS</div>';
        }

        // $html = $logoHtml . '
        $html = '
      

            <br><p class="text-center" style="text-align:center; margin:0 0 4px 0; font-size:11px; line-height:1.3;">
            <p class="text-center" style="text-align:center; margin:0 0 4px 0; font-size:11px; line-height:1.3;">
                <span>Shop 58, Mirwah Road TandoAllahyar.<br></span>
                <span>Phone # 0334-2830393<br></span>
            </p>

            <p class="text-center receipt-title" style="text-align:center; font-size:13px; font-weight:bold; margin:4px 0 2px 0;">' . e($receiptTitle) . '</p>';

        if ($documentNo) {
            $html .= '<p class="text-center invoice-no" style="text-align:center; font-size:11px; font-weight:bold; margin:0 0 4px 0;">' . e($documentNo) . '</p>';
        }

        $html .= '<hr class="solid-line">';

        return $html;
    }
}


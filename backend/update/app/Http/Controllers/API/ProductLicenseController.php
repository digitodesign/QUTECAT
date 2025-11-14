<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\ProductLicenseRepository;
use Illuminate\Http\Request;
use Mpdf\Mpdf;

class ProductLicenseController extends Controller
{
    public function downloadLicensePdf($licenses){
        $licenseIds = explode(',', $licenses);
        $licenseKeys = ProductLicenseRepository::query()->whereIn('id', $licenseIds)->get()->pluck('product_license')->toArray();
        $licenseData = ProductLicenseRepository::query()->whereIn('id', $licenseIds)->first();

        $setting = generaleSetting('setting');
        $logo = $setting->logo;

        $html = view('PDF.license', compact('licenseData','licenseKeys','logo'))->render();

        // Create mPDF instance
        $mpdf = new Mpdf([
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
            'default_font_size' => 12,
            'default_font' => 'dejavusans',
        ]);

        $mpdf->WriteHTML($html);

        // Output as download
        return response($mpdf->Output("license-{$licenseData->user?->name}.pdf", 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="license-'.$licenseData->user?->name.'.pdf"');
    }
}

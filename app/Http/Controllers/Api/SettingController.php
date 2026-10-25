<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Models\LabelPreference;
use App\Traits\ApiResponse;

class SettingController extends Controller
{
    use ApiResponse;

    /**
     * Get User Label Settings
     */
    public function labelSettings()
    {
        $user = Auth::user();
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }

        $preference = LabelPreference::where('user_id', $user->id)->first();
        
        // If no preference found, return default structure or null
        // Web controller passes 'preference' to view.
        
        if ($preference) {
            // Append full URL for logo if exists
            if ($preference->label_company_logo) {
                $preference->label_company_logo_url = url('storage/settings/label/' . $preference->label_company_logo);
            }
        }

        return $this->successResponse([
            'preference' => $preference
        ], 'Label settings fetched successfully');
    }



    /**
     * Update User Label Settings
     */
    public function updateLabelSettings(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }

        $data = $request->except('_token', 'label_company_logo');
        $user_id = $user->id;

        // Handle File Upload
        if ($request->hasFile('label_company_logo')) {
            $path = storage_path("app/public/settings/label");
            if (!File::isDirectory($path)) {
                File::makeDirectory($path, 0777, true, true);
            }

            $file = $request->file('label_company_logo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move($path, $filename);

            $data['label_company_logo'] = $filename;
        }

        // Handle Checkboxes Defaults
        // In API, if we are sending JSON, missing keys might mean unchanged or false. 
        // Typically for "update", we should merge with existing or assume false if it's a "form submit" style.
        // The web controller explicitly sets them to 0 if missing. We will follow that logic 
        // to Ensure parity with web behavior where checkboxes are not sent if unchecked.
        
        $checkboxes = [
            'label_show_invoice_value',
            'label_show_invoice_number',
            'label_show_seller_name',
            'label_show_seller_address',
            'label_show_company_name',
            'label_show_seller_mobile',
            'label_show_quantity',
            'label_show_item_price',
            'label_show_taxes',
            'label_show_product_name',
            'label_show_sku',
            'label_print_multiple',
            'label_show_weight',
            'label_show_cod_amount'
        ];

        // Only enforce 0 if the request is treated as a full form submission.
        // If it's a PATCH/JSON request, usually we only update what's sent.
        // However, user asked for "Same as it API", which usually implies "Same logic as Web".
        // Web logic assumes if it's missing, it's 0 (unchecked).
        // To be safe for API (which might be partial update), we should check if they are sending *any* data.
        // But let's stick to the web controller logic for consistency unless it breaks API usage patterns.
        // A safer bet is: if the user sends *some* of these, we might assume they are updating.
        // But specifically for form-data (multipart), unchecked boxes are missing.
        
        // let's do exactly what web controller does:
        foreach ($checkboxes as $chk) {
            if (!array_key_exists($chk, $data)) {
                $data[$chk] = 0;
            }
        }

        // Save/Update User Preference
        $preference = LabelPreference::updateOrCreate(
            ['user_id' => $user_id],
            $data
        );
        
        if ($preference->label_company_logo) {
             $preference->label_company_logo_url = url('storage/settings/label/' . $preference->label_company_logo);
        }

        return $this->successResponse([
            'preference' => $preference
        ], 'Label preferences updated successfully.');
    }
}

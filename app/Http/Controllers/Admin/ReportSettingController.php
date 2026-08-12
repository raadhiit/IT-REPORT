<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateReportSettingRequest;
use App\Models\ReportSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReportSettingController extends Controller
{
    /**
     * Show the report recipient settings.
     */
    public function edit(): Response
    {
        return Inertia::render('admin/report-settings/Edit', [
            'setting' => ReportSetting::current()->only('gm_email', 'gm_name', 'spv_email', 'spv_name', 'send_day', 'send_time', 'office_mail_host', 'office_mail_port', 'office_mail_encryption'),
        ]);
    }

    /**
     * Update the report recipient settings.
     */
    public function update(UpdateReportSettingRequest $request): RedirectResponse
    {
        ReportSetting::current()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Report settings updated.')]);

        return to_route('admin.report-settings.edit');
    }
}

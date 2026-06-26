<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(Request $request, AdminReportBuilder $reportBuilder): View|JsonResponse
    {
        $validated = $request->validate([
            'range' => ['nullable', 'string', Rule::in(array_keys(AdminReportBuilder::rangeOptions()))],
        ]);

        $range = $validated['range'] ?? '30d';
        $report = $reportBuilder->build($range);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'range' => $report['range'],
                'range_label' => $report['rangeLabel'],
                'generated_at' => $report['generatedAt']->format('d M Y, h:i A'),
                'export_url' => route('admin.reports.export.pdf', ['range' => $range]),
                'page_url' => route('admin.reports.index', ['range' => $range]),
                'html' => view('admin.reports.partials.content', $report)->render(),
            ]);
        }

        return view('admin.reports.index', array_merge($report, [
            'user' => $request->user(),
        ]));
    }

    public function exportPdf(Request $request, AdminReportBuilder $reportBuilder): Response
    {
        $validated = $request->validate([
            'range' => ['nullable', 'string', Rule::in(array_keys(AdminReportBuilder::rangeOptions()))],
        ]);

        $range = $validated['range'] ?? '30d';
        $report = $reportBuilder->build($range);
        $fileName = 'it-help-desk-report-'.$range.'-'.now()->format('Ymd-His').'.pdf';

        return Pdf::loadView('admin.reports.pdf', array_merge($report, [
            'user' => $request->user(),
        ]))
            ->setPaper('a4', 'portrait')
            ->download($fileName);
    }
}

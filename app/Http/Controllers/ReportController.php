<?php

namespace App\Http\Controllers;

use App\Models\Subdivision;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        \App\Support\ReportBatch::validate($request);
        if (! $request->hasAny(['date_from', 'date_to', 'all_dates'])) {
            $request->query->set('date_from', now()->startOfDay()->format('Y-m-d\TH:i'));
            $request->query->set('date_to', now()->endOfDay()->format('Y-m-d\TH:i:s'));
        }
        $filters = $request->validate([
            'all_dates' => ['nullable', 'boolean'],
            'report_part' => ['nullable', 'integer', 'min:1'],
            'report_max_id' => ['nullable', 'integer', 'min:0'],
            'type' => ['nullable', Rule::in(['incidents', 'visitors'])],
            'q' => ['nullable', 'string', 'max:200'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'subdivision_id' => ['nullable', 'integer', 'exists:subdivisions,subdivision_id'],
            'view' => ['nullable', Rule::in(['all', 'active', 'history'])],
            'report_status' => ['nullable', Rule::in(['Inside', 'Checked Out'])],
        ]);
        $type = $filters['type'] ?? 'incidents';
        $canReportVisitors = $request->user()->isAdmin();
        abort_if($type === 'visitors' && ! $canReportVisitors, 403);
        unset($filters['type']);
        if ($request->boolean('all_dates')) unset($filters['date_from'], $filters['date_to']);
        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');
        if (! $request->user()->isAdmin()) unset($filters['subdivision_id']);
        if ($type === 'incidents') {
            unset($filters['report_status']);
            $filters['view'] = $filters['view'] ?? 'all';
        } else {
            unset($filters['view']);
        }
        $filters['report'] = 1;
        $queryRequest = $request->duplicate($filters);
        $summary = app($type === 'visitors' ? VisitorController::class : IncidentController::class)->reportSummary($queryRequest);
        $totalRecords = $summary['count'];
        $totalParts = max(1, (int) ceil($totalRecords / \App\Support\ReportBatch::SIZE));
        $part = min($request->integer('report_part', 1), $totalParts);
        $filters['report_part'] = $part;
        $filters['report_max_id'] = $filters['report_max_id'] ?? $summary['max_id'];
        $subdivisions = $request->user()->isAdmin() ? Subdivision::orderBy('subdivision_name')->get() : collect();
        $previewUrl = route($type.'.print', $filters + ['preview' => 1, 'embed' => 1], false);
        $printUrl = route($type.'.print', $filters + ['autoprint' => 1], false);
        $pdfUrl = route($type.'.export', $filters + ['format' => 'pdf'], false);
        $excelUrl = route($type.'.export', $filters + ['format' => 'excel'], false);

        return view('reports.index', compact('type', 'filters', 'canReportVisitors', 'subdivisions', 'previewUrl', 'printUrl', 'pdfUrl', 'excelUrl', 'totalRecords', 'totalParts', 'part'));
    }
}

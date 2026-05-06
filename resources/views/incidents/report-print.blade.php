<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; margin: 0; background: #f1f5f9; }
        .page { margin: 18px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; }
        h1 { margin: 0; font-size: 28px; letter-spacing: -0.4px; }
        p.meta { margin: 8px 0 0; color: #475569; font-size: 12px; }
        .actions { margin-top: 14px; display: flex; flex-wrap: wrap; gap: 8px; }
        .actions button { padding: 8px 14px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; }
        .summary { margin: 14px 0 16px; font-size: 12px; color: #334155; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #dbe3ee; padding: 9px; text-align: left; vertical-align: top; }
        th { background: #e2e8f0; font-weight: 700; color: #0f172a; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .proof-thumb { width: 92px; height: 64px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; }
        @media print {
            body { background: #fff; }
            .page { margin: 0; border: 0; border-radius: 0; padding: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="page">
        <h1>{{ $title }}</h1>
        <p class="meta">Generated at: {{ $generatedAt->format('Y-m-d H:i:s') }}</p>
        <p class="summary">Total resolved incidents: {{ count($reportRows ?? []) }}</p>

        <table>
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Reporter</th>
                    <th>Date Reported</th>
                    <th>Date Resolved</th>
                    <th>Proof</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($reportRows ?? []) as $row)
                    <tr>
                        <td>{{ $row['report_id'] }}</td>
                        <td>{{ $row['category'] }}</td>
                        <td>{{ $row['location'] }}</td>
                        <td>{{ $row['status'] }}</td>
                        <td>{{ $row['reporter'] }}</td>
                        <td>{{ $row['reported_at'] }}</td>
                        <td>{{ $row['resolved_at'] }}</td>
                        <td>
                            @if (!empty($row['proof_data_uri']))
                                <img src="{{ $row['proof_data_uri'] }}" alt="Proof for {{ $row['report_id'] }}" class="proof-thumb">
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">No incident records found for current filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if (empty($renderingPdf) && empty(request()->boolean('embed')) && !empty($previewMode))
            <div class="actions">
                <a
                    href="{{ route('incidents.export', $reportQuery + ['format' => 'excel']) }}"
                    onclick="return confirm('Export incident history to Excel now?');"
                    style="display:inline-block;padding:8px 14px;font-size:12px;border-radius:8px;background:#059669;color:#fff;text-decoration:none;"
                >
                    To Excel
                </a>
                <a
                    href="{{ route('incidents.export', $reportQuery + ['format' => 'pdf']) }}"
                    style="display:inline-block;padding:8px 14px;font-size:12px;border-radius:8px;background:#dc2626;color:#fff;text-decoration:none;"
                >
                    To PDF
                </a>
                <button type="button" onclick="window.print()">Print</button>
            </div>
        @elseif (empty($renderingPdf) && empty(request()->boolean('embed')))
            <div class="actions">
                <button type="button" onclick="window.print()">Print</button>
            </div>
        @endif
    </div>

    @if (!empty($autoPrint))
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>

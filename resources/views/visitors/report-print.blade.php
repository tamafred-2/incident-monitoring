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
        .photo-thumb { width: 92px; height: 64px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; }
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
        <p class="summary">Total visitor history records: {{ count($reportRows ?? []) }}</p>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Visit Type</th>
                    <th>Purpose</th>
                    <th>Resident / Host</th>
                    <th>House / Unit</th>
                    <th>Vehicle Type</th>
                    <th>Vehicle Color</th>
                    <th>Plate Number</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>ID Photo</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($reportRows ?? []) as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['phone'] }}</td>
                        <td>{{ $row['visit_type'] }}</td>
                        <td>{{ $row['purpose'] }}</td>
                        <td>{{ $row['host'] }}</td>
                        <td>{{ $row['house_unit'] }}</td>
                        <td>{{ $row['vehicle_type'] }}</td>
                        <td>{{ $row['vehicle_color'] }}</td>
                        <td>{{ $row['plate_number'] }}</td>
                        <td>{{ $row['check_in'] }}</td>
                        <td>{{ $row['check_out'] }}</td>
                        <td>{{ $row['duration'] }}</td>
                        <td>{{ $row['status'] }}</td>
                        <td>
                            @if (!empty($row['photo_data_uri']))
                                <img src="{{ $row['photo_data_uri'] }}" alt="ID Photo for {{ $row['name'] }}" class="photo-thumb">
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14">No visitor history records found for current filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if (empty($renderingPdf) && empty(request()->boolean('embed')) && !empty($previewMode))
            <div class="actions">
                <a
                    href="{{ route('visitors.export', $reportQuery + ['format' => 'excel']) }}"
                    style="display:inline-block;padding:8px 14px;font-size:12px;border-radius:8px;background:#059669;color:#fff;text-decoration:none;"
                >
                    To Excel
                </a>
                <a
                    href="{{ route('visitors.export', $reportQuery + ['format' => 'pdf']) }}"
                    style="display:inline-block;padding:8px 14px;font-size:12px;border-radius:8px;background:#dc2626;color:#fff;text-decoration:none;"
                >
                    To PDF
                </a>
            </div>
        @endif
    </div>

    @if (!empty($autoPrint))
        <script>
            let printHandled = false;
            const closePrintWindow = () => {
                if (printHandled) {
                    return;
                }
                printHandled = true;
                window.close();
            };

            window.addEventListener('afterprint', closePrintWindow);
            window.addEventListener('load', function () {
                window.print();
            });
            window.addEventListener('focus', function () {
                setTimeout(closePrintWindow, 400);
            });
        </script>
    @endif
</body>
</html>

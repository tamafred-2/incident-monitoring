<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 28pt; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #0f172a; }
        h1 { font-size: 20pt; margin: 0 0 8pt; }
        h2 { font-size: 12pt; margin: 0 0 6pt; }
        p { margin: 0 0 5pt; }
        .meta { color: #475569; margin-bottom: 14pt; }
        .visitor { border-top: 1pt solid #cbd5e1; padding-top: 10pt; margin-bottom: 14pt; }
        .photo { width: 92pt; height: 64pt; margin-top: 5pt; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">Generated at: {{ $generatedAt->format('Y-m-d H:i:s') }} | Showing {{ count($reportRows) }} of {{ $totalMatching }} matching visits, newest check-in first.</p>
    @if ($totalMatching > count($reportRows))
        <p class="meta">This PDF includes only the 100 most recent matching visits. Narrow the date range to export older visits.</p>
    @endif
    {{-- Ordinary blocks can split across pages; a tall table row cannot. --}}
    @forelse ($reportRows as $row)
        <div class="visitor">
            <h2>{{ $loop->iteration }}. {{ $row['name'] }}</h2>
            <p><strong>Phone:</strong> {{ $row['phone'] }} | <strong>Visit Type:</strong> {{ $row['visit_type'] }} | <strong>Status:</strong> {{ $row['status'] }}</p>
            <p><strong>Purpose:</strong> {{ $row['purpose'] }}</p>
            <p><strong>Resident / Host:</strong> {{ $row['host'] }} | <strong>House / Unit:</strong> {{ $row['house_unit'] }}</p>
            <p><strong>Vehicle Type:</strong> {{ $row['vehicle_type'] }} | <strong>Vehicle Color:</strong> {{ $row['vehicle_color'] }} | <strong>Plate Number:</strong> {{ $row['plate_number'] }}</p>
            <p><strong>Check In:</strong> {{ $row['check_in'] }} | <strong>Check Out:</strong> {{ $row['check_out'] }} | <strong>Duration:</strong> {{ $row['duration'] }}</p>
            @if (!empty($row['photo_path']))
                <p><strong>ID Photo:</strong></p>
                <img src="{{ $row['photo_path'] }}" class="photo" alt="ID Photo">
            @elseif (!empty($row['photo_omitted']))
                <p>ID photo unavailable in PDF. <a href="{{ $row['photo_url'] }}">View original photo</a></p>
            @else
                <p><strong>ID Photo:</strong> -</p>
            @endif
        </div>
    @empty
        <p>No visitor history records found for current filters.</p>
    @endforelse
</body>
</html>

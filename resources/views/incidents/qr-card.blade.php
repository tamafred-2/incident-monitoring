<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident QR Card - {{ $incident->report_id }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 px-4 py-10 text-slate-900">
    <div class="mx-auto max-w-md rounded-3xl bg-white p-8 shadow-2xl">
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-700">Incident Report</p>
            <h1 class="mt-3 text-2xl font-bold font-mono">{{ $incident->report_id }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ $incident->subdivision->subdivision_name ?? '' }}</p>
            @if ($incident->house)
                <p class="mt-1 text-sm text-slate-600">{{ $incident->house->display_address }}</p>
            @endif
            @if ($incident->assignedStaff)
                <p class="mt-3 text-xs font-medium uppercase tracking-[0.18em] text-slate-500">Assigned Staff</p>
                <p class="mt-1 text-sm text-slate-700">{{ $incident->assignedStaff->full_name }}</p>
            @endif
        </div>

        <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-center">
            <div id="qrcode" class="mx-auto inline-flex min-h-[256px] min-w-[256px] items-center justify-center"></div>
        </div>

        <div class="mt-6 rounded-2xl bg-slate-950 px-4 py-3 text-center text-sm font-semibold tracking-[0.18em] text-white">
            {{ $incident->report_id }}
        </div>

        <div class="mt-8 flex justify-center gap-3 print:hidden">
            <button type="button" onclick="window.print()" class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Print Card</button>
            <a href="{{ route('incidents.show', ['incidentId' => $incident->incident_id]) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        new QRCode(document.getElementById('qrcode'), {
            text: @json($qrPayload),
            width: 256,
            height: 256
        });
    </script>
</body>
</html>

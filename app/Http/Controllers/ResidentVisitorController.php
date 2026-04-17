<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use App\Models\VisitorRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResidentVisitorController extends Controller
{
    public function index(Request $request): View
    {
        $resident = $request->user()->resident;

        abort_if(!$resident, 403, 'No resident profile linked to your account.');

        $requests = VisitorRequest::where('resident_id', $resident->resident_id)
            ->orderByDesc('requested_at')
            ->get();

        return view('resident-visitors.index', compact('requests', 'resident'));
    }

    public function approve(Request $request, VisitorRequest $visitorRequest): RedirectResponse
    {
        $this->authorizeRequest($request, $visitorRequest);

        $visitor = Visitor::create([
            'subdivision_id'        => $visitorRequest->subdivision_id,
            'surname'               => $visitorRequest->surname ?? $visitorRequest->visitor_name,
            'first_name'            => $visitorRequest->first_name ?? $visitorRequest->visitor_name,
            'middle_initials'       => $visitorRequest->middle_initials,
            'extension'             => $visitorRequest->extension,
            'phone'                 => $visitorRequest->phone,
            'plate_number'          => $visitorRequest->plate_number,
            'id_photo_path'         => $visitorRequest->id_photo_path,
            'purpose'               => $visitorRequest->purpose,
            'host_employee'         => $request->user()->resident->full_name,
            'house_address_or_unit' => $visitorRequest->house_address_or_unit,
            'check_in'              => now(),
            'check_out'             => null,
            'status'                => 'Inside',
        ]);

        $visitorRequest->update([
            'status'       => 'Approved',
            'responded_at' => now(),
            'visitor_id'   => $visitor->visitor_id,
        ]);

        return back()->with('success', 'Visitor approved and checked in.');
    }

    public function decline(Request $request, VisitorRequest $visitorRequest): RedirectResponse
    {
        $this->authorizeRequest($request, $visitorRequest);

        $visitorRequest->update(['status' => 'Declined', 'responded_at' => now()]);

        return back()->with('success', 'Visitor request declined.');
    }

    private function authorizeRequest(Request $request, VisitorRequest $visitorRequest): void
    {
        $resident = $request->user()->resident;

        abort_if(!$resident || $visitorRequest->resident_id !== $resident->resident_id, 403);
        abort_if($visitorRequest->status !== 'Pending', 422, 'This request has already been responded to.');
    }
}

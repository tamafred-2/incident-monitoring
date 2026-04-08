<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\Subdivision;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ResidentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filterQ = trim((string) $request->query('q', ''));
        $filterStatus = trim((string) $request->query('status', ''));
        $filterSubdivision = (int) $request->query('subdivision_id', 0);

        $query = Resident::query()
            ->with(['subdivision', 'house'])
            ->orderBy('full_name');

        if ($filterQ !== '') {
            $query->where(function ($builder) use ($filterQ) {
                $builder->where('full_name', 'like', "%{$filterQ}%")
                    ->orWhere('resident_code', 'like', "%{$filterQ}%")
                    ->orWhere('address_or_unit', 'like', "%{$filterQ}%")
                    ->orWhereHas('house', function ($houseQuery) use ($filterQ) {
                        $houseQuery->where('block', 'like', "%{$filterQ}%")
                            ->orWhere('lot', 'like', "%{$filterQ}%");
                    });
            });
        }

        if (in_array($filterStatus, ['Active', 'Inactive'], true)) {
            $query->where('status', $filterStatus);
        }

        if (!$user->isAdmin()) {
            $query->where('subdivision_id', $user->allowedSubdivisionId());
        } elseif ($filterSubdivision) {
            $query->where('subdivision_id', $filterSubdivision);
        }

        $residents = $query->get();
        $subdivisions = $user->isAdmin()
            ? Subdivision::orderBy('subdivision_name')->get()
            : collect();

        return view('residents.index', compact(
            'residents',
            'subdivisions',
            'filterQ',
            'filterStatus',
            'filterSubdivision'
        ));
    }

    public function qrCard(Request $request, Resident $resident): View|Response
    {
        if (!$request->user()->canAccessSubdivision($resident->subdivision_id)) {
            abort(403);
        }

        $resident->load(['subdivision', 'house']);

        return view('residents.qr-card', [
            'resident' => $resident,
            'qrPayload' => 'RESIDENT:' . $resident->resident_code,
        ]);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PreventDuplicateCreation
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethod('POST') || !$request->routeIs('*.store') || !$request->user()) {
            return $next($request);
        }

        $request->session()->forget('form_saved');
        if (!$request->filled('_submission_token')) {
            $response = $next($request);
            if ($this->wasSaved($request, $response)) {
                $this->markSaved($request);
            }
            return $response;
        }

        $request->validate(['_submission_token' => ['string', 'regex:/^[a-f0-9]{32}$/']]);
        $key = 'form-submission:' . hash('sha256', $request->user()->getAuthIdentifier() . '|' . $request->route()->getName() . '|' . $request->input('_submission_token'));
        $lock = Cache::lock($key . ':lock', 120);
        if (!$lock->get()) {
            return back()->with('error', 'This form is already being submitted. Please wait for it to finish.');
        }
        try {
            if ($location = Cache::get($key)) {
                $this->markSaved($request);
                return $request->expectsJson()
                    ? response()->json(['saved' => true, 'redirect_url' => $request->routeIs('visitors.store') ? route('visitors.index', ['tab' => 'check-out']) : $location])
                    : redirect()->to($location);
            }
            $response = $next($request);
            if ($this->wasSaved($request, $response)) {
                $this->markSaved($request);
                Cache::put($key, $response->headers->get('Location'), now()->addDay());
                if ($request->expectsJson()) {
                    return response()->json(['saved' => true, 'redirect_url' => $request->routeIs('visitors.store') ? route('visitors.index', ['tab' => 'check-out']) : $response->headers->get('Location')], 201);
                }
            } elseif ($request->expectsJson() && $response->isRedirection()) {
                $errors = $request->session()->get('errors');
                return response()->json(['message' => $request->session()->get('error', 'Please correct the form and try again.'), 'errors' => $errors?->getBag('default')->messages() ?? []], 422);
            }
            return $response;
        } finally {
            $lock->release();
        }
    }

    private function wasSaved(Request $request, Response $response): bool
    {
        $flashed = $request->session()->get('_flash.new', []);
        return $response->isRedirection() && in_array('success', $flashed, true)
            && !in_array('errors', $flashed, true) && !in_array('error', $flashed, true);
    }

    private function markSaved(Request $request): void
    {
        $request->session()->forget(['errors', '_old_input']);
        $request->session()->flash('form_saved', true);
    }
}

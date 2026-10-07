@forelse ($pendingRequests as $resetRequest)
                    <div data-record-key="{{ $resetRequest->getKey() }}" class="flex flex-col gap-4 border-b border-slate-100 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium text-slate-900">{{ $resetRequest->user?->full_name ?? 'Unknown user' }}</p>
                            <p class="text-sm text-slate-500">{{ $resetRequest->email }}</p>
                            <p class="mt-1 text-xs text-slate-400">Requested {{ $resetRequest->updated_at->diffForHumans() }}</p>
                            @if ($resetRequest->expires_at)
                                <p class="mt-1 text-xs font-medium text-amber-600">Expires {{ $resetRequest->expires_at->diffForHumans() }} if not confirmed</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('admin.password-resets.resolve', $resetRequest) }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700"
                                >
                                    Reset Password
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.password-resets.destroy', $resetRequest) }}"
                                  onsubmit="return confirm('Dismiss this request without resetting?');">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                                >
                                    Dismiss
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-slate-400">No pending password reset requests.</p>
                @endforelse

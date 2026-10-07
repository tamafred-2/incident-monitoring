@forelse ($recentRequests as $resetRequest)
                    <div data-record-key="{{ $resetRequest->getKey() }}" class="flex flex-col gap-2 border-b border-slate-100 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium text-slate-900">{{ $resetRequest->user?->full_name ?? 'Unknown user' }}</p>
                            <p class="text-sm text-slate-500">{{ $resetRequest->email }}</p>
                            @if ($resetRequest->isExpired())
                                <p class="mt-1 text-xs text-slate-400">Expired {{ $resetRequest->updated_at?->diffForHumans() }}</p>
                            @else
                                <p class="mt-1 text-xs text-slate-400">
                                    Reset {{ $resetRequest->resolved_at?->diffForHumans() }}
                                    @if ($resetRequest->resolver) by {{ $resetRequest->resolver->full_name }} @endif
                                </p>
                            @endif
                        </div>

                        <div class="text-left sm:text-right">
                            @if ($resetRequest->isExpired())
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-12.5a.75.75 0 00-1.5 0V10c0 .2.08.39.22.53l2.5 2.5a.75.75 0 101.06-1.06l-2.28-2.28V5.5z" clip-rule="evenodd" />
                                    </svg>
                                    Expired (not confirmed in time)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    Password given to user
                                </span>
                                <p class="mt-1 text-xs text-slate-400">Password is shown once at reset and not stored.</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-slate-400">No activity yet.</p>
                @endforelse

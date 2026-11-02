{{-- Keeps an on-duty guard's premises check current; the server signs them out when outside. --}}
@if (auth()->user()?->isSecurity() && \App\Support\GuardAccess::premisesFor(auth()->user()))
    <script>
        (function () {
            var url = @js(route('guard.location'));
            var token = document.querySelector('meta[name="csrf-token"]')?.content || '';
            var intervalMs = {{ (int) config('guard_access.heartbeat_seconds') * 1000 }};

            function send(body) {
                fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify(body)
                }).then(function (response) {
                    if (response.status === 401 || response.status === 419) {
                        return response.json().catch(function () { return {}; }).then(function (data) {
                            window.location.href = data.redirect || @js(route('login'));
                        });
                    }
                }).catch(function () {});
            }

            function check() {
                if (!navigator.geolocation) {
                    send({});
                    return;
                }

                navigator.geolocation.getCurrentPosition(function (position) {
                    send({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    });
                }, function (error) {
                    // Blocked location ends the session; timeouts retry on the next tick.
                    if (error.code === error.PERMISSION_DENIED) {
                        send({});
                    }
                }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 });
            }

            check();
            setInterval(check, intervalMs);
        })();
    </script>
@endif

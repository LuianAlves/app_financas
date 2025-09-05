<script>
    (function(){
        const ua = navigator.userAgent || '';
        const isIOSLike =
            (/(iPad|iPhone|iPod)/.test(ua) && !window.MSStream) ||
            (navigator.platform === 'MacIntel' && (navigator.maxTouchPoints || 0) > 1);

        window.AUTH = @json(auth()->check());
        window.PUSH_CFG = {
            vapidKeyUrl: "{{ url('/vapid-public-key') }}",
            subscribeUrl: "{{ url('/push/subscribe') }}",
            swUrl: "{{ asset('sw.js') }}?v={{ filemtime(public_path('sw.js')) }}",
            loginPath: "{{ route('login') }}",
            isIOS: isIOSLike
        };
    })();
</script>

@php
    $pushRegisterPath = public_path('assets/js/push-register.js');
@endphp
<script src="{{ asset('assets/js/push-register.js') }}?v={{ file_exists($pushRegisterPath) ? filemtime($pushRegisterPath) : time() }}" defer></script>


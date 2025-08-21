<script>
    window.AUTH = @json(auth()->check());

    window.PUSH_CFG = {
        vapidKeyUrl: "{{ url('/vapid-public-key') }}",
        subscribeUrl: "{{ url('/push/subscribe') }}",
        swUrl: "{{ asset('sw.js') }}?v={{ filemtime(public_path('sw.js')) }}",
        loginPath: "{{ route('login') }}",
        isIOS: /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream
    };
</script>

@php
    $pushRegisterPath = public_path('assets/js/push-register.js');
@endphp

<script src="{{ asset('assets/js/push-register.js') }}?v={{ file_exists($pushRegisterPath) ? filemtime($pushRegisterPath) : time() }}" defer></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>



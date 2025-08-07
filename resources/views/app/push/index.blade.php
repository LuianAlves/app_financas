
    <div class="container">
        <h4>Enviar Notificação Push</h4>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ url('/push/teste') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Título</label>
                <input type="text" name="title" class="form-control" placeholder="Título da notificação">
            </div>
            <div class="mb-3">
                <label class="form-label">Mensagem</label>
                <textarea name="body" class="form-control" placeholder="Mensagem..."></textarea>
            </div>
            <button class="btn btn-primary">Enviar</button>
        </form>
    </div>

    <script src="{{ asset('laravelpwa/push-register.js') }}"></script>

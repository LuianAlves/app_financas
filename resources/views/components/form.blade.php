@props([
  'id',
  'path',
  'data' => []
])

<form action="" method="" id="{{$id}}">
    @csrf

    @include("$path", $data)

    <div class="text-end mt-3">
        <button type="submit" class="btn btn-sm bg-color">Salvar</button>
    </div>
</form>

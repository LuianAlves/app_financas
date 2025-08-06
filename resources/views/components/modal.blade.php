@props([
  'modalId',
  'formId',
  'pathForm',
  'data' => []
])

<div id="{{$modalId}}" class="custom-modal">
    <div class="custom-modal-content">
        <span id="closeModal" class="close-btn">&times;</span>

        <x-form id="{{$formId}}" path="{{$pathForm}}" :data="$data"></x-form>
    </div>
</div>

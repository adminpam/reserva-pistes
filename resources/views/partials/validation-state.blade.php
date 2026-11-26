{{-- Solo errores y la identidad del formulario: nunca se serializa old input completo. --}}
@if($errors->any())
    @php
        $validationForm = old('_validation_form');
        $validationState = [
            'form' => is_string($validationForm) && strlen($validationForm) <= 1000 ? $validationForm : null,
            'errors' => $errors->getBag('default')->messages(),
        ];
    @endphp
    <script type="application/json" id="rp-validation-state">@json($validationState, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
@endif

<div class="input_container">
    <input type="{{ $type ?? 'text' }}" placeholder="{{ $placeholder ?? 'Введите значение' }}"
        value="{{ old($name, $value ?? '') }}" name="{{ $name }}">
    @error($name)
        <p style="color:red">{{ $message }}</p>
    @enderror
</div>

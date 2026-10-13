@props(['name','label','type'=>'text','value'=>'','rows'=>4,'useOld'=>true])
@php $current = $useOld ? old($name,$value) : $value; $current = is_scalar($current) ? (string)$current : ''; @endphp
<div class="field"><label for="{{ $name }}">{{ $label }}</label>
@if($type === 'textarea')<textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" {{ $attributes->class(['form-control','is-invalid'=>$errors->has($name)]) }}>{{ $current }}</textarea>
@else<input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : $current }}" {{ $attributes->class(['form-control','is-invalid'=>$errors->has($name)]) }}>@endif
@error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

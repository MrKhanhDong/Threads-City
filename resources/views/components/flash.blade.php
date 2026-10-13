@if(session('success'))<div class="alert alert-success my-3" role="status">{{ session('success') }}</div>@endif
@if(session('mail_error'))<div class="alert alert-warning my-3" role="alert">{{ session('mail_error') }}</div>@endif
@if(isset($errors) && $errors->any())<div class="alert alert-danger my-3" role="alert"><strong>Vui lòng sửa các lỗi sau:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

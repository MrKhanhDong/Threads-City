@foreach($threads as $thread)
    @include('threads.partials.thread_card', ['thread' => $thread])
@endforeach
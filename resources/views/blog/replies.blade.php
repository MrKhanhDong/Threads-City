@foreach($replies as $reply)
    @include('blog.comment', ['comment' => $reply, 'replyTo' => $reply->parent])
@endforeach

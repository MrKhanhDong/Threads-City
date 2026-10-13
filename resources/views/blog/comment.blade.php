<div class="comment" id="comment-{{ $comment->id }}">
    @php
        $isReplyRetry = is_scalar(old('parent_id')) && (string) old('parent_id') === (string) $comment->id;
        $replyBody = $isReplyRetry && is_string(old('body')) ? old('body') : '';
    @endphp
    <div class="comment-heading">
        <x-avatar :user="$comment->user" />
        <strong>{{ $comment->user->name }}</strong>
        <time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->format('d/m/Y H:i') }}</time>
        @if($comment->status === 'pending')
            <span class="status">Chờ duyệt từ bản cũ · chỉ bạn thấy</span>
        @endif
    </div>
    @isset($replyTo)
        <p class="reply-context">Trả lời <a href="#comment-{{ $replyTo->id }}">{{ $replyTo->user->name }}</a></p>
    @endisset
    <p class="comment-body">{{ $comment->body }}</p>
    @auth
        @if(auth()->user()->hasVerifiedEmail())
            <div class="comment-actions">
                @if($comment->status === 'approved')
                    <details @if($isReplyRetry && $errors->any()) open @endif>
                        <summary>Trả lời</summary>
                        <form action="{{ route('comments.store', $post->id) }}" method="post">
                            @csrf
                            <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                            <label for="reply-{{ $comment->id }}">Trả lời {{ $comment->user->name }}</label>
                            <textarea id="reply-{{ $comment->id }}" name="body" class="form-control" rows="3" required minlength="2" maxlength="5000" placeholder="Viết trả lời của bạn...">{{ $replyBody }}</textarea>
                            <button type="submit" class="btn btn-forest btn-sm mt-2">Gửi trả lời</button>
                        </form>
                    </details>
                @endif
                @can('update', $comment)
                    <details>
                        <summary>Sửa</summary>
                        <form action="{{ route('comments.update', $comment) }}" method="post">
                            @csrf
                            @method('patch')
                            <label class="visually-hidden" for="edit-comment-{{ $comment->id }}">Sửa bình luận</label>
                            <textarea id="edit-comment-{{ $comment->id }}" name="body" class="form-control" rows="3" required minlength="2" maxlength="5000">{{ $comment->body }}</textarea>
                            <button type="submit" class="btn btn-forest btn-sm mt-2">Lưu sửa đổi</button>
                        </form>
                    </details>
                    <form action="{{ route('comments.destroy', $comment) }}" method="post" data-confirm="Xóa bình luận này? Các trả lời khác vẫn được giữ.">
                        @csrf
                        @method('delete')
                        <button type="submit" class="link-button">Xóa</button>
                    </form>
                @endcan
                @if($comment->status === 'approved')
                    <details>
                        <summary>Báo cáo</summary>
                        <form action="{{ route('comments.report', $comment) }}" method="post">
                            @csrf
                            <label for="report-{{ $comment->id }}">Lý do báo cáo</label>
                            <textarea id="report-{{ $comment->id }}" name="reason" class="form-control" rows="2" required minlength="5" maxlength="1000"></textarea>
                            <button type="submit" class="btn btn-outline-forest btn-sm mt-2">Gửi báo cáo</button>
                        </form>
                    </details>
                @endif
            </div>
        @endif
    @else
        @if($comment->status === 'approved')
            <a class="text-link small" href="{{ route('login') }}">Đăng nhập để trả lời</a>
        @endif
    @endauth
</div>

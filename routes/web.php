<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommentThreadController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\MentionController;
use App\Http\Controllers\MusicController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ReaderController;
use App\Http\Controllers\ReaderSpaceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SafetyController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ThreadActionController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\UserProfileController;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/people/{user}', [MentionController::class, 'profile'])->whereNumber('user')->name('mentions.profile');
Route::middleware(['auth', 'throttle:360,1,chat'])->prefix('chat')->name('chat.')->group(function (): void {
    Route::get('/', [ChatController::class, 'index'])->name('index');
    Route::get('/people', [ChatController::class, 'people'])->name('people');
    Route::get('/unread', [ChatController::class, 'unread'])->name('unread');
    Route::post('/start', [ChatController::class, 'start'])->middleware('throttle:30,1,chat-start')->name('start');
    Route::get('/{conversation}', [ChatController::class, 'index'])->whereNumber('conversation')->name('show');
    Route::get('/{conversation}/messages', [ChatController::class, 'messages'])->whereNumber('conversation')->name('messages');
    Route::post('/{conversation}/messages', [ChatController::class, 'send'])->whereNumber('conversation')->middleware('throttle:60,1,chat-send')->name('send');
    Route::patch('/{conversation}/messages/{message}', [ChatController::class, 'update'])->whereNumber(['conversation', 'message'])->name('update');
    Route::delete('/{conversation}/messages/{message}', [ChatController::class, 'recall'])->whereNumber(['conversation', 'message'])->name('recall');
    Route::put('/{conversation}/messages/{message}/reaction', [ChatController::class, 'react'])->whereNumber(['conversation', 'message'])->name('react');
    Route::get('/{conversation}/messages/{message}/attachment', [ChatController::class, 'attachment'])->whereNumber(['conversation', 'message'])->name('attachment');
    Route::post('/{conversation}/read', [ChatController::class, 'read'])->whereNumber('conversation')->name('read');
    Route::post('/{conversation}/typing', [ChatController::class, 'typing'])->whereNumber('conversation')->name('typing');
});
Route::get('/users/{user}/connections/{kind}', [UserProfileController::class, 'connections'])->whereNumber('user')->whereIn('kind', ['followers', 'following'])->middleware('throttle:120,1')->name('profile.connections');
Route::get('/search', [SearchController::class, 'index'])->name('search.index');
Route::get('/', [ThreadController::class, 'index'])->name('home');
Route::get('/noi-quy-cong-dong', fn (): View => view('community-rules', ['rules' => Setting::where('key', 'community_rules')->value('value')]))->name('community.rules');
Route::get('/threads', [ThreadController::class, 'index'])->name('threads.index');
Route::get('/t/{thread}', [ThreadController::class, 'show'])->whereNumber('thread')->name('threads.show');
Route::get('/threads/{thread}/embed', [ThreadActionController::class, 'embed'])->whereNumber('thread')->name('threads.embed');
Route::redirect('/blog-cu', '/', 301)->name('blog.legacy_home');

Route::redirect('/bai-viet', '/', 301)->name('posts.index');
Route::get('/bai-viet/{slug}', [BlogController::class, 'show'])->name('posts.show');
Route::redirect('/danh-muc/{category:slug}', '/', 301)->name('categories.show');
Route::redirect('/the/{tag:slug}', '/', 301)->name('tags.show');
Route::redirect('/tac-gia', '/', 301)->name('author');
Route::redirect('/gioi-thieu', '/', 301)->name('about');
Route::redirect('/lien-he', '/', 301)->name('contact');
Route::redirect('/chinh-sach', '/', 301)->name('privacy');
Route::redirect('/dieu-khoan', '/', 301)->name('terms');
Route::get('/sitemap.xml', [BlogController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [BlogController::class, 'robots'])->name('robots');
Route::get('/media/{media}', [Admin\MediaController::class, 'show'])->whereNumber('media')->name('media.show');
Route::get('/avatar/{user}', [AccountController::class, 'avatar'])->whereNumber('user')->name('avatar.show');
Route::get('/binh-luan/{comment}/tra-loi', [CommentThreadController::class, 'index'])->whereNumber('comment')->middleware('throttle:120,1')->name('comments.replies');

Route::middleware('guest')->group(function (): void {
    Route::get('/dang-nhap', [AuthController::class, 'login'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'authenticate'])->middleware('throttle:login')->name('login.store');
    Route::get('/dang-ky', [AuthController::class, 'register'])->name('register');
    Route::post('/dang-ky', [AuthController::class, 'store'])->middleware('throttle:register')->name('register.store');
    Route::get('/quen-mat-khau', [AuthController::class, 'forgot'])->name('password.request');
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->middleware('throttle:login')->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->middleware('throttle:login')->name('google.callback');
});

Route::post('/quen-mat-khau', [AuthController::class, 'sendReset'])->middleware('throttle:password-reset')->name('password.email');
Route::get('/dat-lai-mat-khau/{token}', [AuthController::class, 'reset'])->name('password.reset');
Route::post('/dat-lai-mat-khau', [AuthController::class, 'updatePassword'])->middleware('throttle:password-reset')->name('password.update');

Route::middleware('auth')->group(function (): void {
    Route::get('/music/search', [MusicController::class, 'index'])->middleware('throttle:30,1')->name('music.search');
    Route::get('/mentions/suggestions', [MentionController::class, 'index'])->middleware('throttle:120,1')->name('mentions.suggestions');
    Route::patch('/account/mentions', [SafetyController::class, 'mentions'])->name('safety.mentions');
    Route::post('/push/subscriptions', [PushSubscriptionController::class, 'store'])->middleware('throttle:30,1')->name('push.store');
    Route::delete('/push/subscriptions', [PushSubscriptionController::class, 'destroy'])->middleware('throttle:30,1')->name('push.destroy');
    Route::get('/push/context', [PushSubscriptionController::class, 'context'])->middleware('throttle:120,1')->name('push.context');
    Route::get('/hoat-dong', [ReaderSpaceController::class, 'activity'])->name('activity.index');
    Route::get('/thong-tin-chi-tiet', [ReaderSpaceController::class, 'insights'])->name('insights.index');
    Route::get('/da-thich', [ReaderSpaceController::class, 'liked'])->name('liked.index');
    Route::get('/luu-tru', [ReaderSpaceController::class, 'archives'])->name('archives.index');
    Route::post('/threads/{thread}/archive', [ReaderSpaceController::class, 'archive'])->whereNumber('thread')->name('threads.archive');
    Route::post('/threads/{thread}/unarchive', [ReaderSpaceController::class, 'restore'])->whereNumber('thread')->name('threads.unarchive');
    Route::get('/an-toan', [SafetyController::class, 'index'])->name('safety.index');
    Route::patch('/an-toan/quyen-rieng-tu', [SafetyController::class, 'privacy'])->name('safety.privacy');
    Route::match(['put', 'delete'], '/users/{user}/safety-block', [SafetyController::class, 'block'])->name('safety.block');
    Route::match(['put', 'delete'], '/users/{user}/safety-mute', [SafetyController::class, 'mute'])->name('safety.mute');
    Route::get('/an-toan/avatar/{user}', [AccountController::class, 'managedAvatar'])->name('safety.avatar');
    Route::post('/an-toan/follow-request/{user}', [SafetyController::class, 'followRequest'])->name('safety.follow-request');
    Route::get('/bao-cao', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/bao-cao/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::match(['get', 'post'], '/threads/{thread}/safety-report', [ReportController::class, 'thread'])->name('reports.thread');
    Route::match(['get', 'post'], '/users/{user}/safety-report', [ReportController::class, 'user'])->name('reports.user');
    Route::patch('/threads/{thread}', [ThreadController::class, 'update'])->whereNumber('thread')->middleware('throttle:30,1')->name('threads.update');
    Route::get('/auth/google/hoan-tat', [GoogleAuthController::class, 'profile'])->name('google.profile');
    Route::post('/auth/google/hoan-tat', [GoogleAuthController::class, 'completeProfile'])->middleware('throttle:10,1')->name('google.profile.store');
    Route::post('/dang-xuat', [AuthController::class, 'logout'])->name('logout');
    Route::get('/xac-minh-email', fn (): View => view('auth.verify'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request): RedirectResponse {
        $request->fulfill();

        return redirect()->route('home')->with('success', 'Đã xác minh email.');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', function (Request $request): RedirectResponse {
        if ($request->user()->hasVerifiedEmail()) {
            return back()->with('success', 'Email đã được xác minh.');
        }
        if (in_array(config('mail.default'), ['array', 'log'], true)) {
            return back()->withErrors(['email' => 'Chưa cấu hình SMTP. Hãy liên hệ tác giả để bật dịch vụ gửi thư.']);
        }
        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (Throwable) {
            return back()->withErrors(['email' => 'Dịch vụ gửi thư đang không khả dụng. Vui lòng thử lại sau.']);
        }

        return back()->with('success', 'Đã gửi email xác minh.');
    })->middleware('throttle:password-reset')->name('verification.send');
    Route::get('/tai-khoan', [AccountController::class, 'edit'])->name('account.edit');
    Route::get('/thong-bao', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/api/notifications/unread', [NotificationController::class, 'unreadCount'])->name('notifications.unread');
    Route::post('/thong-bao/doc-tat-ca', [NotificationController::class, 'readAll'])->middleware('throttle:60,1')->name('notifications.read-all');
    Route::match(['get', 'post'], '/thong-bao/{id}/mo', [NotificationController::class, 'open'])->whereUuid('id')->middleware('throttle:60,1')->name('notifications.open');
    Route::patch('/tai-khoan', [AccountController::class, 'update'])->name('account.update');
    Route::put('/tai-khoan/mat-khau', [AccountController::class, 'password'])->name('account.password');
    Route::get('/bai-viet-da-luu', [ReaderController::class, 'saved'])->name('saved');
    Route::post('/threads', [ThreadController::class, 'store'])->middleware('throttle:30,1')->name('threads.store');
    Route::delete('/threads/{thread}', [ThreadController::class, 'destroy'])->whereNumber('thread')->name('threads.destroy');
    Route::post('/threads/upload-media', [ThreadController::class, 'uploadMedia'])->middleware('throttle:30,1')->name('threads.upload-media');
    Route::post('/threads/{thread}/like', [ThreadActionController::class, 'like'])->whereNumber('thread')->name('threads.like');
    Route::post('/threads/{thread}/bookmark', [ThreadActionController::class, 'bookmark'])->whereNumber('thread')->name('threads.bookmark');
    Route::post('/threads/{thread}/repost', [ThreadActionController::class, 'repost'])->whereNumber('thread')->name('threads.repost');
    Route::post('/threads/{thread}/not-interested', [ThreadActionController::class, 'notInterested'])->whereNumber('thread')->name('threads.not-interested');
    Route::post('/threads/{thread}/mute', [ThreadActionController::class, 'toggleMute'])->whereNumber('thread')->name('threads.mute');
    Route::post('/threads/{thread}/report', [ReportController::class, 'thread'])->whereNumber('thread')->name('threads.report');
    Route::post('/users/{user}/block', [ThreadActionController::class, 'toggleBlock'])->whereNumber('user')->name('users.block');
    Route::post('/users/{user}/restrict', [ThreadActionController::class, 'toggleRestrict'])->whereNumber('user')->name('users.restrict');
    Route::post('/threads/{thread}/add-to-feed', [ThreadActionController::class, 'addToCustomFeed'])->whereNumber('thread')->name('threads.add-to-feed');
    Route::get('/api/custom-feeds', [ThreadActionController::class, 'getCustomFeeds'])->name('threads.custom-feeds');
    Route::post('/users/{user}/follow', [ThreadActionController::class, 'follow'])->whereNumber('user')->name('users.follow');
    Route::post('/threads/{thread}/vote', [ThreadController::class, 'votePoll'])->whereNumber('thread')->name('threads.vote');
    Route::get('/api/quote-picker-threads', [ThreadController::class, 'quotePickerThreads'])->name('api.quote-picker');
    Route::get('/api/users/mention-suggestions', [ThreadController::class, 'mentionSuggestions'])->name('users.mention-suggestions');

    Route::middleware('verified')->group(function (): void {
        Route::post('/bai-viet/{id}/thich', [ReaderController::class, 'like'])->whereNumber('id')->middleware('throttle:60,1')->name('likes.store');
        Route::delete('/bai-viet/{id}/thich', [ReaderController::class, 'unlike'])->whereNumber('id')->middleware('throttle:60,1')->name('likes.destroy');
        Route::post('/bai-viet/{id}/luu', [ReaderController::class, 'bookmark'])->whereNumber('id')->name('bookmarks.store');
        Route::delete('/bai-viet/{id}/luu', [ReaderController::class, 'unbookmark'])->whereNumber('id')->name('bookmarks.destroy');
        Route::post('/bai-viet/{id}/binh-luan', [ReaderController::class, 'comment'])->whereNumber('id')->middleware('throttle:comments')->name('comments.store');
        Route::patch('/binh-luan/{comment}', [ReaderController::class, 'updateComment'])->whereNumber('comment')->middleware('throttle:comments')->name('comments.update');
        Route::delete('/binh-luan/{comment}', [ReaderController::class, 'deleteComment'])->whereNumber('comment')->name('comments.destroy');
        Route::post('/binh-luan/{comment}/bao-cao', [ReaderController::class, 'report'])->whereNumber('comment')->middleware('throttle:comments')->name('comments.report');
    });
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('index');
    Route::get('/community', [Admin\CommunityController::class, 'index'])->name('community.index');
    Route::get('/replies', [Admin\CommunityController::class, 'replies'])->name('replies.index');
    Route::patch('/community/{id}', [Admin\CommunityController::class, 'update'])->whereNumber('id')->name('community.update');
    Route::get('/history', [Admin\CommunityController::class, 'history'])->name('history.index');
    Route::resource('posts', Admin\PostController::class)->except('show')->whereNumber('post');
    Route::post('/posts/seo-checks', [Admin\PostController::class, 'seoChecks'])->middleware('throttle:120,1')->name('posts.seo-checks');
    Route::get('/posts/{post}/preview', [BlogController::class, 'preview'])->whereNumber('post')->name('posts.preview');
    Route::post('/posts/{post}/autosave', [Admin\PostController::class, 'autosave'])->whereNumber('post')->name('posts.autosave');
    Route::post('/trash/{id}/restore', [Admin\PostController::class, 'restore'])->whereNumber('id')->name('posts.restore');
    Route::delete('/trash/{id}', [Admin\PostController::class, 'forceDelete'])->whereNumber('id')->name('posts.force-delete');
    Route::get('/taxonomies/{kind}', [Admin\TaxonomyController::class, 'index'])->whereIn('kind', ['categories', 'tags'])->name('taxonomies.index');
    Route::post('/taxonomies/{kind}', [Admin\TaxonomyController::class, 'store'])->whereIn('kind', ['categories', 'tags'])->name('taxonomies.store');
    Route::patch('/taxonomies/{kind}/{id}', [Admin\TaxonomyController::class, 'update'])->whereIn('kind', ['categories', 'tags'])->whereNumber('id')->name('taxonomies.update');
    Route::delete('/taxonomies/{kind}/{id}', [Admin\TaxonomyController::class, 'destroy'])->whereIn('kind', ['categories', 'tags'])->whereNumber('id')->name('taxonomies.destroy');
    Route::get('/media', [Admin\MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [Admin\MediaController::class, 'store'])->middleware('throttle:uploads')->name('media.store');
    Route::patch('/media/{media}', [Admin\MediaController::class, 'update'])->whereNumber('media')->name('media.update');
    Route::delete('/media/{media}', [Admin\MediaController::class, 'destroy'])->whereNumber('media')->name('media.destroy');
    Route::get('/comments', [Admin\CommentController::class, 'index'])->name('comments.index');
    Route::patch('/comments/{comment}', [Admin\CommentController::class, 'update'])->whereNumber('comment')->name('comments.update');
    Route::delete('/comments/{comment}', [Admin\CommentController::class, 'destroy'])->whereNumber('comment')->name('comments.destroy');
    Route::post('/reports/{report}/resolve', [Admin\CommentController::class, 'resolve'])->whereNumber('report')->name('reports.resolve');
    Route::get('/moderation-reports', [Admin\ModerationController::class, 'index'])->name('moderation.index');
    Route::patch('/moderation-reports/{report}', [Admin\ModerationController::class, 'update'])->name('moderation.update');
    Route::patch('/users/{user}/verification', [Admin\UserController::class, 'verification'])->name('users.verification');
    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}', [Admin\UserController::class, 'update'])->whereNumber('user')->name('users.update');
    Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
});

Route::get('/users/{username}/followers', [UserProfileController::class, 'followers'])->name('users.followers');
Route::get('/users/{username}/following', [UserProfileController::class, 'following'])->name('users.following');
Route::get('/@{username}', [UserProfileController::class, 'show'])->name('profile.show');
Route::get('/u/{username}', [UserProfileController::class, 'show']);

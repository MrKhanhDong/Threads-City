<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TaxonomyController;
use App\Http\Controllers\ReaderController;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BlogController::class, 'home'])->name('home');
Route::get('/bai-viet', [BlogController::class, 'archive'])->name('posts.index');
Route::get('/bai-viet/{slug}', [BlogController::class, 'show'])->name('posts.show');
Route::get('/chuyen-muc', [BlogController::class, 'categories'])->name('categories');
Route::get('/gioi-thieu', [BlogController::class, 'about'])->name('about');
Route::get('/media/{path}', [MediaController::class, 'show'])->where('path', '(covers|avatars)/[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)')->name('media');
Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/dang-ky', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/dang-ky', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/quen-mat-khau', [AuthController::class, 'forgotForm'])->name('password.request');
    Route::post('/quen-mat-khau', [AuthController::class, 'forgot'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/dat-lai-mat-khau/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
    Route::post('/dat-lai-mat-khau', [AuthController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});
Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::post('/dang-xuat', [AuthController::class, 'logout'])->name('logout');
    Route::get('/tai-khoan', [AccountController::class, 'edit'])->name('account.edit');
    Route::patch('/tai-khoan', [AccountController::class, 'update'])->name('account.update');
    Route::get('/da-luu', [BlogController::class, 'saved'])->name('saved');
    Route::post('/bai-viet/{post}/luu', [ReaderController::class, 'save'])->name('bookmarks.store');
    Route::delete('/bai-viet/{post}/luu', [ReaderController::class, 'unsave'])->name('bookmarks.destroy');
    Route::post('/bai-viet/{post}/binh-luan', [ReaderController::class, 'comment'])->middleware('throttle:5,1')->name('comments.store');
    Route::delete('/binh-luan/{comment}', [ReaderController::class, 'destroyComment'])->name('comments.destroy');
});
Route::prefix('admin')->name('admin.')->middleware(['auth', 'auth.session', 'admin'])->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/bai-viet', [AdminController::class, 'posts'])->name('posts.index');
    Route::get('/bai-viet/thung-rac', [AdminController::class, 'trash'])->name('posts.trash');
    Route::patch('/bai-viet/{id}/khoi-phuc', [AdminController::class, 'restore'])->whereNumber('id')->name('posts.restore');
    Route::delete('/bai-viet/{id}/xoa-vinh-vien', [AdminController::class, 'forceDestroy'])->whereNumber('id')->name('posts.force-destroy');
    Route::get('/bai-viet/soan', [AdminController::class, 'create'])->name('editor');
    Route::post('/bai-viet', [AdminController::class, 'store'])->name('posts.store');
    Route::get('/bai-viet/{post}/sua', [AdminController::class, 'edit'])->name('posts.edit');
    Route::patch('/bai-viet/{post}', [AdminController::class, 'update'])->name('posts.update');
    Route::delete('/bai-viet/{post}', [AdminController::class, 'destroy'])->name('posts.destroy');
    Route::get('/bai-viet/{post}/xem-truoc', [BlogController::class, 'preview'])->name('posts.preview');
    Route::get('/chuyen-muc', [TaxonomyController::class, 'categories'])->name('categories.index');
    Route::post('/chuyen-muc', [TaxonomyController::class, 'storeCategory'])->name('categories.store');
    Route::patch('/chuyen-muc/{category}', [TaxonomyController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/chuyen-muc/{category}', [TaxonomyController::class, 'deleteCategory'])->name('categories.destroy');
    Route::get('/the', [TaxonomyController::class, 'tags'])->name('tags.index');
    Route::post('/the', [TaxonomyController::class, 'storeTag'])->name('tags.store');
    Route::patch('/the/{tag}', [TaxonomyController::class, 'updateTag'])->name('tags.update');
    Route::delete('/the/{tag}', [TaxonomyController::class, 'deleteTag'])->name('tags.destroy');
    Route::get('/binh-luan', [AdminController::class, 'comments'])->name('comments.index');
    Route::patch('/binh-luan/{comment}', [AdminController::class, 'moderate'])->name('comments.update');
    Route::delete('/binh-luan/{comment}', [AdminController::class, 'deleteComment'])->name('comments.destroy');
    Route::get('/doc-gia', [AdminController::class, 'users'])->name('users.index');
    Route::patch('/doc-gia/{user}', [AdminController::class, 'lockUser'])->name('users.update');
    Route::get('/cai-dat', [AdminController::class, 'settings'])->name('settings.edit');
    Route::patch('/cai-dat', [AdminController::class, 'updateSettings'])->name('settings.update');
});

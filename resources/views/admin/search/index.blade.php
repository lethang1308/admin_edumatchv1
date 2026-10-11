@extends('admin.layout')

@section('content')
    <div class="page-header">
        <h3 class="fw-bold mb-3">Kết quả tìm kiếm cho: "{{ $search }}"</h3>
    </div>

    <div class="row">
        {{-- Articles --}}
        @if($articles->count() > 0)
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Bài viết ({{ $articles->count() }})</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @foreach($articles as $article)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1">{{ $article->title }}</h5>
                                        <small class="text-muted">{{ Str::limit($article->summary, 100) }}</small>
                                    </div>
                                    <a href="{{ route('admin.articles.edit', $article->id) }}"
                                        class="btn btn-sm btn-outline-primary">Sửa</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Categories --}}
        @if($categories->count() > 0)
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Chuyên mục ({{ $categories->count() }})</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @foreach($categories as $category)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>{{ $category->title }}</span>
                                    <a href="{{ route('admin.categories.edit', $category->id) }}"
                                        class="btn btn-sm btn-outline-primary">Sửa</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Users --}}
        @if($users->count() > 0)
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Người dùng ({{ $users->count() }})</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @foreach($users as $user)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div>{{ $user->full_name }}</div>
                                        <small class="text-muted">{{ $user->email }}</small>
                                    </div>
                                    <a href="{{ route('admin.users.edit', $user->id) }}"
                                        class="btn btn-sm btn-outline-primary">Sửa</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Banners --}}
        @if($banners->count() > 0)
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Banners ({{ $banners->count() }})</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @foreach($banners as $banner)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>{{ $banner->title }} ({{ $banner->position }})</span>
                                    <a href="{{ route('admin.banners.edit', $banner->id) }}"
                                        class="btn btn-sm btn-outline-primary">Sửa</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Settings --}}
        @if($settings->count() > 0)
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Cấu hình ({{ $settings->count() }})</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @foreach($settings as $setting)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>{{ $setting->app_name }}</span>
                                    <a href="{{ route('admin.app-setting.index') }}" class="btn btn-sm btn-outline-primary">Xem</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @if($articles->count() == 0 && $categories->count() == 0 && $users->count() == 0 && $banners->count() == 0 && $settings->count() == 0)
            <div class="col-md-12">
                <div class="alert alert-warning text-center">
                    Không tìm thấy kết quả nào cho từ khóa "<strong>{{ $search }}</strong>"
                </div>
            </div>
        @endif
    </div>
@endsection
@extends('admin.layout')

@section('content')
    <h1 class="mb-4">Thêm mới bài viết</h1>

    <form action="{{ route('admin.articles.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label for="title" class="form-label">Tiêu đề bài viết</label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title"
                        value="{{ old('title') }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="summary" class="form-label">Tóm tắt nội dung</label>
                    <textarea class="form-control @error('summary') is-invalid @enderror" id="summary"
                        name="summary">{{ old('summary') }}</textarea>
                    @error('summary')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label">Nội dung chi tiết</label>
                    <textarea class="form-control @error('content') is-invalid @enderror" id="content"
                        name="content">{{ old('content') }}</textarea>
                    @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-4">
                <div class="mb-3">
                    <label for="slug" class="form-label">Đường dẫn (slug)</label>
                    <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug"
                        value="{{ old('slug') }}">
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="category_id" class="form-label mb-0">Chuyên mục</label>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <select class="form-select @error('category_id') is-invalid @enderror" id="category_id"
                        name="category_id" required>
                        <option value="">-- Chọn chuyên mục --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Modal Thêm Danh Mục Nhanh -->
                <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addCategoryModalLabel">Thêm danh mục mới</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="new_category_title" class="form-label">Tên danh mục</label>
                                    <input type="text" class="form-control" id="new_category_title"
                                        placeholder="Nhập tên danh mục...">
                                </div>
                                <div class="mb-3">
                                    <label for="new_category_parent" class="form-label">Danh mục cha (nếu có)</label>
                                    <select class="form-select" id="new_category_parent">
                                        <option value="">-- Chọn danh mục cha --</option>
                                        @foreach ($categories as $cat)
                                            @if(!$cat->parent_category_id)
                                                <option value="{{ $cat->id }}">{{ $cat->title }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                <button type="button" class="btn btn-primary" id="btnSaveCategory">Lưu danh mục</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="published_at" class="form-label">Thời gian đăng</label>
                    <input type="datetime-local" class="form-control @error('published_at') is-invalid @enderror"
                        id="published_at" name="published_at" value="{{ old('published_at') }}">
                    @error('published_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Ảnh đại diện</label>
                    <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image">
                    @error('image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success">Lưu bài viết</button>
        <a href="{{ route('admin.articles.index') }}" class="btn btn-secondary">Quay lại</a>
    </form>

    @push('scripts')
        <script>
            $(document).ready(function () {
                $('#content').summernote({
                    placeholder: 'Nhập nội dung bài viết...',
                    tabsize: 2,
                    height: 300,
                    minHeight: 300,
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'underline', 'clear', 'strikethrough', 'superscript', 'subscript']],
                        ['fontname', ['fontname']],
                        ['fontsize', ['fontsize']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['table', ['table']],
                        ['insert', ['link', 'picture', 'video']],
                        ['view', ['fullscreen', 'codeview', 'help']]
                    ],
                    callbacks: {
                        onImageUpload: function (files) {
                            uploadImage(files[0]);
                        }
                    }
                });

                // Quick Add Category AJAX
                $('#btnSaveCategory').click(function () {
                    const title = $('#new_category_title').val();
                    const parentId = $('#new_category_parent').val();

                    if (!title) {
                        alert('Vui lòng nhập tên danh mục!');
                        return;
                    }

                    $.ajax({
                        url: "{{ route('admin.categories.ajaxStore') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            title: title,
                            parent_category_id: parentId
                        },
                        success: function (response) {
                            if (response.success) {
                                // Add new option to select
                                const newOption = new Option(response.category.title, response.category.id, true, true);
                                $('#category_id').append(newOption).trigger('change');

                                // Clear and close modal
                                $('#new_category_title').val('');
                                $('#new_category_parent').val('');
                                $('#addCategoryModal').modal('hide');

                                // Alert success (optional, or just use a toast if available)
                                // $.notify('Đã thêm danh mục mới', 'success');
                            }
                        },
                        error: function (xhr) {
                            alert('Có lỗi xảy ra: ' + xhr.responseJSON.message);
                        }
                    });
                });

                function uploadImage(file) {
                    let data = new FormData();
                    data.append("upload", file);
                    data.append("_token", "{{ csrf_token() }}");

                    $.ajax({
                        url: "{{ route('admin.uploadCKEditor') }}",
                        cache: false,
                        contentType: false,
                        processData: false,
                        data: data,
                        type: "POST",
                        success: function (response) {
                            if (response.uploaded) {
                                $('#content').summernote('insertImage', response.url);
                            } else {
                                alert("Upload thất bại!");
                            }
                        },
                        error: function (data) {
                            console.log(data);
                            alert("Có lỗi xảy ra khi upload ảnh!");
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
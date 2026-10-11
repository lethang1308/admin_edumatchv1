@extends('admin.layout')

@section('content')
    <div>
        <h1>Cập nhật Thông tin Ứng dụng</h1>

        <form action="{{ route('admin.app-setting.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="app_name">Tên Ứng dụng</label>
                <input type="text" name="app_name" id="app_name" class="form-control"
                    value="{{ old('app_name', $setting->app_name) }}">
            </div>

            <div class="form-group">
                <label for="address">Địa chỉ</label>
                <input type="text" name="address" id="address" class="form-control"
                    value="{{ old('address', $setting->address) }}">
            </div>

            <div class="form-group row">
                <div class="col-md-6">
                    <label for="latitude">Vĩ độ</label>
                    <input type="text" name="latitude" id="latitude" class="form-control"
                        value="{{ old('latitude', $setting->latitude) }}">
                </div>
                <div class="col-md-6">
                    <label for="longitude">Kinh độ</label>
                    <input type="text" name="longitude" id="longitude" class="form-control"
                        value="{{ old('longitude', $setting->longitude) }}">
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-4">
                    <label for="phone">Số điện thoại</label>
                    <input type="text" name="phone" id="phone" class="form-control"
                        value="{{ old('phone', $setting->phone) }}">
                </div>

                <div class="col-md-4">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" class="form-control"
                        value="{{ old('email', $setting->email) }}">
                </div>

                <div class="col-md-4">
                    <label for="default_color">Màu mặc định trên app</label>

                    <div class="input-group">
                        <div class="input-group">
                            <input type="text" name="default_color" id="default_color" class="form-control"
                                placeholder="#000000" value="{{ old('default_color', $setting->default_color) }}">
                            <input type="color" id="color_picker"
                                value="{{ old('default_color', $setting->default_color) }}"
                                style="width: 60px;height: 100%; padding: 0; border: none; background: none;">
                        </div>
                    </div>

                </div>
            </div>

            <div class="form-group">
                <label for="description">Thông tin ứng dụng</label>
                <textarea name="description" id="description" class="form-control"
                    rows="4">{{ old('description', $setting->description) }}</textarea>
            </div>

            <div class="form-group">
                <label for="donated">Tặng khách mới</label>
                <input type="number" name="donated" id="donated" class="form-control"
                    value="{{ old('donated', $setting->donated) }}">
            </div>

            <div class="form-group">
                <label>Logo Web hiện tại:</label><br>
                <div id="logo_container" class="mb-2" style="position: relative; display: inline-block;">
                    @if ($setting->logo_path)
                        <img src="{{ asset('storage/' . $setting->logo_path) }}" width="100" alt="Logo" id="logo_preview">
                        <button type="button" class="btn btn-danger btn-sm" style="position: absolute; top: 0; right: 0;"
                            onclick="deleteImage('logo')">&times;</button>
                        <input type="hidden" name="delete_logo" id="delete_logo" value="0">
                    @else
                        <img src="" width="100" alt="Logo Preview" id="logo_preview" style="display: none;">
                    @endif
                </div>
                <input type="file" name="logo_path" id="logo_input" class="form-control"
                    onchange="previewImage(this, 'logo_preview')">
            </div>

            <div class="form-group">
                <label>Favicon hiện tại:</label><br>
                <div id="favicon_container" class="mb-2" style="position: relative; display: inline-block;">
                    @if ($setting->favicon_path)
                        <img src="{{ asset('storage/' . $setting->favicon_path) }}" width="100" alt="Favicon"
                            id="favicon_preview">
                        <button type="button" class="btn btn-danger btn-sm" style="position: absolute; top: 0; right: 0;"
                            onclick="deleteImage('favicon')">&times;</button>
                        <input type="hidden" name="delete_favicon" id="delete_favicon" value="0">
                    @else
                        <img src="" width="100" alt="Favicon Preview" id="favicon_preview" style="display: none;">
                    @endif
                </div>
                <input type="file" name="favicon_path" id="favicon_input" class="form-control"
                    onchange="previewImage(this, 'favicon_preview')">
            </div>

            <button type="submit" class="btn btn-primary">Cập nhật</button>
            {{-- <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Quay lại</a> --}}
        </form>
    </div>
    <script>
        const colorInput = document.getElementById('default_color');
        const colorPicker = document.getElementById('color_picker');

        // Khi chọn màu từ bảng -> cập nhật ô text
        colorPicker.addEventListener('input', function () {
            colorInput.value = this.value;
        });

        // Khi người dùng gõ vào input text -> cập nhật lại bảng màu
        colorInput.addEventListener('input', function () {
            if (/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(this.value)) {
                colorPicker.value = this.value;
            }
        });

        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);

                // If it's logo or favicon, reset the delete flag
                const type = previewId.split('_')[0];
                const deleteInput = document.getElementById('delete_' + type);
                if (deleteInput) deleteInput.value = "0";
            }
        }

        function deleteImage(type) {
            if (confirm('Bạn có chắc muốn xóa ảnh này?')) {
                const preview = document.getElementById(type + '_preview');
                const deleteInput = document.getElementById('delete_' + type);
                const fileInput = document.getElementById(type + '_input');

                preview.style.display = 'none';
                preview.src = '';
                if (deleteInput) deleteInput.value = "1";
                fileInput.value = ''; // Clear file input
            }
        }
    </script>

@endsection
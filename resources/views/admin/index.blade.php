@extends('admin.layout')
@section('content')
    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
        <div>
            <h3 class="fw-bold mb-3">Dashboard</h3>
            {{-- <h6 class="op-7 mb-2">Free Bootstrap 5 Admin Dashboard</h6> --}}
        </div>
        <div class="ms-md-auto py-2 py-md-0">
            <a href="#" class="btn btn-label-info btn-round me-2">Manage</a>
            {{-- <a href="{{ route('personal.create') }}" class="btn btn-primary btn-round">Thêm nhân viên</a> --}}
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6 col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-primary bubble-shadow-small">
                                <i class="fas fa-tag"></i>
                            </div>
                        </div>
                        <div class="col col-stats ms-3 ms-sm-0">
                            <div class="numbers">
                                <p class="card-category">Doanh số</p>
                                <h4 class="card-title">{{ number_format($totalRevenue, 0, ',', '.') }} đ</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-info bubble-shadow-small">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                        <div class="col col-stats ms-3 ms-sm-0">
                            <div class="numbers">
                                <p class="card-category">Tổng số đơn</p>
                                <h4 class="card-title">{{ $totalOrders }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-secondary bubble-shadow-small">
                                <i class="far fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="col col-stats ms-3 ms-sm-0">
                            <div class="numbers">
                                <p class="card-category">Tổng đơn thành công</p>
                                <h4 class="card-title">{{ $totalSuccessfulOrders }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-success bubble-shadow-small">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="col col-stats ms-3 ms-sm-0">
                            <div class="numbers">
                                <p class="card-category">Khách hàng</p>
                                <h4 class="card-title">{{ $totalUsers }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    {{-- <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">Doanh thu</div>
                <div class="card-body">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-round">
                <div class="card-header">
                    <div class="card-head-row">
                        <div class="card-title">Top 10 sản phẩm bán chạy</div>
                        <div class="card-tools">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-label-light dropdown-toggle" type="button"
                                    id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false">
                                    Export
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <a class="dropdown-item" href="#">Action</a>
                                    <a class="dropdown-item" href="#">Another action</a>
                                    <a class="dropdown-item" href="#">Something else here</a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="card-body p-4">
                    <div class="pull-in">
                        <ul class="list-group">
                            @foreach ($topProducts as $product)
                            <li class="list-group-item d-flex justify-content-between align-items-center"
                                style="border: none">
                                - {{ $product->product_name }}
                                <span class="badge bg-primary rounded-pill">{{ $product->total_sold }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card card-primary card-round">
                <div class="card-header">
                    <div class="card-head-row">
                        <div class="card-title">Doanh thu hằng ngày</div>
                        <div class="card-tools">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-label-light dropdown-toggle" type="button"
                                    id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false">
                                    Export
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <a class="dropdown-item" href="#">Action</a>
                                    <a class="dropdown-item" href="#">Another action</a>
                                    <a class="dropdown-item" href="#">Something else here</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-category">{{ \Carbon\Carbon::now()->format('d/m/Y') }}</div>

                </div>
                <div class="card-body pb-0">
                    <div class="mb-4 mt-2">
                        <h1>{{ number_format($todayRevenue, 0, ',', '.') }} đ</h1>
                    </div>
                    <div class="pull-in">
                        <canvas id="dailySalesChart"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div> --}}

    {{-- <div class="row">
        <div class="col-md-4">
            <div class="card card-round">
                <div class="card-body">
                    <div class="card-head-row card-tools-still-right">
                        <div class="card-title">Khách hàng</div>
                    </div>
                    <div class="card-list py-4">
                        @foreach ($topUsers as $user)
                        <div class="item-list">
                            <div class="avatar">
                                <img src="assets/img/jm_denis.jpg" alt="..." class="avatar-img rounded-circle" />
                            </div>
                            <div class="info-user ms-3">
                                <div class="username">{{ $user->full_name }}</div>
                                <div class="status">{{ number_format($user->balance, 0, ',', '.') }}đ</div>
                            </div>
                            <a href="mailto:{{ $user->email ?? '' }}" class="btn btn-icon btn-link op-8 me-1">
                                <i class="far fa-envelope"></i>
                            </a>
                            <a href="tel:{{ $user->phone ?? '' }}" class="btn btn-icon btn-link btn-danger op-8">
                                <i class="fas fa-phone"></i>
                            </a>
                        </div>
                        @endforeach


                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card card-round">
                <div class="card-header">
                    <div class="card-head-row card-tools-still-right">
                        <div class="card-title">Lịch sử đặt hàng</div>
                        <div class="card-tools">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-clean me-0" type="button" id="dropdownMenuButton"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <a class="dropdown-item" href="#">Action</a>
                                    <a class="dropdown-item" href="#">Another action</a>
                                    <a class="dropdown-item" href="#">Something else here</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <!-- Projects table -->
                        <table class="table align-items-center mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th scope="col">Người đặt</th>
                                    <th scope="col" class="text-end">Ngày & Giờ</th>
                                    <th scope="col" class="text-end">Tổng tiền</th>
                                    <th scope="col" class="text-end">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $order)
                                <tr>
                                    <th scope="row">
                                        <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                            <i class="fa fa-check"></i>
                                        </button>
                                        {{ $order->name }}
                                    </th>
                                    <td class="text-end">{{ $order->created_at->setTimezone('Asia/Ho_Chi_Minh')->format('d -
                                        m - Y, g.iA') }}</td>
                                    <td class="text-end">{{ number_format($order->total, 0, ',', '.') }}đ</td>
                                    @php
                                    $statusLabels = [
                                    'pending' => ['label' => 'Khởi tạo', 'class' => 'secondary'],
                                    'approved' => ['label' => 'Duyệt', 'class' => 'info'],
                                    'packed' => ['label' => 'Đóng gói', 'class' => 'primary'],
                                    'shipped' => ['label' => 'Xuất kho', 'class' => 'warning'],
                                    'completed' => ['label' => 'Hoàn thành', 'class' => 'success'],
                                    'cancelled' => ['label' => 'Hủy đơn', 'class' => 'danger'],
                                    ];

                                    $status = $statusLabels[$order->status] ?? [
                                    'label' => 'Không xác định',
                                    'class' => 'dark',
                                    ];
                                    @endphp
                                    <td class="text-end">
                                        <span class="badge fs-5 bg-{{ $status['class'] }}">
                                            {{ $status['label'] }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
    {{--
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($labels) !!
        },
            datasets: [{
                label: 'Tổng giá trị',
                data: {!! json_encode($values) !!},
        fill: false,
            borderColor: 'rgba(255,99,132,1)',
                backgroundColor: 'rgba(255,99,132,0.2)',
                    tension: 0.4,
                        pointBackgroundColor: '#fff',
                            pointRadius: 5,
                                borderWidth: 2
                    }]
                },
        options: {
            responsive: true,
                plugins: {
                legend: {
                    position: 'top',
                        }
            },
            scales: {
                y: {
                    beginAtZero: true,
                        ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat().format(value) + ' đ';
                        }
                    }
                }
            }
        }
            });
    </script> --}}
@endsection
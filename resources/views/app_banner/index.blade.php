<style>
    .table thead th {
        border-bottom: 0px !important;
    }
    .table td, .table th {
        border-top: 0px !important;
    }

    .table-striped tbody tr{
           box-shadow: 0 0 10px rgb(0, 0, 0, 0.09) !important;
    background: transparent !important;
    border-radius: 10px !important;
    }

    .table-spacing {
    border-collapse: separate !important;
    border-spacing: 0 15px; 
}

.table-spacing tbody tr {
    background: #fff; /* row ko white background dena zaroori hai warna gap transparent dikhega */
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
  
</style>

@extends('layouts.backend.app')
@section('title', 'App Banner')
@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="new-title-b2c-order">App Banners</h4>
                                <a href="{{ route('app.banner.create') }}" class="btn btn-main-1"><span class="mdi mdi-plus"></span>Add New Banner</a>
                            </div>
                            <div class="card-body">
                                @if (session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif
                                <table class="table table-striped table-spacing">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Title</th>
                                            <th>Banner Image</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($banners as $banner)
                                            <tr style="   border-bottom: 0px !important;">
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $banner->title }}</td>
                                                <td>
                                                    @if ($banner->banner_image)
                                                        <img src="{{ asset('storage/' . $banner->banner_image) }}"
                                                            alt="Banner" width="100">
                                                    @endif
                                                </td>
                                                <td><span class="badge badge-{{ $banner->status == 1 ? 'success' : 'danger'}}">{{ $banner->status == 1 ? 'Active' : 'In-active' }}</td>
                                                <td>
                                                    <a href="{{ route('app.banner.edit', $banner->id) }}"
                                                       ><i class="mdi mdi-pencil class-pencil"></i></a>
                                                    <form action="{{ route('app.banner.delete', $banner->id) }}"
                                                        method="POST" style="display:inline-block;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" style="border: none; background : none !important;"
                                                            onclick="return confirm('Are you sure?')"><i class="mdi mdi-trash-can-outline class-delete"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">No banners found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

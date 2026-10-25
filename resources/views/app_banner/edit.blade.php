@extends('layouts.backend.app')
@section('title', 'Edit App Banner')
@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="new-title-b2c-order">Edit Banner</h4>
                            </div>
                            <div class="card-body">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <form method="POST" action="{{ route('app.banner.update', $banner->id) }}"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group mb-3">
                                        <label for="title"  style=" font-size: 15px;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
    color: #484848;
    width: 100%;">Title</label>
                                        <input type="text" name="title" class="form-control"
                                            value="{{ old('title', $banner->title) }}" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="banner_image"  style=" font-size: 15px;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
    color: #484848;
    width: 100%;">Banner Image</label>
                                        <input type="file" name="banner_image" class="form-control">
                                        @if ($banner->banner_image)
                                            <img src="{{ asset('storage/' . $banner->banner_image) }}" alt="Banner"
                                                width="120" class="mt-2">
                                        @endif
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="banner_image"  style=" font-size: 15px;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
    color: #484848;
    width: 100%;">Status</label>
                                        <select type="file" name="status" class="form-control" required style="    height: 49px;
    background: #f3f3f3;
    border: none;
    border-radius: 10px;">
                                                <option value="1" {{  $banner->status == 1 ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{  $banner->status == 0 ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                    <div style="float: right;">

                                        <button type="submit" class="new-submit-btn">Update</button>
                                        <a href="{{ route('app.banner.index') }}" class="new-submit-btn-1">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

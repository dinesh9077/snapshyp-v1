@extends('layouts.backend.app')
@section('title', 'Add App Banner')
@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="new-title-b2c-order">Add New Banner</h4>
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
                                <form method="POST" action="{{ route('app.banner.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group mb-3">
                                        <label for="title" style="font-size: 15px; font-weight: 500; font-family: 'Poppins', sans-serif; color: #484848; width: 100%;">Title</label>
                                        <input type="text" name="title" class="form-control"
                                            value="{{ old('title') }}" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="banner_image" style="font-size: 15px; font-weight: 500; font-family: 'Poppins', sans-serif; color: #484848; width: 100%;">Banner Image</label>
                                        <input type="file" name="banner_image" class="form-control" required>
                                    </div>
 
                                    <div class="form-group mb-3">
                                        <label for="banner_image" style="font-size: 15px; font-weight: 500; font-family: 'Poppins', sans-serif; color: #484848; width: 100%;">Status</label>
                                        <select type="file" name="status" class="form-control" required style="    height: auto; padding: 15px; font-size: 14px; font-weight: 400; color: black; background-color: #F3F3F3; border: none; border-radius: 10px;">
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                        </select>
                                    </div>
                                    <div style="float: right;">
                                        <button type="submit" class="new-submit-btn">Save</button>
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

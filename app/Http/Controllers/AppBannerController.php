<?php

namespace App\Http\Controllers;

use App\Models\AppBanner;
use Illuminate\Http\Request;

class AppBannerController extends Controller
{
    public function index()
    {
        $banners = AppBanner::all();
        return view('app_banner.index', compact('banners'));
    }

    public function create()
    {
        return view('app_banner.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'banner_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:0,1',
        ]);
        $imagePath = $request->file('banner_image')->store('banners', 'public');
        $data['banner_image'] = $imagePath;
        AppBanner::create($data);
        return redirect()->route('app.banner.index')->with('success', 'Banner created successfully.');
    }

    public function edit($id)
    {
        $banner = AppBanner::findOrFail($id);
        return view('app_banner.edit', compact('banner'));
    }

    public function update(Request $request, $id)
    {
        $banner = AppBanner::findOrFail($id);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:0,1',
        ]);
        if ($request->hasFile('banner_image')) {
            $imagePath = $request->file('banner_image')->store('banners', 'public');
            $data['banner_image'] = $imagePath;
        }
        $banner->update($data);
        return redirect()->route('app.banner.index')->with('success', 'Banner updated successfully.');
    }

    public function destroy($id)
    {
        $banner = AppBanner::findOrFail($id);
        $banner->delete();
        return redirect()->route('app.banner.index')->with('success', 'Banner deleted successfully.');
    }
}

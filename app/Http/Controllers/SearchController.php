<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Models\Banner;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        if (!$search) {
            return view('admin.search.index', [
                'articles' => collect(),
                'categories' => collect(),
                'users' => collect(),
                'banners' => collect(),
                'settings' => collect(),
                'search' => ''
            ]);
        }

        $articles = Article::where('title', 'LIKE', "%{$search}%")
            ->orWhere('summary', 'LIKE', "%{$search}%")
            ->orWhere('content', 'LIKE', "%{$search}%")
            ->get();

        $categories = Category::where('title', 'LIKE', "%{$search}%")
            ->orWhere('description', 'LIKE', "%{$search}%")
            ->get();

        $users = User::where('name', 'LIKE', "%{$search}%")
            ->orWhere('full_name', 'LIKE', "%{$search}%")
            ->orWhere('email', 'LIKE', "%{$search}%")
            ->orWhere('phone', 'LIKE', "%{$search}%")
            ->get();

        $banners = Banner::where('title', 'LIKE', "%{$search}%")
            ->orWhere('position', 'LIKE', "%{$search}%")
            ->get();

        $settings = AppSetting::where('app_name', 'LIKE', "%{$search}%")
            ->orWhere('description', 'LIKE', "%{$search}%")
            ->orWhere('address', 'LIKE', "%{$search}%")
            ->get();

        return view('admin.search.index', compact('articles', 'categories', 'users', 'banners', 'settings', 'search'));
    }
}

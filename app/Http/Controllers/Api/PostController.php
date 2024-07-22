<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function index() {
        $posts = Post::latest()->paginate(5);

        return response()->json([
            'success' => true,
            'message' => 'List Data',
            'data' => $posts
        ], 200);
        // return new PostResource(true, 'List Data Posts', $posts);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:png,jpg,jpeg,gif,svg|max:2048',
            'title' => 'required',
            'content' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $image = $request->file('image');
        $image->storeAs('public/posts', $image->hashName());

        $post = Post::create([
            'image' => $image->hashName(),
            'title' => $request->title,
            'content' => $request->content
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil ditambahkan',
            'data' => $post
        ], 201);
        // return new PostResource(true, 'Data Post berhasil Ditambahkan', $post);
    }

    public function show($id) {
        $post = Post::find($id);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil ditemukan',
            'data' => $post
        ], 200);
        // return new PostResource(true, 'Data berhasil ditemukan', $post);
    }

    public function update(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $post = Post::find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image->storeAs('public/posts', $image->hashName());

            Storage::delete('public/posts' . basename($post->image));

            $post->update($request->all());
        } else {
            $post->update([
                'title' => $request->title,
                'content' => $request->content
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diupdate',
            'data' => $post
        ], 200);
        // return new PostResource(true, 'Data berhasil di update', $post);
    }

    public function destroy($id) {
        $post = Post::find($id);

        Storage::delete('public/posts' . basename($post->image));

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diupdate',
            'data' => null
        ], 200);
        // return new PostResource(true, 'Data berhasil dihapus', null);
    }
}

<?php

namespace App\Http\Controllers\Api;

//import Model "Post"
use App\Models\Post;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

//import Resource "PostResource"
use App\Http\Resources\PostResource;

//import Facade "Storage"
use Illuminate\Support\Facades\Storage;

//import Facade "Validator"
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    /**
     * index
     *
     * @param  Request $request
     * @return void
     */
    public function index(Request $request)
{
    //get search query
    $search = $request->query('title');

    //get all posts or search by title
    $posts = $search 
        ? Post::where('title', 'like', "%{$search}%")->latest()->paginate(5)
        : Post::latest()->paginate(5);

    //check if posts data is empty
    if ($posts->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'Data Posts Tidak Ditemukan!',
        ], 404);
    }

    //return collection of posts as a resource
    return new PostResource(true, 'List Data Posts', $posts);
}

    /**
     * store
     *
     * @param  mixed $request
     * @return void
     */
    public function store(Request $request)
{
    //define validation rules
    $validator = Validator::make($request->all(), [
        'image'     => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'title'     => 'required',
        'description'   => 'required',
        'price'   => 'required|numeric|min:0',
        'stock'   => 'required|integer|min:0',
    ]);

    //check if validation fails
    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }

    //upload image
    $image = $request->file('image');
    $image->storeAs('public/posts', $image->hashName());

    //create post
    $post = Post::create([
        'image'     => $image->hashName(),
        'title'     => $request->title,
        'description'   => $request->description,
        'price'   => $request->price,
        'stock'   => $request->stock,
    ]);

    //return response with 201 status code
    return (new PostResource(true, 'Data Post Berhasil Ditambahkan!', $post))
        ->response()
        ->setStatusCode(201);
}

    /**
     * show
     *
     * @param  mixed $post
     * @return void
     */
    public function show($id)
    {
        //find post by ID
        $post = Post::find($id);

        //return single post as a resource
        return new PostResource(true, 'Detail Data Post!', $post);
    }

    /**
     * update
     *
     * @param  mixed $request
     * @param  mixed $post
     * @return void
     */
    public function update(Request $request, $id)
    {
        //define validation rules
        $validator = Validator::make($request->all(), [
            'title'     => 'required',
            'description'   => 'required',
            'price'   => 'required|numeric|min:0',
            'stock'   => 'required|integer|min:0',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        //find post by ID
        $post = Post::find($id);

        //check if image is not empty
        if ($request->hasFile('image')) {

            //upload image
            $image = $request->file('image');
            $image->storeAs('public/posts', $image->hashName());

            //delete old image
            Storage::delete('public/posts/'.basename($post->image));

            //update post with new image
            $post->update([
                'image'     => $image->hashName(),
                'title'     => $request->title,
                'description'   => $request->description,
                'price'   => $request->price,
                'stock'   => $request->stock,
            ]);

        } else {

            //update post without image
            $post->update([
                'title'     => $request->title,
                'description'   => $request->description,
                'price'   => $request->price,
                'stock'   => $request->stock,
            ]);
        }

        //return response
        return new PostResource(true, 'Data Post Berhasil Diubah!', $post);
    }

    /**
     * destroy
     *
     * @param  mixed $post
     * @return void
     */
    public function destroy($id)
{
    //find post by ID
    $post = Post::find($id);

    //check if post exists
    if (!$post) {
        return response()->json([
            'success' => false,
            'message' => 'Data Post Tidak Ditemukan!',
        ], 404);
    }

    //delete image
    Storage::delete('public/posts/'.basename($post->image));

    //delete post
    $post->delete();
    
    return response()->noContent();
}
}

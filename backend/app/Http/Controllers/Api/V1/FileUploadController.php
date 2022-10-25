<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\File;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FileUploadController extends Controller
{
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:jpg,png,web,jpeg,tiff,tag',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        if ($file = $request->file('file')) {
            $path = $file->store('public/images');
            $name = $file->getClientOriginalName();
            // return $path;
            //store your file into directory and db
            $save = new File();
            $save->name = $name;
            $save->path = $path;
            $save->save();
            return response()->json([
                "success" => true,
                "message" => "File successfully uploaded",
                "file" => $save
            ]);
        }
    }
}

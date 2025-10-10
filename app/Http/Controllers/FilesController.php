<?php

namespace App\Http\Controllers;

use App\Models\Files;
use Illuminate\Http\Request;

class FilesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'file_name' => 'required|string',
            'file_description' => 'nullable|string',
            'folder' => 'nullable|string'
        ]);

        $file = $request->file('file');
        $folder = $request->folder ?? 'uploads';
        
        // Store file with custom filename
        $extension = $file->getClientOriginalExtension();
        $originalFilename = $request->file_name;
        $counter = 1;

        // Check if file exists and increment counter until unique name is found
        do {
            $filename = $counter === 1 ? 
                $originalFilename . '.' . $extension :
                $originalFilename . ' ' . $counter . '.' . $extension;
            
            $exists = Files::where('file_path', $folder . '/' . $filename)->exists();
            $counter++;
        } while ($exists);

        $file_path = $file->storeAs($folder, $filename, 'public');

        $fileModel = Files::create([
            'file_name' => pathinfo($filename, PATHINFO_FILENAME),
            'file_path' => $file_path,
            'file_size' => $file->getSize(),
            'file_mime_type' => $file->getMimeType(), 
            'file_type' => $file->getClientOriginalExtension(),
            'file_extension' => $file->getClientOriginalExtension(),
            'file_status' => 'active',
            'file_description' => $request->file_description
        ]);

        return response()->json([
            'message' => 'File uploaded successfully',
            'file' => $fileModel
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Files $files)
    {
        if (!$files) {
            return response()->json([
                'message' => 'File not found'
            ], 404);
        }

        return response()->json([
            'file' => $files
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Files $files)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Files $files)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Files $files)
    {
        //
    }
}

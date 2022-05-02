<?php

namespace App\Http\Controllers\api;

use App\Helpers\ImageUploader;
use App\Http\Controllers\Controller;
use App\Http\Resources\StudentCollection;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = $request->per_page ? $request->per_page : 10;
        $students = Student::paginate($per_page);

        return new StudentCollection($students);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required',
            'place' => 'required',
            'dob' => 'required',
            'adno' => 'required',
            'class' => 'required',
        ],
        [
            'name.required' => 'Name is required',
            'place.required' => 'Place is required',
            'dob.required' => 'Date of birth is required',
            'adno.required' => 'Admission number is required',
            'class.required' => 'Class is required',
        ])->validate();


        $data = $request->all();

        if($request->hasFile('photo')) {
            $data['photo'] = ImageUploader::upload($request->file('photo'), 'students');
        }

        $student = Student::create($data);

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student),
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function show(Student $student)
    {
        return response()->json([
            'success' => true,
            'data' => new StudentResource($student),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Student $student)
    {
        Validator::make($request->all(), [
            'name' => 'required',
            'place' => 'required',
            'dob' => 'required',
            'class' => 'required',
        ],
        [
            'name.required' => 'Name is required',
            'place.required' => 'Place is required',
            'dob.required' => 'Date of birth is required',
            'class.required' => 'Class is required',
        ])->validate();


        $data = $request->all();

        if($request->hasFile('photo')) {
            if($student->photo) {
                ImageUploader::delete('students', $student->photo);
            }

            $data['photo'] = ImageUploader::upload($request->file('photo'), 'students');
        }

        $student->update($data);

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function destroy(Student $student)
    {
        $student->delete();

        if($student->hasPhoto()) {
            ImageUploader::delete('students', $student->photo);
        }

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }
}

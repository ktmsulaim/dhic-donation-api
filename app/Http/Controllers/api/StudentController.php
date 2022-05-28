<?php

namespace App\Http\Controllers\api;

use App\Helpers\ImageUploader;
use App\Http\Controllers\Controller;
use App\Http\Resources\StudentCollection;
use App\Http\Resources\StudentResource;
use App\Imports\StudentsImport;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

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
        $q = $request->q ? $request->q : '';
        $students = Student::where(function($query) use($q){
            if($q){
                $query->where('name', 'like', '%'.$q.'%')
                    ->orWhere('adno', '=', $q);
            }
        })->paginate($per_page);

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
        $data = $request->all();
        
        Validator::make($data, [
            'name' => 'required',
            'place' => 'required',
            'dob' => 'required',
            'adno' => ['required', 'unique:students,adno'],
            'class' => 'required',
        ],
        [
            'name.required' => 'Name is required',
            'place.required' => 'Place is required',
            'dob.required' => 'Date of birth is required',
            'adno.required' => 'Admission number is required',
            'adno.unique' => 'Admission number already exists',
            'class.required' => 'Class is required',
        ])->validate();

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
     * Import students from excel file.
     */
    public function import(Request $request) {
        try {
            $collection = (new StudentsImport)->toCollection($request->file('file'));
            $imported = 0;

            foreach($collection[0] as $row) {
                $student = Student::where('adno', $row['adno'])->first();

                if(!$student) {
                    $student = Student::create([
                        'name' => $row["name"],
                        'place' => $row["place"],
                        'dob' => $row["dob"],
                        'class' => $row["class"],
                        'adno' => $row["adno"],
                    ]);
                    $imported++;
                }

                if(!$student->subscription) {
                    $subscription = $student->subscription()->create([
                        'amount' => $row["amount"],
                        'interval' => $row["interval"],
                        'start_date' => $row["start_date"],
                        'end_date' => $row["end_date"],
                    ]);

                    $subscription->createHistory();
                }
            }

            return response()->json([
                'success' => true,
                'data' => $imported . " students imported successfully",
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'success' => false]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function show(Student $student)
    {
        $student->load('subscription');
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

    public function updateMany(Request $request)
    {
        Validator::make($request->only('students'), [
            'students' => 'required',
        ], 
        [
            'students.required' => 'Students are required',
        ])->validate();

        $students = $request->students;

        foreach($students as $student) {
            $student = Student::find($student);

            if($student) {
                if($request->class > 0) {
                    $student->class = $request->class;
                }

                if(!is_null($request->active) && in_array($request->active, [0, 1])) {
                    $student->active = $request->active;
                }

                $student->save();
            }
        }

        return response()->json([
            'success' => true,
            'data' => "Students updated successfully",
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
            'data' => "The student has been deleted successfully",
        ], 204);
    }

    public function destroyMany(Request $request) {
        Validator::make($request->only('students'), [
            'students' => 'required',
        ], 
        [
            'students.required' => 'Students are required',
        ])->validate();

        $students = $request->students;

        foreach($students as $student) {
            $student = Student::find($student);

            if($student) {
                $student->delete();
            }
        }

        return response()->json([
            'success' => true,
            'data' => "The students have been deleted successfully",
        ], 204);
    }
}

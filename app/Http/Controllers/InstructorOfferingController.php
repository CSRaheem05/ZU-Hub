<?php
namespace App\Http\Controllers;

use App\Http\Requests\CourseRequest;
use App\Services\CourseCoverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Instructor course requests: create, edit / resubmit, track approval, mark completed.
 *
 * A course has two independent states:
 *   approval_status  pending | changes_requested | approved | rejected   (decided by the administration)
 *   course_status    ongoing | completed                                  (decided by the instructor)
 */
class InstructorOfferingController extends Controller {
    public function __construct(private readonly CourseCoverService $covers) {}

    private function checkInstructor(Request $r): void { abort_unless($r->user()?->role === 'instructor',403); }
    private function own(Request $r,string $id): object {
        $this->checkInstructor($r);
        $course=DB::table('course_offerings')->where('offering_id',$id)->where('instructor_id',$r->user()->user_id)->first();
        abort_unless($course,404); return $course;
    }
    private function subjects() {
        return DB::table('courses as c')->join('departments as d','d.department_code','=','c.department_code')
            ->join('colleges as co','co.college_code','=','d.college_code')
            ->select('c.course_id','c.course_name','d.department_code','d.department_name','co.college_code','co.college_name')
            ->orderBy('co.college_name')->orderBy('d.department_name')->orderBy('c.course_name')->get();
    }
    private function topics() { return DB::table('topics')->orderBy('topic_name')->get(); }

    public function create(Request $r) {
        $this->checkInstructor($r);
        return view('instructor.course-form',['subjects'=>$this->subjects(),'topics'=>$this->topics(),'course'=>null]);
    }

    public function store(CourseRequest $r) {
        $fields=$r->details();
        $cover=$r->hasFile('cover_image') ? $this->covers->store($r->file('cover_image')) : null;

        DB::table('course_offerings')->insert($fields+[
            'offering_id'=>(string)Str::uuid(),'instructor_id'=>$r->user()->user_id,'cover_image'=>$cover,
            'students_count'=>0,'approval_status'=>'pending','course_status'=>'ongoing','created_at'=>now()
        ]);
        return redirect()->route('instructor.requests')->with('success','Course request submitted for approval.');
    }

    /** Edit a course that needs changes (resubmit) or an approved one (details only). */
    public function edit(Request $r,string $offeringId) {
        $c=$this->own($r,$offeringId);
        abort_unless(in_array($c->approval_status,['changes_requested','approved'],true),403,'This course cannot be edited right now.');
        return view('instructor.course-form',['course'=>$c,'subjects'=>$this->subjects(),'topics'=>$this->topics()]);
    }

    public function update(CourseRequest $r,string $offeringId) {
        $c=$r->course();
        $fields=$r->details();

        // cover: a new upload replaces the old one; "remove" clears it
        $oldCover=$c->cover_image; $newCover=null; $coverChanged=false;
        if($r->hasFile('cover_image')) { $newCover=$this->covers->store($r->file('cover_image')); $coverChanged=true; }
        elseif($r->boolean('remove_cover')) { $coverChanged=true; }
        if($coverChanged) $fields['cover_image']=$newCover;

        if($c->approval_status==='changes_requested') {
            // resubmitting: the request goes back to the administration
            DB::table('course_offerings')->where('offering_id',$offeringId)->where('approval_status','changes_requested')
                ->update($fields+['approval_status'=>'pending','reviewed_by'=>null,'reviewed_at'=>null]);
            $message='Request updated and resubmitted.'; $to=route('instructor.requests');
        } else {
            // approved: only details that do not alter what was approved; stays published
            DB::table('course_offerings')->where('offering_id',$offeringId)->where('approval_status','approved')->update($fields);
            $message='Course updated.'; $to=route('instructor.courses');
        }

        if($coverChanged) $this->covers->delete($oldCover);
        return redirect($to)->with('success',$message);
    }

    public function requests(Request $r) {
        $this->checkInstructor($r);
        $requests=DB::table('course_offerings as o')->join('courses as c','c.course_id','=','o.course_id')
            ->leftJoin('topics as t','t.topic_id','=','o.topic_id')
            ->where('o.instructor_id',$r->user()->user_id)
            ->select('o.*','c.course_name','t.topic_name')
            ->orderByRaw("CASE o.approval_status WHEN 'changes_requested' THEN 0 WHEN 'pending' THEN 1 ELSE 2 END")  // what needs attention first
            ->orderByDesc('o.created_at')->get();
        return view('instructor.requests',compact('requests'));
    }

    public function complete(Request $r,string $offeringId) {
        $c=$this->own($r,$offeringId); abort_unless($c->approval_status==='approved',403);
        DB::table('course_offerings')->where('offering_id',$offeringId)->update([
            'course_status'=>$c->course_status==='ongoing'?'completed':'ongoing',
            'completed_at'=>$c->course_status==='ongoing'?now():null
        ]);
        return back()->with('success','Course learning status updated.');
    }
}

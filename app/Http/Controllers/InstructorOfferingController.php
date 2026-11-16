<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InstructorOfferingController extends Controller {
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
    private function validateData(Request $r): array {
        $d=$r->validate([
            'course_id'=>['required',Rule::exists('courses','course_id')],
            'topic_id'=>['nullable',Rule::exists('topics','topic_id')],
            'title'=>['required','string','max:255'],
            'description'=>['nullable','string'],
            'duration_hours'=>['nullable','numeric','min:0.01','max:9999.99'],
            'lecture_schedule'=>['nullable','string','max:2000'],
        ]);
        if (!empty($d['topic_id'])) {
            $college=DB::table('courses as c')->join('departments as d','d.department_code','=','c.department_code')
                ->where('c.course_id',$d['course_id'])->value('d.college_code');
            abort_unless(DB::table('topics')->where('topic_id',$d['topic_id'])->where('college_code',$college)->exists(),422,'Topic must belong to the selected course college.');
        }
        return $d;
    }
    public function create(Request $r) { $this->checkInstructor($r); return view('instructor.course-form',['subjects'=>$this->subjects(),'topics'=>$this->topics(),'course'=>null]); }
    public function store(Request $r) {
        $this->checkInstructor($r); $d=$this->validateData($r);
        DB::table('course_offerings')->insert([
            'offering_id'=>(string)Str::uuid(),'course_id'=>$d['course_id'],'instructor_id'=>$r->user()->user_id,
            'title'=>$d['title'],'description'=>$d['description']??null,'duration_hours'=>$d['duration_hours']??null,
            'lecture_schedule'=>$d['lecture_schedule']??null,'topic_id'=>$d['topic_id']??null,
            'students_count'=>0,'approval_status'=>'pending','course_status'=>'ongoing','is_active'=>0,'created_at'=>now()
        ]);
        return redirect()->route('instructor.requests')->with('success','Course request submitted for approval.');
    }
    public function edit(Request $r,string $offeringId) {
        $c=$this->own($r,$offeringId);
        abort_unless(in_array($c->approval_status,['changes_requested'],true),403,'Only requests needing changes can be edited here.');
        return view('instructor.course-form',['course'=>$c,'subjects'=>$this->subjects(),'topics'=>$this->topics()]);
    }
    public function update(Request $r,string $offeringId) {
        $c=$this->own($r,$offeringId);
        abort_unless($c->approval_status==='changes_requested',403);
        $d=$this->validateData($r);
        DB::table('course_offerings')->where('offering_id',$offeringId)->where('approval_status','changes_requested')->update([
            'course_id'=>$d['course_id'],'topic_id'=>$d['topic_id']??null,'title'=>$d['title'],
            'description'=>$d['description']??null,'duration_hours'=>$d['duration_hours']??null,
            'lecture_schedule'=>$d['lecture_schedule']??null,'approval_status'=>'pending',
            'reviewed_by'=>null,'reviewed_at'=>null
        ]);
        return redirect()->route('instructor.requests')->with('success','Request updated and resubmitted.');
    }
    public function requests(Request $r) {
        $this->checkInstructor($r);
        $requests=DB::table('course_offerings')->where('instructor_id',$r->user()->user_id)
            ->orderByDesc('created_at')->get();
        return view('instructor.requests',compact('requests'));
    }
    public function toggleStatus(Request $r,string $offeringId) {
        $c=$this->own($r,$offeringId); abort_unless($c->approval_status==='approved',403);
        DB::table('course_offerings')->where('offering_id',$offeringId)->update(['is_active'=>$c->is_active?0:1]);
        return back()->with('success','Visibility updated.');
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

<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminCourseRequestController extends Controller {
    private function authorizeManager(Request $r): void { abort_unless($r->user()?->role==='manager',403); }
    private function query() {
        return DB::table('course_offerings as o')->join('users as u','u.user_id','=','o.instructor_id')
            ->join('courses as c','c.course_id','=','o.course_id')
            ->join('departments as d','d.department_code','=','c.department_code')
            ->join('colleges as co','co.college_code','=','d.college_code')
            ->leftJoin('topics as t','t.topic_id','=','o.topic_id')
            ->select('o.*','u.name as instructor_name','u.email as instructor_email',
                'c.course_name','co.college_name','d.department_name','t.topic_name');
    }
    public function index(Request $r) {
        $this->authorizeManager($r);
        $status=$r->query('status','pending');
        $q=$this->query();
        if(in_array($status,['pending','changes_requested','approved','rejected'],true)) $q->where('o.approval_status',$status);
        $counts=DB::table('course_offerings')->selectRaw('approval_status, COUNT(*) as total')->groupBy('approval_status')->pluck('total','approval_status');
        $counts=['pending'=>(int)($counts['pending']??0),'changes_requested'=>(int)($counts['changes_requested']??0),
            'approved'=>(int)($counts['approved']??0),'rejected'=>(int)($counts['rejected']??0)]+['all'=>0];
        $counts['all']=array_sum($counts);
        return view('management.requests',['requests'=>$q->orderByDesc('o.created_at')->get(),'status'=>$status,'counts'=>$counts]);
    }
    public function show(Request $r,string $offeringId) {
        $this->authorizeManager($r);
        $course=$this->query()->where('o.offering_id',$offeringId)->first(); abort_unless($course,404);
        $topics = DB::table('topics as t')
            ->join('departments as d', 'd.college_code', '=', 't.college_code')
            ->join('courses as c', 'c.department_code', '=', 'd.department_code')
            ->where('c.course_id', $course->course_id)
            ->select('t.topic_id', 't.topic_name')
            ->distinct()
            ->orderBy('t.topic_name')
            ->get();
        return view('management.review',compact('course','topics'));
    }
    public function updateDetails(Request $r,string $offeringId) {
        $this->authorizeManager($r);
        $data=$r->validate(['title'=>['required','string','max:255'],
            'topic_id'=>['nullable','string','max:50']]);
        DB::transaction(function()use($offeringId,$data){
            $c=DB::table('course_offerings')->where('offering_id',$offeringId)->lockForUpdate()->first();
            abort_unless($c,404);
            abort_unless($c->approval_status==='pending',409);
            if(!empty($data['topic_id'])) {
                $college=DB::table('courses as c')->join('departments as d','d.department_code','=','c.department_code')
                    ->where('c.course_id',$c->course_id)->value('d.college_code');
                abort_unless(DB::table('topics')->where('topic_id',$data['topic_id'])->where('college_code',$college)->exists(),422);
            }
            DB::table('course_offerings')->where('offering_id',$offeringId)->update([
                'title'=>$data['title'],'topic_id'=>$data['topic_id']??null
            ]);
        });
        return back()->with('success','Administrative details updated.');
    }
    public function decide(Request $r,string $offeringId) {
        $this->authorizeManager($r);
        $data=$r->validate(['decision'=>['required',Rule::in(['approved','changes_requested','rejected'])],
            'review_note'=>['nullable','string','max:3000']]);
        if($data['decision']!=='approved' && trim($data['review_note']??'')==='') {
            return back()->withErrors(['review_note'=>'A reason is required for this decision.'])->withInput();
        }
        $changed=DB::transaction(function () use($r,$offeringId,$data) {
            $c=DB::table('course_offerings')->where('offering_id',$offeringId)->lockForUpdate()->first();
            abort_unless($c,404);
            abort_unless($c->approval_status==='pending',409,'Only pending requests can be reviewed.');
            return DB::table('course_offerings')->where('offering_id',$offeringId)->update([
                'approval_status'=>$data['decision'],'review_note'=>trim($data['review_note']??'')?:null,
                'reviewed_by'=>$r->user()->user_id,'reviewed_at'=>now()
            ]);
        });
        return redirect()->route('management.requests')->with('success','Request decision saved.');
    }
}

<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoursePageController extends Controller {
    private function isStudent(Request $r): void { abort_unless($r->user()?->role === 'student',403); }
    /** Courses students may see: approved ones. (Ongoing = needs enrolment, completed = open to watch.) */
    private function published() { return DB::table('course_offerings')->where('approval_status','approved'); }
    /**
     * The course discovery page. Every approved offering is sent to the page once; the page filters instantly in the
     * browser, while the same ?college=&topic=&status=&q= query decides what is shown first (and without JavaScript).
     */
    public function index(Request $r) {
        $college=trim((string)$r->query('college'));
        $topic=trim((string)$r->query('topic'));
        $status=in_array($r->query('status'),['ongoing','completed'],true)?$r->query('status'):'';
        $search=mb_substr(trim((string)$r->query('q')),0,80);

        $courses=DB::table('course_offerings as o')->join('courses as c','c.course_id','=','o.course_id')
            ->join('departments as d','d.department_code','=','c.department_code')
            ->join('colleges as co','co.college_code','=','d.college_code')
            ->join('users as u','u.user_id','=','o.instructor_id')
            ->leftJoin('topics as t','t.topic_id','=','o.topic_id')
            ->where('o.approval_status','approved')
            ->select('o.*','c.course_name','co.college_code','co.college_name','t.topic_name','u.name as instructor_name')
            ->orderByDesc('o.created_at')->get();

        $totals=$courses->countBy('college_code');
        // all colleges, those that have courses first (the rest are tucked away by the page)
        $colleges=DB::table('colleges')->orderBy('college_name')->get()
            ->each(fn($co)=>$co->total=(int)($totals[$co->college_code]??0));

        // only topics that really have an approved course are offered
        $topicsByCollege=DB::table('topics')->whereIn('topic_id',$courses->pluck('topic_id')->filter()->unique()->all())
            ->orderBy('topic_name')->get()->groupBy('college_code')
            ->map(fn($rows)=>$rows->map(fn($t)=>['id'=>$t->topic_id,'name'=>$t->topic_name])->values())->all();

        // a topic only means something inside its own college: an old topic-only link picks up that college,
        // and a topic that does not belong to the chosen college is ignored
        if($topic!==''&&$college===''){
            foreach($topicsByCollege as $code=>$rows){ if(collect($rows)->contains('id',$topic)){ $college=(string)$code; break; } }
        }
        if($topic!==''&&!collect($topicsByCollege[$college]??[])->contains('id',$topic)){ $topic=''; }

        $needle=mb_strtolower($search);
        $courses->each(function($c) use($college,$topic,$status,$needle){
            $c->search=mb_strtolower(implode(' ',[$c->title,$c->course_name,$c->topic_name,$c->college_name,$c->instructor_name]));
            $c->visible=(!$college||$c->college_code===$college)&&(!$topic||$c->topic_id===$topic)&&(!$status||$c->course_status===$status)
                &&($needle===''||str_contains($c->search,$needle));
        });

        $enrolledOfferingIds=collect();$savedOfferingIds=collect();
        if($r->user()?->role==='student'){
            $enrolledOfferingIds=DB::table('course_members')->where('user_id',$r->user()->user_id)->pluck('offering_id');
            $savedOfferingIds=DB::table('user_saved_courses')->where('user_id',$r->user()->user_id)->pluck('offering_id');
        }
        return view('courses.index',compact('courses','colleges','topicsByCollege','college','topic','status','search','enrolledOfferingIds','savedOfferingIds'));
    }
    public function studentCourses(Request $r) {
        $this->isStudent($r);$id=$r->user()->user_id;
        $base=DB::table('course_offerings as o')->join('courses as c','c.course_id','=','o.course_id')
            ->join('departments as d','d.department_code','=','c.department_code')
            ->join('colleges as co','co.college_code','=','d.college_code')
            ->join('users as u','u.user_id','=','o.instructor_id');
        $cols=['o.offering_id','o.title','o.description','o.course_status','o.cover_image','o.duration_hours','c.course_name','co.college_name','u.name as instructor_name'];
        $courses=(clone $base)->join('course_members as m','m.offering_id','=','o.offering_id')
            ->where('m.user_id',$id)->where('o.approval_status','approved')
            ->select($cols)->orderByDesc('o.created_at')->get();
        $saved=(clone $base)->join('user_saved_courses as s','s.offering_id','=','o.offering_id')
            ->where('s.user_id',$id)->where('o.approval_status','approved')->where('o.course_status','completed')
            ->whereNotIn('o.offering_id',$courses->pluck('offering_id')->all())
            ->select($cols)->orderByDesc('s.saved_at')->get();

        // What the library can honestly show: how many lessons a course has, and the student's own recorded progress.
        // Progress is only ever read from the `progress` table (rows exist only once a student has really started a lesson);
        // nothing is inferred from enrolment.
        $ids=$courses->pluck('offering_id')->merge($saved->pluck('offering_id'))->unique()->values()->all();
        $totals=$ids?DB::table('lessons')->whereIn('offering_id',$ids)->selectRaw('offering_id, COUNT(*) as total')->groupBy('offering_id')->pluck('total','offering_id'):collect();
        $mine=$ids?DB::table('progress as p')->join('lessons as l','l.lesson_id','=','p.lesson_id')
            ->where('p.user_id',$id)->whereIn('l.offering_id',$ids)
            ->selectRaw('l.offering_id, COUNT(*) as started, SUM(p.is_completed) as done')->groupBy('l.offering_id')->get()->keyBy('offering_id'):collect();
        foreach($courses->concat($saved) as $c){
            $c->lessons_total=(int)($totals[$c->offering_id]??0);
            $c->lessons_started=(int)($mine[$c->offering_id]->started??0);
            $c->lessons_done=(int)($mine[$c->offering_id]->done??0);
        }
        return view('student.my-courses',compact('courses','saved'));
    }
    public function enroll(Request $r,string $offeringId) {
        $this->isStudent($r);
        abort_unless($this->published()->where('offering_id',$offeringId)->where('course_status','ongoing')->exists(),404);
        DB::table('course_members')->insertOrIgnore(['user_id'=>$r->user()->user_id,'offering_id'=>$offeringId]);
        // Database triggers maintain students_count.
        return redirect()->route('student.courses')->with('success','You are enrolled.');
    }
    public function saveCompleted(Request $r,string $offeringId) {
        $this->isStudent($r);
        abort_unless($this->published()->where('offering_id',$offeringId)->where('course_status','completed')->exists(),404);
        DB::table('user_saved_courses')->insertOrIgnore(['user_id'=>$r->user()->user_id,'offering_id'=>$offeringId,'saved_at'=>now()]);
        return back()->with('success','Course saved to My Courses.');
    }
    public function unsaveCompleted(Request $r,string $offeringId) {
        $this->isStudent($r);
        DB::table('user_saved_courses')->where('user_id',$r->user()->user_id)->where('offering_id',$offeringId)->delete();
        return redirect()->route('student.courses')->with('success','Course removed from saved courses.');
    }
    public function instructorCourses(Request $r) {
        abort_unless($r->user()?->role==='instructor',403);
        $courses=DB::table('course_offerings as o')->join('courses as c','c.course_id','=','o.course_id')
            ->where('o.instructor_id',$r->user()->user_id)->where('o.approval_status','approved')
            ->select('o.*','c.course_name')->orderByDesc('o.created_at')->get();
        // requests waiting on the instructor (changes asked for) or on the administration (pending)
        $requestCounts=DB::table('course_offerings')->where('instructor_id',$r->user()->user_id)
            ->whereIn('approval_status',['changes_requested','pending'])
            ->selectRaw('approval_status, COUNT(*) as total')->groupBy('approval_status')->pluck('total','approval_status');
        return view('instructor.my-courses',['courses'=>$courses,'needsChanges'=>(int)($requestCounts['changes_requested']??0),'pendingRequests'=>(int)($requestCounts['pending']??0)]);
    }
    public function instructorStudents(Request $r,string $offeringId) {
        abort_unless($r->user()?->role==='instructor',403);
        $course=DB::table('course_offerings')->where('offering_id',$offeringId)->where('instructor_id',$r->user()->user_id)
            ->where('approval_status','approved')->first();abort_unless($course,404);
        $students=DB::table('course_members as m')->join('users as u','u.user_id','=','m.user_id')
            ->where('m.offering_id',$offeringId)->where('u.role','student')->select('u.user_id','u.name','u.email')->orderBy('u.name')->get();
        return view('instructor.students',compact('course','students'));
    }
    public function myCoursesRedirect(Request $r) {return match($r->user()->role){
        'student'=>redirect()->route('student.courses'),'instructor'=>redirect()->route('instructor.courses'),default=>abort(403)};}
    public function management(Request $r) {
        abort_unless($r->user()?->role==='manager',403);
        $people=DB::table('users')->selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total','role');
        $statuses=DB::table('course_offerings')->selectRaw('approval_status, COUNT(*) as total')->groupBy('approval_status')->pluck('total','approval_status');
        $pendingQueue=DB::table('course_offerings as o')
            ->join('users as u','u.user_id','=','o.instructor_id')
            ->join('courses as c','c.course_id','=','o.course_id')
            ->where('o.approval_status','pending')
            ->select('o.offering_id','o.title','o.created_at','u.name as instructor_name','c.course_name')
            ->orderBy('o.created_at')->limit(6)->get();
        return view('management.index',[
            'people'=>[
                'students'=>(int)($people['student']??0),
                'instructors'=>(int)($people['instructor']??0),
            ],
            'counts'=>[
                'pending'=>(int)($statuses['pending']??0),
                'changes_requested'=>(int)($statuses['changes_requested']??0),
                'approved'=>(int)($statuses['approved']??0),
                'rejected'=>(int)($statuses['rejected']??0),
            ],
            'pendingQueue'=>$pendingQueue,
        ]);
    }
    public function showStudentCourse(Request $r,string $offeringId) {
        $this->isStudent($r);
        $course=DB::table('course_offerings as o')->join('courses as c','c.course_id','=','o.course_id')
            ->join('users as u','u.user_id','=','o.instructor_id')
            ->where('o.offering_id',$offeringId)->where('o.approval_status','approved')
            ->select('o.*','c.course_name','u.name as instructor_name')->first();
        abort_unless($course,404);
        if($course->course_status==='ongoing'){
            abort_unless(DB::table('course_members')->where('user_id',$r->user()->user_id)->where('offering_id',$offeringId)->exists(),403);
        }
        // sections in the instructor's order, each with its content in order (access was checked above, unchanged)
        $sections=app(\App\Services\LessonService::class)->structure($offeringId);
        return view('student.course-content',compact('course','sections'));
    }
    public function unenroll(Request $r,string $offeringId) {
        $this->isStudent($r);
        $deleted=DB::table('course_members')->where('user_id',$r->user()->user_id)->where('offering_id',$offeringId)->delete();
        return redirect()->route('student.courses')->with($deleted?'success':'error',$deleted?'Enrollment cancelled.':'Not enrolled.');
    }
}

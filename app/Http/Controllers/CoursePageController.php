<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoursePageController extends Controller {
    private function isStudent(Request $r): void { abort_unless($r->user()?->role === 'student',403); }
    private function published() { return DB::table('course_offerings')->where('approval_status','approved')->where('is_active',1); }
    public function index(Request $r) {
        $college=$r->query('college');$topic=$r->query('topic');$status=$r->query('status');
        $colleges=DB::table('colleges')->orderBy('college_name')->get();
        $topics=DB::table('topics')->when($college,fn($q)=>$q->where('college_code',$college))->orderBy('topic_name')->get();
        $q=DB::table('course_offerings as o')->join('courses as c','c.course_id','=','o.course_id')
            ->join('departments as d','d.department_code','=','c.department_code')
            ->join('colleges as co','co.college_code','=','d.college_code')
            ->join('users as u','u.user_id','=','o.instructor_id')
            ->leftJoin('topics as t','t.topic_id','=','o.topic_id')
            ->where('o.approval_status','approved')->where('o.is_active',1);
        if($college)$q->where('co.college_code',$college);
        if($topic)$q->where('o.topic_id',$topic);
        if(in_array($status,['ongoing','completed'],true))$q->where('o.course_status',$status);
        $courses=$q->select('o.*','c.course_name','co.college_name','t.topic_name','u.name as instructor_name')
            ->orderByDesc('o.created_at')->get();
        $enrolledOfferingIds=collect();$savedOfferingIds=collect();
        if($r->user()?->role==='student'){
            $enrolledOfferingIds=DB::table('course_members')->where('user_id',$r->user()->user_id)->pluck('offering_id');
            $savedOfferingIds=DB::table('user_saved_courses')->where('user_id',$r->user()->user_id)->pluck('offering_id');
        }
        return view('courses.index',compact('courses','colleges','topics','college','topic','status','enrolledOfferingIds','savedOfferingIds'));
    }
    public function studentCourses(Request $r) {
        $this->isStudent($r);$id=$r->user()->user_id;
        $base=DB::table('course_offerings as o')->join('courses as c','c.course_id','=','o.course_id')
            ->join('users as u','u.user_id','=','o.instructor_id');
        $courses=(clone $base)->join('course_members as m','m.offering_id','=','o.offering_id')
            ->where('m.user_id',$id)->select('o.offering_id','o.title','o.description','o.is_active','o.course_status','c.course_name','u.name as instructor_name')
            ->orderByDesc('o.created_at')->get();
        $saved=(clone $base)->join('user_saved_courses as s','s.offering_id','=','o.offering_id')
            ->where('s.user_id',$id)->where('o.approval_status','approved')->where('o.is_active',1)->where('o.course_status','completed')
            ->whereNotIn('o.offering_id',$courses->pluck('offering_id')->all())
            ->select('o.offering_id','o.title','c.course_name','u.name as instructor_name')->get();
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
        return view('instructor.my-courses',compact('courses'));
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
        return view('management.index',['userCount'=>DB::table('users')->count(),'courseCount'=>DB::table('course_offerings')->count()]);
    }
    public function showStudentCourse(Request $r,string $offeringId) {
        $this->isStudent($r);
        $course=DB::table('course_offerings as o')->join('courses as c','c.course_id','=','o.course_id')
            ->join('users as u','u.user_id','=','o.instructor_id')
            ->where('o.offering_id',$offeringId)->where('o.approval_status','approved')->where('o.is_active',1)
            ->select('o.*','c.course_name','u.name as instructor_name')->first();
        abort_unless($course,404);
        if($course->course_status==='ongoing'){
            abort_unless(DB::table('course_members')->where('user_id',$r->user()->user_id)->where('offering_id',$offeringId)->exists(),403);
        }
        $lessons=DB::table('lessons')->where('offering_id',$offeringId)->orderBy('lesson_order')->orderBy('created_at')->get();
        return view('student.course-content',compact('course','lessons'));
    }
    public function unenroll(Request $r,string $offeringId) {
        $this->isStudent($r);
        $deleted=DB::table('course_members')->where('user_id',$r->user()->user_id)->where('offering_id',$offeringId)->delete();
        return redirect()->route('student.courses')->with($deleted?'success':'error',$deleted?'Enrollment cancelled.':'Not enrolled.');
    }
}

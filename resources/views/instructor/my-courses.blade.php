<!doctype html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ZU Hub | My Courses</title>
  <link rel="stylesheet" href="{{ asset('css/zu-my-courses.css') }}">
</head>
<body>
<header class="page-header"><div class="header-content">
  <a href="{{ route('home') }}" class="brand">⌂ ZU Hub</a>
  <div class="header-right">
    <div class="identity"><strong>{{ auth()->user()->name }}</strong> · <span data-role>{{ ucfirst(auth()->user()->role) }}</span></div>
    <button class="language" id="languageButton" type="button">العربية</button>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" type="submit" data-en="Log out" data-ar="تسجيل الخروج">Log out</button></form>
  </div>
</div></header>
<main class="container">
 <div class="heading"><div class="eyebrow" data-en="INSTRUCTOR WORKSPACE" data-ar="مساحة المدرس">INSTRUCTOR WORKSPACE</div>
 <h1 data-en="My Courses" data-ar="دوراتي">My Courses</h1><p data-en="Courses assigned to you. View enrolled students and prepare future lessons." data-ar="الدورات المسندة إليك. شاهد الطلاب المسجلين وجهّز المحاضرات القادمة.">Courses assigned to you. View enrolled students and prepare future lessons.</p></div>
 @if($courses->isEmpty())
   <section class="empty"><h2 data-en="No courses assigned yet" data-ar="لا توجد دورات مسندة إليك بعد">No courses assigned yet</h2><p data-en="When a course offering is assigned to your instructor account, it will appear here." data-ar="عند إسناد دورة إلى حسابك ستظهر هنا.">When a course offering is assigned to your instructor account, it will appear here.</p></section>
 @else
  <div class="cards">
   @foreach($courses as $course)
    <article class="course-card"><span class="chip">{{ $course->course_name }}</span><h2>{{ $course->title }}</h2>
     <p>{{ $course->description }}</p>
     <span class="small"><span data-en="Enrolled students" data-ar="الطلاب المسجلون">Enrolled students</span>: {{ $course->students_count }}</span>
     <div class="course-foot"><span class="chip {{ $course->is_active ? '' : 'inactive' }}" data-en="{{ $course->is_active ? 'Active' : 'Inactive' }}" data-ar="{{ $course->is_active ? 'نشطة' : 'غير نشطة' }}">{{ $course->is_active ? 'Active' : 'Inactive' }}</span>
     <a class="action" href="{{ route('instructor.students', $course->offering_id) }}" data-en="View Students" data-ar="عرض الطلاب">View Students</a></div>
    </article>
   @endforeach
  </div>
 @endif
</main>
<script src="{{ asset('js/zu-my-courses.js') }}" defer></script>
</body></html>

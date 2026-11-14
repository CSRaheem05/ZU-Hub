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
 <div class="heading"><div class="eyebrow" data-en="STUDENT LEARNING SPACE" data-ar="مساحة الطالب التعليمية">STUDENT LEARNING SPACE</div>
 <h1 data-en="My Courses" data-ar="دوراتي">My Courses</h1><p data-en="Courses you have joined. Return here to continue learning." data-ar="الدورات التي سجلت فيها. ارجع إلى هنا لمتابعة التعلم.">Courses you have joined. Return here to continue learning.</p></div>
 @if($courses->isEmpty())
   <section class="empty"><h2 data-en="No enrolled courses yet" data-ar="لم تسجل في أي دورة بعد">No enrolled courses yet</h2><p data-en="Once you enroll in a course, it will appear here." data-ar="عندما تسجل في دورة ستظهر هنا.">Once you enroll in a course, it will appear here.</p><a class="action" href="{{ route('courses.index') }}" data-en="Explore Courses" data-ar="استعراض الدورات">Explore Courses</a></section>
 @else
   <div class="cards">
   @foreach($courses as $course)
    <article class="course-card"><span class="chip">{{ $course->course_name }}</span><h2>{{ $course->title }}</h2>
     <p>{{ $course->description }}</p><div class="course-foot"><span class="small"><span data-en="Instructor" data-ar="المدرس">Instructor</span>: {{ $course->instructor_name }}</span>
     <span class="chip {{ $course->is_active ? '' : 'inactive' }}" data-en="{{ $course->is_active ? 'Active' : 'Inactive' }}" data-ar="{{ $course->is_active ? 'نشطة' : 'غير نشطة' }}">{{ $course->is_active ? 'Active' : 'Inactive' }}</span></div>
    </article>
   @endforeach
   </div>
 @endif
</main>
<script src="{{ asset('js/zu-my-courses.js') }}" defer></script>
</body></html>

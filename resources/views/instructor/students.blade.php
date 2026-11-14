<!doctype html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ZU Hub | Enrolled Students</title>
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
 <h1 data-en="Enrolled Students" data-ar="الطلاب المسجلون">Enrolled Students</h1><p>{{ $course->title }}</p></div>
 @if($students->isEmpty())
  <section class="empty"><h2 data-en="No students enrolled yet" data-ar="لا يوجد طلاب مسجلون بعد">No students enrolled yet</h2><p data-en="Students who join this course will be listed here." data-ar="سيظهر هنا الطلاب الذين يسجلون في هذه الدورة.">Students who join this course will be listed here.</p></section>
 @else
 <div class="student-table-wrap"><table><thead><tr><th data-en="Student name" data-ar="اسم الطالب">Student name</th><th data-en="University email" data-ar="البريد الجامعي">University email</th><th data-en="Student ID" data-ar="الرقم الجامعي">Student ID</th></tr></thead><tbody>
 @foreach($students as $student)<tr><td>{{ $student->name }}</td><td>{{ $student->email }}</td><td>{{ $student->user_id }}</td></tr>@endforeach
 </tbody></table></div>
 @endif
 <a class="back" href="{{ route('instructor.courses') }}" data-en="← Back to My Courses" data-ar="← العودة إلى دوراتي">← Back to My Courses</a>
</main>
<script src="{{ asset('js/zu-my-courses.js') }}" defer></script>
</body></html>

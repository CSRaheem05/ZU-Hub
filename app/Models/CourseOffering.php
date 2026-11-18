<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Minimal read model for the existing `course_offerings` table. */
class CourseOffering extends Model
{
    protected $table = 'course_offerings';
    protected $primaryKey = 'offering_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}

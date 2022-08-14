<?php

namespace App\Http\Controllers;

use App\Models\CourseTrackSchedule;
use App\Models\Day;
use App\Models\Diploma;
use App\Models\DiplomaTrack;
use App\Models\DiplomaTrackCost;
use App\Models\DiplomaTrackDay;
use App\Models\DiplomaTrackSchedule;
use App\Models\DiplomaTrackStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiplomaTrackController extends Controller
{

    /**
     * check schedule
     */
    public function  checkSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'diploma_id' => 'required|exists:diplomas,id',
            'lab_id' => 'required|exists:labs,id',
            'instructor_id' => 'required|exists:instructors,id',
            'days' => 'required|array',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'start_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors,422);
        }

        //check date equal day

        $tempData = date('l', strtotime($request->start_date));
        $day_title = Day::where('day',$tempData)->first();
        if ( $day_title->id != $request->days[0])
        {
            return response()->json("This day is not equal date",422);
        }

        $count_days_available = (count($request->days));

        $diploma = Diploma::find($request->diploma_id);

        $course_hours = ceil($diploma->course_hours);

        $start_time = strtotime($request->start_time );
        $end_time = strtotime($request->end_time);
        $totalSecondsDiff = abs($start_time-$end_time);
        $totalHoursDiff   = $totalSecondsDiff/60/60;
        $totalHoursInDay = ceil($totalHoursDiff);

        $count_of_day = $course_hours / $totalHoursInDay;

        $number_of_weeks = $count_of_day / $count_days_available;

        $end_date = $request->start_date;
        $old_day = 0;
        $new_day = 0;

        //check course schedule

        for ($i = 0 ; $i < ceil($number_of_weeks) ; $i++)
        {
            if($i == 0)
            {
                foreach($request->days as $index=>$day)
                {
                    if ($index == 0)
                    {
                        $old_day = $day;
                        $course_track_schedules = CourseTrackSchedule::where([
                            ['instructor_id',$request->instructor_id],
                            ['day_id',$old_day],
                            ['date',$request->start_date]
                        ])->orWhere([
                            ['lab_id',$request->lab_id],
                            ['day_id',$old_day],
                            ['date',$request->start_date]
                        ])->get();

                        if (count($course_track_schedules)  > 0)
                        {
                            foreach ($course_track_schedules as $course_track_schedule)
                            {
                                if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }

                                if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }
                            }

                        }
                    }else{
                        $da = $day - $old_day ;
                        $new_day = $day;

                        if ($da <= 0)
                        {
                            $old_day = $da + 7;
                        }else{
                            $old_day = $da ;
                        }
                        $end_date = date('Y-m-d', strtotime($end_date. ' + '.$old_day.' days'));

                        $course_track_schedules = CourseTrackSchedule::where([
                            ['instructor_id',$request->instructor_id],
                            ['day_id',$day],
                            ['date',$end_date]
                        ])->orWhere([
                            ['lab_id',$request->lab_id],
                            ['day_id',$day],
                            ['date',$end_date]
                        ])->get();

                        if (count($course_track_schedules)  > 0)
                        {
                            foreach ($course_track_schedules as $course_track_schedule)
                            {
                                if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }

                                if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }
                            }

                        }
                    }
                }
            }else{

                foreach($request->days as $index=>$day)
                {
                    $da = $day -  $new_day ;
                    $new_day = $day;

                    if ($da <= 0)
                    {
                        $old_day = $da + 7;
                    }else{
                        $old_day = $da ;
                    }
                    $end_date = date('Y-m-d', strtotime($end_date. ' + '.$old_day.' days'));

                    $course_track_schedules = CourseTrackSchedule::where([
                        ['instructor_id',$request->instructor_id],
                        ['day_id',$day],
                        ['date',$end_date]
                    ])->orWhere([
                        ['lab_id',$request->lab_id],
                        ['day_id',$day],
                        ['date',$end_date]
                    ])->get();

                    if (count($course_track_schedules)  > 0)
                    {
                        foreach ($course_track_schedules as $course_track_schedule)
                        {
                            if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                            {
                                return response()->json("This time is reserved",422);
                            }

                            if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                            {
                                return response()->json("This time is reserved",422);
                            }
                        }

                    }
                }
            }
        }

        //check diploma schedule
        $check_end_date = $request->start_date;
        $check_old_day = 0;
        $check_new_day = 0;
        for ($i = 0 ; $i < ceil($number_of_weeks) ; $i++)
        {
            if($i == 0)
            {
                foreach($request->days as $index=>$day)
                {
                    if ($index == 0)
                    {
                        $check_old_day = $day;
                        $course_track_schedules = DiplomaTrackSchedule::where([
                            ['instructor_id',$request->instructor_id],
                            ['day_id',$check_old_day],
                            ['date',$request->start_date]
                        ])->orWhere([
                            ['lab_id',$request->lab_id],
                            ['day_id',$check_old_day],
                            ['date',$request->start_date]
                        ])->get();

                        if (count($course_track_schedules)  > 0)
                        {
                            foreach ($course_track_schedules as $course_track_schedule)
                            {
                                if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }

                                if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }
                            }

                        }
                    }else{
                        $da = $day - $check_old_day ;
                        $check_new_day = $day;

                        if ($da <= 0)
                        {
                            $check_old_day = $da + 7;
                        }else{
                            $check_old_day = $da ;
                        }
                        $check_end_date = date('Y-m-d', strtotime($check_end_date. ' + '.$check_old_day.' days'));

                        $course_track_schedules = DiplomaTrackSchedule::where([
                            ['instructor_id',$request->instructor_id],
                            ['day_id',$day],
                            ['date',$check_end_date]
                        ])->orWhere([
                            ['lab_id',$request->lab_id],
                            ['day_id',$day],
                            ['date',$check_end_date]
                        ])->get();

                        if (count($course_track_schedules)  > 0)
                        {
                            foreach ($course_track_schedules as $course_track_schedule)
                            {
                                if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }

                                if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }
                            }

                        }
                    }
                }
            }else{

                foreach($request->days as $index=>$day)
                {
                    $da = $day -  $check_new_day ;
                    $check_new_day = $day;

                    if ($da <= 0)
                    {
                        $check_old_day = $da + 7;
                    }else{
                        $check_old_day = $da ;
                    }
                    $check_end_date = date('Y-m-d', strtotime($check_end_date. ' + '.$check_old_day.' days'));

                    $course_track_schedules = DiplomaTrackSchedule::where([
                        ['instructor_id',$request->instructor_id],
                        ['day_id',$day],
                        ['date',$check_end_date]
                    ])->orWhere([
                        ['lab_id',$request->lab_id],
                        ['day_id',$day],
                        ['date',$check_end_date]
                    ])->get();

                    if (count($course_track_schedules)  > 0)
                    {
                        foreach ($course_track_schedules as $course_track_schedule)
                        {
                            if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                            {
                                return response()->json("This time is reserved",422);
                            }

                            if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                            {
                                return response()->json("This time is reserved",422);
                            }
                        }

                    }
                }
            }
        }

        $data['start_date'] = $request->start_date;
        $data['diploma_name'] = $diploma->name;
        $data['end_date'] = $end_date;

        return response()->json($data);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $diploma_tracks = DiplomaTrack::all();

        foreach ($diploma_tracks as $diploma_track)
        {
            $diploma_track->tab_index = 0;
            $diploma_track->discount_percent = 0;

            if ($diploma_track->start_date > now()){

                $diploma_track->tab_index = 1;
            }
            if ($diploma_track->start_date <= now())
            {
                $diploma_track->tab_index = 2;
            }
            if ($diploma_track->end_date < now())
            {
                $diploma_track->tab_index = 3;
            }
            if ($diploma_track->cancel == 1)
            {
                $diploma_track->tab_index = 4;
            }

            $diploma_track->lab;
            $diploma_track->diploma;
            $diploma_track->instructor;
            $diploma_track->category;
            $diploma_track->vendor;
            $diploma_track->diplomaTrackCost;
            $diploma_track->diplomaTrackDay;
            $days = [];

            foreach ( $diploma_track->diplomaTrackDay as $day)
            {
                $days[] = $day->day_id;
            }
            $diploma_track->days = $days;
            $diploma_track->diplomaTrackSchedule;
            $diploma_track->publicDiscountDiploma;
            foreach ($diploma_track->publicDiscountDiploma as $discount)
            {
                if ($discount->to_date >= now() && $discount->from_date <= now())
                {
                    $diploma_track->price_after_discount = $discount->price_after_discount;
                    $diploma_track->discount_percent = $discount->discount_percent;
                }else{
                    $diploma_track->price_after_discount = $diploma_track->total_cost;
                }
            }
            foreach ($diploma_track->diplomaTrackSchedule as $schedule)
            {
                $schedule->day;
            }
        }

        return response()->json($diploma_tracks);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'diploma_id' => 'required|exists:diplomas,id',
            'lab_id' => 'required|exists:labs,id',
            'instructor_id' => 'required|exists:instructors,id',
            'category_id' => 'required|exists:categories,id',
            'vendor_id' => 'required|exists:vendors,id',
            'days' => 'required|array',
            'instructor_hour_cost' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'start_date' => 'required|date',
            'registration_last_date' => 'required|date',
            'trainees_allowed_count' => 'required|integer',
            'minimum_students_notification' => 'required|integer',
            'total_cost' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'price' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'certificate_price' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'lab_cost' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'material_cost' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'assignment_cost' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'placement_cost' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'exam_cost' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'application' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'interview' => 'required|regex:/^\d+(\.\d{1,2})?$/',

            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors,422);
        }

        //check date equal day

        $tempData = date('l', strtotime($request->start_date));
        $day_title = Day::where('day',$tempData)->first();
        if ( $day_title->id != $request->days[0])
        {
            return response()->json("This day is not equal date",422);
        }

        $count_days_available = (count($request->days));


        $diploma = Diploma::find($request->diploma_id);


        $course_hours = ceil($diploma->course_hours);


        $start_time = strtotime($request->start_time );
        $end_time = strtotime($request->end_time);
        $totalSecondsDiff = abs($start_time-$end_time);
        $totalHoursDiff   = $totalSecondsDiff/60/60;
        $totalHoursInDay = ceil($totalHoursDiff);


        $count_of_day = $course_hours / $totalHoursInDay;

        $number_of_weeks = $count_of_day / $count_days_available;

        $end_date = $request->start_date;
        $old_day = 0;
        $new_day = 0;

        //check day and time and lab and instructor in course scedual

        for ($i = 0 ; $i < ceil($number_of_weeks) ; $i++)
        {
            if($i == 0)
            {
                foreach($request->days as $index=>$day)
                {
                    if ($index == 0)
                    {
                        $old_day = $day;
                        $course_track_schedules = CourseTrackSchedule::where([
                            ['instructor_id',$request->instructor_id],
                            ['day_id',$old_day],
                            ['date',$request->start_date]
                        ])->orWhere([
                            ['lab_id',$request->lab_id],
                            ['day_id',$old_day],
                            ['date',$request->start_date]
                        ])->get();

                        if (count($course_track_schedules)  > 0)
                        {
                            foreach ($course_track_schedules as $course_track_schedule)
                            {
                                if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }

                                if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }
                            }

                        }
                    }else{
                        $da = $day - $old_day ;
                        $new_day = $day;

                        if ($da <= 0)
                        {
                            $old_day = $da + 7;
                        }else{
                            $old_day = $da ;
                        }
                        $end_date = date('Y-m-d', strtotime($end_date. ' + '.$old_day.' days'));

                        $course_track_schedules = CourseTrackSchedule::where([
                            ['instructor_id',$request->instructor_id],
                            ['day_id',$day],
                            ['date',$end_date]
                        ])->orWhere([
                            ['lab_id',$request->lab_id],
                            ['day_id',$day],
                            ['date',$end_date]
                        ])->get();

                        if (count($course_track_schedules)  > 0)
                        {
                            foreach ($course_track_schedules as $course_track_schedule)
                            {
                                if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }

                                if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }
                            }

                        }
                    }
                }
            }else{

                foreach($request->days as $index=>$day)
                {
                    $da = $day -  $new_day ;
                    $new_day = $day;

                    if ($da <= 0)
                    {
                        $old_day = $da + 7;
                    }else{
                        $old_day = $da ;
                    }
                    $end_date = date('Y-m-d', strtotime($end_date. ' + '.$old_day.' days'));

                    $course_track_schedules = CourseTrackSchedule::where([
                        ['instructor_id',$request->instructor_id],
                        ['day_id',$day],
                        ['date',$end_date]
                    ])->orWhere([
                        ['lab_id',$request->lab_id],
                        ['day_id',$day],
                        ['date',$end_date]
                    ])->get();

                    if (count($course_track_schedules)  > 0)
                    {
                        foreach ($course_track_schedules as $course_track_schedule)
                        {
                            if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                            {
                                return response()->json("This time is reserved",422);
                            }

                            if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                            {
                                return response()->json("This time is reserved",422);
                            }
                        }

                    }
                }
            }
        }

        //check diploma schedule
        $check_end_date = $request->start_date;
        $check_old_day = 0;
        $check_new_day = 0;
        for ($i = 0 ; $i < ceil($number_of_weeks) ; $i++)
        {
            if($i == 0)
            {
                foreach($request->days as $index=>$day)
                {
                    if ($index == 0)
                    {
                        $check_old_day = $day;
                        $course_track_schedules = DiplomaTrackSchedule::where([
                            ['instructor_id',$request->instructor_id],
                            ['day_id',$check_old_day],
                            ['date',$request->start_date]
                        ])->orWhere([
                            ['lab_id',$request->lab_id],
                            ['day_id',$check_old_day],
                            ['date',$request->start_date]
                        ])->get();

                        if (count($course_track_schedules)  > 0)
                        {
                            foreach ($course_track_schedules as $course_track_schedule)
                            {
                                if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }

                                if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }
                            }

                        }
                    }else{
                        $da = $day - $check_old_day ;
                        $check_new_day = $day;

                        if ($da <= 0)
                        {
                            $check_old_day = $da + 7;
                        }else{
                            $check_old_day = $da ;
                        }
                        $check_end_date = date('Y-m-d', strtotime($check_end_date. ' + '.$check_old_day.' days'));

                        $course_track_schedules = DiplomaTrackSchedule::where([
                            ['instructor_id',$request->instructor_id],
                            ['day_id',$day],
                            ['date',$check_end_date]
                        ])->orWhere([
                            ['lab_id',$request->lab_id],
                            ['day_id',$day],
                            ['date',$check_end_date]
                        ])->get();

                        if (count($course_track_schedules)  > 0)
                        {
                            foreach ($course_track_schedules as $course_track_schedule)
                            {
                                if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }

                                if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }
                            }

                        }
                    }
                }
            }else{

                foreach($request->days as $index=>$day)
                {
                    $da = $day -  $check_new_day ;
                    $check_new_day = $day;

                    if ($da <= 0)
                    {
                        $check_old_day = $da + 7;
                    }else{
                        $check_old_day = $da ;
                    }
                    $check_end_date = date('Y-m-d', strtotime($check_end_date. ' + '.$check_old_day.' days'));

                    $course_track_schedules = DiplomaTrackSchedule::where([
                        ['instructor_id',$request->instructor_id],
                        ['day_id',$day],
                        ['date',$check_end_date]
                    ])->orWhere([
                        ['lab_id',$request->lab_id],
                        ['day_id',$day],
                        ['date',$check_end_date]
                    ])->get();

                    if (count($course_track_schedules)  > 0)
                    {
                        foreach ($course_track_schedules as $course_track_schedule)
                        {
                            if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                            {
                                return response()->json("This time is reserved",422);
                            }

                            if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                            {
                                return response()->json("This time is reserved",422);
                            }
                        }

                    }
                }
            }
        }

        // start create

        $course_track = DiplomaTrack::create([
            'lab_id' => $request->lab_id,
            'diploma_id' => $request->diploma_id,
            'instructor_id' => $request->instructor_id,
            'category_id' => $request->category_id,
            'vendor_id' => $request->vendor_id,
            'instructor_hour_cost' => $request->instructor_hour_cost,
            'start_date' => $request->start_date,
            'registration_last_date' => $request->registration_last_date,
            'trainees_allowed_count' => $request->trainees_allowed_count,
            'minimum_students_notification' => $request->minimum_students_notification,
            'total_cost' => $request->total_cost,
        ]);

        $course_track_cost = DiplomaTrackCost::create([
            'diploma_track_id' => $course_track->id,
            'price' => $request->price,
            'certificate_price' => $request->certificate_price,
            'lab_cost' => $request->lab_cost,
            'material_cost' => $request->material_cost,
            'assignment_cost' => $request->assignment_cost,
            'placement_cost' => $request->placement_cost,
            'exam_cost' => $request->exam_cost,
            'interview' => $request->interview,
            'application' => $request->application,
        ]);

        foreach ($request->days as $course_day)
        {
            $day_title = Day::findOrFail($course_day);
            $course_track_day = DiplomaTrackDay::create([
                'day' => $day_title->day,
                'diploma_track_id' => $course_track->id,
                'day_id' => $day_title->id,
            ]);
        }

        $length = 0;

        for ($i = 0 ; $i < ceil($number_of_weeks) ; $i++)
        {
            if($i == 0)
            {
                foreach($request->days as $index=>$day)
                {
                    if ($index == 0)
                    {
                        DiplomaTrackSchedule::create([
                            'diploma_track_id' => $course_track->id,
                            'lab_id' => $request->lab_id,
                            'diploma_id' => $request->diploma_id,
                            'course_id' => $diploma['courses'][$length]->id,
                            'instructor_id' => $request->instructor_id,
                            'day_id' => $day,
                            'start_time' => $request->start_time,
                            'end_time' => $request->end_time,
                            'date' => $request->start_date,
                        ]);

                    }else{
                        $course_track_schedule = DiplomaTrackSchedule::where([
                            ['lab_id',$request->lab_id],
                            ['diploma_id',$request->diploma_id],
                            ['instructor_id',$request->instructor_id],
                        ])->get()->last();
                        $day_plus = $day - $course_track_schedule->day_id ;

                        if ($day_plus <= 0)
                        {
                            $day_plus = $day_plus + 7;
                        }

                        $date = date('Y-m-d', strtotime($course_track_schedule->date. ' + '.$day_plus.' days'));
                        $course_track_schedule = DiplomaTrackSchedule::create([
                            'diploma_track_id' => $course_track->id,
                            'lab_id' => $request->lab_id,
                            'diploma_id' => $request->diploma_id,
                            'course_id' => $diploma['courses'][$length]->id,
                            'instructor_id' => $request->instructor_id,
                            'day_id' => $day,
                            'start_time' => $request->start_time,
                            'end_time' => $request->end_time,
                            'date' => $date,
                        ]);
                    }
                }
            }else{
                foreach($request->days as $index=>$day)
                {
                    $count_diploma_track_schedule = DiplomaTrackSchedule::where([
                        ['diploma_track_id', $course_track->id],
                    ])->get()->count();
                    if ($course_hours > $count_diploma_track_schedule * $totalHoursInDay) {
                        //check course time

                        $schedules = DiplomaTrackSchedule::where([
                            ['diploma_track_id', $course_track->id],
                            ['course_id', $diploma['courses'][$length]->id],
                        ])->get();

                        $remainingtime = 0;

                        foreach ($schedules as $schedule) {
                            $start_time = strtotime($schedule->start_time);
                            $end_time = strtotime($schedule->end_time);
                            $totalSecondsDiff = abs($start_time - $end_time);
                            $totalHoursDiff = $totalSecondsDiff / 60 / 60;
                            $totalHoursInDay = ceil($totalHoursDiff);
                            $remainingtime += $totalHoursInDay;
                        }

                        if ($remainingtime == $diploma['courses'][$length]->hour_count) {

                            if (count($diploma['courses']) - 1 == $length) {
                                $old = $length;
                                $length = $old;
                            } else {
                                $old = $length;
                                $length = $old + 1;
                            }
                        }

                        $course_track_schedule = DiplomaTrackSchedule::where([
                            ['lab_id', $request->lab_id],
                            ['diploma_id', $request->diploma_id],
                            ['instructor_id', $request->instructor_id],
                        ])->get()->last();

                        $day_plus = $day - $course_track_schedule->day_id;
                        if ($day_plus <= 0) {
                            $day_plu = $day_plus + 7;
                        } else {
                            $day_plu = $day_plus;
                        }
                        $date = date('Y-m-d', strtotime($course_track_schedule->date . ' + ' . $day_plu . ' days'));
                        $course_track_schedule = DiplomaTrackSchedule::create([
                            'diploma_track_id' => $course_track->id,
                            'lab_id' => $request->lab_id,
                            'diploma_id' => $request->diploma_id,
                            'course_id' => $diploma['courses'][$length]->id,
                            'instructor_id' => $request->instructor_id,
                            'day_id' => $day,
                            'start_time' => $request->start_time,
                            'end_time' => $request->end_time,
                            'date' => $date,
                        ]);
                    }
                }
            }

        }

        $course_track_schedule = DiplomaTrackSchedule::where('diploma_track_id',$course_track->id)->get()->last();

        $course_track->update([
            'end_date' => $course_track_schedule->date
        ]);

        return response()->json($course_track);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $diploma_track = DiplomaTrack::with(['lab','diploma','instructor','category','vendor','diplomaTrackCost','diplomaTrackSchedule','diplomaTrackDay','publicDiscountDiploma'])->findOrFail($id);

        return response()->json($diploma_track);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'lab_id' => 'required|exists:labs,id',
            'instructor_id' => 'required|exists:instructors,id',
            'days' => 'required|array',
            'start_date' => 'required|date',

            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors, 422);
        }

        //check date equal day

        $tempData = date('l', strtotime($request->start_date));
        $day_title = Day::where('day', $tempData)->first();
        if ($day_title->id != $request->days[0]) {
            return response()->json("This day is not equal date", 422);
        }

        $count_days_available = (count($request->days));

        $courseTrack = DiplomaTrack::find($id);
        $course = Diploma::find($courseTrack->diploma_id);
        $course_hours = ceil($course->course_hours);

        $start_time = strtotime($request->start_time);
        $end_time = strtotime($request->end_time);
        $totalSecondsDiff = abs($start_time - $end_time);
        $totalHoursDiff = $totalSecondsDiff / 60 / 60;
        $totalHoursInDay = ceil($totalHoursDiff);

        $count_of_day = $course_hours / $totalHoursInDay;

        $number_of_weeks = $count_of_day / $count_days_available;

        $end_date = $request->start_date;
        $old_day = 0;
        $new_day = 0;

        //check day and time and lab and instructor

        for ($i = 0; $i < ceil($number_of_weeks); $i++) {
            if ($i == 0) {
                foreach ($request->days as $index => $day) {
                    if ($index == 0) {
                        $old_day = $day;
                        $course_track_schedules = CourseTrackSchedule::where([
                            ['instructor_id', $request->instructor_id],
                            ['day_id', $old_day],
                            ['date', $request->start_date],
                            ['course_track_id', '!=', $id],
                        ])->orWhere([
                            ['lab_id', $request->lab_id],
                            ['day_id', $old_day],
                            ['date', $request->start_date],
                            ['course_track_id', '!=', $id],
                        ])->get();

                        if (count($course_track_schedules) > 0) {
                            foreach ($course_track_schedules as $course_track_schedule) {
                                if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time) {
                                    return response()->json("This time is reserved", 422);
                                }

                                if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time) {
                                    return response()->json("This time is reserved", 422);
                                }
                            }

                        }
                    } else {
                        $da = $day - $old_day;
                        $new_day = $day;

                        if ($da <= 0) {
                            $old_day = $da + 7;
                        } else {
                            $old_day = $da;
                        }
                        $end_date = date('Y-m-d', strtotime($end_date . ' + ' . $old_day . ' days'));

                        $course_track_schedules = CourseTrackSchedule::where([
                            ['instructor_id', $request->instructor_id],
                            ['day_id', $day],
                            ['date', $end_date],
                            ['course_track_id', '!=', $id],
                        ])->orWhere([
                            ['lab_id', $request->lab_id],
                            ['day_id', $day],
                            ['date', $end_date],
                            ['course_track_id', '!=', $id],
                        ])->get();

                        if (count($course_track_schedules) > 0) {
                            foreach ($course_track_schedules as $course_track_schedule) {
                                if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time) {
                                    return response()->json("This time is reserved", 422);
                                }

                                if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time) {
                                    return response()->json("This time is reserved", 422);
                                }
                            }
                        }
                    }
                }
            } else {

                foreach ($request->days as $index => $day) {
                    $da = $day - $new_day;
                    $new_day = $day;

                    if ($da <= 0) {
                        $old_day = $da + 7;
                    } else {
                        $old_day = $da;
                    }
                    $end_date = date('Y-m-d', strtotime($end_date . ' + ' . $old_day . ' days'));

                    $course_track_schedules = CourseTrackSchedule::where([
                        ['instructor_id', $request->instructor_id],
                        ['day_id', $day],
                        ['date', $end_date],
                        ['course_track_id', '!=', $id],
                    ])->orWhere([
                        ['lab_id', $request->lab_id],
                        ['day_id', $day],
                        ['date', $end_date],
                        ['course_track_id', '!=', $id],
                    ])->get();

                    if (count($course_track_schedules) > 0) {
                        foreach ($course_track_schedules as $course_track_schedule) {
                            if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time) {
                                return response()->json("This time is reserved", 422);
                            }

                            if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time) {
                                return response()->json("This time is reserved", 422);
                            }
                        }

                    }
                }
            }
        }

        //check diploma schedule
        $check_end_date = $request->start_date;
        $check_old_day = 0;
        $check_new_day = 0;
        for ($i = 0 ; $i < ceil($number_of_weeks) ; $i++)
        {
            if($i == 0)
            {
                foreach($request->days as $index=>$day)
                {
                    if ($index == 0)
                    {
                        $check_old_day = $day;
                        $course_track_schedules = DiplomaTrackSchedule::where([
                            ['instructor_id',$request->instructor_id],
                            ['day_id',$check_old_day],
                            ['date',$request->start_date],
                            ['diploma_track_id',"!=",$id]
                        ])->orWhere([
                            ['lab_id',$request->lab_id],
                            ['day_id',$check_old_day],
                            ['date',$request->start_date],
                            ['diploma_track_id',"!=",$id]
                        ])->get();

                        if (count($course_track_schedules)  > 0)
                        {
                            foreach ($course_track_schedules as $course_track_schedule)
                            {
                                if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }

                                if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }
                            }

                        }
                    }else{
                        $da = $day - $check_old_day ;
                        $check_new_day = $day;

                        if ($da <= 0)
                        {
                            $check_old_day = $da + 7;
                        }else{
                            $check_old_day = $da ;
                        }
                        $check_end_date = date('Y-m-d', strtotime($check_end_date. ' + '.$check_old_day.' days'));

                        $course_track_schedules = DiplomaTrackSchedule::where([
                            ['instructor_id',$request->instructor_id],
                            ['day_id',$day],
                            ['date',$check_end_date],
                            ['diploma_track_id',"!=",$id]
                        ])->orWhere([
                            ['lab_id',$request->lab_id],
                            ['day_id',$day],
                            ['date',$check_end_date],
                            ['diploma_track_id',"!=",$id]
                        ])->get();

                        if (count($course_track_schedules)  > 0)
                        {
                            foreach ($course_track_schedules as $course_track_schedule)
                            {
                                if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }

                                if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }
                            }

                        }
                    }
                }
            }else{

                foreach($request->days as $index=>$day)
                {
                    $da = $day -  $check_new_day ;
                    $check_new_day = $day;

                    if ($da <= 0)
                    {
                        $check_old_day = $da + 7;
                    }else{
                        $check_old_day = $da ;
                    }
                    $check_end_date = date('Y-m-d', strtotime($check_end_date. ' + '.$check_old_day.' days'));

                    $course_track_schedules = DiplomaTrackSchedule::where([
                        ['instructor_id',$request->instructor_id],
                        ['day_id',$day],
                        ['date',$check_end_date],
                        ['diploma_track_id',"!=",$id]
                    ])->orWhere([
                        ['lab_id',$request->lab_id],
                        ['day_id',$day],
                        ['date',$check_end_date],
                        ['diploma_track_id',"!=",$id]
                    ])->get();

                    if (count($course_track_schedules)  > 0)
                    {
                        foreach ($course_track_schedules as $course_track_schedule)
                        {
                            if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                            {
                                return response()->json("This time is reserved",422);
                            }

                            if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                            {
                                return response()->json("This time is reserved",422);
                            }
                        }

                    }
                }
            }
        }

        // start create

        $courseTrack->update([
            'lab_id' => $request->lab_id,
            'instructor_id' => $request->instructor_id,
            'start_date' => $request->start_date,
        ]);

        $course_track_days = DiplomaTrackDay::where('diploma_track_id', $id)->get();

        foreach ($course_track_days as $course_track_day) {
            $course_track_day->delete();
        }

        foreach ($request->days as $course_day) {
            $day_title = Day::findOrFail($course_day);
            $course_track_day = DiplomaTrackDay::create([
                'day' => $day_title->day,
                'diploma_track_id' => $id,
                'day_id' => $day_title->id,
            ]);
        }

        $CourseTrackSchedules = DiplomaTrackSchedule::where('diploma_track_id', $id)->get();

        foreach ($CourseTrackSchedules as $CourseTrackSchedule)
        {
            $CourseTrackSchedule->delete();
        }

        for ($i = 0 ; $i < ceil($number_of_weeks) ; $i++)
        {
            if($i == 0)
            {
                foreach($request->days as $index=>$day)
                {
                    if ($index == 0)
                    {
                        $dd=DiplomaTrackSchedule::create([
                            'diploma_track_id' => $id,
                            'lab_id' => $request->lab_id,
                            'diploma_id' => $courseTrack->diploma_id,
                            'instructor_id' => $request->instructor_id,
                            'day_id' => $day,
                            'start_time' => $request->start_time,
                            'end_time' => $request->end_time,
                            'date' => $request->start_date,
                        ]);
                    }else{
                        $course_track_schedule = DiplomaTrackSchedule::where([
                            ['lab_id',$request->lab_id],
                            ['diploma_id',$courseTrack->diploma_id],
                            ['instructor_id',$request->instructor_id],
                        ])->get()->last();


                        $day_plus = $day - $course_track_schedule->day_id ;

                        if ($day_plus <= 0)
                        {
                            $day_plus = $day_plus + 7;
                        }

                        $date = date('Y-m-d', strtotime($course_track_schedule->date. ' + '.$day_plus.' days'));
                        $course_track_schedule = DiplomaTrackSchedule::create([
                            'diploma_track_id' => $id,
                            'lab_id' => $request->lab_id,
                            'diploma_id' => $courseTrack->diploma_id,
                            'instructor_id' => $request->instructor_id,
                            'day_id' => $day,
                            'start_time' => $request->start_time,
                            'end_time' => $request->end_time,
                            'date' => $date,
                        ]);
                    }
                }
            }else{
                foreach($request->days as $day)
                {
                    $count_diploma_track_schedule = DiplomaTrackSchedule::where([
                        ['diploma_track_id', $id],
                    ])->get()->count();
                    if ($course_hours > $count_diploma_track_schedule * $totalHoursInDay) {
                        $course_track_schedule = DiplomaTrackSchedule::where([
                            ['lab_id', $request->lab_id],
                            ['diploma_id', $courseTrack->diploma_id],
                            ['instructor_id', $request->instructor_id],
                        ])->get()->last();

                        $day_plus = $day - $course_track_schedule->day_id;
                        if ($day_plus <= 0) {
                            $day_plu = $day_plus + 7;
                        } else {
                            $day_plu = $day_plus;
                        }
                        $date = date('Y-m-d', strtotime($course_track_schedule->date . ' + ' . $day_plu . ' days'));
                        $course_track_schedule = DiplomaTrackSchedule::create([
                            'diploma_track_id' => $id,
                            'lab_id' => $request->lab_id,
                            'diploma_id' => $courseTrack->diploma_id,
                            'instructor_id' => $request->instructor_id,
                            'day_id' => $day,
                            'start_time' => $request->start_time,
                            'end_time' => $request->end_time,
                            'date' => $date,
                        ]);
                    }
                }
            }

        }

        $course_track_schedule = DiplomaTrackSchedule::where('diploma_track_id',$id)->get()->last();
        $courseTrack->update([
            'end_date' => $course_track_schedule->date
        ]);

        return response()->json($courseTrack);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $diploma_track = DiplomaTrack::findOrFail($id);
        $diploma_track->delete();

        return response()->json("deleted successfully");
    }

    /**
     * cancel diploma track
     */

    public function cancel($id)
    {
        $diploma_track = DiplomaTrack::findOrFail($id);
        $diploma_track->update([
            'cancel' => 1
        ]);

        return response()->json("cancelled successfully");
    }

    /**
     * check diploma track Schedule By diploma track id to update diploma track Schedule
     */

    public function  checkDiplomaTrackSchedule(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'lab_id' => 'required|exists:labs,id',
            'instructor_id' => 'required|exists:instructors,id',
            'days' => 'required|array',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'start_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors,422);
        }

        //check date equal day

        $tempData = date('l', strtotime($request->start_date));
        $day_title = Day::where('day',$tempData)->first();
        if ( $day_title->id != $request->days[0])
        {
            return response()->json("This day is not equal date",422);
        }

        $count_days_available = (count($request->days));

        $course_track= DiplomaTrack::find($id);
        $course = Diploma::find($course_track->diploma_id);
        $course_hours = ceil($course->course_hours);

        $start_time = strtotime($request->start_time );
        $end_time = strtotime($request->end_time);
        $totalSecondsDiff = abs($start_time-$end_time);
        $totalHoursDiff   = $totalSecondsDiff/60/60;
        $totalHoursInDay = ceil($totalHoursDiff);

        $count_of_day = $course_hours / $totalHoursInDay;

        $number_of_weeks = $count_of_day / $count_days_available;

        $end_date = $request->start_date;
        $old_day = 0;
        $new_day = 0;

        for ($i = 0 ; $i < ceil($number_of_weeks) ; $i++)
        {
            if($i == 0)
            {
                foreach($request->days as $index=>$day)
                {
                    if ($index == 0)
                    {
                        $old_day = $day;
                        $course_track_schedules = DiplomaTrackSchedule::where([
                            ['instructor_id',$request->instructor_id],
                            ['day_id',$old_day],
                            ['date',$request->start_date],
                            ['diploma_track_id','!=',$id],
                        ])->orWhere([
                            ['lab_id',$request->lab_id],
                            ['day_id',$old_day],
                            ['date',$request->start_date],
                            ['diploma_track_id','!=',$id],
                        ])->get();

                        if (count($course_track_schedules)  > 0)
                        {
                            foreach ($course_track_schedules as $course_track_schedule)
                            {
                                if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }

                                if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }
                            }

                        }
                    }else{
                        $da = $day - $old_day ;
                        $new_day = $day;

                        if ($da <= 0)
                        {
                            $old_day = $da + 7;
                        }else{
                            $old_day = $da ;
                        }
                        $end_date = date('Y-m-d', strtotime($end_date. ' + '.$old_day.' days'));

                        $course_track_schedules = DiplomaTrackSchedule::where([
                            ['instructor_id',$request->instructor_id],
                            ['day_id',$day],
                            ['date',$end_date],
                            ['diploma_track_id','!=',$id],
                        ])->orWhere([
                            ['lab_id',$request->lab_id],
                            ['day_id',$day],
                            ['date',$end_date],
                            ['diploma_track_id','!=',$id],
                        ])->get();

                        if (count($course_track_schedules)  > 0)
                        {
                            foreach ($course_track_schedules as $course_track_schedule)
                            {
                                if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }

                                if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                                {
                                    return response()->json("This time is reserved",422);
                                }
                            }
                        }
                    }
                }
            }else{

                foreach($request->days as $index=>$day)
                {
                    $da = $day -  $new_day ;
                    $new_day = $day;

                    if ($da <= 0)
                    {
                        $old_day = $da + 7;
                    }else{
                        $old_day = $da ;
                    }
                    $end_date = date('Y-m-d', strtotime($end_date. ' + '.$old_day.' days'));

                    $course_track_schedules = DiplomaTrackSchedule::where([
                        ['instructor_id',$request->instructor_id],
                        ['day_id',$day],
                        ['date',$end_date],
                        ['diploma_track_id','!=',$id],
                    ])->orWhere([
                        ['lab_id',$request->lab_id],
                        ['day_id',$day],
                        ['date',$end_date],
                        ['diploma_track_id','!=',$id],
                    ])->get();

                    if (count($course_track_schedules)  > 0)
                    {
                        foreach ($course_track_schedules as $course_track_schedule)
                        {
                            if ($course_track_schedule->start_time <= $request->end_time && $course_track_schedule->end_time >= $request->end_time)
                            {
                                return response()->json("This time is reserved",422);
                            }

                            if ($course_track_schedule->start_time <= $request->start_time && $course_track_schedule->end_time > $request->start_time)
                            {
                                return response()->json("This time is reserved",422);
                            }
                        }

                    }
                }
            }
        }

        $data['start_date'] = $request->start_date;
        $data['diploma_name'] = $course->name;
        $data['end_date'] = $end_date;

        return response()->json($data);
    }

    /**
     * chang diploma track price by diploma track id
     */

    public function updateDiplomaTrackPrice(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'trainees_allowed_count' => 'required|integer',
            'total_cost' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'price' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'certificate_price' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'lab_cost' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'material_cost' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'assignment_cost' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'placement_cost' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'exam_cost' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'application' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'interview' => 'required|regex:/^\d+(\.\d{1,2})?$/',

        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors, 422);
        }

        $course_track = DiplomaTrack::findOrFail($id);

        $course_track->update([
            'trainees_allowed_count' => $request->trainees_allowed_count,
            'total_cost' => $request->total_cost,
        ]);

        $CourseTrackCost = DiplomaTrackCost::where('diploma_track_id',$id)->first();

        $CourseTrackCost->update([
            'price' => $request->price,
            'certificate_price' => $request->certificate_price,
            'lab_cost' => $request->lab_cost,
            'material_cost' => $request->material_cost,
            'assignment_cost' => $request->assignment_cost,
            'placement_cost' => $request->placement_cost,
            'exam_cost' => $request->exam_cost,
            'application' => $request->application,
            'interview' => $request->interview,
        ]);

        return response()->json($course_track);

    }

    //get drop down diploma track by vendor id

    public function DropdownsDiplomaTrack($id)
    {
        $course_tracks = DiplomaTrack::with(['lab','instructor'])->where([
            ['end_date','>=',now()],
            ['vendor_id','=',$id],
        ])->get();

        return response()->json($course_tracks);
    }

    //get drop down course by diploma track id

    public function DropdownsCourseDiplomaTrack($id)
    {
        $course_tracks = DiplomaTrackSchedule::with('course')->where([
            ['diploma_track_id','=',$id],
            ['date','>=',now()],
        ])->get();
        $courses = [];
        $id = 0 ;
        foreach ($course_tracks as $course_track)
        {
            if ($id != $course_track->course->id)
            {
                $id = $course_track->course->id;
                $courses[] = $course_track->course;
            }
        }

        return response()->json($courses);
    }

    /**
     * get diploma track by lead id
     */

    public function getDiplomaTrackByLeadId($id)
    {
        $diploma_tracks = DiplomaTrackStudent::where('lead_id',$id)->get();
        $data = [];
        foreach ($diploma_tracks as $diploma_track)
        {
            $data[] = $diploma_track->diplomaTrack;
        }

        return response()->json($data);
    }
}

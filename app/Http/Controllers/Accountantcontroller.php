<?php

namespace App\Http\Controllers;

use App\Models\CourseTrackStudentCancel;
use App\Models\CourseTrackStudentPayment;
use App\Models\DealIndividualPlacementTest;
use App\Models\DealInterview;
use App\Models\DiplomaTrackStudentCancel;
use App\Models\DiplomaTrackStudentPayment;
use App\Models\Employee;
use App\Models\Lead;
use App\Models\SalesTeamPayment;
use App\Models\TargetEmployees;
use App\Models\TraineesPayment;
use App\Models\Treasury;
use App\Models\TreasuryNotes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Accountantcontroller extends Controller
{

    // start gemyi
    // function to get accountant home page numbers report
    public function accountantHomeNumbersReport(Request $request,$id){
        $data = [];
        if ($request->from_date != null && $request->to_date != null)
        {
            $employee=Employee::findOrFail($id);
            $data['total_income']=$employee->incomeAndExpense()->where('type','income')->whereDate('created_at','>=',$request->from_date)->whereDate('created_at','<=',$request->to_date)->sum(DB::raw('amount'));
            $data['total_expense']=$employee->incomeAndExpense()->where('type','expense')->whereDate('created_at','>=',$request->from_date)->whereDate('created_at','<=',$request->to_date)->sum(DB::raw('amount'));
            $data['total_course_reservation']=$employee->traineesPaymentAccountant()->where('product_type','course')->whereDate('created_at','>=',$request->from_date)->whereDate('created_at','<=',$request->to_date)->sum(DB::raw('amount'));
            $data['total_diploma_reservation']=$employee->traineesPaymentAccountant()->where('product_type','diploma')->whereDate('created_at','>=',$request->from_date)->whereDate('created_at','<=',$request->to_date)->sum(DB::raw('amount'));
            $data['total_sales_team_payments']=$employee->salesTeamPayment()->where('is_payed',1)->whereDate('created_at','>=',$request->from_date)->whereDate('created_at','<=',$request->to_date)->sum(DB::raw('amount'));
            $data['total_instructor_payments']=$employee->instructorPayment()->where('treasury_id','!=',null)->whereDate('created_at','>=',$request->from_date)->whereDate('created_at','<=',$request->to_date)->sum(DB::raw('amount'));
            return $data;
        }else{
            $employee=Employee::findOrFail($id);
            $data['total_income']=$employee->incomeAndExpense->where('type','income')->sum(DB::raw('amount'));
            $data['total_expense']=$employee->incomeAndExpense->where('type','expense')->sum(DB::raw('amount'));
            $data['total_course_reservation']=$employee->traineesPaymentAccountant->where('product_type','course')->sum(DB::raw('amount'));
            $data['total_diploma_reservation']=$employee->traineesPaymentAccountant->where('product_type','diploma')->sum(DB::raw('amount'));
            $data['total_sales_team_payments']=$employee->salesTeamPayment->where('is_payed',1)->sum(DB::raw('amount'));
            $data['total_instructor_payments']=$employee->instructorPayment->where('treasury_id','!=',null)->sum(DB::raw('amount'));


            return $data;
        }
    }

    // end gemyi

    /**
     * get lead accountant details
     */
    public function getAccountantLead()
    {
        $leads = Lead::with(['country','city','interestingLevel','leadSources','dealIndividualPlacementTest','dealInterview'])
            ->where([
                ['add_placement',1],
                ['is_client',0],
            ])
            ->orWhere([
                ['add_interview',1],
                ['is_client',0],
            ])
            ->orWhere([
                ['add_selta',1],
                ['is_client',0],
            ])->get();

        foreach ( $leads as $lead)
        {
            $is_payid = 0;
            $lead->dealInterview;
            $celta = [];
            foreach ($lead->dealIndividualPlacementTest as $test)
            {
                if ($test->is_payed == 1)
                {
                    $is_payid = 1;
                }else{
                    $is_payid = 0;
                }
            }
            foreach ( $lead->dealInterview as $interview)
            {
                if ($interview->selta == 1)
                {
                    $celta[] =  $interview;
                }
                if ($interview->is_payed == 1)
                {
                    $is_payid = 1;
                }else{
                    $is_payid = 0;
                }

            }
            $lead->is_payid = $is_payid;
            $lead->celta = $celta;
        }
        return response()->json($leads);
    }

    /**
     * create accountant payment lead by lead id
     */
    public function AccountantPaymentLead(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'type' => 'required',
            'employee_id'=> 'required|exists:employees,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors,422);
        }

        if($request->type == "placement_test")
        {
            $lead = Lead::findOrFail($id);

            $plasment = DealIndividualPlacementTest::where('lead_id',$id)->first();

            $plasment->update([
               'is_payed' => 1,
               'amount' => $request->amount,
            ]);

            $invoice = TraineesPayment::create([
                'amount' =>  $request->amount,
                'lead_id' =>  $id,
                'seals_man_id' => $lead->employee_id,
                'accountant_id' => $request->employee_id,
                'product_name' => "placement_test",
                'product_type' => 'placement_test',
                'type' => 'in',
            ]);


            $targetEmployee = TargetEmployees::with(['salesTarget'=>function($q){
                $q-> where('to_date','>',now());
            }])->where([

                ['employee_id','=',$lead->employee_id],
                ['target_amount','>','achievement'],

            ])->first();
            if ($targetEmployee != null){

                if ($targetEmployee->salesTarget != null)
                {
                    $achievement = $targetEmployee->achievement + $request->payment_amount;
                    $targetEmployee->update([
                        'achievement' => $achievement
                    ]);

                    $seals_team_payment = SalesTeamPayment::create([
                       'target_employee_id' => $targetEmployee->id,
                       'employee_id' => $lead->employee_id,
                       'product_type' => "placement_test",
                       'lead_id' => $id,
                       'amount' => $request->amount,
                       'product_name' => "placement test",
                    ]);
                }
            }

            return response()->json($invoice);
        }

        if($request->type == "interview")
        {
            $lead = Lead::findOrFail($id);

            $interview = DealInterview::where('lead_id',$id)->first();

            $interview->update([
                'is_payed' => 1,
                'amount' => $request->amount,
            ]);

            $invoice = TraineesPayment::create([
                'amount' =>  $request->amount,
                'lead_id' =>  $id,
                'seals_man_id' => $lead->employee_id,
                'accountant_id' => $request->employee_id,
                'product_name' => "interview",
                'product_type' => 'interview',
                'type' => 'in',
            ]);


            $targetEmployee = TargetEmployees::with(['salesTarget'=>function($q){
                $q-> where('to_date','>',now());
            }])->where([

                ['employee_id','=',$lead->employee_id],
                ['target_amount','>','achievement'],

            ])->first();
            if ($targetEmployee != null){

                if ($targetEmployee->salesTarget != null)
                {
                    $achievement = $targetEmployee->achievement + $request->payment_amount;
                    $targetEmployee->update([
                        'achievement' => $achievement
                    ]);
                }

                $seals_team_payment = SalesTeamPayment::create([
                    'target_employee_id' => $targetEmployee->id,
                    'employee_id' => $lead->employee_id,
                    'product_type' => "interview",
                    'lead_id' => $id,
                    'amount' => $request->amount,
                    'product_name' => "interview",
                ]);

            }

            return response()->json($invoice);
        }

        if($request->type == "selta")
        {
            $lead = Lead::findOrFail($id);

            $interview = DealInterview::where('lead_id',$id)->first();

            $interview->update([
                'is_payed' => 1,
                'amount' => $request->amount,
            ]);

            $invoice = TraineesPayment::create([
                'amount' =>  $request->amount,
                'lead_id' =>  $id,
                'seals_man_id' => $lead->employee_id,
                'accountant_id' => $request->employee_id,
                'product_name' => "selta",
                'product_type' => 'selta',
                'type' => 'in',
            ]);

            $targetEmployee = TargetEmployees::with(['salesTarget'=>function($q){
                $q-> where('to_date','>',now());
            }])->where([

                ['employee_id','=',$lead->employee_id],
                ['target_amount','>','achievement'],

            ])->first();
            if ($targetEmployee != null){

                if ($targetEmployee->salesTarget != null)
                {
                    $achievement = $targetEmployee->achievement + $request->payment_amount;
                    $targetEmployee->update([
                        'achievement' => $achievement
                    ]);
                }

                $seals_team_payment = SalesTeamPayment::create([
                    'target_employee_id' => $targetEmployee->id,
                    'employee_id' => $lead->employee_id,
                    'product_type' => "selta",
                    'lead_id' => $id,
                    'amount' => $request->amount,
                    'product_name' => "celta",
                ]);
            }
            return response()->json($invoice);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'payment_date' => 'required|date',
            'payment_amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            // 'next_payment_date' => 'required|date',
            // 'next_payment_amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'employee_id'=> 'required|exists:employees,id',
            'treasury_id'=> 'required|exists:treasuries,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors,422);
        }

        if($request->type == "Course" || $request->type == "Course Reservation")
        {
            $studentsPayment = CourseTrackStudentPayment::findOrFail($id);

            if ($studentsPayment->amount == $request->payment_amount)
            {
                $studentsPayment->update([
                    'payment_date' => $request->payment_date,
                    'amount' => $request->payment_amount,
                    'all_paid' => $request->payment_amount,
                    'checkIs_paid' => 1,
                    'employee_id' => $request->employee_id
                ]);

                $invoice = TraineesPayment::create([
                    'amount' =>  $request->payment_amount,
                    'lead_id' =>  $studentsPayment->courseTrackStudent->lead_id,
                    'seals_man_id' => $studentsPayment->courseTrackStudent->employee_id,
                    'accountant_id' => $request->employee_id,
                    'product_name' => $studentsPayment->courseTrackStudent->course->name,
                    'product_type' => 'course',
                    'type' => 'in',
                    'treasury_id' => $request->treasury_id,
                ]);

                $treasury = Treasury::findOrFail($request->treasury_id);

                $income = $treasury->income + $request->payment_amount;

                $treasury->update([
                    'income' =>  $income
                ]);

                TreasuryNotes::create([
                    'employee_id' => $request->employee_id,
                    'trainees_payment_id' => $invoice->id,
                    'treasury_id' => $request->treasury_id,
                    'type' => 'in',
                    'note' => 'payment course',
                    'amount' => $request->payment_amount,
                ]);

            }else{

                $validator = Validator::make($request->all(), [
                    'next_payment_date' => 'required|date',
                    'next_payment_amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    return response()->json($errors,422);
                }

                $studentsPayment->update([
                    'payment_date' => $request->payment_date,
                    'amount' => $request->payment_amount,
                    'all_paid' => $request->payment_amount,
                    'checkIs_paid' => 1,
                    'employee_id' => $request->employee_id
                ]);

                $studentsPaymen = CourseTrackStudentPayment::create([
                    'payment_date' => $request->next_payment_date,
                    'amount' => $request->next_payment_amount,
                    'course_track_student_id' => $studentsPayment->course_track_student_id,
                ]);

                $invoice = TraineesPayment::create([
                    'amount' =>  $request->payment_amount,
                    'lead_id' =>  $studentsPayment->courseTrackStudent->lead_id,
                    'seals_man_id' => $studentsPayment->courseTrackStudent->employee_id,
                    'accountant_id' => $request->employee_id,
                    'product_name' => $studentsPayment->courseTrackStudent->course->name,
                    'product_type' => 'course',
                    'type' => 'in',
                    'treasury_id' => $request->treasury_id,
                ]);

                $treasury = Treasury::findOrFail($request->treasury_id);

                $income = $treasury->income + $request->payment_amount;

                $treasury->update([
                    'income' =>  $income
                ]);
                TreasuryNotes::create([
                    'employee_id' => $request->employee_id,
                    'trainees_payment_id' => $invoice->id,
                    'treasury_id' => $request->treasury_id,
                    'type' => 'in',
                    'note' => 'payment course',
                    'amount' => $request->payment_amount,
                ]);

            }

                $targetEmployee = TargetEmployees::with(['salesTarget'=>function($q){
                    $q-> where('to_date','>',now());
                }])->where([

                    ['employee_id','=',$studentsPayment->courseTrackStudent->employee_id],
                    ['target_amount','>','achievement'],

                ])->first();
                if ($targetEmployee != null){

                    if ($targetEmployee->salesTarget != null)
                    {
                        $achievement = $targetEmployee->achievement + $request->payment_amount;
                        $targetEmployee->update([
                            'achievement' => $achievement
                        ]);
                        $seals_team_payment = SalesTeamPayment::create([
                            'target_employee_id' => $targetEmployee->id,
                            'employee_id' => $studentsPayment->courseTrackStudent->employee_id,
                            'product_type' => "course",
                            'lead_id' => $studentsPayment->courseTrackStudent->lead_id,
                            'amount' => $request->payment_amount,
                            'product_name' => $studentsPayment->courseTrackStudent->course->name,
                            'course_track_id' => $studentsPayment->courseTrackStudent->course_track_id,
                        ]);

                    }

                }

            return response()->json($invoice);
        }
        if($request->type == "Diploma" || $request->type == "Diploma Reservation")
        {
            $studentsPayment = DiplomaTrackStudentPayment::findOrFail($id);

            if ($studentsPayment->amount == $request->payment_amount)
            {
                $studentsPayment->update([
                    'payment_date' => $request->payment_date,
                    'amount' => $request->payment_amount,
                    'all_paid' => $request->payment_amount,
                    'checkIs_paid' => 1,
                    'employee_id' => $request->employee_id
                ]);

                $invoice = TraineesPayment::create([
                    'amount' =>  $request->payment_amount,
                    'lead_id' =>  $studentsPayment->diplomaTrackStudent->lead_id,
                    'seals_man_id' => $studentsPayment->diplomaTrackStudent->employee_id,
                    'accountant_id' => $request->employee_id,
                    'product_name' => $studentsPayment->diplomaTrackStudent->diploma->name,
                    'product_type' => 'diploma',
                    'type' => 'in',
                    'treasury_id' => $request->treasury_id,
                ]);

                $treasury = Treasury::findOrFail($request->treasury_id);

                $income = $treasury->income + $request->payment_amount;

                $treasury->update([
                    'income' =>  $income
                ]);

                TreasuryNotes::create([
                    'employee_id' => $request->employee_id,
                    'trainees_payment_id' => $invoice->id,
                    'treasury_id' => $request->treasury_id,
                    'type' => 'in',
                    'note' => 'payment diploma',
                    'amount' => $request->payment_amount,
                ]);

            }else{

                $validator = Validator::make($request->all(), [
                    'next_payment_date' => 'required|date',
                    'next_payment_amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    return response()->json($errors,422);
                }

                $studentsPayment->update([
                    'payment_date' => $request->payment_date,
                    'amount' => $request->payment_amount,
                    'all_paid' => $request->payment_amount,
                    'checkIs_paid' => 1,
                    'employee_id' => $request->employee_id
                ]);

                $studentsPayment = DiplomaTrackStudentPayment::create([
                    'payment_date' => $request->next_payment_date,
                    'amount' => $request->next_payment_amount,
                    'diploma_track_student_id' => $studentsPayment->diploma_track_student_id,
                ]);

                $invoice = TraineesPayment::create([
                    'amount' =>  $request->payment_amount,
                    'lead_id' =>  $studentsPayment->diplomaTrackStudent->lead_id,
                    'seals_man_id' => $studentsPayment->diplomaTrackStudent->employee_id,
                    'accountant_id' => $request->employee_id,
                    'product_name' => $studentsPayment->diplomaTrackStudent->diploma->name,
                    'product_type' => 'diploma',
                    'type' => 'in',
                    'treasury_id' => $request->treasury_id,
                ]);

                $treasury = Treasury::findOrFail($request->treasury_id);

                $income = $treasury->income + $request->payment_amount;

                $treasury->update([
                    'income' =>  $income
                ]);

                TreasuryNotes::create([
                    'employee_id' => $request->employee_id,
                    'trainees_payment_id' => $invoice->id,
                    'treasury_id' => $request->treasury_id,
                    'type' => 'in',
                    'note' => 'payment diploma',
                    'amount' => $request->payment_amount,
                ]);

            }

            $targetEmployee = TargetEmployees::with(['salesTarget'=>function($q){
                $q-> where('to_date','>',now());
            }])->where([

                ['employee_id','=',$studentsPayment->diplomaTrackStudent->employee_id],
                ['target_amount','>','achievement'],

            ])->first();
            if ($targetEmployee != null){

                if ($targetEmployee->salesTarget != null)
                {
                    $achievement = $targetEmployee->achievement + $request->payment_amount;
                    $targetEmployee->update([
                        'achievement' => $achievement
                    ]);

                    $seals_team_payment = SalesTeamPayment::create([
                        'target_employee_id' => $targetEmployee->id,
                        'employee_id' => $studentsPayment->diplomaTrackStudent->employee_id,
                        'product_type' => "diploma",
                        'lead_id' => $studentsPayment->diplomaTrackStudent->lead_id,
                        'amount' => $request->payment_amount,
                        'product_name' => $studentsPayment->diplomaTrackStudent->diploma->name,
                        'diploma_track_id' => $studentsPayment->diplomaTrackStudent->diploma_track_id,
                    ]);
                }

            }

            return response()->json($invoice);

        }

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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    /**
     * student refund payment
     */
    public function refundPayment(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'refund' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'employee_id'=> 'required|exists:employees,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors,422);
        }

        if($request->type == "Course Cancellation")
        {
            $studentsCancel = CourseTrackStudentCancel::findOrFail($id);

            $studentsCancel->update([
              'is_refund' => 1
            ]);

            $invoice = TraineesPayment::create([
                'amount' =>  $request->refund,
                'lead_id' =>  $studentsCancel->courseTrackStudent->lead_id,
                'seals_man_id' => $studentsCancel->courseTrackStudent->employee_id,
                'accountant_id' => $request->employee_id,
                'product_name' => $studentsCancel->courseTrackStudent->course->name,
                'product_type' => 'course',
                'type' => 'out',
            ]);

            $targetEmployee = TargetEmployees::with(['salesTarget'=>function($q){
                $q-> where('to_date','>',now());
            }])->where([

                ['employee_id','=',$studentsCancel->courseTrackStudent->employee_id],
                ['target_amount','>','achievement'],

            ])->first();
            if ($targetEmployee != null){

                if ($targetEmployee->salesTarget != null)
                {
                    $achievement = $targetEmployee->achievement - $request->refund;
                    $targetEmployee->update([
                        'achievement' => $achievement
                    ]);
                }

                $seals_team_payment = SalesTeamPayment::create([
                    'target_employee_id' => $targetEmployee->id,
                    'employee_id' => $studentsCancel->courseTrackStudent->employee_id,
                    'product_type' => "course",
                    'lead_id' => $studentsCancel->courseTrackStudent->lead_id,
                    'amount' => $request->refund,
                    'product_name' => $studentsCancel->courseTrackStudent->course->name,
                    'course_track_id' => $studentsCancel->courseTrackStudent->course_track_id,
                    'type' => 'out',
                ]);

            }

            return response()->json($invoice);
        }

        if($request->type == "Diploma Cancellation")
        {
            $studentsCancel = DiplomaTrackStudentCancel::findOrFail($id);

            $studentsCancel->update([
                'is_refund' => 1
            ]);

            $invoice = TraineesPayment::create([
                'amount' =>  $request->refund,
                'lead_id' =>  $studentsCancel->diplomaTrackStudent->lead_id,
                'seals_man_id' => $studentsCancel->diplomaTrackStudent->employee_id,
                'accountant_id' => $request->employee_id,
                'product_name' => $studentsCancel->diplomaTrackStudent->diploma->name,
                'product_type' => 'diploma',
                'type' => 'out',
            ]);

            $targetEmployee = TargetEmployees::with(['salesTarget'=>function($q){
                $q-> where('to_date','>',now());
            }])->where([

                ['employee_id','=',$studentsCancel->diplomaTrackStudent->employee_id],
                ['target_amount','>','achievement'],

            ])->first();
            if ($targetEmployee != null){

                if ($targetEmployee->salesTarget != null)
                {
                    $achievement = $targetEmployee->achievement - $request->refund;
                    $targetEmployee->update([
                        'achievement' => $achievement
                    ]);

                    $seals_team_payment = SalesTeamPayment::create([
                        'target_employee_id' => $targetEmployee->id,
                        'employee_id' => $studentsCancel->diplomaTrackStudent->employee_id,
                        'product_type' => "diploma",
                        'lead_id' => $studentsCancel->diplomaTrackStudent->lead_id,
                        'amount' => $request->refund,
                        'product_name' => $studentsCancel->diplomaTrackStudent->diploma->name,
                        'diploma_track_id' => $studentsCancel->diplomaTrackStudent->diploma_track_id,
                        'type' => 'out',
                    ]);
                }

            }

            return response()->json($invoice);

        }

    }

}

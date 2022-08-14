<?php

namespace App\Http\Controllers;

use App\Models\EmailMessage;
use App\Models\Employee;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SendMailController extends Controller
{
    public function sendMail(Request $request){

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'subject' => 'required|string',
            'body' => 'required',
            'lead_id' => 'required|exists:leads,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors,422);
        }

        $lead = Lead::find($request->lead_id);
        $employee = Employee::find($request->employee_id);

        // send Email
        $data = array(
            'email' => $lead->email,
            'subject' => $request->subject,
            'body' => $request->body,
        );

        Mail::send('mailer', $data, function ($message) use ($data,$employee) {
            $message->from($employee->user->email, $employee->first_name.$employee->middle_name.$employee->last_name);
            $message->to($data['email']);
            $message->subject($data['subject']);
        });

        EmailMessage::create([
            'subject' => $request->subject,
            'employee_id' => $request->employee_id,
            'lead_id' => $request->lead_id,
            'message' => $request->body,
        ]);

        return response()->json("successfully");
    }
}

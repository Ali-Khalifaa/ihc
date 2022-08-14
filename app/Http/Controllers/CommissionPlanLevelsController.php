<?php

namespace App\Http\Controllers;

use App\Models\SalesComissionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommissionPlanLevelsController extends Controller
{
    /**
     * get Sales Commission Plan Levels by commission id
     */
    public function getCommissionPlanLevels($id)
    {
        $salesCommissionPlan = SalesComissionPlan::where('comission_management_id','=',$id)->get();

        return response()->json($salesCommissionPlan);
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
            'comission_management_id' => 'required|exists:comission_management,id',
            'individual_target_amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'individual_percentage' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'corporation_target_amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'corporation_percentage' => 'required|regex:/^\d+(\.\d{1,2})?$/',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors,422);
        }

         $salesCommissionPlan = SalesComissionPlan::create([
            'individual_target_amount' => $request->individual_target_amount,
            'individual_percentage' => $request->individual_percentage,
            'corporation_target_amount' => $request->corporation_target_amount,
            'corporation_percentage' => $request->corporation_percentage,
            'comission_management_id' => $request->comission_management_id,
        ]);

        return response()->json($salesCommissionPlan);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $SalesCommissionPlan = SalesComissionPlan::findOrFail($id);
        return response()->json($SalesCommissionPlan);
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
            'individual_target_amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'individual_percentage' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'corporation_target_amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'corporation_percentage' => 'required|regex:/^\d+(\.\d{1,2})?$/',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors,422);
        }

        $salesCommissionPlan = SalesComissionPlan::findOrFail($id);
        $salesCommissionPlan->update([
            'individual_target_amount' => $request->individual_target_amount,
            'individual_percentage' => $request->individual_percentage,
            'corporation_target_amount' => $request->corporation_target_amount,
            'corporation_percentage' => $request->corporation_percentage,
        ]);

        return response()->json($salesCommissionPlan);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $salesCommissionPlan = SalesComissionPlan::findOrFail($id);
        $salesCommissionPlan->delete();
        return response()->json('deleted successfully');
    }
}

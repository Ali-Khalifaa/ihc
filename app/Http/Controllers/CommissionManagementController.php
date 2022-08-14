<?php

namespace App\Http\Controllers;

use App\Models\ComissionManagement;
use App\Models\SalesComissionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommissionManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $commissions = ComissionManagement::with(['salesComissionPlans','salesTarget','targetEmployees'])->get();

        foreach($commissions as $commission)
        {
            $commission->noAction = 0;

            if (count($commission->salesComissionPlans ) > 0 || count($commission->salesTarget ) > 0 || count($commission->targetEmployees ) > 0){

                $commission->noAction = 1;
    
            }
        }

        return response()->json($commissions);
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
            'name' => 'required|string|max:100|unique:comission_management',
            'individual_target_amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'individual_percentage' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'corporation_target_amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'corporation_percentage' => 'required|regex:/^\d+(\.\d{1,2})?$/',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors,422);
        }
        $commissions = ComissionManagement::create([
            'name' => $request->name,
        ]);

        SalesComissionPlan::create([
            'individual_target_amount' => $request->individual_target_amount,
            'individual_percentage' => $request->individual_percentage,
            'corporation_target_amount' => $request->corporation_target_amount,
            'corporation_percentage' => $request->corporation_percentage,
            'comission_management_id' => $commissions->id,
        ]);

        return response()->json($commissions);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $commissions = ComissionManagement::with('salesComissionPlans')->findOrFail($id);

        return response()->json($commissions);
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
        $commissions = ComissionManagement::findOrFail($id);

        if ($commissions->name != $request->name)
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100|unique:comission_management',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                return response()->json($errors,422);
            }
        }

        $commissions->update([
            'name' => $request->name,
        ]);

        return response()->json($commissions);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $commissions = ComissionManagement::findOrFail($id);
        $commissions->delete();

        return response()->json('deleted success');
    }
}

<?php

namespace App\Http\Controllers;


use App\Models\Hubspot;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HubSpotController extends Controller
{

    public function searchHub(Request $request){
        $filter = new \HubSpot\Client\Crm\Contacts\Model\Filter();
        $filter
            ->setOperator('EQ')
            ->setPropertyName('firstname')
            ->setValue($request->search);

        $filterGroup = new \HubSpot\Client\Crm\Contacts\Model\FilterGroup();
        $filterGroup->setFilters([$filter]);

        $searchRequest = new \HubSpot\Client\Crm\Contacts\Model\PublicObjectSearchRequest();
        $searchRequest->setFilterGroups([$filterGroup]);

        $handlerStack = \GuzzleHttp\HandlerStack::create();
        $handlerStack->push(
            \HubSpot\RetryMiddlewareFactory::createRateLimitMiddleware(
                \HubSpot\Delay::getConstantDelayFunction()
            )
        );

        $handlerStack->push(
            \HubSpot\RetryMiddlewareFactory::createInternalErrorsMiddleware(
                \HubSpot\Delay::getExponentialDelayFunction(2)
            )
        );

        $client = new \GuzzleHttp\Client(['handler' => $handlerStack]);

        $hubSpot = \HubSpot\Factory::createWithApiKey('684e75f0-7097-404f-a043-7284fdc67516', $client);

// @var CollectionResponseWithTotalSimplePublicObject $contactsPage
        $contactsPage = $hubSpot->crm()->contacts()->searchApi()->doSearch($searchRequest);

        return $contactsPage;
    }


    public function getHubLead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric',
            'after' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors, 422);
        }

        $handlerStack = \GuzzleHttp\HandlerStack::create();
        $handlerStack->push(
            \HubSpot\RetryMiddlewareFactory::createRateLimitMiddleware(
                \HubSpot\Delay::getConstantDelayFunction()
            )
        );

        $handlerStack->push(
            \HubSpot\RetryMiddlewareFactory::createInternalErrorsMiddleware(
                \HubSpot\Delay::getExponentialDelayFunction(2)
            )
        );

        $client = new \GuzzleHttp\Client(['handler' => $handlerStack]);

        $hubSpot = \HubSpot\Factory::createWithApiKey('684e75f0-7097-404f-a043-7284fdc67516', $client);
        $response = $hubSpot->crm()->contacts()->basicApi()->getPage($request->limit, $request->after);

        return response()->json($response);
    }

    /**
     * add hub spot leads
     */

    public function addHubspotLead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors, 422);
        }

        $hubspot = Hubspot::where('hubspot_id', $request->id)->first();
        if ($hubspot)
        {
            $errors = [];
            $errors['errors'] = "this lead already exist";
            return response()->json($errors,422);
        }

        $handlerStack = \GuzzleHttp\HandlerStack::create();
        $handlerStack->push(
            \HubSpot\RetryMiddlewareFactory::createRateLimitMiddleware(
                \HubSpot\Delay::getConstantDelayFunction()
            )
        );

        $handlerStack->push(
            \HubSpot\RetryMiddlewareFactory::createInternalErrorsMiddleware(
                \HubSpot\Delay::getExponentialDelayFunction(2)
            )
        );

        $client = new \GuzzleHttp\Client(['handler' => $handlerStack]);

        $hubSpot = \HubSpot\Factory::createWithApiKey('684e75f0-7097-404f-a043-7284fdc67516', $client);
        $response = $hubSpot->crm()->contacts()->basicApi()->getById($request->id);

        // check hubspot

        Hubspot::create(['hubspot_id' => $response['id'],
            'email' => $response['properties']['email'],
            'first_name' => $response['properties']['firstname'],
            'last_name' => $response['properties']['lastname'],
            'create_date' => $response['properties']['createdate'],
            'last_modified_date' => $response['properties']['lastmodifieddate'],
        ]);

        Lead::create([
            "first_name" => $response['properties']['firstname'],
            "last_name" => $response['properties']['lastname'],
            "email" => $response['properties']['email'],
            "lead_source_id"=> 8
        ]);

        return response()->json("successfully");
    }

}

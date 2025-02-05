<?php

namespace App\Http\Controllers;

use App\Models\CallInfo;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class CallInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {

        $curl = curl_init();

        // Define the data to be sent
        $dataApi = [
            "phone_number" => "$request->phone",
            // "pathway_id" => "4397a88d-af20-4400-993f-9a74d0e7932a",
            "task" => $request->customData['task'] ?? $request->task,
            "max_duration" => 4,
            // "voice" => "maya",
            // "voice" => "e1289219-0ea2-4f22-a994-c542c2a48a0f",
            "voice" => "1d054475-3908-4f64-9158-9d3911fe9597",
            "model" => "enhanced",
            "language" => "en-US",
            "record" => true,
            "from" => "+14158765880",
            "analysis_schema" => [
               "name" => 'What is the client’s name? (Client’s info only)',
                // Capture the business or company name
                "business_name" => 'What is the name of the client’s business?',
                // Identify the client’s primary goal or challenge
                "main_goal_or_challenge" => 'What is the biggest goal or challenge the client wants to address with PowerinAI?',
                // Determine which processes or areas the client wants to automate
                "automation_focus" => 'Which processes does the client want to automate or streamline?',
                // Understand the client's current communication methods
                "current_communication_methods" => 'How is the client currently managing customer communication?',
                // Gather the client’s preference for a demo date/time
                "demo_scheduling_preference" => 'When is the client available/prefer to schedule the demo?',
                // Check if the client is interested in proceeding or scheduling
                'is_interested' => 'Is the client interested in this solution or scheduling a demo? (yes/no/maybe) (Client’s info only)'
            ],
            "webhook" => $request->customData['webhook'] ?? "https://old.powerinai.com/call-api/callback-call",
            "summary_prompt" => $request->customData['summary_prompt'] ?? $request->summary_prompt,
        ];

        // Convert the data array to a JSON string
        $jsonDataApi = json_encode($dataApi);

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.bland.ai/v1/calls",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $jsonDataApi,  // Use the JSON-encoded data
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "authorization: sk-yw2mdp3d2golqrm41vg7jvsxlx1vuvtl95bjmchx1q3u5pzbvrl0q9mb8b3u0ckg69"
            ],
        ]);

        // Execute the request
        $response = curl_exec($curl);
        $err = curl_error($curl);

        // Close the cURL session
        curl_close($curl);

        $status = false;
        $call_id = null;
        $message = null;
        // Check for errors and output the response
        if ($err) {
            // echo "cURL Error #:" . $err;
            $status = false;
        } else {
            // echo $response;
            // exit;
            $response = json_decode($response);
            if (isset($response->call_id)) {
                $status = true;
                $call_id = $response->call_id;
            } elseif (isset($response->errors)) {
                $message = json_encode($response->errors);
            } else {
                $message = json_encode($response);
            }
        }


        $callInfo = new CallInfo();
        $callInfo->name = $request->full_name;
        $callInfo->phone = "$request->phone";
        $callInfo->call_id = $call_id;
        $callInfo->status = $status;
        $callInfo->message = $message;
        $callInfo->save();

        return response()->json($callInfo, 200);
    }


    public function callbackCall(Request $request)
    {

        //$callInfo = CallInfo::find(296);
        $callInfo = new CallInfo();
        $callInfo->name = "callbackCall";
        $callInfo->phone = "callbackCall";
        $callInfo->call_id = 'This is test callbackCall!';
        $callInfo->status = true;
        $callInfo->is_completed = true;
        $callInfo->is_send_gohihglevel = true;
        $callInfo->message = json_encode($request->all());
        $callInfo->save();
        if($callInfo->message){
            $call = json_decode($callInfo->message);
            if(isset($call->call_id)){
                 $ifoutbound = CallInfo::where('call_id', $call->call_id)->first();

                 $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.bland.ai/v1/calls/'.$call->call_id,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'authorization: sk-yw2mdp3d2golqrm41vg7jvsxlx1vuvtl95bjmchx1q3u5pzbvrl0q9mb8b3u0ckg69'
                    ),
                ));

                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
                if ($err) {

                } else {
                    $response = json_decode($response);
                    if (isset($response->completed)) {
                     $sendData = [
                    "phone" => $response->inbound ? $response->from : $response->to,
                    "call_id" => $response->call_id,
                    "inbound" => $response->inbound ? 'Yes':'NO',
                    "recording_url" => $response->recording_url,
                    "is_interested" => $response->analysis->is_interested,
                    "name" => $response->analysis->name,
                    "business_name" => $response->analysis->business_name,
                    "main_goal_or_challenge" => $response->analysis->main_goal_or_challenge,
                    "automation_focus" => $response->analysis->automation_focus,
                    "current_communication_methods" => $response->analysis->current_communication_methods,
                    "demo_scheduling_preference" => $response->analysis->demo_scheduling_preference,
                    "summary" => $response->summary,
                    "status" => $response->status
                ];
                $url = "https://services.leadconnectorhq.com/hooks/FFoC8B5sSLMlbYaQ4Tcz/webhook-trigger/14dfb751-ae5b-499c-8e82-adac3ac24d8b";

                 $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $sendData
                ));

                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);


                if($ifoutbound){
                     $ifoutbound->is_send_gohihglevel = true;
                }

                    }

                }

            }

        }


        // return redirect()->route('call.status.update');
        return response()->json([
            'status' => true
        ], 200);
    }

    public function callbackCallAptt(Request $request)
    {

        //  $callInfo = CallInfo::find(296);
        $callInfo = new CallInfo();
        $callInfo->name = "callbackCall";
        $callInfo->phone = "callbackCall";
        $callInfo->call_id = 'This is test callbackCall!';
        $callInfo->status = true;
        $callInfo->is_completed = true;
        $callInfo->is_send_gohihglevel = true;
        $callInfo->message = json_encode($request->all());
        $callInfo->save();
        if($callInfo->message){
            $call = json_decode($callInfo->message);
            if(isset($call->call_id)){
                 $ifoutbound = CallInfo::where('call_id', $call->call_id)->first();

                 $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.bland.ai/v1/calls/'.$call->call_id,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'authorization: sk-yw2mdp3d2golqrm41vg7jvsxlx1vuvtl95bjmchx1q3u5pzbvrl0q9mb8b3u0ckg69'
                    ),
                ));

                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
                if ($err) {

                } else {
                    $response = json_decode($response);
                    if (isset($response->completed)) {
                     $sendData = [
                    "summary" => $response->summary,
                    "phone" => $response->inbound ? $response->from : $response->to,
                    "call_id" => $response->call_id,
                    "inbound" => $response->inbound ? 'Yes':'NO',
                    "recording_url" => $response->recording_url,
                    "is_interested" => $response->analysis->is_interested,
                    "status" => $response->status
                ];
                $url = "https://services.leadconnectorhq.com/hooks/FFoC8B5sSLMlbYaQ4Tcz/webhook-trigger/14dfb751-ae5b-499c-8e82-adac3ac24d8b";

                 $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $sendData
                ));

                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);


                if($ifoutbound){
                     $ifoutbound->is_send_gohihglevel = true;
                }

                    }

                }

            }

        }


        // return redirect()->route('call.status.update');
        return response()->json([
            'status' => true
        ], 200);
    }
    public function callbackCallmm(Request $request)
    {
        $callInfo = new CallInfo();
        $callInfo->name = "callbackCallmm";
        $callInfo->phone = "callbackCallmm";
        $callInfo->call_id = 'This is test callbackCallmm!';
        $callInfo->status = true;
        $callInfo->is_completed = true;
        $callInfo->is_send_gohihglevel = true;
        $callInfo->message = json_encode($request->all());
        $callInfo->save();

        return response()->json($callInfo, 200);
    }

    public function CallStatusUpdate()
    {
        $calls = CallInfo::where('is_completed', false)->orWhere('is_completed', null)->get();
        foreach ($calls as $call) {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.bland.ai/v1/calls/' . $call->call_id,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'authorization: sk-yw2mdp3d2golqrm41vg7jvsxlx1vuvtl95bjmchx1q3u5pzbvrl0q9mb8b3u0ckg69'
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);


            // Check for errors and output the response
            if ($err) {
                $call->message = $err;
            } else {
                // echo $response;
                // exit;
                $response = json_decode($response);
                if (isset($response->completed)) {
                    $call->is_completed = $response->completed;
                } elseif (isset($response->errors)) {
                    $call->message = json_encode($response->errors);
                } else {
                    $call->message = json_encode($response);
                }
            }

            $call->save();
        }

        // return response()->json([
        //     'status' => true
        // ], 200);
        return redirect()->route('info.send.gohighlevel');
    }

    public function infoSendGohighlevel()
    {
        $calls = CallInfo::where('is_completed', true)->where('is_send_gohihglevel', null)->get();
        foreach ($calls as $call) {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.bland.ai/v1/calls/fa8853ac-d7c5-4b73-b4a6-7b1d34cc022c',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'authorization: sk-yw2mdp3d2golqrm41vg7jvsxlx1vuvtl95bjmchx1q3u5pzbvrl0q9mb8b3u0ckg69'
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);


            // Check for errors and output the response
            if ($err) {
                $call->message = $err;
            } else {
                // echo $response;
                // exit;
                $response = json_decode($response);
                if (isset($response->completed)) {


                    $sendData = [
                        "summary" => $response->summary,
                        "phone" => $response->to,
                        "call_id" => $response->call_id,
                        "inbound" => "$response->inbound",
                        "recording_url" => $response->recording_url,
                        "status" => $response->status
                    ];

                    $url = "https://services.leadconnectorhq.com/hooks/M2C8oiFvzNkUMtsqxp3Z/webhook-trigger/d8b061d9-0550-4042-ab8e-4c95596cd368";


                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $sendData
                    ));

                    $response = curl_exec($curl);
                    $err = curl_error($curl);
                    curl_close($curl);
return $response;

                   // $call->is_send_gohihglevel = true;
                } elseif (isset($response->errors)) {
                  //  $call->message = json_encode($response->errors);
                } else {
                  //  $call->message = json_encode($response);
                }
            }

           $call->save();
        }

        return response()->json([
            'status' => true
        ], 200);
    }


    public function sendSms(Request $request)
    {

        // $phone = Str::replace('+88', '', $request->phone);
        $phone = Str::remove('-', $request->phone);
        // JSON data to be sent
        $postData = [
            // 'api_key' => '$2y$10$rm1a9jVBOS8y/tzQGUdeuOhk1xM7DAVnvFLdTrqKJNlD9E.tHAtMy150',
            'api_key' => '$2y$10$hpiN3v/GeXOgrSBzVrzIiutLJICUFK905608ndv8T69y/3e7XoK6a343',
            'transaction_type' => 'P',
            'sms_data' => [
                [
                    'recipient' =>  $phone,
                    // 'sender_id' => '8809617611657',
                    'sender_id' => '8809617619933',
                    'message' => $request->customData['message'] ?? $request->message,
                ],

            ],
        ];

        // Convert data to JSON format
        $jsonData = json_encode($postData);

        if ($request->phone) {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'http://sms.greenheritageit.com/smsapi',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            $callInfo = new CallInfo();
            $callInfo->name = $request->full_name;
            $callInfo->phone = $phone;
            $callInfo->call_id = 'This is test sms from highlevel!';
            $callInfo->status = true;
            $callInfo->is_completed = true;
            $callInfo->is_send_gohihglevel = true;
            $callInfo->message = $response;
            $callInfo->save();

            return $response;
        }
        return "Something Went Wrong!";
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CallInfo $callInfo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CallInfo $callInfo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CallInfo $callInfo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CallInfo $callInfo)
    {
        //
    }
}

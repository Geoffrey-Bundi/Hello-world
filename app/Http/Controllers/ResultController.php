<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Pt;
use App\Result;
use App\Field;
use App\Option;
use App\User;
use App\Notification;
use App\Enrol;
use App\Round;
use App\County;
use App\SubCounty;
use App\Facility;
use App\Program;
use App\Panel;
use App\Material;

use App\Libraries\AfricasTalkingGateway as Bulk;

use Auth;
use Jenssegers\Date\Date as Carbon;
use DB;
use PDF;

class ResultController extends Controller
{

    public function manageResult()
    {
        return view('result.index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $error = ['error' => 'No results found, please try with different keywords.'];
        $items_per_page = 100;

        if(Auth::user()->isCountyCoordinator())
        {
            $results = County::find(Auth::user()->ru()->tier)->results()->latest()->withTrashed()->paginate($items_per_page);
        }
        else if(Auth::user()->isSubCountyCoordinator())
        {
           $results = SubCounty::find(Auth::user()->ru()->tier)->results()->latest()->withTrashed()->paginate($items_per_page);
        }
        else if(Auth::user()->isFacilityInCharge())
        {
           $results = Facility::find(Auth::user()->ru()->tier)->results()->latest()->withTrashed()->paginate($items_per_page);
        }
        else if(Auth::user()->isParticipant())
        {
            $results = Auth::user()->results()->latest()->withTrashed()->paginate($items_per_page);
        }else
        {
            $results = Pt::latest()->withTrashed()->paginate($items_per_page);

        }

        //search results by user details
        if ($request->all()) {        
            if($request->has('q')) 
            {
                $search = $request->get('q');
                $search_ar = ['search'=>$search];
                
                if(Auth::user()->isCountyCoordinator())
                {
                    $results = County::find(Auth::user()->ru()->tier)->results($search_ar);
                    // ->latest()->withTrashed()->paginate($items_per_page);                
                }
                else if(Auth::user()->isSubCountyCoordinator())
                {
                    $results = SubCounty::find(Auth::user()->ru()->tier)->results($search_ar);
                    // ->latest()->withTrashed()->paginate($items_per_page);
                }
                else if(Auth::user()->isFacilityInCharge())
                {
                   $results = Facility::find(Auth::user()->ru()->tier)->results($search_ar);
                   // ->latest()->withTrashed()->paginate($items_per_page);
                }
                else if(Auth::user()->isPartner())
                {
                   $results = ImplementingPartner::find(Auth::user()->ru()->tier)->results($search_ar);
                   // ->latest()->withTrashed()->paginate($items_per_page);
                }
                else{
                    $users = User::where('name', 'LIKE', "%{$search}%")
                        ->orWhere('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('middle_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%")
                        ->orWhere('uid', 'LIKE', "%{$search}%")->pluck('id');
                        // dd($users);

                    $enrolments = Enrol::whereIn('user_id', $users)->pluck('id');
                    $results = Pt::whereIn('enrolment_id', $enrolments);
                    //->
                }
            }
            //search results by filters
            if($request->has('county')) 
            {            
                    $results = County::find($request->get('county'))->results();
                    // ->latest()->withTrashed()->paginate($items_per_page);                
                
            }
             if($request->has('sub_county')) 
            {
                    $results = SubCounty::find($request->get('sub_county'))->results();
                    // ->latest()->withTrashed()->paginate($items_per_page);

            }
            if($request->has('facility')) 
            {
                   $results = Facility::find($request->get('facility'))->results();
                   // ->latest()->withTrashed()->paginate($items_per_page);

            }
            if ($request->has('result_status')|| $request->has('feedback_status')) {               
            
                if($request->has('result_status')) 
                {
                    if ($request->has('county') ||$request->has('sub_county')||$request->has('facility')) {
                        $results = $results->where('panel_status', $request->get('result_status'))->latest()->withTrashed()->paginate($items_per_page);
                        
                    }else{
                        $results = Pt::where('panel_status', $request->get('result_status'))->latest()->withTrashed()->paginate($items_per_page);

                    }
                }
                if($request->has('feedback_status')) 
                {     
                    if ($request->has('county') ||$request->has('sub_county')||$request->has('facility')) {
                        $results = $results->where('feedback', $request->get('feedback_status'))->latest()->withTrashed()->paginate($items_per_page);
                        
                    }else{
                        $results = Pt::where('feedback', $request->get('feedback_status'))->latest()->withTrashed()->paginate($items_per_page);
                    }
                }
            }else{

                $results = $results->latest()->withTrashed()->paginate($items_per_page);
            }
        }

        foreach($results as $result)
        {
            $result->rnd = $result->enrolment->round->name;
            $result->tester = $result->enrolment->user->name;
            $result->uid = $result->enrolment->user->uid;

            //particpants should not see the result feedback until it has been verified by the admin             
            if(Auth::user()->isParticipant() ||Auth::user()->isSubCountyCoordinator()||Auth::user()->isCountyCoordinator()||Auth::user()->isPartner()){
                if ($result->panel_status != 3) {
                    $result->feedback = 2;
                }                
            }

	        $result->user_role = Auth::user()->ru()->role_id;
        }

        $response = [
            'pagination' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem()
            ],
            'user_role' => Auth::user()->ru()->role_id,
            'data' => $results
        ]; 
        return $results->count() > 0 ? response()->json($response) : $error;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {        
        // dd($request->all());
        //Check if round has been         
        if ($request->get('round_id') =="") {
            return response()->json(['1']);            
        } else
        {   
            //	Save pt first then proceed to save form fields
            $round_id = $request->get('round_id');
            $enrolment = Enrol::where('user_id', Auth::user()->id)->where('round_id', $round_id)->first();
            
            //Validation: Check if the enrolment results have been submitted
            if ($enrolment->status ==1) {
                return response()->json(['2']);            
            }else
            {   
                $pt = new Pt;
                $pt->enrolment_id = $enrolment->id;
                $pt->panel_status = Pt::NOT_CHECKED;
                $pt->save();

                //update enrollment status to 1
                $enrolment->status = Enrol::DONE;        
                $enrolment->save();     
               
                //	Proceed to form-fields
                // get all fields and insert into results
                $fields = Field::all();
                $response = '';

                foreach ($fields as $field) {
                    $result = new Result;
                    $result->pt_id = $pt->id;
                    $result->field_id = $field->id;
                   
                   //loop through the results entered and get the response for each field
                    foreach ($request->all() as $key => $value)
                    {
                        if((stripos($key, 'token') !==FALSE) || (stripos($key, 'method') !==FALSE))
                            continue;
                        else if(stripos($key, 'field') !==FALSE)
                        {
                            $fieldId = (int)$this->strip($key);
                            if(is_array($value))
                              $value = implode(', ', $value);
                            

                            if ($field->id == $fieldId) {
                                $response = $value;
                                break;
                            }else if ($field->id != $fieldId) {
                                $response = '';

                            }                      
                        }                        
                    } 
                    // save the response for respective field
                    $result->response = $response;
                    $result->save();   
                }                    
                    //  Send SMS
                    $round = Round::find($pt->enrolment->round->id)->description;
                    $message = Notification::where('template', Notification::RESULTS_RECEIVED)->first()->message;
                    $message = $this->replace_between($message, '[', ']', $round);
                    $message = str_replace(' [', ' ', $message);
                    $message = str_replace(']', ' ', $message);

                    $created = Carbon::today()->toDateTimeString();
                    $updated = Carbon::today()->toDateTimeString();
                    //  Time
                    $now = Carbon::now('Africa/Nairobi');
                    $bulk = DB::table('bulk')->insert(['notification_id' => Notification::RESULTS_RECEIVED, 'round_id' => $pt->enrolment->round->id, 'text' => $message, 'user_id' => $pt->enrolment->user->id, 'date_sent' => $now, 'created_at' => $created, 'updated_at' => $updated]);
                 // dd($bulk);
                    $recipients = NULL;
                    $recipients = User::find($pt->enrolment->user->id)->value('phone');
                    //  Bulk-sms settings
                    $api = DB::table('bulk_sms_settings')->first();
                    $username   = $api->code;
                    $apikey     = $api->api_key;

                    /*if($recipients)
                    {
                        // Specified sender-id
                        // $from = $api->code;
                        $from ='NPHL';
                        // Create a new instance of Bulk SMS gateway.
                        $sms    = new Bulk($username, $apikey);
                        // use try-catch to filter any errors.
                        try
                        {
                        // Send messages
                        $results = $sms->sendMessage($recipients, $message, $from);
                        foreach($results as $result)
                        {
                            // status is either "Success" or "error message" and save.
                            $number = $result->number;
                            //  Save the results
                            DB::table('broadcast')->insert(['number' => $number, 'bulk_id' => $bulk->id]);
                        }
                        }
                        catch ( AfricasTalkingGatewayException $e )
                        {
                        echo "Encountered an error while sending: ".$e->getMessage();
                        }
                    }*/
                return response()->json('Saved.');

             }
         }
    }

    /**
     * Fetch pt with related components for editing
     *
     * @param ID of the selected pt -  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pt = Pt::find($id);
        $round = $pt->enrolment->round;
        $results = $pt->results;
        $response = [
            'pt' => $pt,
            'round' => $round,
            'results' => $results,
            'pt_id'=>$pt->id
        ];
        // dd($response);
        return response()->json($response);
    }
    /*
    *   verify the result after reviewing
    */
    public function verify(Request $request)
    {
        $id = $request->pt_id;
        $user_id = Auth::user()->id;

        $result = Pt::find($id);
        $result->verified_by = $user_id;
        $result->panel_status = Pt::CHECKED;
        if($request->comment)
            $result->comment = $request->comment;
        $result->save();
        // Send SMS
        $round = Round::find($result->enrolment->round->id)->description;
        $message = Notification::where('template', Notification::RESULTS_RECEIVED)->first()->message;
        $message = $this->replace_between($message, '[', ']', $round);
        $message = str_replace(' [', ' ', $message);
        $message = str_replace(']', ' ', $message);
        
        $created = Carbon::today()->toDateTimeString();
        $updated = Carbon::today()->toDateTimeString();
        //  Time
        $now = Carbon::now('Africa/Nairobi');
        $bulk = DB::table('bulk')->insert(['notification_id' => Notification::RESULTS_RECEIVED, 'round_id' => $result->enrolment->round->id, 'text' => $message, 'user_id' => $result->enrolment->user->id, 'date_sent' => $now, 'created_at' => $created, 'updated_at' => $updated]);
        
        //get the last id inserted and use it in the broadcast table
        $bulk_id = DB::getPdo()->lastInsertId(); 

        $recipients = NULL;
        $recipients = User::find($result->enrolment->user->id)->phone;
        //  Bulk-sms settings
        $api = DB::table('bulk_sms_settings')->first();
        $username   = $api->username;
        $apikey     = $api->api_key;
        if($recipients)
        {
            // Specified sender-id
            $from = $api->code; //Mapesa: this still picks the wrong code. Nat-HivPT instead of NPHL
            $from = "NPHL";
            // Create a new instance of Bulk SMS gateway.
            $sms    = new Bulk($username, $apikey);
\Log::info(Auth::user()->email);
\Log::info(Auth::user()->phone);
\Log::info($from);
\Log::info($recipients);
\Log::info($message);
            // use try-catch to filter any errors.
            try
            {
                // Send messages
                $send_messages = $sms->sendMessage($recipients, $message, $from);
                foreach($send_messages as $send_message)
                {
                    // status is either "Success" or "error message" and save.
                    $number = $send_message->number;
                    //  Save the results
                    DB::table('broadcast')->insert(['number' => $number, 'bulk_id' => $bulk_id]);
                }
            }
            catch ( AfricasTalkingGatewayException $e )
            {
                echo "Encountered an error while sending: ".$e->getMessage();
            }
 
       }
        return response()->json($result);
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
        $pt = Pt::find($id);
        
        //  Proceed to form-fields
        foreach ($request->all() as $key => $value)
        {
            if((stripos($key, 'token') !==FALSE) || (stripos($key, 'method') !==FALSE))
                continue;
            else if(stripos($key, 'field') !==FALSE)
            {
                $fieldId = $this->strip($key);
                if(is_array($value))
                  $value = implode(', ', $value);

                $results = Result::where('pt_id', $pt->id)->get();

                foreach ($results as $result_key => $result) {

                   if ($result->field_id ==$fieldId) {
                        $result->response = $value;
                        $result->save();
                   }
                }
            }
            else if(stripos($key, 'comment') !==FALSE)
            {
                if($value)
                {
                    $result = Result::where('field_id', $key)->first();
                    $result->comment = $value;
                    $result->save();
                }
            }
        }    

        return response()->json(["Done"]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Shipment::find($id)->delete();
        return response()->json(['done']);
    }

    /**
     * enable soft deleted record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id) 
    {
        $shipment = Shipment::withTrashed()->find($id)->restore();
        return response()->json(['done']);
    }

    /**
     * Receive a shipment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function receive(Request $request)
    {
        $this->validate($request, [
            'date_received' => 'required',
            'panels_received' => 'required',
            'condition' => 'required',
            'receiver' => 'required'
        ]);

        $create = Receipt::create($request->all());

        return response()->json($create);
    }
    /**
  	 * Remove the specified begining of text to get Id alone.
  	 *
  	 * @param  int  $id
  	 * @return Response
  	 */
  	public function strip($field)
  	{
    		if(($pos = strpos($field, '_')) !== FALSE)
    		return substr($field, $pos+1);
  	}
    /**
     * Replace strings between two characters.
     *
     */
    function replace_between($str, $needle_start, $needle_end, $replacement) 
    {
        $pos = strpos($str, $needle_start);
        $start = $pos === false ? 0 : $pos + strlen($needle_start);

        $pos = strpos($str, $needle_end, $start);
        $end = $pos === false ? strlen($str) : $pos;

        return substr_replace($str, $replacement, $start, $end - $start);
    }
     /**
     * Verify results evaluted by a NON-Participant
     *
     * @param ID of the selected pt -  $id
     */
    public function evaluated_results($id)
    {
        //get actual results
        $pt = Pt::find($id);
        $round_id = $pt->enrolment->round->id;
        $pt_results = $pt->results;
        $option = new Option;

        $pt_panel_1_kit1_results = '';
        $pt_panel_2_kit1_results = '';
        $pt_panel_3_kit1_results = '';;
        $pt_panel_4_kit1_results = '';
        $pt_panel_5_kit1_results = '';
        $pt_panel_6_kit1_results = '';

        $pt_panel_1_kit2_results = '';
        $pt_panel_2_kit2_results = '';
        $pt_panel_3_kit2_results = '';;
        $pt_panel_4_kit2_results = '';
        $pt_panel_5_kit2_results = '';
        $pt_panel_6_kit2_results = '';

        $determine = '';
        $determine_lot_no = '';
        $determine_expiry_date = '';

        $firstresponse = '';
        $firstresponse_lot_no = '';
        $firstresponse_expiry_date = '';

        $date_received = '';
        $date_constituted = '';
        $date_tested = '';

        $expected_result_1 = '';
        $expected_result_2 = '';
        $expected_result_3 = '';
        $expected_result_4 = '';
        $expected_result_5 = '';
        $expected_result_6 = '';



        foreach ($pt_results as $rss) {
            //test kit 1 results
            if($rss->field_id == Field::idByUID('PT Panel 1 Test 1 Results'))
                $pt_panel_1_kit1_results = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('PT Panel 2 Test 1 Results'))
                $pt_panel_2_kit1_results = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('PT Panel 3 Test 1 Results'))
                $pt_panel_3_kit1_results = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('PT Panel 4 Test 1 Results'))
                $pt_panel_4_kit1_results = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('PT Panel 5 Test 1 Results'))
                $pt_panel_5_kit1_results = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('PT Panel 6 Test 1 Results'))
                $pt_panel_6_kit1_results = $option::nameByID($rss->response);

            //test kit 2 results
            if($rss->field_id == Field::idByUID('PT Panel 1 Test 2 Results'))
                $pt_panel_1_kit2_results = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('PT Panel 2 Test 2 Results'))
                $pt_panel_2_kit2_results = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('PT Panel 3 Test 2 Results'))
                $pt_panel_3_kit2_results = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('PT Panel 4 Test 2 Results'))
                $pt_panel_4_kit2_results = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('PT Panel 5 Test 2 Results'))
                $pt_panel_5_kit2_results = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('PT Panel 6 Test 2 Results'))
                $pt_panel_6_kit2_results = $option::nameByID($rss->response);
           
            //final results
            if($rss->field_id == Field::idByUID('PT Panel 1 Final Results'))
                $pt_panel_1_final_results = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('PT Panel 2 Final Results'))
                $pt_panel_2_final_results = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('PT Panel 3 Final Results'))
                $pt_panel_3_final_results = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('PT Panel 4 Final Results'))
                $pt_panel_4_final_results = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('PT Panel 5 Final Results'))
                $pt_panel_5_final_results = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('PT Panel 6 Final Results'))
                $pt_panel_6_final_results = $option::nameByID($rss->response);
           
            // //test kit 1 results
            if($rss->field_id == Field::idByUID('Test 1 Kit Name'))
                $determine = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('Test 1 Lot No.'))
                $determine_lot_no = $rss->response;
            if($rss->field_id == Field::idByUID('Test 1 Expiry Date'))
                $determine_expiry_date = $rss->response;

            // //test kit 2 results
            if($rss->field_id == Field::idByUID('Test 2 Kit Name'))
                $firstresponse = $option::nameByID($rss->response);
            if($rss->field_id == Field::idByUID('Test 2 Lot No.'))
                $firstresponse_lot_no = $rss->response;
            if($rss->field_id == Field::idByUID('Test 2 Expiry Date'))
                $firstresponse_expiry_date = $rss->response;  

            //dates
            if($rss->field_id == Field::idByUID('Date PT Panel Received'))
                $date_received = $rss->response;
            if($rss->field_id == Field::idByUID('Date PT Panel Constituted'))
                $date_constituted = $rss->response;
            if($rss->field_id == Field::idByUID('Date PT Panel Tested'))
                $date_tested = $rss->response;

            //comment
             if($rss->field_id == Field::idByUID('Comments'))
                $tester_comments = $rss->response;
        }
        $actual_results = array();

        //get expected results
        $round = Round::find($round_id);
        $user = $pt->enrolment->user;
        $lot = $user->lot($round_id);
        $expected_results = $lot->panels()->get();
        // $material_id = $expected_results->first()->material_id;
        // $material = Material::find($material_id); 

        foreach ($expected_results as $ex_rslts) {

            if($ex_rslts->panel == 1)
                $expected_result_1 = $ex_rslts->result($ex_rslts->result);
                $sample_1 = "PT-".$round->name."-S1";
            if($ex_rslts->panel == 2)
                $expected_result_2 = $ex_rslts->result($ex_rslts->result);
                $sample_2 = "PT-".$round->name."-S2";
            if($ex_rslts->panel == 3)
                $expected_result_3 = $ex_rslts->result($ex_rslts->result);
                $sample_3 = "PT-".$round->name."-S3";
            if($ex_rslts->panel == 4)
                $expected_result_4 = $ex_rslts->result($ex_rslts->result);
                $sample_4 = "PT-".$round->name."-S4";
            if($ex_rslts->panel == 5)
                $expected_result_5 = $ex_rslts->result($ex_rslts->result);
                $sample_5 = "PT-".$round->name."-S5";
            if($ex_rslts->panel == 6)
                $expected_result_6 = $ex_rslts->result($ex_rslts->result);
                $sample_6 = "PT-".$round->name."-S6";
        }

        //get participant details
        $user_name = $user->name;
        $tester_id = $user->username;
        $roleUser = $user->ru();
        $facility = Facility::find($roleUser->tier);
        $designation = $user->designation($roleUser->designation);
        $program = Program::find($roleUser->program_id)->name;
        $county = strtoupper($facility->subCounty->county->name);
        $sub_county = $facility->subCounty->name;
        $mfl = $facility->code;
        $facility = $facility->name;
        
        //combine expected and actual result into one array
        $all_results = array();
        $round_name = $round->name;
        $feedback = $pt->outcome($pt->feedback);
        $panel_status = $pt->panel_status;

        //get reasons for unsatisfactory
        $incorrect_results = $pt->incorrect_results;
        $incomplete_kit_data = $pt->incomplete_kit_data;
        $dev_from_procedure = $pt->dev_from_procedure;
        $incomplete_other_information = $pt->incomplete_other_information;
        $use_of_expired_kits = $pt->use_of_expired_kits;
        $invalid_results = $pt->invalid_results;
        $wrong_algorithm = $pt->wrong_algorithm;
        $incomplete_results = $pt->incomplete_results;

        if($pt->feedback == Pt::UNSATISFACTORY)
            $remark = $pt->unsatisfactory(); 
        else
            $remark = 'None';
        
        // dd($remark);
         $all_results = array( 
                    //user details
                    'round_name'=> $round_name, 
                    'feedback' => $feedback, 
                    'remark' => $remark, 
                    'panel_status' => $panel_status, 
                    'pt_id' => $pt->id,
                    'pt_approved_comment' => $pt->approved_comment,
                    'date_approved' => $pt->date_approved,
                    'user_name' => $user_name,
                    'tester_id' => $tester_id,
                    'designation' => $designation,
                    'program' => $program,
                    'county' => $county,
                    'sub_county' => $sub_county,
                    'facility' => $facility,
                    'mfl' => $mfl,

                    //material details
                    'date_received' =>$date_received,
                    'date_constituted' =>$date_constituted,
                    'date_tested' =>$date_tested,

                    //panel info
                    'determine' =>$determine,
                    'determine_lot_no' =>$determine_lot_no,
                    'determine_expiry_date' =>$determine_expiry_date,
                    'firstresponse' =>$firstresponse,
                    'firstresponse_lot_no' =>$firstresponse_lot_no,
                    'firstresponse_expiry_date' =>$firstresponse_expiry_date,

                    //results
                    //test 1 results
                    "pt_panel_1_kit1_results"=>$pt_panel_1_kit1_results, 
                    "pt_panel_2_kit1_results"=>$pt_panel_2_kit1_results,
                    "pt_panel_3_kit1_results"=>$pt_panel_3_kit1_results,
                    "pt_panel_4_kit1_results"=>$pt_panel_4_kit1_results,
                    "pt_panel_5_kit1_results"=>$pt_panel_5_kit1_results,
                    "pt_panel_6_kit1_results"=>$pt_panel_6_kit1_results,

                    //test 2 results
                    "pt_panel_1_kit2_results"=>$pt_panel_1_kit2_results, 
                    "pt_panel_2_kit2_results"=>$pt_panel_2_kit2_results,
                    "pt_panel_3_kit2_results"=>$pt_panel_3_kit2_results,
                    "pt_panel_4_kit2_results"=>$pt_panel_4_kit2_results,
                    "pt_panel_5_kit2_results"=>$pt_panel_5_kit2_results,
                    "pt_panel_6_kit2_results"=>$pt_panel_6_kit2_results,

                    //final tested results
                    "pt_panel_1_final_results"=>$pt_panel_1_final_results, 
                    "pt_panel_2_final_results"=>$pt_panel_2_final_results,
                    "pt_panel_3_final_results"=>$pt_panel_3_final_results,
                    "pt_panel_4_final_results"=>$pt_panel_4_final_results,
                    "pt_panel_5_final_results"=>$pt_panel_5_final_results,
                    "pt_panel_6_final_results"=>$pt_panel_6_final_results,

                    //expected results
                    "expected_result_1"=>$expected_result_1, 
                    "expected_result_2"=>$expected_result_2,
                    "expected_result_3"=>$expected_result_3,
                    "expected_result_4"=>$expected_result_4,
                    "expected_result_5"=>$expected_result_5,
                    "expected_result_6"=>$expected_result_6,
                    
                    //sample name
                    "sample_1"=>$sample_1, 
                    "sample_2"=>$sample_2,
                    "sample_3"=>$sample_3,
                    "sample_4"=>$sample_4,
                    "sample_5"=>$sample_5,
                    "sample_6"=>$sample_6,

                    'incorrect_results' => $incorrect_results,
                    'incomplete_kit_data' => $incomplete_kit_data,
                    'dev_from_procedure' => $dev_from_procedure,
                    'incomplete_other_information' => $incomplete_other_information,
                    'use_of_expired_kits' => $use_of_expired_kits,
                    'invalid_results' => $invalid_results,
                    'wrong_algorithm' => $wrong_algorithm,
                    'incomplete_results' => $incomplete_results
                );

        // return response()->json($all_results); 
        return $all_results;       
    }
/**
     * Verify results evaluted by a NON-Participant
     *
     * @param ID of the selected pt -  $id
     */
    public function show_evaluated_results($id)
    {
        $data = $this->evaluated_results($id);
        return response()->json($data);

    }    

     /**
     * Save the verification of evaluated results
     *
     * @param ID of the selected pt -  $id
     */
    public function verify_evaluated_results(Request $request, $id )
    {
        $pt_id = $request->pt_id;
        $user_id = Auth::user()->id; 

        $result = Pt::find($id);
        $result->panel_status = Pt::VERIFIED;
        $result->date_approved = Carbon::today()->toDateTimeString();
        if ($request) {
            $result->approved_comment = $request->comment;            
     
            if($request->comment)
                $result->approved_by = $user_id;
        }
        $result->save();

         //  Send SMS
        $round = Round::find($pt->enrolment->round->id)->description;
        $message = Notification::where('template', Notification::FEEDBACK_RELEASE)->first()->message;
        $message = $this->replace_between($message, '[', ']', $round);
        $message = str_replace(' [', ' ', $message);
        $message = str_replace(']', ' ', $message);

        $created = Carbon::today()->toDateTimeString();
        $updated = Carbon::today()->toDateTimeString();
        //  Time
        $now = Carbon::now('Africa/Nairobi');
        $bulk = DB::table('bulk')->insert(['notification_id' => Notification::FEEDBACK_RELEASE, 'round_id' => $pt->enrolment->round->id, 'text' => $message, 'user_id' => $pt->enrolment->user->id, 'date_sent' => $now, 'created_at' => $created, 'updated_at' => $updated]);
        $recipients = NULL;
        $recipients = User::find($pt->enrolment->user->id)->value('phone');
        //  Bulk-sms settings
        $api = DB::table('bulk_sms_settings')->first();
        $username   = $api->code;
        $apikey     = $api->api_key;
        if($recipients)
        {
            // Specified sender-id
            // $from = $api->code;
            $from ='NPHL';
            // Create a new instance of Bulk SMS gateway.
            $sms    = new Bulk($username, $apikey);
            // use try-catch to filter any errors.
            try
            {
            // Send messages
            $results = $sms->sendMessage($recipients, $message, $from);
            foreach($results as $result)
            {
                // status is either "Success" or "error message" and save.
                $number = $result->number;
                //  Save the results
                DB::table('broadcast')->insert(['number' => $number, 'bulk_id' => $bulk->id]);
            }
            }
            catch ( AfricasTalkingGatewayException $e )
            {
            echo "Encountered an error while sending: ".$e->getMessage();
            }
        }
        return response()->json($result);
    }
     /**
     * Update the evaluated results
     *
     * @param ID of the selected pt -  $id
     */
    public function update_evaluated_results(Request $request, $id )
    {
        $pt_id = $request->pt_id;
        $user_id = Auth::user()->id; 

        $result = Pt::find($id);
        if ($request->incorrect_results) {
            $result->incorrect_results = $request->incorrect_results;
        }else{
            $result->incorrect_results = 0;
        }

        if ($request->incomplete_kit_data) {
            $result->incomplete_kit_data = $request->incomplete_kit_data;
        }else{
            $result->incomplete_kit_data = 0;
        }

        if ($request->dev_from_procedure) {
            $result->dev_from_procedure = $request->dev_from_procedure;
        }else{
            $result->dev_from_procedure = 0;
        }

        if ($request->incomplete_other_information) {
            $result->incomplete_other_information = $request->incomplete_other_information;
        }else{
            $result->incomplete_other_information = 0;
        }

        if ($request->use_of_expired_kits) {
            $result->use_of_expired_kits = $request->use_of_expired_kits;
        }else{

            $result->use_of_expired_kits = 0;
        }
        if ($request->invalid_results) {
            $result->invalid_results = $request->invalid_results;
        }else{
            $result->invalid_results = 0;
        }

        if ($request->wrong_algorithm) {
            $result->wrong_algorithm = $request->wrong_algorithm;
        }else{
            $result->wrong_algorithm = 0;
        }

        if ($request->incomplete_results) {
            $result->incomplete_results = $request->incomplete_results;
        }else{
            $result->incomplete_results =0;
        }

        if ($request->feedback==1) {        //cannot check if value is 0.    
            $result->feedback = $request->feedback;
        }else{
            $result->feedback = 0; 
        }
        
        $result->save();
        
        return response()->json($result);
    }
        
    /**
     * Fetch feedback for the given id
     *
     * @param ID of the selected pt -  $id
     */

    public function print_result($id){
      $data = $this->evaluated_results($id);

      if(\request('type') == 0){//satisfactory

          $pdf = PDF::loadView('result/print1', compact('data'));
      }

        if(\request('type') == 1){//unsatisfactory

            $pt = Pt::where('id',$id)->first();


            $pdf = PDF::loadView('result/print', compact('data','pt'));
        }

        $pt = Pt::find($id);
        $user = User::find($pt->enrolment->user_id)->id;
        if ($pt->download_status == 0 && Auth::user()->id==$user) {
            $pt->download_status = Pt::DOWNLOAD_STATUS;
            $pt->save();
        }

      return $pdf->download('Round '.$data['round_name'].' Results.pdf');

    }
    public function feedback($id)
    {
        $pt = Pt::find($id);
        $usr = User::find($pt->enrolment->user_id);
        $pt->uid = (string)$usr->uid;
        $pt->tester = $usr->name;
        $pt->program = Program::find(1)->name;
        $facility = Facility::find(1);
        $pt->county = strtoupper($facility->subCounty->county->name);
        $pt->sub_county = $facility->subCounty->name;
        $pt->facility = $facility->name;
        $pt->round = $pt->enrolment->round->name;
        $pt->verdict = $pt->outcome($pt->feedback);
        $pt->remark = '';
        if($pt->feedback == Pt::UNSATISFACTORY)
            $pt->remark = 'Reason: '.$pt->unsatisfactory();
        $pt->date_authorized = Carbon::parse($pt->updated_at)->toFormattedDateString();
        $response = [
            'data' => $pt
        ];

        return response()->json($response);
    }
}
